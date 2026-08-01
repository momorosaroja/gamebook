<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'utils.php';
$pdo = getPDO(); // PDO-Verbindung aus utils.php

//--------filer

// 2. Aktuelle Filter-Parameter
$filterType  = $_GET['filter_type']  ?? '';
$filterValue = $_GET['filter_value'] ?? '';

// 3. Mögliche Werte für das zweite Select je nach filter_type
$filterOptions = [];
if ($filterType) {
    switch ($filterType) {
        case 'place':
            $stmt = $pdo->query("SELECT id, name FROM places ORDER BY name");
            $filterOptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
        case 'skills':
            $stmt = $pdo->query("SELECT id, name FROM skills ORDER BY name");
            $filterOptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
        case 'objects':
            $stmt = $pdo->query("SELECT id, name FROM objects ORDER BY name");
            $filterOptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
        case 'decision':
            // decision hat nur eine ID, daher nur DISTINCT id
            $stmt = $pdo->query("
                SELECT DISTINCT decision_id 
                FROM decision_links 
                WHERE decision_id IS NOT NULL 
                ORDER BY decision_id
            ");
            // fetchColumn liefert eine einfache Liste
            $filterOptions = $stmt->fetchAll(PDO::FETCH_COLUMN);
            break;
    }
}

//----end filter

// 1) Link zum Bearbeiten laden
$editLink = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $pdo->prepare("
        SELECT *
        FROM links
        WHERE id = :id
    ");
    $stmt->execute([':id' => $editId]);
    $editLink = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 2) Bestehende link_objects für das Formular laden
$linkObjects = [];
if ($editLink) {
    $stmt = $pdo->prepare("
        SELECT 
            lo.id,
            lo.object_type,
            lo.object_id,
            lo.number1,
            lo.number2,
            COALESCE(s.name, o.name) AS name
        FROM link_objects lo
        LEFT JOIN skills  s ON lo.object_type = 'skills'  AND lo.object_id = s.id
        LEFT JOIN objects o ON lo.object_type = 'objects' AND lo.object_id = o.id
        WHERE lo.link_id = ?
    ");
    $stmt->execute([$editLink['id']]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $linkObjects[$row['object_type']][] = $row;
    }
}

// 3) Link aktualisieren inkl. link_objects
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_link'])) {
    // 3.1 Basis-Update der links-Tabelle

    $pars = [
      'id'        => intval($_GET['edit']),
      'place'     => intval($_POST['place']),
      'choice'    => $_POST['choice'] !== '' ? intval($_POST['choice']) : null,
      'last_date' => date('Y-m-d H:i:s')
    ];

    $pdo->prepare("
        UPDATE links
        SET place_id   = :place,
            choice_id  = :choice,
            last_date  = :last_date
        WHERE id       = :id
    ")->execute($pars);

    $linkId = $pars['id'];

    // 3.2 Entfernen markierter link_objects
    if (!empty($_POST['remove_lo_ids'])) {
        $ids = array_map('intval', $_POST['remove_lo_ids']);
        $in  = implode(',', $ids);
        $pdo->exec("DELETE FROM link_objects WHERE id IN ($in)");
    }

    // 3.3 Neue Skill-Ranges anlegen
    if (!empty($_POST['new_skill_id'])) {
        $insertSkill = $pdo->prepare("
            INSERT INTO link_objects
              (link_id, object_id, object_type, number1, number2)
            VALUES (?, ?, 'skills', ?, ?)
        ");
        foreach ($_POST['new_skill_id'] as $i => $skId) {
            $insertSkill->execute([
                $linkId,
                intval($skId),
                intval($_POST['new_skill_von'][$i]),
                intval($_POST['new_skill_bis'][$i])
            ]);
        }
    }

    // 3.4 Neue Objects anlegen
    if (!empty($_POST['new_object_id'])) {
        $insertObj = $pdo->prepare("
            INSERT INTO link_objects
              (link_id, object_id, object_type, number1, number2)
            VALUES (?, ?, 'objects', 0, 0)
        ");
        foreach ($_POST['new_object_id'] as $objId) {
            $insertObj->execute([
                $linkId,
                intval($objId)
            ]);
        }
    }

    //validateAndPopulateLink($linkId);

    // 3.5 Zurück zur Übersicht
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// 4) Neuer Link anlegen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_link'])) {
    $pars = [
        'place'  => $_POST['place']  !== '' ? intval($_POST['place'])  : null,
        'choice' => $_POST['choice'] !== '' ? intval($_POST['choice']) : null
    ];

    if($_POST['page']){
      $pars['page_id'] = $_POST['page'];
    }

    add_link($pars);
}

// 5) Link löschen
if (isset($_GET['delete'])) {
    $delId = intval($_GET['delete']);

    // dann den Link
    $pdo->prepare("DELETE FROM links WHERE id = :id")
        ->execute([':id' => $delId]);

    // und alle zugehörigen link_objects
    $pdo->prepare("DELETE FROM link_objects WHERE link_id = :id")
        ->execute([':id' => $delId]);

    // und alle zugehörigen decision_links
    $pdo->prepare("DELETE FROM decision_links WHERE link_id = :id")
        ->execute([':id' => $delId]);        
}


// 5.5) Link kopieren
if (isset($_GET['copy'])) {
    $copyId = intval($_GET['copy']);

    // 1. Original-Link abrufen
    $stmt = $pdo->prepare("SELECT * FROM links WHERE id = :id");
    $stmt->execute([':id' => $copyId]);
    $originalLink = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($originalLink) {
        // 2. Neuen Link einfügen (Kopie)
        $stmt = $pdo->prepare("
            INSERT INTO links (place_id, choice_id, page_id, first_date, last_date)
            VALUES (:place_id, :choice_id, :page_id, :first_date, :last_date)
        ");
        $stmt->execute([
            ':place_id'   => $originalLink['place_id'],
            ':choice_id'  => $originalLink['choice_id'],
            ':page_id'    => $originalLink['page_id'],
            ':first_date' => $originalLink['first_date'],
            ':last_date'  => $originalLink['last_date']
        ]);

        // 3. Neue Link-ID abrufen
        $newLinkId = $pdo->lastInsertId();

        // 4. Zugehörige link_objects abrufen
        $stmt = $pdo->prepare("SELECT * FROM link_objects WHERE link_id = :id");
        $stmt->execute([':id' => $copyId]);
        $objects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 5. Kopien der link_objects einfügen
        foreach ($objects as $obj) {
            $stmt = $pdo->prepare("
                INSERT INTO link_objects (link_id, object_id, object_type, number1, number2)
                VALUES (:link_id, :object_id, :object_type, :number1, :number2)
            ");
            $stmt->execute([
                ':link_id'     => $newLinkId,
                ':object_id'   => $obj['object_id'],
                ':object_type' => $obj['object_type'],
                ':number1'     => $obj['number1'],
                ':number2'     => $obj['number2']
            ]);
        }

        // 6. Zugehörige decision_links abrufen
        $stmt = $pdo->prepare("SELECT * FROM decision_links WHERE link_id = :id");
        $stmt->execute([':id' => $copyId]);
        $decisions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 7. Kopien der decision_links einfügen
        foreach ($decisions as $dec) {
            $stmt = $pdo->prepare("
                INSERT INTO decision_links (decision_id, link_id)
                VALUES (:decision_id, :link_id)
            ");
            $stmt->execute([
                ':decision_id' => $dec['decision_id'],
                ':link_id'     => $newLinkId
            ]);
        }        
    }
}


// 6) Dropdown-Daten laden
$skills  = $pdo->query("SELECT id, name FROM skills")->fetchAll();
$objects = $pdo->query("SELECT id, name FROM objects")->fetchAll();
$places  = $pdo->query("SELECT id, name FROM places")->fetchAll();
$pages  = $pdo->query("SELECT id, text, comments FROM pages")->fetchAll();
$choices = $pdo->query("SELECT id FROM choice")->fetchAll();

if(isset($_GET['place_id'])){
    $placeId = (int)$_GET['place_id'];

    $stmt = $pdo->prepare("
        SELECT DISTINCT c.id 
        FROM choice c
        JOIN decision d ON c.decision_id = d.id
        JOIN decision_links dl ON d.id = dl.decision_id
        JOIN links l ON dl.link_id = l.id
        WHERE l.place_id = :place_id
    ");
    $stmt->execute([':place_id' => $placeId]);
    $choices_for_new_link = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
else{
    $stmt = $pdo->prepare("
        SELECT DISTINCT c.id 
        FROM choice c
    ");
    $stmt->execute();
    $choices_for_new_link = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 7) Bestehende Links mit GROUP_CONCAT laden

$orderby = isset($_GET['orderby']) ? $_GET['orderby'] : "p.name, c.id";

$sql = "
SELECT
    l.id,
    l.page_id,
    l.place_id,                
    l.choice_id AS choice_id,    
    l.first_date,
    l.last_date,
    p.name AS place,
    pa.text AS page_text,
    c.id   AS choice,
    d.id as decision,
    d.name as decision_name,
  GROUP_CONCAT(DISTINCT
    CONCAT(
      '<span>skill:', s.name,
      ' [', lo.number1, '-', lo.number2, ' (ID:', lo.id ,')', ']</span>'
    )
    ORDER BY lo.number1, s.name
    SEPARATOR '<br>'
  ) AS skills,

  GROUP_CONCAT(DISTINCT
    CONCAT('<span>object:', o.name , '(ID:' , o.id , ')', '</span>')
    ORDER BY o.name
    SEPARATOR '<br>'
  ) AS objects

FROM links l
LEFT JOIN places        p  ON l.place_id  = p.id
LEFT JOIN choice        c  ON l.choice_id = c.id
LEFT JOIN decision_links dl ON l.id = dl.link_id
LEFT JOIN decision      d  ON dl.decision_id = d.id
LEFT JOIN pages         pa  ON l.page_id = pa.id
LEFT JOIN link_objects  lo ON lo.link_id  = l.id
LEFT JOIN skills        s  ON lo.object_type = 'skills'  AND lo.object_id = s.id
LEFT JOIN objects       o  ON lo.object_type = 'objects' AND lo.object_id = o.id
";


// Filter-Klausel hinzufügen
if ($filterType && $filterValue !== '') {
    switch ($filterType) {
        case 'place':
            $sql .= " WHERE l.place_id = :filter_value";
            $params['filter_value'] = $filterValue;
            break;
        case 'skills':
            $sql .= " WHERE s.id = :filter_value";
            $params['filter_value'] = $filterValue;
            break;
        case 'objects':
            $sql .= " WHERE o.id = :filter_value";
            $params['filter_value'] = $filterValue;
            break;
        case 'decision':
            $sql .= " WHERE d.id = :filter_value";
            $params['filter_value'] = $filterValue;
            break;
    }
}
else {
    $params = [];
}

$sql .=  " GROUP BY l.id
   ORDER BY ".$orderby.", l.id
  ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$links = $stmt->fetchAll(PDO::FETCH_ASSOC);

$groupColors = [];
foreach ($links as $link) {
    // key aus place_id und choice (nicht choice_id!)
    $key = $link['place_id'] . '-' . $link['choice'];
    
    if (!isset($groupColors[$key])) {
        // Hash erstellen, basierend auf dem key
        $hash = substr(md5($key), 0, 6);
        
        // Farbcode in RGB aufteilen
        $r = hexdec(substr($hash, 0, 2)); // Rot
        $g = hexdec(substr($hash, 2, 2)); // Grün
        $b = hexdec(substr($hash, 4, 2)); // Blau

        // Helligkeit überprüfen und gegebenenfalls anpassen
        // Eine einfache Methode ist, dafür zu sorgen, dass die RGB-Komponenten immer über 128 (mittel) liegen
        // (dunkle Farben sind problematisch bei Werten unter 128)
        $r = max($r, 128);
        $g = max($g, 128);
        $b = max($b, 128);

        // Sicherstellen, dass der Wert im erlaubten Bereich bleibt (0-255)
        $r = min($r, 255);
        $g = min($g, 255);
        $b = min($b, 255);

        // Farbe neu erstellen
        $groupColors[$key] = sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}


?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Links verwalten</title>
    <style>
        label { display: inline-block; width: 100px; margin-top: 8px; }
        select, input, button { margin-bottom: 12px; }
        table { border-collapse: collapse; margin-top: 20px; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 6px 12px; vertical-align: top; }
        hr { margin: 20px 0; }
    </style>
    <script>
        function showPage(pageId) {
            window.parent.loadFrame('pages.php?id=' + pageId);
        }
        // Templates für dynamic fields
        var skillOptions = `<?php foreach($skills as $s): ?>
          <option value="<?= $s['id'] ?>"><?= addslashes($s['name']) ?></option>
        <?php endforeach; ?>`;

        function addSkillRange() {
          var cont = document.createElement('div');
          cont.innerHTML = `
            <select name="new_skill_id[]">${skillOptions}</select>
            <input type="number" name="new_skill_von[]" placeholder="von" required>
            <input type="number" name="new_skill_bis[]" placeholder="bis" required>
            <button type="button" onclick="this.parentNode.remove()">✖</button>
          `;
          document.getElementById('new-skills').appendChild(cont);
        }

        var objectOptions = `<?php foreach($objects as $o): ?>
          <option value="<?= $o['id'] ?>"><?= addslashes($o['name']) ?></option>
        <?php endforeach; ?>`;

        function addObject() {
          var cont = document.createElement('div');
          cont.innerHTML = `
            <select name="new_object_id[]">${objectOptions}</select>
            <button type="button" onclick="this.parentNode.remove()">✖</button>
          `;
          document.getElementById('new-objects').appendChild(cont);
        }
    </script>
</head>
<body>

<h1><?= $editLink ? 'Link bearbeiten' : 'Neuen Link anlegen' ?></h1>
<form method="post">


  <label for="place">Ziel:</label>

  <select name="place" id="place" required onchange="location.href='<?= $_SERVER['PHP_SELF'] ?>?place_id=' + this.value;">
    <option value="">– wählen –</option>
    <?php foreach ($places as $row): ?>


      <option value="<?= $row['id'] ?>"
        <?php
          // Wenn Bearbeiten: aus DB, sonst aus GET
          $selected = '';
          if ($editLink && $editLink['place_id'] == $row['id']) {
              $selected = 'selected';
          } elseif (isset($_GET['place_id']) && $_GET['place_id'] == $row['id']) {
              $selected = 'selected';
          }
          echo $selected;
        ?>>
        <?= htmlspecialchars($row['name']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <br>

    <label for="choice">Choice:</label>
    <select name="choice" id="choice">
      <option value="">– keiner –</option>
      <?php foreach ($choices_for_new_link as $row): ?>
        <option value="<?= $row['id'] ?>"
          <?= ($editLink && $editLink['choice_id'] == $row['id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars(getChoiceName($row['id'])) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <br>

    <label for="page">Page:</label>
    <select name="page" id="page">
      <option value="">– keiner –</option>
      <?php foreach ($pages as $row): ?>
        <option value="<?= $row['id'] ?>"
          <?= ($editLink && $editLink['page_id'] == $row['id']) ? 'selected' : '' ?>>
          <?=$row['id'];?>: <?= htmlspecialchars(substr($row['text'],0,200)) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <br>    

    <?php if ($editLink): ?>
      <hr>
      <h2>Link-Objekte</h2>

      <h3>Skill-Ranges</h3>
      <div id="existing-skills">
        <?php foreach ($linkObjects['skills'] ?? [] as $lo): ?>
          <div>
            <label>
              skill:<?= htmlspecialchars($lo['name']) ?>
              (<?= $lo['number1'] ?>-<?= $lo['number2'] ?>)
            </label>
            <label>
              <input type="checkbox"
                     name="remove_lo_ids[]"
                     value="<?= $lo['id'] ?>">
              Entfernen
            </label>
          </div>
        <?php endforeach; ?>
      </div>
      <div id="new-skills"></div>
      <button type="button" onclick="addSkillRange()">+ Skill-Range</button>

      <h3>Objects</h3>
      <div id="existing-objects">
        <?php foreach ($linkObjects['objects'] ?? [] as $lo): ?>
          <div>
            <label>object:<?= htmlspecialchars($lo['name']) ?></label>
            <label>
              <input type="checkbox"
                     name="remove_lo_ids[]"
                     value="<?= $lo['id'] ?>">
              Entfernen
            </label>
          </div>
        <?php endforeach; ?>
      </div>
      <div id="new-objects"></div>
      <button type="button" onclick="addObject()">+ Object</button>
    <?php endif; ?>

    <hr>
    <?php if ($editLink): ?>
      <button type="submit" name="update_link">Aktualisieren</button>
      <a href="<?= $_SERVER['PHP_SELF'] ?>" style="margin-left:12px;">Abbrechen</a>
    <?php else: ?>
      <button type="submit" name="add_link">Hinzufügen</button>
    <?php endif; ?>
</form>

<hr><hr>

<h1>Bestehende Links</h1>

<select onchange="window.location.href='?orderby=' + this.value;">
  <option value="p.name, c.id">placename, choice_id</option>
  <option value="l.last_date">last_date</option>
  <option value="l.first_date">first_date</option>
</select>

<form method="get" id="filterForm">
  <label>
    Filtertyp:
    <select name="filter_type" onchange="document.getElementById('filterForm').submit()">
      <option value="">– wählen –</option>
      <option value="place"    <?= $filterType==='place'   ? 'selected' : '' ?>>Place</option>
      <option value="decision" <?= $filterType==='decision'? 'selected' : '' ?>>Decision</option>
      <option value="skills"   <?= $filterType==='skills'  ? 'selected' : '' ?>>Skills</option>
      <option value="objects"  <?= $filterType==='objects' ? 'selected' : '' ?>>Objects</option>
    </select>
  </label>

  <?php if ($filterType): ?>
  <label>
    Wert:
    <select name="filter_value" onchange="document.getElementById('filterForm').submit()">
      <option value="">– alle <?= ucfirst($filterType) ?> –</option>
      <?php foreach ($filterOptions as $opt): ?>
        <?php if ($filterType === 'decision'): ?>
          <option value="<?= $opt ?>" <?= $filterValue == $opt ? 'selected' : '' ?>>
            <?= $opt ?>
          </option>
        <?php else: ?>
          <option value="<?= $opt['id'] ?>" <?= $filterValue == $opt['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($opt['name']) ?>
          </option>
        <?php endif; ?>
      <?php endforeach; ?>
    </select>
  </label>
  <?php endif; ?>
</form>

<table>
  <thead>
    <tr>
      <th></th>
      <th>ID</th>
      <th>Graph</th>
      <th>Place</th>
      <th>Choice</th>
      <th>Skills</th>
      <th>Objects</th>
      <th>Decision</th>
      <th>Aktion</th>
      <th>erstellt</th>
      <th>bearbeitet</th>
    </tr>
  </thead>

  <tbody>
    <?php if (count($links)): ?>
      <?php foreach ($links as $link): 
        $key = $link['place_id'] . '-' . $link['choice'];
        ?>
        <tr>
          <td style="
            width: 12px;
            background-color: <?= $groupColors[$key] ?>;
            padding: 0;
            border: none;
          "></td>          
          <td><?= $link['id'] ?></td>
          <td onclick="parent.loadFrame('decisions/graph.php?lid=<?= $link['id'] ?>')">show</td>
          <td><?= htmlspecialchars($link['place']) ?></td>
          <td><?= htmlspecialchars(getChoiceName($link['choice'])) ?></td>
          <td><?= $link['skills']  ?: '<i>–</i>' ?></td>
          <td><?= $link['objects'] ?: '<i>–</i>' ?></td>
          <td onclick="parent.loadFrame('decisions.php?<?= isset($link['decision']) && $link['decision'] ? 'd_id=' . $link['decision'] . '#new_choice' : 'l_id=' . $link['id']  ?>')">
            <?= $link['decision']  ?> - <?= $link['decision_name']  ?>
          </td>
          <td>
            <a href="?copy=<?= $link['id'] ?>">Kopieren</a> |
            <a href="?edit=<?= $link['id'] ?>">Bearbeiten</a> |
            <a href="?delete=<?= $link['id'] ?>"
               onclick="return confirm('Wirklich löschen?')">Löschen</a>
          </td>
          <td><?= $link['first_date'] ?></td>
          <td><?= $link['last_date'] ?></td>
        </tr>
        <tr>
          <td onclick="parent.loadFrame('pages.php?id=<?=$link['page_id'];?>')" colspan='9'><?= $link['page_id'] ?>: <?= $link['page_text'] ?></td>            
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr>
        <td colspan="6">Keine Links vorhanden.</td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>

</body>
</html>
