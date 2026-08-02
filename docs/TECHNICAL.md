# Technische Dokumentation — DB-Manager

Kurz: Beschreibt die Implementierung und Betriebsdetails der Änderungen in `php/db_manager.php`.

Wichtige Dateien
- [php/db_manager.php](php/db_manager.php): Hauptseite mit Verwaltung (Auswahl, Dump, Kopie, Umbenennung, Löschung).
- [php/config.php](php/config.php): Enthält `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`.
- `php/dumps/`: Verzeichnis, in dem automatische Dumps abgelegt werden.

Kernfunktionen
- Dynamische DB-Liste: Per PDO-Admin-Connection wird `SHOW DATABASES LIKE 'gamebook\\_%'` ausgeführt, nur DBs mit Prefix `gamebook_` werden angezeigt.
- Aktive DB: `DB_NAME` in `php/config.php` wird per `define('DB_NAME', ...)` ausgelesen und kann per Formular gesetzt werden.
- Aktionen: `dump`, `copy`, `rename`, `delete`.

Technische Details
- PDO-Admin: Eine PDO-Verbindung ohne Default-DB wird für Verwaltungs-Queries verwendet.
- Dump-Verhalten:
  - Dumps werden mit dem System-`mysqldump` (`/usr/bin/mysqldump`) ausgeführt.
  - Befehl wird in einer sauberen Umgebung gestartet (`env -i PATH=/usr/bin:/bin LD_LIBRARY_PATH= LD_PRELOAD=`), um Bibliothekskonflikte (z. B. XAMPP libs in `/opt/lampp/lib`) zu vermeiden.
  - `--protocol=TCP` wird verwendet, damit nicht der lokale Unix-Socket nötig ist.
  - Dumps landen in `php/dumps/` (Dateiname z. B. `gamebook_dev2_20260802_000000.sql`).
  - Bei Lösch-Operationen wird zuerst ein automatischer Dump erzeugt; schlägt der Dump fehl, wird die Löschung abgebrochen.
  - Die letzte ausgeführte mysqldump-Kommandozeile wird in `php/dumps/last_mysqldump_cmd.txt` geloggt (zu Debugging-Zwecken).

- Validierung und Sicherheit:
  - Neuer DB-Name (`new_db`) wird serverseitig geprüft: es muss mit `gamebook_` beginnen (Regex `^gamebook_`).
  - Clientseitig gibt es eine `pattern`-Prüfung und eine JS-Confirm vor Löschung.
  - Aktionen, die DB-Namen verwenden, überprüfen, dass die gewählten/übergebenen DBs in der angezeigten Liste (`$dbList`) enthalten sind.

Fehlerbehandlung
- Fehler beim Auslesen der DB-Liste werden in `$message` angezeigt.
- Output von `mysqldump` (stdout+stderr) wird bei Fehlern in die Seite geschrieben (HTML-escaped) zur Diagnostik.

Betriebs- und Berechtigungs-Hinweise
- `php/dumps/` muss vom Webserver beschreibbar sein (der Webserver-Prozess owner erstellt dort Dateien). Standardmäßig werden Dumps mit dem Webserver-User/Group geschrieben.
- Empfohlen: Backup-Aufbewahrungsrichtlinie, z. B. automatisches Löschen älterer Dumps / Speicherort außerhalb des Webroots.
- Absicherung: Seite sollte nur lokalen Admins zugänglich sein (z. B. durch HTTP-Auth oder IP-Restriktion), da sie DB-Verwaltungsrechte bietet.

Empfohlene nächste Schritte / Verbesserungen
- Verschieben der Dumps in ein außerhalb des Webroots liegendes Verzeichnis und Setzen passender Berechtigungen.
- Implementieren einer Aufräum-Routine (z. B. ältere Dumps älter als N Tage löschen).
- Optionale Authentifizierung/ACL für die `db_manager.php`-Seite.
- Optional: Verwenden von `mysqldump` via PHP-MySQL-Client-APIs statt Exec, wenn praktikabel.

Datum: 2026-08-02
