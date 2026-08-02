# Technische Dokumentation — Net (net.php)

Kurz: Visualisierung des Orts-/Weg-Netzwerks mit `vis-network`, Gruppierung nach `parent_id` und rekursiven Subnetzwerken.

Datei
- `php/net.php`

Funktionen
- Lädt `places` und `ways`, gruppiert Orte nach `parent_id` und baut `nodes` und `edges` Arrays.
- `getNetworkData(parentId, ...)` produziert `nodes` und `edges` für vis.
- `renderSubnetworks()` unterstützt rekursive Anzeige via `parent_chain[]` GET-Parameter.

Frontend
- Verwendet `vis-network` (remote via unpkg CDN) zum Rendern.

Performance & Skalierung
- Bei vielen Knoten/Edges kann das Rendering langsam werden; Optionen: Pagination, Server-side clustering, node/edge limits.

Empfehlungen
- Cache generierte Netzwerke für große Sets; prüfe `vis-network` Konfiguration für Performance-Tuning.

Datum: 2026-08-02
