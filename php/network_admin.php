<?php

require 'utils.php';
$pdo = getPDO();

// Filterung per GET-Parameter
$selected_parent = isset($_GET['parent_filter']) ? $_GET['parent_filter'] : '0';

// ----------- PLACES HANDLING -----------
if (isset($_POST['new_place'])) {
    $stmt = $pdo->prepare("INSERT INTO places (name, parent_id) VALUES (?, ?)");
    $stmt->execute([$_POST['place_name'], $_POST['place_parent']]);
}

if (isset($_POST['update_place'])) {
    $stmt = $pdo->prepare("UPDATE places SET name = ?, parent_id = ? WHERE id = ?");
    $stmt->execute([$_POST['place_name'], $_POST['place_parent'], $_POST['place_id']]);
}


if (isset($_GET['delete_place'])) {
    $stmt = $pdo->prepare("DELETE FROM places WHERE id = ?");
    $stmt->execute([$_GET['delete_place']]);
}

// ----------- WAYS HANDLING -----------
if (isset($_POST['new_way'])) {
    $start = $_POST['way_start'];
    $end = $_POST['way_end'];

    $stmt = $pdo->prepare("SELECT id, parent_id FROM places WHERE id IN (?, ?)");
    $stmt->execute([$start, $end]);
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($start == $end) {
        echo "<p style='color:red'>Start und Ziel dürfen nicht gleich sein!</p>";
    } elseif (count($locations) < 2 || $locations[0]['parent_id'] != $locations[1]['parent_id']) {
        echo "<p style='color:red'>Start und Ziel müssen denselben Parent haben!</p>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO ways (start, end, bidirectional) VALUES (?, ?, ?)");
        $stmt->execute([$start, $end, isset($_POST['way_bidirectional']) ? 1 : 0]);

        // nachdem $stmt->execute() für ways gelaufen ist…
        $wayId = isset($way_id) ? $way_id : $pdo->lastInsertId();

        // wenn Arrays für wo_object_type existieren…
        if (!empty($_POST['wo_object_type'])) {
            // Alte Einträge beim Update löschen (optional)
            if (isset($_POST['update_way'])) {
                $pdo->prepare("DELETE FROM way_objects WHERE way_id = ?")
                    ->execute([$wayId]);
            }

            foreach ($_POST['wo_object_type'] as $i => $type) {
            
                $objId = $_POST['wo_object_id'][$i];
                $n1    = $_POST['wo_number1'][$i] ?: 0;
                $n2    = $_POST['wo_number2'][$i] ?: 0;

                $stmt = $pdo->prepare("
                    INSERT INTO way_objects
                        (way_id, object_id, object_type, number1, number2)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$wayId, $objId, $type, $n1, $n2]);
            }
        }
    }
}

if (isset($_GET['delete_way'])) {
    $stmt = $pdo->prepare("DELETE FROM ways WHERE id = ?");
    $stmt->execute([$_GET['delete_way']]);
}

// ----------- DATA FETCHING -----------
$all_places = $pdo->query("SELECT id, name, parent_id FROM places ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Orte mit ausgewählter parent_id
$filtered_places = [];
if ($selected_parent !== '') {
    $stmt = $pdo->prepare("SELECT id, name FROM places WHERE parent_id = ? ORDER BY name");
    $stmt->execute([$selected_parent]);
    $filtered_places = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT id, name FROM places WHERE id = ? ORDER BY name");
    $stmt->execute([$selected_parent]);
    $filtered_places[] = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
}

// Verbindungen mit gleicher parent_id
$ways = [];
if ($selected_parent !== '') {
    $stmt = $pdo->prepare("
        SELECT w.id, w.bidirectional, p1.name AS start_name, p2.name AS end_name
        FROM ways w
        JOIN places p1 ON w.start = p1.id
        JOIN places p2 ON w.end = p2.id
        WHERE p1.parent_id = p2.parent_id AND p1.parent_id = ?
        ORDER BY p1.name, p2.name
    ");
    $stmt->execute([$selected_parent]);
    $ways = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ----------- WAY_OBJECTS HANDLING -----------
$wayObjectsByWay = [];
$stmt = $pdo->query("
    SELECT wo.*, 
           CASE wo.object_type
               WHEN 'objects'    THEN o.name
               WHEN 'characters' THEN c.name
               WHEN 'skills'     THEN s.name
           END AS object_name
    FROM way_objects wo
    LEFT JOIN objects o ON wo.object_type='objects'    AND wo.object_id=o.id
    LEFT JOIN characters c ON wo.object_type='character' AND wo.object_id=c.id
    LEFT JOIN skills s ON wo.object_type='skill'      AND wo.object_id=s.id
");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $wayObjectsByWay[$row['way_id']][] = $row;
}


// ----------- WAY_OBJECTS ADD HANDLING -----------
if (isset($_POST['add_way_object'])) {
    $wayId = $_POST['add_way_object'];
    $type  = $_POST['wo_object_type'];
    $objId = $_POST['wo_object_id'];
    $n1    = $_POST['wo_number1'] ?: 0;
    $n2    = $_POST['wo_number2'] ?: 0;

    $stmt = $pdo->prepare("
        INSERT INTO way_objects (way_id, object_id, object_type, number1, number2)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$wayId, $objId, $type, $n1, $n2]);
}


// ----------- WAY_OBJECTS DELETE HANDLING -----------
if (isset($_GET['delete_way_object'])) {
    $stmt = $pdo->prepare("DELETE FROM way_objects WHERE id = ?");
    $stmt->execute([$_GET['delete_way_object']]);
}


?>
<!DOCTYPE html>
<html>
<head>
    <title>Netzwerkverwaltung</title>
    <script>
    function validateWayForm() {
        const start = document.querySelector("[name='way_start']").value;
        const end = document.querySelector("[name='way_end']").value;
        if (start === end) {
            alert("Start und Ziel dürfen nicht identisch sein!");
            return false;
        }
        return true;
    }

    // Globale Listen für alle Objekte/Charaktere/Skills
    const allObjects = <?php echo json_encode($pdo->query("SELECT id,name FROM objects")->fetchAll()); ?>;
    const allChars   = <?php echo json_encode($pdo->query("SELECT id,name FROM characters")->fetchAll()); ?>;
    const allSkills  = <?php echo json_encode($pdo->query("SELECT id,name FROM skills")->fetchAll()); ?>;

    // Inline-Funktion für das Hinzufügen in der Tabelle
    function onTypeChangeInline(sel, wayId) {
        const type = sel.value;
        const selObj = document.getElementById('wo_object_id_inline_' + wayId);
        selObj.innerHTML = '';
        let list = [];
        if (type === 'objects') list = allObjects;
        if (type === 'characters') list = allChars;
        if (type === 'skills') list = allSkills;
        list.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.name;
            selObj.append(opt);
        });
        document.getElementById('wo_numbers_inline_' + wayId).style.display = type === 'skills' ? 'inline-block' : 'none';
    }
    </script>
</head>
<body>
    <span style='color:red'> - Manueller reload nach jeder way_objects-aktion nötig!</span>
    <h1>Orte verwalten</h1>
    <form method="post">
        <input type="hidden" name="new_place" value="1">
        <input type="text" name="place_name" placeholder="Name" required>
        <select name="place_parent">
            <option value="0">(kein Parent)</option>
            <?php foreach ($all_places as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Ort hinzufügen</button>
    </form>

    <table border="1">
        <tr>
            <th>Start</th><th>Ziel</th><th>Bidirektional</th><th>Zugeordnete Objekte</th><th>Aktion</th>
        </tr>
        <?php foreach ($ways as $way): ?>
            <tr>
                <td><?= htmlspecialchars($way['start_name']) ?></td>
                <td><?= htmlspecialchars($way['end_name']) ?></td>
                <td><?= $way['bidirectional'] ? 'Ja' : 'Nein' ?></td>
                <td>
                    <?php
                    if (!empty($wayObjectsByWay[$way['id']])) {
                        foreach ($wayObjectsByWay[$way['id']] as $wo) {
                            
                            if ($wo['object_type'] === 'skill') {
                                echo " ({$wo['number1']}–{$wo['number2']})";
                            }
                            // Löschen-Link für way_object
                            echo ' <a href="?delete_way_object=' . $wo['id'] . '&parent_filter=' . $selected_parent . '" onclick="return confirm(\'Wirklich löschen?\')">✕</a>';
                            echo "<br>";
                        }
                    } else {
                        echo "—";
                    }
                    ?>
                    <!-- Formular zum Hinzufügen eines way_object -->
                    <form method="post" style="margin-top:5px;">
                        <input type="hidden" name="add_way_object" value="<?= $way['id'] ?>">
                        <select name="wo_object_type" onchange="onTypeChangeInline(this, <?= $way['id'] ?>)" required>
                            <option value="">Typ wählen</option>
                            <option value="objects">Objects</option>
                            <option value="characters">Characters</option>
                            <option value="skills">Skills</option>
                        </select>
                        <select name="wo_object_id" id="wo_object_id_inline_<?= $way['id'] ?>" required>
                            <option value="">erst Typ wählen</option>
                        </select>
                        <span id="wo_numbers_inline_<?= $way['id'] ?>" style="display:none;">
                            <input type="number" name="wo_number1" placeholder="Num 1">
                            <input type="number" name="wo_number2" placeholder="Num 2">
                        </span>
                        <button type="submit">+ Hinzufügen</button>
                    </form>
                </td>
                <td>
                    <a href="?delete_way=<?= $way['id'] ?>&parent_filter=<?= $selected_parent ?>" onclick="return confirm('Löschen?')">Löschen</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>


    <h1>Wege verwalten</h1>
    <form method="get">
        <label>Parent auswählen:
            <select name="parent_filter" onchange="this.form.submit()">
                <option value="0">kein Parent</option>
                <?php foreach ($all_places as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $selected_parent == $p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>    
    </form>

    <?php if ($selected_parent !== ''): ?>
        <form method="post" onsubmit="return validateWayForm()">
            <input type="hidden" name="new_way" value="1">
            <select name="way_start" required>
                <?php foreach ($filtered_places as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
            →
            <select name="way_end" required>
                <?php foreach ($filtered_places as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <label><input type="checkbox" name="way_bidirectional"> Bidirektional</label>

            <h3>Objekte/Charaktere/Skills zuordnen</h3>
            <div id="way-objects-container"></div>
            <button type="button" onclick="addWayObject()">+ Objekt hinzufügen</button>

            <script>
            const allObjects = <?php echo json_encode($pdo->query("SELECT id,name FROM objects")->fetchAll()); ?>;
            const allChars   = <?php echo json_encode($pdo->query("SELECT id,name FROM characters")->fetchAll()); ?>;
            const allSkills  = <?php echo json_encode($pdo->query("SELECT id,name FROM skills")->fetchAll()); ?>;

            function addWayObject() {
                const idx = document.querySelectorAll('.wo-block').length;
                const container = document.getElementById('way-objects-container');
                const div = document.createElement('div');
                div.className = 'wo-block';
                div.innerHTML = `
                    <select name="wo_object_type[${idx}]" onchange="onTypeChange(${idx}, this.value)" required>
                        <option value="">Typ wählen</option>
                        <option value="objects">Objects</option>
                        <option value="characters">Characters</option>
                        <option value="skills">Skills</option>
                    </select>
                    <select name="wo_object_id[${idx}]" id="wo_object_id_${idx}" required>
                        <option value="">erst Typ wählen</option>
                    </select>
                    <span id="wo_numbers_${idx}" style="display:none;">
                        <input type="number" name="wo_number1[${idx}]" placeholder="Num 1">
                        <input type="number" name="wo_number2[${idx}]" placeholder="Num 2">
                    </span>
                    <button type="button" onclick="this.parentNode.remove()">✕</button>
                `;
                container.appendChild(div);
            }

            function onTypeChange(idx, type) {
                const sel = document.getElementById(`wo_object_id_${idx}`);
                sel.innerHTML = '';
                let list = [];
                if (type === 'objects') list = allObjects;
                if (type === 'characters') list = allChars;
                if (type === 'skills') list = allSkills;
                list.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.name;
                    sel.append(opt);
                });
                document.getElementById(`wo_numbers_${idx}`).style.display = type === 'skills' ? 'inline-block' : 'none';
            }
            </script> 

            <button type="submit">Weg hinzufügen</button>
        </form>

        <table border="1">
            <tr><th>Start</th><th>Ziel</th><th>Bidirektional</th><th>Aktion</th></tr>
            <?php foreach ($ways as $way): ?>
                <tr>
                    <td><?= htmlspecialchars($way['start_name']) ?></td>
                    <td><?= htmlspecialchars($way['end_name']) ?></td>
                    <td><?= $way['bidirectional'] ? 'Ja' : 'Nein' ?></td>
                    <td><a href="?delete_way=<?= $way['id'] ?>&parent_filter=<?= $selected_parent ?>" onclick="return confirm('Löschen?')">Löschen</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

</body>
</html>

