<?php
require 'utils.php';

$pdo = getPDO();

// 1. Choice laden, wenn zum Bearbeiten angefragt
$editChoice = null;
if (isset($_GET['edit_choice_id']) && ctype_digit($_GET['edit_choice_id'])) {
    $stmt = $pdo->prepare("
        SELECT c.id, c.decision_id, c.place_id, c.label, c.text
        FROM choice c
        JOIN decision d ON c.decision_id = d.id
        WHERE c.id = :id
    ");
    $stmt->execute(['id' => (int)$_GET['edit_choice_id']]);
    $editChoice = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 2. Daten für Formulare laden
$links = $pdo->query("
    SELECT
        l.id,
        p.name    AS place_name,
        ch.label  AS choice_name
    FROM links AS l
    LEFT JOIN places         AS p  ON l.place_id  = p.id
    LEFT JOIN choice         AS ch ON l.choice_id = ch.id
    LEFT JOIN decision_links AS dl ON l.id = dl.link_id
    WHERE dl.link_id IS NULL
")->fetchAll();

$decisions = $pdo->query("
    SELECT d.id, d.name, d.finished,
           GROUP_CONCAT(dl.link_id) AS link_ids
    FROM decision d
    LEFT JOIN decision_links dl ON d.id = dl.decision_id
    GROUP BY d.id
")->fetchAll();

$d_id = isset($_GET['selected_decision']) ? (int)$_GET['selected_decision'] : false;

if(!$d_id){
    $d_id = $_GET['d_id'] ?? null;
}


$selected_link_id = $_GET['l_id'] ?? ''; 

if ($d_id) {
    $stmt = $pdo->prepare("
        SELECT c.id, c.decision_id, c.label, c.text, c.place_id, l.id AS link_id
        FROM choice c
        JOIN decision d ON c.decision_id = d.id
        LEFT JOIN links l ON c.id = l.choice_id
        WHERE c.decision_id = :d_id
        ORDER BY c.id
    ");
    $stmt->execute(['d_id' => $d_id]);
} else {
    $stmt = $pdo->query("
        SELECT c.id, c.decision_id, c.label, c.text, c.place_id, l.id AS link_id
        FROM choice c
        JOIN decision d ON c.decision_id = d.id
        LEFT JOIN links l ON c.id = l.choice_id
        ORDER BY c.id
    ");
}
$choices = $stmt->fetchAll();

$places = $pdo->query("
    SELECT p.id, p.name
    FROM places p
    ORDER BY p.name
")->fetchAll();

// 3. POST-Handling: add_decision, add_choice, edit_choice
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 3.1. Decision anlegen
    if (isset($_POST['action']) && $_POST['action'] === 'add_decision') {
        $linkId = (int)$_POST['link_id'];

        // Decision einfügen (ggf. mit Feldern, hier leer)
        $stmt = $pdo->prepare('INSERT INTO decision (name) VALUES (:name)');
        $stmt->execute(['name' => $_POST['decision_name'] ?? '']);

        $d_id = $pdo->lastInsertId();

        // Verknüpfung mit Link
        $stmt = $pdo->prepare('INSERT INTO decision_links (decision_id, link_id) VALUES (:decision_id, :link_id)');
        $stmt->execute(['decision_id' => $d_id, 'link_id' => $linkId]);

        // Redirect mit decision_id als GET-Parameter
        header('Location: ' . $_SERVER['PHP_SELF'] . '?selected_decision=' . $d_id);
        exit;
    }

    // 3.2. Neue Choice anlegen
    if (isset($_POST['action']) && $_POST['action'] === 'add_choice') {
        $decisionId = (int)$_POST['decision_id'];
        $placeId     = (int)$_POST['place_id'] ?: null;
        $label       = trim($_POST['label']);
        $text        = trim($_POST['text']);

        $stmt = $pdo->prepare('
            INSERT INTO choice (decision_id, place_id, label, text)
            VALUES (:decision_id, :place_id, :label, :text)
        ');
        $stmt->execute([
            'decision_id' => $decisionId,
            'place_id'    => $placeId,
            'label'       => $label,
            'text'        => $text
        ]);

        $choice_id = $pdo->lastInsertId();

        $pars = [];

        $pars['skill'] = null;
        $pars['object'] = null;   
        $pars['place']  = $placeId;     
        $pars['choice'] = $choice_id;    
        $pars['start_place'] = null;   

        add_link($pars);

        header('Location: ' . $_SERVER['PHP_SELF'] . '?d_id=' . urlencode($decisionId));
        exit;
    }

    // 3.3. Bestehende Choice aktualisieren
    if (isset($_POST['action']) && $_POST['action'] === 'edit_choice') {
        $choiceId   = (int)$_POST['choice_id'];
        $decisionId = (int)$_POST['decision_id'];
        $placeId    = (int)$_POST['place_id'] ?: null;
        $label      = trim($_POST['label']);
        $text       = trim($_POST['text']);

        $stmt = $pdo->prepare("
            UPDATE choice
            SET decision_id = :decision_id,
                place_id    = :place_id,
                label       = :label,
                text        = :text
            WHERE id = :id
        ");
        $stmt->execute([
            'id'          => $choiceId,
            'decision_id' => $decisionId,
            'place_id'    => $placeId,
            'label'       => $label,
            'text'        => $text
        ]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

        // 3.4. Decision bearbeiten
    if (isset($_POST['action']) && $_POST['action'] === 'edit_decision') {
        $decisionId = (int)$_POST['decision_id'];
        $name = trim($_POST['decision_name']);

        $stmt = $pdo->prepare('UPDATE decision SET name = :name WHERE id = :id');
        $stmt->execute(['name' => $name, 'id' => $decisionId]);

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

}

// 4. GET-Handling: Choice/Decision löschen
if (isset($_GET['delete'])) {
    if ($_GET['delete'] === 'decision' && isset($_GET['id'])) {
        $pdo->prepare('DELETE FROM decision WHERE id = ?')
            ->execute([(int)$_GET['id']]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    if ($_GET['delete'] === 'choice' && isset($_GET['id'])) {
        $pdo->prepare('DELETE FROM choice WHERE id = ?')
            ->execute([(int)$_GET['id']]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// 4.5 GET-Handling: add_link
if (isset($_GET['add'])) {
    if ($_GET['add'] === 'link' && isset($_GET['choice_id'])&& isset($_GET['place_id'])) {

        $pars = [
            'place'  => $_GET['place_id'],
            'choice' => $_GET['choice_id']
        ];

        add_link($pars); 

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// 5. GET-Handling: Choice/Decision togglen
if (isset($_GET['toggle'])) {
    if ($_GET['toggle'] === 'decision' && isset($_GET['id'])) {
        $pdo->prepare('UPDATE decision set finished = 1 - finished WHERE id = ?')
            ->execute([(int)$_GET['id']]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

function getDecisionLinkIds($pdo, $decisionId) {
    $stmt = $pdo->prepare("
        SELECT link_id FROM decision_links WHERE decision_id = :did
    ");
    $stmt->execute(['did' => $decisionId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Decision &amp; Choice Manager</title>
    <style>
        body { font-family: sans-serif; margin: 2rem; }
        form { margin-bottom: 2rem; padding: 1rem; border: 1px solid #ccc; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 2rem; }
        th, td { border: 1px solid #ddd; padding: 0.5rem; }
        th { background: #f4f4f4; }
        a.delete { color: red; text-decoration: none; }
        a.edit   { color: blue; }
    </style>
</head>
<body>

<h2>Neue Decision anlegen</h2>

<form method="post">
    <input type="hidden" name="action" value="add_decision">
    <label for="decision_name">Name:</label>
    <input type="text" name="decision_name">
    <label for="link_id">Link auswählen:</label>
    <select name="link_id" id="link_id" required>
        <option value="">– bitte wählen –</option>
        <?php foreach ($links as $link): ?>
            <?php 
                $link_name = build_link_name($link['id']); 
                $selected = ($link['id'] == $selected_link_id) ? 'selected' : '';
            ?>
            <option value="<?= $link['id'] ?>" <?= $selected ?>>
                <?= htmlspecialchars("({$link['id']}) {$link_name}") ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Decision anlegen</button>
</form>

<h3>Bestehende Decisions</h3>
<?php
$editDecision = null;
if (isset($_GET['edit_decision_id']) && ctype_digit($_GET['edit_decision_id'])) {
    $stmt = $pdo->prepare("SELECT id, name FROM decision WHERE id = ?");
    $stmt->execute([$_GET['edit_decision_id']]);
    $editDecision = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Links TODO</th>
            <th>Finished</th>
            <th>Aktion</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($decisions as $d): ?>
        <tr>
            <td><?= $d['id'] ?></td>
            <td><?= $d['name'] ?></td>
            <td onclick="window.location.href='decisions.php?d_id=<?= $d['id'] ?>'">
                <?= "todo";//(" . $d['link_id'] . ")" . htmlspecialchars(build_link_name($d['link_id'])) ?>
            </td>          
            <td><?= $d['finished'] ?></td>
            <td>
                <a class="delete"
                   href="?delete=decision&id=<?= $d['id'] ?>"
                   onclick="return confirm('Decision löschen?')">
                   Löschen
                </a>
                <a class="toggle"
                   href="?toggle=decision&id=<?= $d['id'] ?>">
                   fertig/unfertig 
                </a>   
                <a class="edit"
                href="?edit_decision_id=<?= $d['id'] ?>">
                Bearbeiten
                </a>        
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php if ($editDecision): ?>
    <h3>Decision bearbeiten (ID <?= $editDecision['id'] ?>)</h3>
    <form method="post">
        <input type="hidden" name="action" value="edit_decision">
        <input type="hidden" name="decision_id" value="<?= $editDecision['id'] ?>">
        
        <label for="decision_name">Neuer Name:</label>
        <input type="text" name="decision_name" id="decision_name"
               value="<?= htmlspecialchars($editDecision['name']) ?>" required>
        <button type="submit">Speichern</button>
        <a href="<?= $_SERVER['PHP_SELF'] ?>">Abbrechen</a>
    </form>
<?php endif; ?>

<hr>

<?php if ($editChoice): ?>
    <!-- Bearbeiten-Formular -->
    <h2>Choice bearbeiten (ID <?= $editChoice['id'] ?>)</h2>
    <form method="post">
        <input type="hidden" name="action" value="edit_choice">
        <input type="hidden" name="choice_id" value="<?= $editChoice['id'] ?>">

        <label for="decision_id">Decision auswählen:</label><br>

        <select name="decision_id" id="decision_id" required>
            <?php foreach ($decisions as $d): ?>
                <option value="<?= $d['id'] ?>"
                    <?= $d['id'] == $d_id ? 'selected' : '' ?>>
                    <?= "(" . $d['id'] . ") ".$d['name'] ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label for="label">Label:</label>
        <input type="text" name="label" id="label"
               value="<?= htmlspecialchars($editChoice['label']) ?>" required><br><br>

        <label for="text">Text:</label><br>
        <textarea name="text" id="text" rows="4" cols="50" required><?= 
            htmlspecialchars($editChoice['text']) ?></textarea><br><br>

        <label for="place_id">Ziel-Place auswählen:</label><br>
        <select name="place_id" id="place_id">
            <option value="">– bitte wählen –</option>
            <?php foreach ($places as $place): ?>
                <option value="<?= $place['id'] ?>"
                    <?= $place['id'] == $editChoice['place_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($place['name']) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <button type="submit">Speichern</button>
        <a href="<?= $_SERVER['PHP_SELF'] ?>">Abbrechen</a>
    </form>

<?php else: ?>
    <!-- Neues Choice-Formular -->
    <h2 id='new_choice'>Neue Choice anlegen</h2>
    <form method="post">
        <input type="hidden" name="action" value="add_choice">

        <label for="decision_id">Decision auswählen:</label>
        
        <br>

        <select name="decision_id" id="decision_id" required onchange="location.href='?d_id=' + this.value;">
            <option value="">– bitte wählen –</option>
            <?php foreach ($decisions as $d): ?>
                <option value="<?= $d['id'] ?>" <?= ($d['id'] == $d_id) ? 'selected' : '' ?>>
                    <?= "(" . $d['id'] . ") ".$d['name'] ?>
                </option>
            <?php endforeach; ?>
        </select>

        <br><br>

        <label for="label">Label:</label>
        <input type="text" name="label" id="label" required><br><br>

        <label for="text">Text:</label><br>
        <textarea name="text" id="text" rows="4" cols="50" required></textarea><br><br>

        <label for="place_id">Ziel-Place auswählen:</label><br>
        <select name="place_id" id="place_id" required>
            <option value="">– bitte wählen –</option>
            <?php foreach ($places as $place): ?>
                <option value="<?= $place['id'] ?>">
                    <?= htmlspecialchars($place['name']) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <button type="submit">Choice anlegen</button>
    </form>
<?php endif; ?>

<h3>Bestehende Choices</h3>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Decision ID</th>
            <th>Label</th>
            <th>Text</th>
            <th>Ziel-Place</th>
            <th>Link</th>
            <th>Aktion</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($choices as $c): ?>
        <?php
            // Platz-Name ermitteln (Kürzere Variante)
            $placeName = $c['place_id']
                ? htmlspecialchars(
                    $places[array_search($c['place_id'], array_column($places, 'id'))]['name']
                  )
                : '–';

            $linkName = isset($c['link_id']) ? build_link_name($c['link_id']) : "";            
        ?>
        <tr>
            <td><?= $c['id'] ?></td>
            <td><?= $c['decision_id'] ?></td>
            <td><?= htmlspecialchars($c['label']) ?></td>
            <td><?= htmlspecialchars($c['text']) ?></td>
            <td><?= $placeName ?></td>
            <td>    
                <?php

                    if($linkName!=""){
                        ?><a onclick="parent.loadFrame('links.php?edit=<?=$c['link_id'];?>')"><?= "(".$c['link_id'].")".htmlspecialchars($linkName) ?></a><?php
                    }
                    else{ 
                        if($c['place_id'] != ""){                 
                        ?>
                            <a class="add-link"
                            href="?add=link&choice_id=<?= $c['id'] ?>&place_id=<?= $c['place_id'] ?>"
                            onclick="return confirm('Link erstellen?')">
                            erstellen
                        </a>
                        <?php   
                        }
                        else{
                            ?>- link erstellen, wenn place ausgewählt -<?php
                        }                  
                    }
                ?>
            </td>
            <td>
                <a class="edit"
                   href="?edit_choice_id=<?= $c['id'] ?>">
                   Bearbeiten
                </a>
                &nbsp;|&nbsp;
                <a class="delete"
                   href="?delete=choice&id=<?= $c['id'] ?>"
                   onclick="return confirm('Choice löschen?')">
                   Löschen
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
