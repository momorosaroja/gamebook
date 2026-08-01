<?php

require_once 'config.php';

function shutdownHandler() {
    $error = error_get_last();
    if ($error !== null) {
        echo "Letzte Fehlermeldung:\n";
        echo "Typ: {$error['type']}\n";
        echo "Nachricht: {$error['message']}\n";
        echo "Datei: {$error['file']}\n";
        echo "Zeile: {$error['line']}\n";
    }
}

register_shutdown_function('shutdownHandler');


function getPDO(): PDO {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    return new PDO($dsn, DB_USER, DB_PASS, $opts);
}

function add_link($pars){

    global $pdo;

    if(!isset($pars['page_id'])){
        // TODO-Seite erstellen
        $pdo->prepare("INSERT INTO pages (text) VALUES ('TODO')")->execute();
        $page_id = $pdo->lastInsertId();
    }
    else{
        $page_id = $pars['page_id'];
    }

    $stmt = $pdo->prepare("
        INSERT INTO links 
            (place_id, choice_id, page_id, first_date, last_date)
        VALUES 
            (:place, :choice, :page_id, :first_date, :last_date)
    ");

    $now = date('Y-m-d H:i:s');
    $stmt->execute([
        ':place'       => $pars['place'],
        ':choice'      => $pars['choice'],
        ':page_id'    => $page_id,
        ':first_date' => $now,
        ':last_date'  => $now
    ]);    

    $link_id = $pdo->lastInsertId();
}

function getChoiceName($choiceId){

    global $pdo;

    $stmt = $pdo->prepare("
        SELECT label, text, decision_id
        FROM choice
        WHERE id = :id
    ");
    $stmt->execute(['id' => $choiceId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ? "[".getDecisionName($result['decision_id'])."]".$result['label'] . " " . $result['text'] : '';
}

function getDecisionName($decisionId){

    global $pdo;
    
    return $decisionId;
}   

function build_link_name($link_id) {

    global $pdo;

    // 1. Basis-Daten aus links + Orte
    $sql = "
      SELECT 
        l.choice_id,
        p.name   AS place_name
      FROM links l
      LEFT JOIN places p ON l.place_id       = p.id
      WHERE l.id = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$link_id]);
    $link = $stmt->fetch(PDO::FETCH_ASSOC);

    $parts = [];

    // Zielort
    if (!empty($link['place_name'])) {
        $parts[] = 'place:' . $link['place_name'];
    }

    // 2. Skills (object_type = 'skill_id')
    $sql = "
      SELECT 
        s.name        AS skill_name,
        lo.number1    AS skill_von,
        lo.number2    AS skill_bis
      FROM link_objects lo
      JOIN skills        s ON lo.object_id = s.id
      WHERE lo.link_id = ? 
        AND lo.object_type = 'skills'
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$link_id]);
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($skills as $sk) {
        $parts[] = sprintf(
            "skill:%s (%d-%d)",
            $sk['skill_name'],
            $sk['skill_von'],
            $sk['skill_bis']
        );
    }

    // 3. Objekte (object_type = 'object_id')
    $sql = "
      SELECT 
        o.name AS object_name
      FROM link_objects lo
      JOIN objects       o ON lo.object_id = o.id
      WHERE lo.link_id = ? 
        AND lo.object_type = 'objects'
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$link_id]);
    $objects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($objects as $obj) {
        $parts[] = 'object:' . $obj['object_name'];
    }

    // 4. Choice
    if(isset($link['choice_id'])){
        $choiceName = getChoiceName($link['choice_id']);
        if ($choiceName !== '') {
            $parts[] = 'choice:' . $choiceName;
        }
    }

    // 5. Alles mit Komma verbinden
    return implode(',', $parts);
}

?>