# Anwenderdokumentation — DB-Manager (Kurz)

Übersicht
Diese Seite erlaubt es Administratoren, Datenbanken mit dem Präfix `gamebook_` zu verwalten: auswählen, als aktive DB setzen, Dump erstellen, kopieren, umbenennen oder löschen.

Zugriff
Öffne im Browser: `/php/db_manager.php` (bzw. den entsprechenden Pfad auf deinem Server).

1) Aktive Datenbank setzen
- In Abschnitt "1) Aktive Datenbank in config.php setzen" eine Datenbank auswählen und auf "Übernehmen" klicken. Das schreibt `DB_NAME` in `php/config.php`.

2) Datenbank verwalten
- Wähle unter "2) Datenbank verwalten" die gewünschte DB aus.
- Wähle eine Aktion:
  - `Dump`: Erstellt einen SQL-Dump der gewählten DB.
  - `Kopieren`: Erzeugt eine neue DB mit dem angegebenen Namen und kopiert Tabellen + Daten.
  - `Umbenennen`: Verschiebt Tabellen in eine neu erstellte DB; die alte DB wird entfernt. Falls die umbenannte DB aktuell ist, wird `php/config.php` aktualisiert.
  - `Löschen`: Erstellt automatisch einen Dump (mit Zeitstempel) und löscht anschließend die DB. Vor dem Löschen erscheint eine Sicherheitsabfrage.
- Für `Kopieren` und `Umbenennen` muss im Feld "Neuer Name" ein Name eingegeben werden, der mit `gamebook_` beginnt. Andere Namen werden abgewiesen.

Dump-Dateien
- Dumps werden in `php/dumps/` abgelegt. Dateinamen enthalten die DB und gegebenenfalls einen Zeitstempel.

Fehler und Troubleshooting
- Wenn ein Dump fehlschlägt, wird eine Fehlermeldung (inkl. Ausgabe von `mysqldump`) auf der Seite angezeigt.
- Prüfe `php/dumps/last_mysqldump_cmd.txt` für das genaue Kommando, das vom Webserver ausgeführt wurde.
- Wenn keine Dateien in `php/dumps/` erscheinen: überprüfe Berechtigungen des Verzeichnisses (Webserver muss schreiben können).

Sicherheitshinweise
- Die Seite führt administrative Aktionen aus. Beschränke den Zugriff auf vertrauenswürdige Admin-Benutzer (z. B. mittels HTTP-Auth oder IP-Whitelist).
- Dumps enthalten sensible Daten — lagere sie sicher außerhalb des Webroots, wenn möglich.

Hilfe
Wenn du Unterstützung brauchst, poste hier die Fehlermeldungen (die auf der Seite erscheinen) oder kontaktiere den Systemadministrator.

Datum: 2026-08-02
