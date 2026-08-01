# Technische Dokumentation — Entity Manager (entity_manager.php)

Kurz: Einfaches CRUD-Interface für Lookup-Tabellen: `skills`, `objects`, `characters`.

Datei
- `php/entity_manager.php`

Tabellen & Verhalten
- Unterstützte Tabellen sind in `$allowed_tables = ['skills', 'objects', 'characters']` definiert.
- Tabelle wird per GET `?table=skills` gewählt; default `skills`.

Operationen
- List: SELECT id, name FROM $table ORDER BY name
- Create: POST `new_name` → INSERT INTO $table (name)
- Update: POST `update_id` & `name` → UPDATE $table SET name = ? WHERE id = ?
- Delete: GET `?table=...&delete=ID` → DELETE FROM $table WHERE id = ?

Validierung & Sicherheit
- Whitelist für Tabellen verhindert willkürliche dynamische Tabellennamen.
- Dennoch fehlt CSRF-Schutz und Authentifizierung; destructive Aktionen können per GET ausgeführt werden.

Fehlerbehandlung
- Bei DB-Fehlern werden PHP-Exceptions hochgeworfen; empfehlenswert ist zentralisiertes Error-Handling und Logging.

Empfehlungen
- Ersetze GET-Delete durch POST mit CSRF-Token.
- Füge Undo-/Soft-Delete-Option hinzu, wenn Daten kritisch sind.

Datum: 2026-08-02
