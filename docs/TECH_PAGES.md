# Technische Dokumentation — Pages (pages.php)

Kurz: Verwaltung von Seiteninhalten (pages table), Speichern, Löschen mit Schutz gegen vorhandene Links.

Datei
- `php/pages.php`

Tabellen
- `pages` (id, text, comments, ...)
- `links` (page_id)

Wesentliches Verhalten
- Beim Löschen (`POST delete_id`) wird vorher geprüft, ob `links` auf die Seite verweisen (COUNT). Nur wenn `link_count == 0` wird gelöscht.
- Seiten können per GET `?id=` ausgewählt und bearbeitet werden; Änderungen werden per UPDATE gespeichert.
- Liste aller Seiten mit Link-Anzahl wird angezeigt.

Implementation-Details
- SELECT mit LEFT JOIN und GROUP BY um `link_count` zu erhalten.
- Bei Edit-Form speichert POST `text` und `comments` per prepared UPDATE.

Validierung & Sicherheit
- Löschschutz vermeidet Inkonsistenzen; dennoch sollte Auth/ACL und CSRF-Schutz ergänzt werden.

Fehlerbehandlung
- Fehlermeldungen und success-Status werden in `$deleteError` / `$deleteSuccess` angezeigt.

Empfehlungen
- Bessere Texteditoren (WYSIWYG/Markdown) für Seiteninhalt; Preview-Funktion.
- Möglichkeit, Links auf eine andere Page umzubinden bevor Löschung.

Datum: 2026-08-02
