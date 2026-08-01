<?php

require 'utils.php';

$pdo = getPDO();

$links = $pdo->query("SELECT id, length, finished FROM links")->fetchAll(PDO::FETCH_ASSOC);

$linksById = [];

$ids = [];

foreach ($links as $link) {

    $linksById[$link['id']] = $link;
    $ids[] = $link['id'];
}

shuffle($ids);

$page_nr = 1;

foreach($ids as $id){
    $length = $linksById[$id]['length'];

    $stmt = $pdo->prepare("UPDATE links SET page_nr = ".$page_nr." WHERE id=".$id);
    $stmt->execute();   
    
    $page_nr += $length;
}

echo "done";

