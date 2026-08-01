<?php

require 'utils.php';

$pdo = getPDO(); // PDO-Verbindung aus utils.php

// Alle Orte
$places = $pdo->query("SELECT id, name, parent_id FROM places")->fetchAll(PDO::FETCH_ASSOC);

// Mapping für parent_id → Name
$groupNames = [];
foreach ($places as $place) {
    $groupNames[$place['id']] = $place['name'];
}

// Gruppierung der Orte nach parent_id
$groupedPlaces = [];
foreach ($places as $place) {
    $groupedPlaces[$place['parent_id']][] = $place;
}

// Wege abrufen
$ways = $pdo->query("SELECT start, end, bidirectional FROM ways")->fetchAll(PDO::FETCH_ASSOC);

// Wege nach parent_id filtern
$groupedWays = [];
foreach ($ways as $way) {
    $startPlace = array_filter($places, fn($p) => $p['id'] == $way['start']);
    $endPlace = array_filter($places, fn($p) => $p['id'] == $way['end']);
    if (count($startPlace) && count($endPlace)) {
        $startPlace = array_values($startPlace)[0];
        $endPlace = array_values($endPlace)[0];
        if ($startPlace['parent_id'] == $endPlace['parent_id']) {
            $groupedWays[$startPlace['parent_id']][] = $way;
        }
    }
}

// Funktion für die Netzwerkgrafik-Daten
function getNetworkData($parentId, $groupedPlaces, $groupNames, $groupedWays) {
    $placesGroup = $groupedPlaces[$parentId] ?? [];
    $groupName = $parentId === 0 ? "Hauptnetzwerk" : ($groupNames[$parentId] ?? "Unbekannt");
    $nodes = [];
    foreach ($placesGroup as $place) {
        $hasSub = isset($groupedPlaces[$place['id']]);
        $nodes[] = [
            'id' => $place['id'],
            'label' => $place['name'],
            'color' => $hasSub ? '#ff9800' : '#97C2FC'
        ];
    }
    $edges = [];
    foreach ($groupedWays[$parentId] ?? [] as $way) {
        $edges[] = [
            'from' => $way['start'],
            'to' => $way['end'],
            'arrows' => $way['bidirectional'] ? 'to, from' : 'to'
        ];
    }
    return [
        'groupName' => $groupName,
        'placesGroup' => $placesGroup,
        'nodes' => $nodes,
        'edges' => $edges
    ];
}

// Rekursive Subnetzwerk-Anzeige
function renderSubnetworks($parentChain, $groupedPlaces, $groupNames, $groupedWays) {
    if (empty($parentChain)) return;
    $parentId = end($parentChain);
    $network = getNetworkData($parentId, $groupedPlaces, $groupNames, $groupedWays);
    $networkDivId = "network_sub_" . implode("_", $parentChain);
    echo "<h2>" . htmlspecialchars($network['groupName']) . "</h2>";
    echo "<div id='$networkDivId' class='network'></div>";
    echo "<ul>";
    foreach ($network['placesGroup'] as $place) {
        echo "<li>" . htmlspecialchars($place['name']);
        if (isset($groupedPlaces[$place['id']])) {
            // Baue die neue Kette für die nächste Rekursion
            $newChain = $parentChain;
            $newChain[] = $place['id'];
            $chainParam = "";
            foreach ($newChain as $cid) {
                $chainParam .= "&parent_chain[]=" . $cid;
            }
            echo " <a class='subnet-link' href='?parent_chain[]=" . implode("&parent_chain[]=", $newChain) . "'>[Subnetzwerk anzeigen]</a>";
        }
        echo "</li>";
    }
    echo "</ul>";
    // JS für das Netzwerk
    echo "<script>
        (function() {
            const nodes = new vis.DataSet(" . json_encode($network['nodes']) . ");
            const edges = new vis.DataSet(" . json_encode($network['edges']) . ");
            const container = document.getElementById('$networkDivId');
            const data = { nodes: nodes, edges: edges };
            const options = { layout: { improvedLayout: true }, physics: { enabled: true } };
            new vis.Network(container, data, options);
        })();
    </script>";
}

// Hauptnetzwerk immer anzeigen
$mainNetwork = getNetworkData(0, $groupedPlaces, $groupNames, $groupedWays);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Netzwerk-Anzeige</title>
    <script type="text/javascript" src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
    <style>
        .network {
            width: 100%;
            height: 1000px;
            border: 1px solid #aaa;
            margin-bottom: 30px;
        }
        .subnet-link {
            font-size: 0.9em;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <h1><?= htmlspecialchars($mainNetwork['groupName']) ?></h1>
    <div id="network_main" class="network"></div>
    <ul>
        <?php foreach ($mainNetwork['placesGroup'] as $place): ?>
            <li>
                <?= htmlspecialchars($place['name']) ?>
                <?php if (isset($groupedPlaces[$place['id']])): ?>
                    <a class="subnet-link" href="?parent_chain[]=<?= $place['id'] ?>">[Subnetzwerk anzeigen]</a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <script>
        // Hauptnetzwerk
        (function() {
            const nodes = new vis.DataSet(<?= json_encode($mainNetwork['nodes']) ?>);
            const edges = new vis.DataSet(<?= json_encode($mainNetwork['edges']) ?>);
            const container = document.getElementById("network_main");
            const data = { nodes: nodes, edges: edges };
            const options = { layout: { improvedLayout: true }, physics: { enabled: true } };
            new vis.Network(container, data, options);
        })();
    </script>
    <?php
    // Rekursive Subnetzwerke anzeigen
    if (!empty($_GET['parent_chain'])) {
        $parentChain = array_map('intval', (array)$_GET['parent_chain']);
        renderSubnetworks($parentChain, $groupedPlaces, $groupNames, $groupedWays);
    }
    ?>
</body>
</html>

