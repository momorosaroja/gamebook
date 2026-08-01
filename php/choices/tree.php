<?php
require 'utils.php';

function getTree($linkId, $pdo) {
    $stmt = $pdo->prepare("SELECT pages.text FROM links JOIN pages ON links.page_id = pages.id WHERE links.id = ?");
    $stmt->execute([$linkId]);
    $pageText = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT id FROM decision WHERE link_id = ?");
    $stmt->execute([$linkId]);
    $decisions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $children = [];

    foreach ($decisions as $decisionId) {
        $stmt = $pdo->prepare("SELECT id, label, text FROM choice WHERE decision_id = ?");
        $stmt->execute([$decisionId]);
        $choices = $stmt->fetchAll();

        foreach ($choices as $choice) {
            $stmt = $pdo->prepare("SELECT id FROM links WHERE choice_id = ?");
            $stmt->execute([$choice['id']]);
            $childLinkId = $stmt->fetchColumn();

            if ($childLinkId) {
                $children[] = getTree($childLinkId, $pdo);
            } else {
                $children[] = [
                    'name' => $choice['label'],
                    'text' => $choice['text'],
                    'children' => []
                ];
            }
        }
    }

    return [
        'name' => "Link #$linkId",
        'text' => $pageText,
        'children' => $children
    ];
}

$tree = getTree($_GET['link_id'], $pdo);
header('Content-Type: application/json');
echo json_encode($tree);
?>

