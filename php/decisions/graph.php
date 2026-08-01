<?php
require '../utils.php';

$pdo = getPDO();

$sql = "SELECT link_id FROM decision_links GROUP BY link_id";
$stmt = $pdo->prepare($sql);
$stmt->execute();   

$links_with_decision = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->query("SELECT id FROM decision");
$decisions = $stmt->fetchAll(PDO::FETCH_COLUMN);

if(isset($_GET['lid'])){
    $rootLink  = (int)$_GET['lid'];
}
else{
    $rootLink  = (int)$links_with_decision[0];
}

$sql = "SELECT decision_id FROM decision_links WHERE link_id = :link_id";
$stmt = $pdo->prepare($sql);

$stmt->bindParam(':link_id', $rootLink, PDO::PARAM_INT);
$stmt->execute();

// Ergebnis holen
$did = $stmt->fetchColumn();

echo "decision id: " . $did;

// 2) Root-Page-Text holen über diesen Link
$stmt = $pdo->prepare("
    SELECT p.text
    FROM pages p
    JOIN links l ON p.id = l.page_id
    WHERE l.id = ?
");
$stmt->execute([$rootLink]);
$rootPageText = $stmt->fetchColumn() ?: "Decision #{$did}";

// Arrays vorbereiten und Root-Knoten anlegen
$nodes = [];
$edges = [];

// Root-Decision-Node: Label = Page-Text
$wrappedRoot = wordwrap($rootPageText, 60, "\n", true);
$nodes["decision_{$did}"] = [
    'data' => [
        'id'    => "decision_{$did}",
        'label' => $wrappedRoot
    ]
];

/**
 * Baut eine Decision (mit ihren Choices → Pages → Folge-Decisions) rekursiv
 */
function buildDecision(int $decisionId, PDO $pdo, array &$nodes, array &$edges) {
    $decId = "decision_{$decisionId}";

    // 1) Decision-Node nur anlegen, wenn nicht schon (Root wurde vorab erzeugt)
    if (!isset($nodes[$decId])) {
        $nodes[$decId] = [
            'data' => [
                'id'    => $decId,
                'label' => "Decision #{$decisionId}"
            ]
        ];
    }

    // 2) Alle Choices dieser Decision
    $stmt = $pdo->prepare("
        SELECT id, label, text
        FROM choice
        WHERE decision_id = ?
    ");
    $stmt->execute([$decisionId]);
    while ($choice = $stmt->fetch()) {
        $cid      = (int)$choice['id'];
        $choiceId = "choice_{$cid}";
        $label    = $choice['label'] . ": " . $choice['text'];
        $wrapped  = wordwrap($label, 30, "\n", true);

        // Choice-Node
        if (!isset($nodes[$choiceId])) {
            $nodes[$choiceId] = [
                'data' => [
                    'id'    => $choiceId,
                    'label' => $wrapped
                ]
            ];
        }

        // Edge Decision → Choice
        $edges[] = [
            'data' => [
                'id'     => "e_dec{$decisionId}_ch{$cid}",
                'source' => $decId,
                'target' => $choiceId
            ]
        ];

        // 3) Page, auf die diese Choice zeigt
        $stmt2 = $pdo->prepare("
            SELECT page_id
            FROM links
            WHERE choice_id = ?
        ");
        $stmt2->execute([$cid]);
        $link = $stmt2->fetch();

        if ($link) {
            $pageNum = (int)$link['page_id'];
            $pageId  = "page_{$pageNum}";

            // Page-Text holen und Node anlegen
            if (!isset($nodes[$pageId])) {
                $stmt3 = $pdo->prepare("SELECT text FROM pages WHERE id = ?");
                $stmt3->execute([$pageNum]);
                $pageText   = $stmt3->fetchColumn() ?: '';
                $wrappedPg  = wordwrap($pageText, 30, "\n", true);

                $nodes[$pageId] = [
                    'data' => [
                        'id'    => $pageId,
                        'label' => $wrappedPg
                    ]
                ];
            }

            // Edge Choice → Page
            $edges[] = [
                'data' => [
                    'id'     => "e_ch{$cid}_pg{$pageNum}",
                    'source' => $choiceId,
                    'target' => $pageId
                ]
            ];

            // 4) Folge-Decision prüfen
            $stmt4 = $pdo->prepare("
                SELECT decision_id as id
                FROM decision_links
                WHERE link_id = (
                    SELECT id FROM links
                    WHERE page_id = ? AND choice_id = ?
                )
            ");
            $stmt4->execute([$pageNum, $cid]);
            $next = $stmt4->fetch();
            if ($next) {
                buildDecision((int)$next['id'], $pdo, $nodes, $edges);
                $edges[] = [
                    'data' => [
                        'id'     => "e_pg{$pageNum}_dec{$next['id']}",
                        'source' => $pageId,
                        'target' => "decision_{$next['id']}"
                    ]
                ];
            }
        } else {
            // Endknoten
            $endId = "end_{$cid}";
            if (!isset($nodes[$endId])) {
                $nodes[$endId] = [
                    'data' => [
                        'id'    => $endId,
                        'label' => "Ende"
                    ]
                ];
            }
            $edges[] = [
                'data' => [
                    'id'     => "e_ch{$cid}_end",
                    'source' => $choiceId,
                    'target' => $endId
                ]
            ];
        }
    }
}

// Rekursion starten (Root haben wir schon angelegt)
buildDecision($did, $pdo, $nodes, $edges);

// Cytoscape erwartet numerisch indiziertes Array
$elements = array_merge(array_values($nodes), $edges);
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <title>Graph-Ansicht für Decision <?= htmlspecialchars($did) ?></title>
  <script src="static/cytoscape.min.js"></script>
  <link rel="stylesheet" href="static/style.css">
  <style>
    #cy { width:100%; height:90vh; border:1px solid #ccc }
  </style>
</head>
<body>

  <h1>Link auswählen</h1>
  <ul>
    <?php foreach($links_with_decision as $link_id): ?>
      <li>
        <a href="graph.php?lid=<?= $link_id ?>">Graph <?= $link_id ?>(todo: link_name einbauen)</a>
      </li>
    <?php endforeach ?>
  </ul>

  <h1>Graph für Decision <?= htmlspecialchars($did) ?></h1>
  <div id="cy"></div>
  <script>
    const elements = <?= json_encode($elements, JSON_UNESCAPED_UNICODE) ?>;
    const cy = cytoscape({
      container: document.getElementById('cy'),
      elements: elements,
      style: [
        { selector: 'node', style: {
            'label': 'data(label)',
            'text-wrap': 'wrap',
            'text-max-width': '600px',
            'text-valign': 'center',
            'text-halign': 'right', 
            'shape': 'round-rectangle',
            'padding': '10px',
            'background-color': '#fafafa',
            'font-size': '12px'
          }
        },
        { selector: 'edge', style: {
            'curve-style': 'bezier',
            'target-arrow-shape': 'triangle',
            'label': 'data(label)',
            'font-size': '10px'
          }
        }
      ],
      layout: { name: 'breadthfirst', directed: true, padding: 10 }
    });
  </script>
  <p><a href="index.php">← zurück</a></p>
</body>
</html>
