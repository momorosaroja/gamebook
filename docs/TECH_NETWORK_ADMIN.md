# Technische Dokumentation — Network Admin (network_admin.php)

Kurz: Verwaltung von Wegen (`ways`) und zugeordneten Objekten zwischen Orten; bietet UI für parent-Filter und Inline-Zuordnung.

Datei
- `php/network_admin.php`

Tabellen
- `ways` (id, start, end, bidirectional)
- `way_objects` (id, way_id, object_id, object_type, number1, number2)
- `places` (id, name, parent_id)

Funktionen
- Neues Way anlegen: Validiert dass Start != End und dass beide denselben parent_id haben.
- Bei Anlegen/Update können `way_objects` hinzugefügt werden (Objects, Characters, Skills) — Skills unterstützen number ranges.
- Löschen per GET `?delete_way=ID`.
- Zusätzlich CRUD für `places` in gleichem Modul.

Clientseitige Hilfen
- JS-Funktionen für Inline-Selects und dynamisches Anzeigen der Nummer-Felder bei Skill-Type.

Risiken & Verbesserungspotential
- Manuelles Reload-Hinweis für way_objects-Änderungen (UI nicht dynamisch aktualisiert). Kann verbessert werden.
- Validierung serverseitig gut, jedoch fehlt CSRF/ACL.

Empfehlungen
- Stelle konsistente APIs bereit und verwende AJAX-Calls für inline Aktionen, damit Reloads unnötig werden.

Datum: 2026-08-02
