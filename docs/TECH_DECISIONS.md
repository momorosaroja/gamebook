# Technische Dokumentation — Decisions (decisions.php)

Kurz: Verwaltung von Decisions, Choices und deren Verknüpfungen mit Links. Dieses Dokument beschreibt Logik, DB-Operationen, Sicherheitsaspekte und Troubleshooting.

Datei
- `php/decisions.php`

Eingesetzte DB-Tabellen
- `decision` (id, name, finished...)
- `choice` (id, decision_id, place_id, label, text)
- `links` (id, place_id, choice_id, page_id...)
- `decision_links` (decision_id, link_id)
- `places` (id, name)

Wesentliche Funktionen
- Anzeigen aller Decisions mit Anzahl/IDs verknüpfter Links (GROUP_CONCAT).
- Anlegen Decision: POST `action=add_decision` — Insert in `decision`, dann Insert in `decision_links` mit ausgewähltem `link_id`.
- Anlegen Choice: POST `action=add_choice` — Insert in `choice`, dann `add_link($pars)` aufrufen (siehe `utils.php`).
- Edit Choice: POST `action=edit_choice` — UPDATE auf `choice`.
- Edit Decision: POST `action=edit_decision` — UPDATE `decision`.
- Delete via GET: `?delete=decision&id=...` oder `?delete=choice&id=...` — direkte DELETE-Statements.
- Toggle finished per GET: `?toggle=decision&id=...` — flips `finished` (UPDATE).

Wichtige Implementation-Details
- Datenbankzugriff erfolgt über `getPDO()` (utilities in `utils.php`).
- Prepared statements werden für INSERT/UPDATE/SELECT verwendet.
- Der Code verwendet Redirects (header('Location: ...')) nach Schreiboperationen, dadurch simple PRG-Pattern.
- Beim Anlegen einer Choice wird `add_link()` aufgerufen; diese helper-Funktion kümmert sich um das Erstellen eines `links`-Eintrags und verknüpfte Objekte.

Validierung & Sicherheit
- Einige Parameter werden mit `ctype_digit` oder `(int)` überprüft. Dennoch existieren direkte GET-basierte Lösch-Operationen (ohne CSRF-Token) — dies ist riskant.
- Empfohlen: Schütze Seite mit HTTP-Auth/IP-Whitelist; ergänze CSRF-Schutz für alle Formulare; prüfe Autorisierung (wer darf welche Decision löschen?).

Fehlerbehandlung
- Aktuell wird bei DB-Fehlern meist keine detaillierte Ausnahmebehandlung gezeigt, PDO Exceptions können nach Server-Settings sichtbar werden. Empfohlen: Fehler-Logging und Benutzerfreundliche Fehlermeldungen.

Empfohlene Verbesserungen
- Vermeide destructive Aktionen per GET; verwende POST + CSRF-Token.
- Wenn Decision gelöscht wird, sicherstellen, welche abhängigen Datensätze (Choices, decision_links) ebenfalls bereinigt werden sollten; aktuell werden Choices nicht automatisch gelöscht.
- Bessere Darstellung der `link_ids` in der Liste (derzeit TODO-Platzhalter).

Troubleshooting
- Leere Dropdowns: Prüfe `links`-Query; kann fehlende `places` oder `choice`-Zuweisungen bedeuten.
- Fehler beim Insert: Prüfe DB-Schema und `getPDO()`-Konfiguration in `utils.php`.

Datum: 2026-08-02
