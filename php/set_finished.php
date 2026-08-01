<?php

require 'utils.php';

$linkId = $_GET['link_id'];

$partNr = $_GET['part_nr'];

$pdo = getPDO();

$filename = '../html/'.$linkId.'.'.($partNr+2).'.html';

if(is_file($filename)){

    $stmt = $pdo->prepare("update links set length = ".($partNr+2).", bubble_field_index = 0 where id = :id");
    $stmt->execute(['id' => $linkId]);

    $stmt = $pdo->prepare("select bubble_field_index FROM links where id = :id");
    $stmt->execute(['id' => $linkId]);
    $bubble_field_index = $stmt->fetch(PDO::FETCH_ASSOC);

    if($bubble_field_index != -1){
        echo "load_next_page";
    }
}
else{
    $stmt = $pdo->prepare("update links set finished = 1, bubble_field_index = 0 where id = :id");
    $stmt->execute(['id' => $linkId]);

    echo "done";
}

