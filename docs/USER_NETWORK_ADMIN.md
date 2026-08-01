# Anwenderdokumentation — Network Admin (network_admin.php)

Zweck
- Legt Wege zwischen Orten an und verwaltet Objekte/Charaktere/Skills, die an diesen Wegen hängen.

Benutzung
- Parent auswählen (Filter) → Liste der Orte dieses Parents erscheint.
- Weg anlegen: Wähle Start und Ziel (müssen verschiedenen Orten gehören und denselben Parent haben), optional bidirektional.
- Objekte/Skills zuordnen: Beim Anlegen oder später Inline per Formular hinzufügen.
- Löschen: Klicke "Löschen" auf einen Way (Bestätigung wird angefordert).

Hinweise
- Start und Ziel müssen denselben Parent haben; ansonsten wird das Einfügen abgelehnt.
- Bei Änderungen an way_objects die Seite neu laden (UI-Hinweis existiert), bis AJAX-Verbesserung implementiert ist.

Datum: 2026-08-02
