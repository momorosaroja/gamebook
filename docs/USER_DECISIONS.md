# Anwenderdokumentation — Decisions (decisions.php)

Was macht diese Seite?
- Erlaubt das Anlegen, Bearbeiten und Löschen von `Decisions` und `Choices`.
- Verknüpft Decisions mit bestehenden Links (wenn beim Anlegen ein Link ausgewählt wird).

Wie benutze ich sie?
1. Neue Decision anlegen
   - Fülle "Name" aus, wähle einen Link und klicke auf "Decision anlegen".
   - Nach dem Anlegen springt die Ansicht zur neuen Decision.

2. Choice anlegen
   - Wähle eine Decision, fülle Label/Text und optional Ziel-Place, klicke auf "Choice anlegen".
   - Ein Link für die Choice wird automatisch erstellt.

3. Choice bearbeiten
   - Klicke auf "Bearbeiten" neben einer Choice, ändere Felder und speichere.

4. Decision löschen / fertig markieren
   - Löschen: Klick auf "Löschen" (es erscheint eine Bestätigung).
   - Fertig/Unfertig: Klick auf "fertig/unfertig" toggelt den Status.

Wichtige Hinweise
- Löschoperationen sind destruktiv; erstelle ein Backup, wenn du unsicher bist.
- Löschen geschieht sofort (kein Mehrstufiger Lösch-Workflow). Achte auf abhängige Daten.

Fehlerbehebung
- Wird beim Anlegen ein Fehler angezeigt, prüfe die Formularfelder auf Pflichtangaben. Bei DB-Fehlern melde die genaue Meldung an den Administrator.

Datum: 2026-08-02
