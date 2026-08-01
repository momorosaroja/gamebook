<?php

require 'utils.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
/*
$linkId = (int)$data['link_id'];

$pdo = getPDO();

$stmt = $pdo->prepare("SELECT id, text FROM pages where id = (SELECT page_id FROM links WHERE id = :id)");
$stmt->execute(['id' => $linkId]);

$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_text = $pages[0]['text'];
$page_id = $pages[0]['id'];

$lines = explode("\n", $page_text);

print_r($lines);

$new_lines = [];

$i=0;

$tag = "{NEW_PAGE}";

$tag_found = false;

foreach($lines as $line){

    if($line == $tag){
        $tag_found = true;
        $new_page_pos = $i;
    }
    else{
        $new_lines[] = $line;   
    }

    $i++;
}

if($tag_found){
    $new_page_pos--;
}
else{
    $new_page_pos = count($new_lines)-1;
}

array_splice($new_lines, $new_page_pos, 0, $tag);

print_r($new_lines);

$new_text = implode("\n", $new_lines);

$stmt = $pdo->prepare("UPDATE pages SET text = \"".addslashes($new_text)."\" WHERE id = :id");
$stmt->execute(['id' => $page_id]);
*/
echo "done";

