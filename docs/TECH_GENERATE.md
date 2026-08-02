# Technische Dokumentation — Generate (generate.php)

Kurz: Generator-Skript zur Erzeugung statischer HTML-Dateien aus DB-Daten, Templates und Regeln (Navigation, Decisions, Pages, Place-Text).

Datei
- `php/generate.php`

Wichtige Eingaben/Files
- `template.html`, `distribute_template.html` (Templates im Root)
- DB-Tabellen: `links`, `places`, `pages`, `choice`, `decision_links`, `objects`, `skills`, `ways` (u.a.)

Arbeitsweise
- Initialisiert Zustandsfelder in `links` (length, bubble_field_index, regenerated, finished, etc.).
- Liest alle Links und ruft `handle_link($link)` auf, welches:
  - `build_navigation`, `build_decisions`, `build_page_text`, `build_place_text`
  - Templates mit Platzhaltern ersetzt (`replace_without_page_nr`, `replace_page_nr`)
  - `write_page()` erzeugt HTML-Dateien in `html/` oder `html_with_pagenumbers/` (im Finish-Modus).
- Finish-Mode: konsolidiert generierte Teile, berechnet finale Seitenzahlen und schreibt in `html_with_pagenumbers/`.

Technische Risiken
- Intensive String-Manipulation und direkte DB-Updates (z. B. UPDATE links SET text_place = "...") mit `addslashes` — Risiko für Encoding/Quoting-Probleme.
- Kein Transaktions-Management: Teilweise Schreiboperationen können inkonsistent sein, wenn das Skript abbricht.
- Dateinamen/Seiten-Nummerierung: komplexe Logik, die leicht fehleranfällig ist; empfehlenswert Tests.

Performance & Betrieb
- Operationen können lange laufen; Script wird synchron ausgeführt und schreibt viele Dateien.
- Empfohlen: Führe es in einer CLI-Umgebung mit ausreichend Timeouts/Mem-Limits; nicht per Web-Request in Produktivmodus.

Empfehlungen
- Extrahiere die Generator-Logik in testbare Funktionen / CLI-Tool.
- Verwende Transaktionen, temporäre Tabellen oder staging directories, um inkonsistente Zustände zu vermeiden.
- Schreibe ausführliche Logs (z. B. generation.log) und optional Progress-Output.

Datum: 2026-08-02
