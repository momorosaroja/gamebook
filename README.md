# gamebook

Dieses Projekt ist eine kleine Webanwendung zur Verwaltung von Spiel- und Entscheidungsknoten, Verbindungen und Seiteninhalten.

## Struktur

- `php/` – Hauptanwendung und Seitenlogik
- `database/` – SQL-Schema und Datenbankdateien
- `docs/` – technische und Nutzer-Dokumentation
- `html/`, `html_adds/`, `html_with_pagenumbers/` – Vorlagen und HTML-Assets

## Voraussetzungen

- Apache / XAMPP bzw. ein PHP-fähiger Webserver
- PHP 8+
- MariaDB/MySQL

- Todo für die Generierung (was zum PDF-Merge und chromium oder so zur pdf-Erstellung anhand der HTML-Seiten)

wget https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb
sudo apt install ./google-chrome-stable_current_amd64.deb
google-chrome --version


## Einrichtung

1. Die Projektdateien in das Webroot des Servers kopieren oder dort ausführen.
2. Die Datenbank anlegen und das Schema aus `database/schema.sql` importieren. 
3. In `php/config.php` die Datenbank-Zugangsdaten anpassen.
4. Die Anwendung über den Browser aufrufen, z. B. über `http://localhost/gamebook/`.

## Wichtige Hinweise

- Die Anwendung verwendet die PHP-Dateien im Ordner `php/` als Einstiegspunkte.
- Für Änderungen am Datenmodell sollte zuerst das Schema in `database/schema.sql` angepasst werden.

## Entwicklung

Die meisten Funktionen sind in den PHP-Dateien unter `php/` organisiert, beispielsweise:

- `php/decisions.php`
- `php/links.php`
- `php/places.php`
- `php/network_admin.php`
- `php/generate.php`

## Erkkärung der Datenstruktur

### Grundprinzip

Das, was als einzelne HTML-Seiten später ausgegeben wird, sind pages.

Eine Page hat einen Text, der auf dieser HTML-Seite ausgegeben wird.

Zu einer Page kommt man über einen Link.
- Ein Link hat immer eine Ziel-Page. Man kann auf eine schon bestehende Page verlinken; tut man das nicht, wird einen neue Page angelegt.
- Ein Link ist mit einem Place verbunden; das ist der ZIELORT.
(FRAGE: müsste man dann nicht irgendwie dafür sorgen, dass eine Page immer demselben Place zugeordnet ist???)

Places sind über ways miteinander verbunden; diese können unidirektional oder bidirektional sein.
Ways können an objekte gebunden sein; d.h., der way ist nur verfügbar, wenn bestimmte Bedingungen erfüllt sind, die durch object_id, objegt_type, number1 und number2 bestimmt sind.

Für einen Link kann eine Decision hinterlegt werden, d.h., am Ziel des Links kann eine Entscheidung getroffen werden.

An eine Decision können eine oder mehrere Choices angebunden werden.

Beim Erstellen eines Links kann dann eine Choice ausgewählt werden, d.h., die getroffene Entscheidung (=Choice) ist dann der Ausgangspunkt für diesen Link.
Eine Choice ohne hinterliegenden Link ist also eine Inkonsistenz!

Objects und SKills:

Beide können als Bedingung für ways in way_objects referenziert werden.
Beide können als Bedingung für einen Link in link_objects angegeben werden.

Objects repräsentieren Gegenstände, Skills repräsentieren Fähigkeiten.
Skills sollten eigentlich einen Wert haben, also z.B. "Stärke=5", dazu dienen auch number1 und number2 in way_objets. 

### "RAM-Speicher"

Einzelne Entscheidungen, wie das nehmen/ablegen von Objekten sowie das erwerben von skills erfordern Spielnotizen.
Damit diese für den Spieler nicht zu offensichtlich sind, können für objects (müsste später dann auch mal für skills möglich sein) Texte hinterlegt werden, die dann auf der HTML-Seite angezeigt werden, etwa z.B.: "lege den blauen Holzwürfel auf das Feld b4".
Diese Zustände werden dann später im Decision- und Navigationsteil der HTML-Seiten abgefragt.
(für objects gibts da set_text, unset_text, id_text und if_not_text, für skills ist das noch nicht implementiert).

### versteckte Features

- Places können ein png hinterlegt haben, mit dem Dateinamen: [id].png

- Es gibt eine kleine Direktiven-Sprache für die Textinhalte von Pages und Places. Die aktuelle Generator-Logik berücksichtigt aktuell folgende Befehle:

  - `{SET OBJECT ID:X}`: ersetzt den Befehl durch den Text aus `objects.set_text` des Objekts mit der ID `X`.
  - `{UNSET OBJECT ID:X}`: ersetzt den Befehl durch den Text aus `objects.unset_text` des Objekts mit der ID `X`.
  - `{SHOW TEXT N}`: fügt den entsprechenden Place-Text ein, wobei `N` eine Zahl von `1` bis `5` ist (`text1` bis `text5`).
  - `{SHOW ALL TEXTS}`: fügt alle nicht-leeren Place-Texte des aktuellen Places hintereinander ein.

  Beispiele:
  - `{SHOW TEXT 2}` zeigt den zweiten Place-Text an.
  - `{SET OBJECT ID:3}` setzt den Zustand des Objekts mit der ID `3`.

- Die IF-Direktiven wie `{IF OBJECT ID:X}` und `{IF NOT OBJECT ID:X}` sind im aktuellen Stand noch nicht in der Generator-Logik umgesetzt.

## Generierung

Gundprinzip ist, dass erstmal pro page eine HTML-Seite erzeugt wird. Diese wird dann automatisch auch im Browser geöffnet. Durch einen JS-Code auf der Seite wird es der Engine zurückgemeldet, wenn der Inhalt nicht auf die Seite passt; die Engine erzeugt die Seite dann so lange mit weniger Inhalt neu, bis dieser auf die Seite passt, und der restliche Inhalt wir auf weitere Seiten verteilt.
Das System blubbert also u.U. ordentlich vor sich hin, bis es fertig ist.
Zur Administration dieses Prozesses dienen auch die links-Felder bubble_field, length, regenerated und finished.
Aus den erstellten HTML-Seiten müssen dann per sh-Skript PDFs erstellt werden, und diese muss dann - ebenfalls per shell - ein Gesamt-PDF erstellt werden.

## Konsistenz-Checks / TODO

Die Datenstruktur setzt bereits einige inhaltliche Anforderungen an Konsistenz voraus, die aktuell nicht zentral geprüft werden:

- Jede Decision sollte idealerweise mindestens einen Link haben, über den sie im Spiel erreichbar ist.
- Jede Choice sollte zu einer bestehenden Decision gehören und sollte möglichst einen passenden Link besitzen.
- Jeder Link sollte auf eine bestehende Page zeigen und sollte mit einem gültigen Place verbunden sein.
- Für Way-Objekte und Link-Objekte sollten referenzierte Objekte, Skills oder Characters existieren.
- Places sollten nur dann mit Ways verbunden werden, wenn Start und Ziel denselben Parent haben (dies wird aktuell nur im UI/Controller geprüft, nicht als allgemeiner Datenbank- oder Anwendungscheck).

Dafür wäre ein zentraler Konsistenz-Check sinnvoll, z. B. als Hintergrundprüfung beim Speichern oder als eigener Admin-/Debug-View, der Inkonsistenzen auflistet und optional automatisch korrigiert.