# Anwenderdokumentation — Dashboard-Module (Kurz)

Dieses Dokument erklärt die Benutzerfunktionen der Dashboard-Module und wie man sie sicher verwendet.

Zugriff
Öffne das Dashboard im Browser: `/php/dashboard.php`.

Module & Benutzeranweisungen

Decisions
- Zweck: Decisions und zugehörige Choices verwalten.
- Aktionen: Neue Decision anlegen (mit zugehörigem Link), Choice anlegen/ändern/löschen, Decision löschen, Toggle finished.
- Hinweise: Beim Löschen werden keine automatisch abhängigen Objekte (z. B. verknüpfte Choices) entfernt — Vorsicht.

Entities (`entity_manager.php`)
- Zweck: Verwalten von Lookup-Tabellen: `skills`, `objects`, `characters`.
- Aktionen: Tabelle wählen, Einträge hinzufügen, editieren, löschen.

Pages
- Zweck: Bearbeiten der Seiteninhalte, Anzeigen aller Seiten, Löschen von Seiten, sofern sie keine Links referenzieren.
- Aktion: Klicke auf eine Seite in der Liste, bearbeite `Text` und `Kommentare`, Speichern.
- Hinweis: Eine Seite kann nur gelöscht werden, wenn keine Links mehr darauf verweisen.

Links
- Zweck: Erstellen, Bearbeiten, Filtern und Kopieren von Links; Verwalten von Bedingungen (Skill-Ranges / Objects).
- Aktionen: Neuer Link (Wähle Place, Choice, Page), Bearbeiten (füge Skill-Ranges oder Objects hinzu), Löschen, Kopieren.
- Filter: Nach Place, Decision, Skill oder Object filtern, Sortierung ändern.

Places
- Zweck: Verwaltung von Orten mit Parent-Hierarchie und bis zu fünf Textfeldern (z. B. Szenenbeschreibung).
- Aktionen: Neuen Ort anlegen, bestehenden Ort auswählen und bearbeiten, Löschung mit Bestätigung.

Network Admin (Ways)
- Zweck: Pflege von Wegen zwischen Orten; Zuordnung von Objekten/Charakteren/Skills zu Wegen.
- Aktionen: Ort hinzufügen, Weg anlegen (Start, Ziel, optional bidirektional), Objekte/Skills zu Wegen hinzufügen, Löschen.
- Validation: Start und Ziel dürfen nicht gleich sein; Start und Ziel müssen denselben Parent haben.

Net (Visualisierung)
- Zweck: Grafische Darstellung des Netzwerks; Rekursive Subnetzwerke anzeigen.
- Aktionen: Hauptnetzwerk betrachten; bei vorhandenen Subnetzwerken auf "Subnetzwerk anzeigen" klicken.

Generate
- Zweck: Erzeugt statische HTML-Seiten aus DB-Inhalten. Benutze vorsichtig: Operationen überschreiben das `html/`-Verzeichnis.
- Aktionen: Über die UI kann die Generierung gestartet; es existiert ein `finish`-Mode, der die finale Nummerierung vornimmt.
- Hinweis: Erzeuge vor dem Start Backups, falls du Änderungen an Daten rückgängig machen möchtest.

Allgemeine Hinweise für Anwender
- Zugangsbeschränkung: Nur Admins sollten Zugriff auf das Dashboard haben.
- Backups: Vor massiven Aktionen (Massenlöschung, Generate, DB-Löschen) immer Backup machen.
- Dumps: `db_manager.php` legt Dumps in `php/dumps/` ab — sichere diese außerhalb des Webroots.

Fehlerbehebung (kurz)
- Bei fehlgeschlagenen Dumps: Prüfe `php/dumps/last_mysqldump_cmd.txt` und die Dump-Datei auf Fehlermeldungen.
- Bei fehlenden Einträgen: prüfe DB-Verbindungsdaten in `php/config.php`.
- Bei Berechtigungsproblemen: Prüfe Dateisystemrechte für `php/dumps/` und `html/`.

Support
- Wenn du Unterstützung brauchst, schicke Fehlerausgaben oder Screenshots der Fehlermeldungen an den Entwickler.

Datum: 2026-08-02
