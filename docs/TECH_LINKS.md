# Technische Dokumentation — Links (links.php)

Kurz: Zentrales Verwaltungstool für `links` und zugehörige `link_objects` (skills/objects). Unterstützt Filter, Kopieren, Editieren und Löschen.

Datei
- `php/links.php`

Wichtige Tabellen
- `links` (id, place_id, choice_id, page_id, first_date, last_date, ...)
- `link_objects` (id, link_id, object_type, object_id, number1, number2)
- `decision_links` (decision_id, link_id)
- `skills`, `objects`, `places`, `pages`, `choice`

Funktionen & Queries
- Filter-Logik: `filter_type` (`place`, `skills`, `objects`, `decision`) und `filter_value` werden in WHERE-Klausel eingesetzt.
- Haupt-Query: komplexe JOINs und GROUP_CONCAT für `skills` und `objects` (aggregiert, HTML innerhalb SQL verwendet).
- Bearbeiten: POST `update_link` → UPDATE links; Entfernen markierter `link_objects` und Einfügen neuer.
- Erstellen: POST `add_link` → Aufruf `add_link($pars)` (utils.php).
- Löschen: GET `?delete=ID` → löscht Link + `link_objects` + `decision_links`.
- Kopieren: GET `?copy=ID` → Dupliziert Link, kopiert `link_objects` und `decision_links` via INSERTs.

Clientseitige Hilfen
- JS-Templates generieren dynamische Formfelder (Skill-Ranges, Objects) beim Bearbeiten.

Sicherheit
- Lösch-/Kopie-Aktionen per GET (riskant) — sollte POST mit CSRF sein.
- Filter-Parameter müssen validiert werden; prepared statements werden für parametrische Werte genutzt.

Fehlerquellen
- GROUP_CONCAT mit HTML kann bei sehr großen Ergebnissen abgeschnitten werden (max length). Bei Problemen `group_concat_max_len` erhöhen.
- Race-Conditions: Parallele Updates auf gleichen Link können zu Inkonsistenzen führen.

Empfehlungen
- Verwende POST für destructive Aktionen und CSRF-Tokens.
- Ziehe in Betracht, die Aggregation außerhalb SQL durch PHP zu machen (bessere Kontrolle über Encoding).

Datum: 2026-08-02
