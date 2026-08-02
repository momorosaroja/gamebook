# Technische Dokumentation — Places (places.php)

Kurz: CRUD-Interface für Orte mit Parent-Hierarchie und bis zu fünf Textfeldern pro Ort.

Datei
- `php/places.php`

Tabellen
- `places` (id, name, parent_id, text1..text5)

Operationen
- Create: INSERT mit name, parent_id und text1..text5
- Update: UPDATE für Felder
- Delete: DELETE FROM places WHERE id = ? (per POST nach Bestätigung)

Details
- Parent-Select zeigt alle Orte für Parent-Auswahl.
- Optionales Nullify-Helper konvertiert leere Strings zu NULLs in DB.
- Verwende `header('Location: places.php')` nach Änderung.

Validierung
- Keine komplexen Validierungen; empfehlenswert: Prüfe referenzielle Integrität (z. B. vorhandene Ways/Links) vor Delete.

Empfehlungen
- Bei Löschen prüfen, ob `ways`, `links` oder andere Tabellen referenzieren; ggf. Warnung oder Verweis-Übernahme anbieten.

Datum: 2026-08-02
