# Anwenderdokumentation — Generate (generate.php)

Zweck
- Erzeugt statische HTML-Seiten aus den DB-Inhalten. Wird für die Produktion der Gamebook-Seiten genutzt.

Benutzung & Hinweise
- Starte die Generierung idealerweise per CLI (nicht per Web) auf dem Server, besonders bei großen Datenmengen.
- Standardlauf schreibt in `html/`; der `finish`-Mode schreibt in `html_with_pagenumbers/` und führt finale Nummerierung durch.
- Vor einem Lauf: Backup der DB und des `html/` Verzeichnisses machen.

Fehlerbehebung
- Bei fehlerhafter Ausgabe prüfe die Konsolen-Ausgabe des Scripts und die erzeugten Dateien in `html/`.
- Probleme mit Encoding/Platzhaltern deuten auf fehlende Escape/Replace-Logik hin.

Datum: 2026-08-02
