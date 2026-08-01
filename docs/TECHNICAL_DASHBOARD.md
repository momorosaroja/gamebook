# Technische Dokumentation — Dashboard-Module

Dieses Dokument fasst die technischen Details der Dashboard-Module zusammen. Ziel ist eine schnelle Referenz für Entwickler/Administratoren.

Module Übersicht
- `decisions.php` — Management von Decisions und Choices; Verknüpfung mit Links; Erzeugung/Änderung/Löschung von Decisions und Choices.
- `entity_manager.php` — CRUD für einfache Lookup-Tabellen: `skills`, `objects`, `characters`.
- `pages.php` — Verwaltung von Seiteninhalten (text, comments) und Löschschutz, solange Links existieren.
- `links.php` — Komplexes Link-Management; Filter, Erstellen/Kopieren/Bearbeiten/Löschen von Links; Verwaltung von `link_objects` (skills/objects ranges); Gruppierung & Farb-Codierung.
- `places.php` — CRUD für Orte (`places`) inkl. Parent-Hierarchie und bis zu fünf Textfelder pro Ort.
- `network_admin.php` — Verwaltung von Wegen (`ways`) zwischen Orten; bidirektionale Flag, Zuordnung von way_objects.
- `net.php` — Visualisierung des Netzwerks via `vis-network`; Gruppierung nach `parent_id`, Subnetzwerke, Nodes/Edges-Erzeugung.
- `generate.php` — Offline-Generator, der HTML-Seiten aus DB-Daten baut (Templates, Navigation, Decisions, Pages, Place-Text, Page-Nummerierung).

Allgemeine technische Eigenschaften
- Alle Module verwenden `require 'utils.php'` und `getPDO()` für eine zentrale PDO-Verbindung.
- Input-Validierung: eine Mischung aus `ctype_digit`, `(int)`-Casts und prepared statements; einige Stellen leiden noch an fehlender zentraler Input-Sanitization.
- SQL: Prepared Statements werden häufig verwendet; an manchen Stellen (z. B. dynamische Tabellennamen) werden Query-Strings direkt zusammengesetzt — Achtung SQL-Injection-Risiken.

Wesentliche Implementationsdetails (pro Modul)

decisions.php
- Lädt Decisions, Choices, freie Links und erlaubt: anlegen, bearbeiten, löschen, toggle finished.
- Beim Anlegen einer Choice wird automatisch ein Link über `add_link()` erzeugt.
- Löschen von Decisions/Choices ist direkt per GET-Parameter (`?delete=decision&id=...`) implementiert — empfohlen: CSRF-/Auth-Schutz.

entity_manager.php
- Unterstützt nur die Tabellen `skills`, `objects`, `characters` (s. `$allowed_tables`).
- Nutzt dynamische Tabellennamen; validiert gegen Whitelist. Bietet Insert/Update/Delete per POST/GET.

pages.php
- Verhindert Löschen einer `page`, falls noch Einträge in `links` referenzieren (Zähllogik).
- Bietet Bearbeiten von Seiteninhalt und Comments; speichert Änderungen per UPDATE.
- Zeigt Link-IDs pro Seite an; Verweise zur Links-Ansicht möglich.

links.php
- Starke, zentrale Komponente: lädt Links mit JOINs, gruppiert `link_objects` (skills/objects) per Link.
- Unterstützt Filter (place, skills, objects, decision) und unterschiedliche Sortierungen.
- Beim Update können `link_objects` entfernt und neue hinzugefügt werden; beim Löschen werden Verbunde (`link_objects`, `decision_links`) aufgeräumt.
- Kopier-Funktion dupliziert Link, zugehörige `link_objects` und `decision_links`.

places.php
- CRUD für `places` inkl. fünf optionaler Textfelder (`text1`..`text5`) und `parent_id`-Beziehung.
- Löschen erfolgt per POST und redirect; Exceptions werden gefangen und als Fehler angezeigt.

network_admin.php
- Create/Update/Delete für `places` und `ways` sowie Zuordnung von `way_objects`.
- Validierungen: Start != End; Start/End müssen gleichen `parent_id` haben.
- UI-Helpers erlauben Inline-Zuordnung von Object/Character/Skill per JS.

net.php
- Erzeugt Datenstrukturen (`nodes`, `edges`) für `vis-network` und rendert das Main-Netzwerk.
- Unterstützt rekursive Ansicht von Subnetzwerken via `parent_chain[]` GET-Parameter.

generate.php
- Komplexer Generator, der für jeden Link HTML-Dateien basierend auf Templates (`template.html`, `distribute_template.html`) erzeugt.
- Kernfunktionen: `build_place_text`, `build_page_text`, `build_navigation`, `build_decisions`, `replace_without_page_nr`, `write_page`.
- Unterstützt ein `finish`-Mode, der Dateien zusammenführt und Seitenzahlen neu berechnet.
- Achtung: viele string-Manipulationen mit direkten Updates in DB (z. B. UPDATE links SET text_place = ... with addslashes) — mögliche Escape-/Encoding-Themen.

Security & Betrieb
- Authentifizierung/Autorisierung: Es gibt keine zentrale Auth-Überprüfung in den Modulen — Seite sollte durch Webserver/Proxy abgesichert werden.
- CSRF: Formulare verwenden keine CSRF-Tokens; riskant für destructive Aktionen (Löschen, Update).
- Dateisystem: `generate.php` und `db_manager.php` schreiben in `html/`, `html_with_pagenumbers/` und `php/dumps/` — sicherstellen, dass diese Verzeichnisse korrekt geschützt sind.

Fehlerbehandlung & Logging
- Viele Module setzen auf Redirects und Anzeigen von Fehlermeldungen in `$error`/`$message`.
- Empfohlen: zentrales Logging (z. B. Monolog) und strukturierte Fehlerseiten.

Empfohlene Verbesserungen
- Zentralisiere Auth/ACL und CSRF-Schutz.
- Validierung: konsequente serverseitige Validierung und Whitelists für dynamische Werte.
- Trenne Präsentation und Logik stärker (z. B. Templates/Views statt mixed inline HTML).
- Bei `generate.php`: externeize komplexe String-Operationen und testbar machen; evtl. Unit-Tests für Pageroutine.

Datum: 2026-08-02
