<?php

require 'utils.php';

$pdo = getPDO();

/*

{NEW_PAGE}

{SET OBJECT ID:X}

{UNSET OBJECT ID:X}

{FILTER OBJECTS:X} ?????

{SHOW TEXT X}
{SHOW ALL TEXTS}

{IF OBJECT ID:X} //in place-texten

{IF NOT OBJECT ID:X} //in place-texten

TODO:

 - SPÄTER links, die nur zu bedingten choices führen, sollten einen Rücksprung-Link haben

  - ifs in place-texten

  - ifs in page-texten

  - vielleicht interlink-seiten für die ifs?
*/

$generate_only_link_id = false;

$finish = false;

if(isset($_GET['subpage_nr'])){
    $subpage_nr = $_GET['subpage_nr'];
}
else{
    $subpage_nr = 1;
}

if(isset($_GET['link_id'])){
    $generate_only_link_id = $_GET['link_id'];
}
else if(isset($_GET['final'])){
    $finish = true;
}
else{
    $generate_only_link_id = false;

    $stmt = $pdo->prepare("UPDATE links SET length = 1");
    $stmt->execute();

    $stmt = $pdo->prepare("UPDATE links SET length = 1");
    $stmt->execute();

    $stmt = $pdo->prepare("UPDATE links SET bubble_field_index = 0");
    $stmt->execute();

    $stmt = $pdo->prepare("UPDATE links SET new_pages_skipped = 0");
    $stmt->execute();   

    $stmt = $pdo->prepare("UPDATE links SET text_place = ''");
    $stmt->execute();

    $stmt = $pdo->prepare("UPDATE links SET text_page = ''");
    $stmt->execute();

    $stmt = $pdo->prepare("UPDATE links SET text_decision = ''");
    $stmt->execute();

    $stmt = $pdo->prepare("UPDATE links SET text_navigation = ''");
    $stmt->execute();   

    $stmt = $pdo->prepare("UPDATE links SET regenerated = 0");
    $stmt->execute();

    $stmt = $pdo->prepare("UPDATE links SET finished = 0");
    $stmt->execute();
}

// Template laden
$template = file_get_contents(__DIR__ . '/../template.html');
$distribute_template = file_get_contents(__DIR__ . '/../distribute_template.html');

$_places = $pdo->query("SELECT id, concat('[@begin]',text1,'[@end]') as text1, concat('[@begin]',text2,'[@end]') as text2, concat('[@begin]',text3,'[@end]') as text3, concat('[@begin]',text4,'[@end]') as text4, concat('[@begin]',text5,'[@end]') as text5, name FROM places")->fetchAll(PDO::FETCH_ASSOC);
$places = [];
foreach ($_places as $row) {
    $id = $row['id'];
    unset($row['id']); // id rausnehmen, damit nur die restlichen Felder im Subarray stehen
    $places[$id] = $row;
}

$_objects = $pdo->query("SELECT id, name, set_text, unset_text, if_text, if_not_text FROM objects")->fetchAll(PDO::FETCH_ASSOC);
$objects = [];
foreach ($_objects as $row) {
    $id = $row['id'];
    unset($row['id']); // id rausnehmen, damit nur die restlichen Felder im Subarray stehen
    $objects[$id] = $row;
}

// Links gruppieren
$links = $pdo->query("SELECT id, place_id, page_id, choice_id, text_place, text_page, text_decision, text_navigation, bubble_field_index, new_pages_skipped, finished, page_nr FROM links")->fetchAll(PDO::FETCH_ASSOC);

$linksById = [];
$linksByPlace = [];
$page_nrs_for_finish = [];

foreach ($links as $link) {
    $linksById[$link['id']] = $link;
    $linksByPlace[$link['place_id']][] = $link['id'];
    $page_nrs_for_finish [$link['id']] = $link['page_nr'];
}

// Ausgabe-Ordner anlegen
if(!$finish){
    $outputDir = __DIR__ . '/../html';
}
else{
    $inputDir =  __DIR__ . '/../html';

    $outputDir = __DIR__ . '/../html_with_pagenumbers';
}

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

if($finish){
    $files = scandir($inputDir);

    $htmls = [];

    foreach ($files as $file) {
        if ($file === "." || $file === "..") {
            continue;
        }

        $path = $inputDir . "/" . $file;

        if (is_file($path)) {
            $_ = explode('.', $file);
            $old_page_nr = $_[0];

            //$file auf nr reduzieren
            $htmls[$old_page_nr] = file_get_contents($path);
        }
    }

    $files = array_keys($htmls);

    foreach($files as $file){
        $page_nr = $page_nrs_for_finish[$file];

        $html = $htmls[$file];

        $html = str_replace($file, $page_nr, $html);

        echo "<br>writing: ".$outputDir."/".$page_nr.".html";

        file_put_contents($outputDir."/".$page_nr.".html", $html);

    }

    die();
}

// Entscheidung-Links laden
$decisionLinks = $pdo->query("SELECT link_id, decision_id FROM decision_links")->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_COLUMN);

// Seiteninhalte
$pages = $pdo->query("SELECT id, text FROM pages")->fetchAll(PDO::FETCH_KEY_PAIR);

// Choices gruppieren
$choicesRaw = $pdo->query("SELECT id, label, text, decision_id FROM choice")->fetchAll(PDO::FETCH_ASSOC);
$choicesByDecision = [];
foreach ($choicesRaw as $c) {
    $choicesByDecision[$c['decision_id']][] = $c;
}

// Navigation (ways)
$ways = $pdo->query("SELECT start, end, bidirectional FROM ways")->fetchAll(PDO::FETCH_ASSOC);
$navigationMap = [];
foreach ($ways as $w) {
    $navigationMap[$w['start']][] = $w['end'];
    if ($w['bidirectional']) {
        $navigationMap[$w['end']][] = $w['start'];
    }
}

// link_id => [ list of page numbers ]
$pageNumberMap = generate_pageNumberMap();

// HTML-Dateien erzeugen
$pageFiles = [];

foreach ($links as $link) {
    handle_link($link);
}

write_launcher_page();

echo "<hr>Starter-Seite erzeugt!";
echo "<br>";
echo "<a href=\"../html/../open-all.html\" target=\"_blank\">Jetzt Seiten öffnen</a><br>";

//-----------------------------------

function adjust_new_page($text, $linkId, $number_of_forbidden_new_pages){

    global $pdo, $newpages_found_so_far, $subpage_nr;

    $tag = "{NEW_PAGE}";

    $highest_pos_of_forbidden_new_page = 0;

    $lines = explode("\n", $text);

    print_r($lines);

    echo "<hr>";

    echo "<br>number of lines in text: ".count($lines);

    echo "<br>subpage_nr: ".$subpage_nr;

    echo "<br>number_of_forbidden_new_pages: ".$number_of_forbidden_new_pages."!";

    if(end($lines)==$tag){
       $new_lines = array_slice($lines, 0, -1);

       $finished_bubbling_one_field = true;
    }
    else{
        $new_lines = [];

        $i=0;

        $tag_found = false;

        $newpages_found_so_far = 0;

        foreach($lines as $line){
            if($line == $tag){
                $newpages_found_so_far++;

                if($newpages_found_so_far >= $number_of_forbidden_new_pages){
                    if($i > $highest_pos_of_forbidden_new_page){
                        $tag_found = true;
                        $new_page_pos = $i;      
                    }
                }
                else{
                    if($i > $highest_pos_of_forbidden_new_page){
                        $highest_pos_of_forbidden_new_page = $i;
                    }

                    $new_lines[] = $line; 
                }
            }
            else{
                $new_lines[] = $line;   
            }

            $i++;
        }

        echo "<br>highest_pos_of_forbidden_new_page: ".$highest_pos_of_forbidden_new_page;

        if($tag_found){
            $new_page_pos--;
        }
        else{
            $new_page_pos = count($new_lines)-1;
            echo "<br>no  newline found!";
        }

        if($new_page_pos != 0){
            if($new_lines[$new_page_pos-1] != $tag){
                array_splice($new_lines, $new_page_pos, 0, $tag);

                $finished_bubbling_one_field = false;
            }
            else{
                $finished_bubbling_one_field = true;
            }
        }
        else{
            //ist die situation realistisch? im ersten feld, des gebubbelt werden kann, kein erfolg? man kann dann gefahrlos weitermachen, aber es wird zu nichts führen!
            $finished_bubbling_one_field = true;
        }

        print_r($new_lines);
    }

    $new_text = implode("\n", $new_lines);

    //echo "<br>"."new_text: ".$new_text;

    echo "<br>"."new_page_pos: ".$new_page_pos."!";
    echo "<br>"."finished_bubbling_one_field: ".$finished_bubbling_one_field;
    echo "<br>";

    $number_of_new_pages_found = substr_count($new_text, $tag);

    return ['new_text' => $new_text, 'finished_bubbling_one_field' => $finished_bubbling_one_field, 'number_of_new_pages_found' => $number_of_new_pages_found];
}

//---------------------------

function bubble($linkId){

    global $linksById, $pdo, $newpages_found_so_far, $subpage_nr;

    $newpages_found_so_far = 0;

    $bubble_fields = ['text_place', 'text_page', 'text_decision', 'text_navigation'];

    $bubble_index = $linksById[$linkId]['bubble_field_index'];

    if($bubble_index==-1){
        die();
    }

    mylog($linkId." - bubble_index: ".$bubble_index);

    $field_to_bubble = $bubble_fields[$bubble_index];

    echo "<br>field_to_bubble:".$field_to_bubble;

    $bubble_text = $linksById[$linkId][$field_to_bubble];

    echo $subpage_nr - $linksById[$linkId]['new_pages_skipped'];

    $_ = adjust_new_page($bubble_text, $linkId, $subpage_nr - $linksById[$linkId]['new_pages_skipped']);   

    $finished_bubbling_one_field = $_['finished_bubbling_one_field'];

    $new_text = $_['new_text'];

    $stmt = $pdo->prepare("UPDATE links SET ".$field_to_bubble." = \"".addslashes($new_text)."\" WHERE id = :id");
    $stmt->execute(['id' => $linkId]);       

    if($finished_bubbling_one_field){

        echo ($linkId." - finished bubbling ".$field_to_bubble);

        if($bubble_index < 3){
            $stmt = $pdo->prepare("UPDATE links SET bubble_field_index = bubble_field_index +1 WHERE id = :id");
            $stmt->execute(['id' => $linkId]);   

            $stmt = $pdo->prepare("UPDATE links SET new_pages_skipped = new_pages_skipped + ".$_['number_of_new_pages_found']." WHERE id = :id");
            $stmt->execute(['id' => $linkId]);              
        }
        else{
            $stmt = $pdo->prepare("UPDATE links SET bubble_field_index = -1 WHERE id = :id");
            $stmt->execute(['id' => $linkId]);   
        }      
    }
}

function handle_link($link){

    global $generate_only_link_id, $objects, $pageFiles, $outputDir, $distribute_template, $template, $links, $choicesByDecision, $decisionLinks, $navigationMap, $linksByPlace, $linksById, $pages, $places, $pageNumberMap, $pdo;

    $linkId    = $link['id'];
    $placeId   = $link['place_id'];
    $nrList    = $pageNumberMap[$linkId];

    if($generate_only_link_id){
        if($generate_only_link_id == $linkId){

            $placeName = $places[$placeId]['name'];

            $stmt = $pdo->prepare("UPDATE links SET regenerated = regenerated + 1 WHERE id = ?");
            $stmt->execute([$linkId]);    

            bubble($linkId);

            //temp, copied:
            $navHtml = build_navigation($linkId, $placeId, $pdo);

            $stmt = $pdo->prepare("SELECT text_place, text_page, text_decision, text_navigation FROM links WHERE id = :id");
            $stmt->execute(['id' => $linkId]); 
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $place_text = $row ['text_place'];
            $page_text = $row ['text_page'];
            $decHtml = $row ['text_decision'];
            $navHtml= $row ['text_navigation'];

            // $part_nr??
            // $count_parts??

            $part_nr = 0;
            $count_parts = 1;

            $tag = "{NEW_PAGE}";

            $htmls = [];

            $place_texts = explode($tag, ''.$place_text);

            $i=0;

            while($i < count($place_texts)-1){
                $htmls[] = replace_without_page_nr($template, '', '', '', $place_texts[$i], $placeId, $placeName, $linkId, count($htmls));
                $i++;
            }

            $place_text = end($place_texts);

            //------------------------

            $page_texts = explode($tag, ''.$page_text);

            $i=0;

            while($i < count($page_texts)-1){
                
                $htmls[] = replace_without_page_nr($template, $page_texts[$i], '', '', $place_text, $placeId, $placeName, $linkId, count($htmls));
                $place_text = "";
                $i++;
            }

            $page_text = end($page_texts);  

            //------------------------

            $decHtmls = explode($tag, ''.$decHtml);

            $i=0;

            while($i < count($decHtmls)-1){
                $htmls[] = replace_without_page_nr($template, $page_texts[$i], '', $decHtmls[$i], $place_text, $placeId, $placeName, $linkId, count($htmls));
                $page_text = "";
                $i++;
            }

            $decHtml = end($decHtmls);       
            
            //------------------------

            $navHtmls = explode($tag, ''.$navHtml);

            $i=0;

            while($i < count($navHtmls)-1){
                $htmls[] = replace_without_page_nr($template, $page_text, $navHtmls[$i], $decHtml, $place_text, $placeId, $placeName, $linkId, count($htmls));
                $nav_html = "";
                $i++;
            }

            $decHtml = end($decHtmls);                 

            //----------

            $htmls[] = replace_without_page_nr($template, $page_text, $navHtml, $decHtml, $place_text, $placeId, $placeName, $linkId, count($htmls));

            //------------------------
            //------------------------

            $part_nr = 1;

            echo count($htmls);

            foreach($htmls as $html){
                $html = replace_page_nr($html, $linkId);
                
                $filename = $linkId.".".$part_nr.".html";//todo: muss pagenr werden!
                $filePath = "$outputDir/$filename";
                        
                write_page($filename, $filePath, $html);

                $pageFiles[] = $filename;

                $part_nr++;
            }


            /*

            //ifs in place-texten (egal, ob in page_text oder in place_text) verarbeiten
            $positions = [];
            $pos = 0;

            while (($pos = strpos($html, "[@begin]{IF ", $pos)) !== false) {
                $positions[] = $pos;
                $pos++;
            }

            if(count($positions)>0){
            
                echo "<br>TODO VERTEILERSEITE!!";
            }
            else{
                $html = replace_page_nr($html, $linkId);
                
                $filename = $linkId.".html";//todo: muss pagenr werden!
                $filePath = "$outputDir/$filename";
                        
                write_page($filename, $filePath, $html);

                $pageFiles[] = $filename;
            }  

            */          
        }
    }
    else{

        $placeName = $places[$placeId]['name'];

        echo "<hr>";
        echo "Verarbeite Link ID: {$link['id']} (Place ID: {$link['place_id']})<br>";

        $navHtml = build_navigation($linkId, $placeId, $pdo);

        $decHtml = build_decisions($linkId, $pdo);

        $page_text = build_page_text($linkId, $pdo, $link);

        $place_text = build_place_text($linkId, $placeId, $pdo);

        $html = replace_without_page_nr($template, $page_text, $navHtml, $decHtml, $place_text, $placeId, $placeName, $linkId, 0);

        $html = replace_page_nr($html, $linkId);

        $filename = $linkId.".1.html";//todo: muss pagenr werden!
        $filePath = "$outputDir/$filename";
            
        write_page($filename, $filePath, $html);

        $pageFiles[] = $filename;       
    }
}

function build_place_text($linkId, $placeId, $pdo){

    global $linksById, $places;

    $places_temp = $places[$placeId];

    //wird ein link über einen choice erreicht, dann werden die place-texte nicht ausgegeben!
    if ($linksById[$linkId]['choice_id']!== null) {
        for($text_nr = 1; $text_nr <=5; $text_nr++) {
            $places_temp["text".$text_nr] = "";
        }
    }
    else{ //wenn sie aber ausgegeben werden, dann müssen sie in tags verpackt werden
        for($text_nr = 1; $text_nr <=5; $text_nr++) {
            if ($places_temp["text".$text_nr] != "") {
                $places_temp["text".$text_nr] = "<div>".$places_temp["text$text_nr"] . '</div><br/>';
            }
        }
    }

    $place_text = "";

    $place_text_comma = "";

    for($text_nr = 1; $text_nr <=5; $text_nr++) {
        if($places_temp["text".$text_nr] != ""){
            $place_text .= $place_text_comma.$places_temp["text".$text_nr];

            $place_text_comma = "\n";
        }
    }

    $stmt = $pdo->prepare("UPDATE links SET text_place = \"".addslashes($place_text)."\" WHERE id = ?");
    $stmt->execute([$linkId]);

    return $place_text;
}


function build_page_text($linkId, $pdo, $link){

    global $places, $pages, $objects;

    $page_text = $pages[$link['page_id']];

    $object_ids = array_keys($objects);

    foreach($object_ids as $object_id){
        $page_text = str_replace('{SET OBJECT ID:'.$object_id.'}',$objects[$object_id]['set_text'], $page_text);

        $page_text = str_replace('{SET OBJECT ID:'.$object_id.'}',$objects[$object_id]['unset_text'], $page_text);
    }

    //------- alle show-text-commands in PAGES-text einbauen ----------
    $all_place_texts = "";

    for($placeText_nr = 1; $placeText_nr <=5; $placeText_nr++) {
        $page_text = str_replace("{SHOW TEXT ".$placeText_nr."}", "<br/><br/>".$places[$placeId]["text".$placeText_nr]."<br/><br/>", $page_text);

        //hier wird schonmal $all_place_texts gebaut, falls es gleich für {show_all_texts} gebraucht wird
        $all_place_texts .= "<br/><br/>".$places[$placeId]["text".$placeText_nr];
    }

    if($all_place_texts != ""){
        $all_place_texts .= "<br/><br/>";

        $page_text = str_replace("{SHOW ALL TEXTS}", $all_place_texts, $page_text);
    }   

    $stmt = $pdo->prepare("UPDATE links SET text_page = \"".addslashes($page_text)."\" WHERE id = ?");
    $stmt->execute([$linkId]);

    return $page_text;
}

function write_page($filename, $filePath, $html){

    // todo: die replaces kannman bestimmt woanders besser vornehmen:()
    $html = str_replace('[@begin]','', $html);
    $html = str_replace('[@end]','', $html);   
    $html = str_replace('<div></div><br/>','', $html); 

    //todo: nicht hier!!
    //$html = nl2br(htmlspecialchars(trim($html), ENT_QUOTES));

    file_put_contents($filePath, $html);
    echo "✅ Generiert: $filename<br>\n";
}

function replace_page_nr($html, $pageNr){

    $replacements = [
        '{PAGE_NR}'    => (int)$pageNr,
    ];    

    $html = str_replace(
        array_keys($replacements),
        array_values($replacements),
        $html
    );

    return $html;
}

function replace_without_page_nr($template, $page_text, $navHtml, $decHtml, $place_text, $placeId, $placeName, $linkId, $part_nr){

    $isFirst = ($part_nr === 0);

    $replacements = [
        '{TITLE}'      => $placeName ."(".$part_nr.")", 
        '{PLACE_NAME}' => $isFirst ? $placeName: '',
        '{PLACE_NR}' => $placeId,
        '{PLACE_TEXT}' => $place_text,
        '{PAGE_TEXT}'  => $page_text,
        '{NAVIGATION}' => $navHtml,
        '{DECISION}'   => $decHtml,
        '{LINK_ID}'    => $linkId,
        '{PART_NR}'    => $part_nr
    ];

    $html = str_replace(
        array_keys($replacements),
        array_values($replacements),
        $template
    );

    return $html;
}

function build_navigation($linkId, $placeId, $pdo){

    global $places, $pageNumberMap, $linksByPlace, $linksById, $navigationMap;

    $print_navHtml = false;

    $navHtml = '';

    $nav_nr = 0;

    foreach ($navigationMap[$placeId] ?? [] as $destPlaceId) {

        foreach ($linksByPlace[$destPlaceId] ?? [] as $destLinkId) {

            if ($linksById[$destLinkId]['choice_id'] !== null) continue;

            // Finde den passenden Way
            $wayStmt = $pdo->prepare("SELECT id FROM ways WHERE (start = ? AND end = ?) OR (bidirectional = 1 AND start = ? AND end = ?)");
    
            $wayStmt->execute([$placeId, $destPlaceId, $destPlaceId, $placeId]);
            $way = $wayStmt->fetch(PDO::FETCH_ASSOC);

            $showWay = false;
            if ($way) {

                // Prüfe, ob way_objects existieren
                $woStmt = $pdo->prepare("SELECT * FROM way_objects WHERE way_id = ?");
                $woStmt->execute([$way['id']]);
                $wayObjects = $woStmt->fetchAll(PDO::FETCH_ASSOC);

                $comma = "";

                foreach ($wayObjects as $wo) {

                    $oStmt = $pdo->prepare("SELECT * FROM objects WHERE id = ?");
                
                    $oStmt->execute([$wo['object_id']]);
                    $object = $oStmt->fetch(PDO::FETCH_ASSOC);

                    if($comma == ""){
                        $navHtml .= $comma."Nur, wenn ";
                    }
                    else{
                        $navHtml .= $comma;
                    }

                    $navHtml .= $object['if_text'];
                    
                    $comma = " und ";
                }

                if($comma != ""){
                    $navHtml .= ": ";
                }

                $destNr   = $pageNumberMap[$destLinkId][0];

                $destName = htmlspecialchars($places[$destPlaceId]['name'], ENT_QUOTES);
                $navHtml .= sprintf(
                '<a href="%2$s.html">%3$s (s.%1$s)</a><br>',
                (int)$destNr,
                $destNr,
                $destName
                );

                $print_navHtml = true;              
            }

            if ($showWay) {
                $destNr   = $pageNumberMap[$destLinkId][0];
                $destName = htmlspecialchars($places[$destPlaceId], ENT_QUOTES);
                $navHtml .= sprintf(
                    '<a href="%2$s.html">%3$s (s.%1$s)</a><br>',
                    (int)$destNr,
                    $destNr,
                    $destName
                );

                $print_navHtml = true;

                if($linksById[$linkId]['split_pos_navigation']){
                    if($nav_nr == $linksById[$linkId]['split_pos_navigation']){
                        if($nav_nr != 0){
                            $navHtml .= "{NEW_PAGE}";  
                        }                   
                    }
                }

                $nav_nr++;
            }
        }
    }

    if($print_navHtml){
        $navHtml = '<b>Wohin möchtest du gehen?</b><br><br>'.$navHtml;
    }

    $stmt = $pdo->prepare("UPDATE links SET text_navigation = \"".addslashes($navHtml)."\" WHERE id = ?");
    $stmt->execute([$linkId]);

    return $navHtml;
}

function build_decisions($linkId, $pdo){

    global $pageNumberMap, $links, $choicesByDecision, $decisionLinks;

    $print_decHtml = false;
    
    $decHtml = '';

    $condition = "";

    if (isset($decisionLinks[$linkId])) {
        foreach ($decisionLinks[$linkId] as $decisionId) {
            foreach ($choicesByDecision[$decisionId] ?? [] as $choice) {
                foreach ($links as $lr) {               
                    if ($lr['choice_id'] == $choice['id']) {

                        // Prüfe, ob link_objects existieren
                        $loStmt = $pdo->prepare("SELECT * FROM link_objects WHERE link_id = ?");
                        $loStmt->execute([$lr['id']]);
                        $linkObjects = $loStmt->fetchAll(PDO::FETCH_ASSOC);

                        $condition_comma = "";

                        foreach ($linkObjects as $lo) {

                            $oStmt = $pdo->prepare("SELECT * FROM objects WHERE id = ?");
                        
                            $oStmt->execute([$lo['object_id']]);
                            $object = $oStmt->fetch(PDO::FETCH_ASSOC);

                            if($condition_comma == ""){
                                $condition .= $condition_comma."Nur, wenn ";
                            }
                            else{
                                $condition .= condition_comma;
                            }

                            $condition .= $object['if_text'];
                            
                            $condition_comma = " und ";
                        }

                        if($condition_comma != ""){
                            $condition .= ": ";
                        }

                        $cNr = $pageNumberMap[$lr['id']][0];
                        $label = htmlspecialchars($choice['label'], ENT_QUOTES);
                        $text  = htmlspecialchars($choice['text'], ENT_QUOTES);
                        $decHtml .= sprintf(
                            '<a href="%2$s.html">%3$s %5$s %4$s (s.%1$s)</a>',
                            (int)$cNr,
                            $cNr,
                            $label,
                            $text,
                            $condition
                        );

                        $decHtml .= "\n\n";

                        $print_decHtml = true;

                        break;
                    }
                }
            }
        }
    }

    if ($print_decHtml) {
        $decHtml = '<b>Was möchtest du tun?</b> <br><br>' . $decHtml;
    }

    $stmt = $pdo->prepare("UPDATE links SET text_decision = \"".addslashes($decHtml)."\" WHERE id = ?");
    $stmt->execute([$linkId]);

    return $decHtml;
}

function write_launcher_page(){

    global $linksById, $outputDir;

    $link_ids = array_keys($linksById);

    $launcherHtml = <<<HTML
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <title>Alle Seiten öffnen</title>
        <script>
        function openAll() {
            const pages = [
    HTML;

    foreach ($link_ids as $link_id) {

        $file = $link_id.".1.html";

        $launcherHtml .= "            \"$file\",\n";
    }

    $launcherHtml .= <<<HTML
            ];
            pages.forEach((page, index) => {
                setTimeout(() => {
                    window.open('./html/' + page, '_blank');
                }, 100);
            });
        }
        </script>
    </head>
    <body>
        <h1>Alle Seiten öffnen</h1>
        <p>
            Hinweis: Stelle sicher, dass dein Browser Popups erlaubt.
        </p>
        <button onclick="openAll()">Seiten öffnen</button>
    </body>
    </html>
    HTML;

    file_put_contents($outputDir."/../open-all.html", $launcherHtml);
}

function generate_pageNumberMap(){

    global $links, $pages;

    $pageNumberMap = [];        

    $counter = 1;

    foreach ($links as $link) {
        $linkId = $link['id'];
        $pageId = $link['page_id'];

        $segments = explode('{NEW_PAGE}', $pages[$pageId]);
        $pageNumberMap[$linkId] = [];

        foreach ($segments as $i => $_) {
            $nr = sprintf('%06d', $counter++);
            $pageNumberMap[$linkId][] = $nr;
        }
    }

    return $pageNumberMap;
}

function mylog($text){

    $file = fopen("./generate.log", "a");

    fwrite($file, $text."\n\n");

    fclose($file);
}

?>
