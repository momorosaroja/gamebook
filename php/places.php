<?php
// places.php
require 'utils.php';
$pdo = getPDO();

// Alle Orte für Select laden
$places = $pdo
    ->query("SELECT id, name FROM places ORDER BY name")
    ->fetchAll(PDO::FETCH_ASSOC);

// Aktuell ausgewähltes Place
$selectedId = isset($_GET['place_id']) && ctype_digit($_GET['place_id'])
    ? (int) $_GET['place_id']
    : null;

$placeData = null;
if ($selectedId) {
    $stmt = $pdo->prepare("SELECT * FROM places WHERE id = ?");
    $stmt->execute([$selectedId]);
    $placeData = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --------------- FORM-VERARBEITUNG ---------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Hilfsfunktion: leere Strings → NULL
    $nullify = function(string $key) {
        return ($_POST[$key] ?? '') === '' ? null : $_POST[$key];
    };

    try {
        if ($action === 'create') {
            $stmt = $pdo->prepare("
                INSERT INTO places
                   (name, parent_id, text1, text2, text3, text4, text5)
                VALUES
                   (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['name'],
                $nullify('parent_id'),
                $nullify('text1'),
                $nullify('text2'),
                $nullify('text3'),
                $nullify('text4'),
                $nullify('text5'),
            ]);
            header('Location: places.php');
            exit;
        }

        if ($action === 'update' && ctype_digit($_POST['place_id'])) {
            $stmt = $pdo->prepare("
                UPDATE places SET
                  name      = ?,
                  parent_id = ?,
                  text1     = ?,
                  text2     = ?,
                  text3     = ?,
                  text4     = ?,
                  text5     = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['name'],
                $nullify('parent_id'),
                $nullify('text1'),
                $nullify('text2'),
                $nullify('text3'),
                $nullify('text4'),
                $nullify('text5'),
                (int) $_POST['place_id'],
            ]);
            header('Location: places.php?place_id=' . (int)$_POST['place_id']);
            exit;
        }

        if ($action === 'delete' && ctype_digit($_POST['place_id'])) {
            $stmt = $pdo->prepare("DELETE FROM places WHERE id = ?");
            $stmt->execute([(int) $_POST['place_id']]);
            header('Location: places.php');
            exit;
        }
    } catch (Exception $e) {
        $error = "Fehler: " . htmlspecialchars($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Places-Admin</title>
    <style>
      body { font-family: sans-serif; margin: 20px; }
      fieldset { margin-bottom: 1.5em; }
      label { display: block; margin-top: .5em; }
      input[type="text"], select, textarea {
        width: 100%; box-sizing: border-box; padding: .4em;
        margin-top: .2em;
      }
      textarea { height: 80px; }
      button { margin-top: .8em; padding: .6em 1.2em; }
      .error { color: red; margin-bottom: 1em; }
    </style>
</head>
<body>

<h1>Orte verwalten</h1>

<?php if (!empty($error)): ?>
  <div class="error"><?= $error ?></div>
<?php endif; ?>



<!-- 2) Existierenden Ort auswählen -->
<fieldset>
  <legend>Existierenden Ort bearbeiten / löschen</legend>
  <form method="get">
    <label>
      Ort auswählen
      <select name="place_id" onchange="this.form.submit()">
        <option value="">– bitte wählen –</option>
        <?php foreach ($places as $p): ?>
          <option value="<?= $p['id'] ?>"
            <?= $selectedId === (int)$p['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
  </form>

  <?php if ($placeData): ?>
    <hr>

    <!-- 2a) Update-Form -->
    <form method="post" style="margin-bottom:1em;">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="place_id" value="<?= $placeData['id'] ?>">

      <label>
        Name
        <input type="text" name="name"
               value="<?= htmlspecialchars($placeData['name']) ?>"
               required>
      </label>

      <label>
        Parent (optional)
        <select name="parent_id">
          <option value="0">– kein Parent –</option>
          <?php foreach ($places as $p): ?>
            <option value="<?= $p['id'] ?>"
              <?= $p['id'] == $placeData['parent_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($p['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <?php for ($i = 1; $i <= 5; $i++): ?>
        <label>
          Text<?= $i ?> (optional)
          <textarea name="text<?= $i ?>"><?= 
            htmlspecialchars($placeData["text{$i}"]) 
          ?></textarea>
        </label>
      <?php endfor; ?>

      <button type="submit">Änderungen speichern</button>
    </form>

    <!-- 2b) Löschen -->
    <form method="post" onsubmit="return confirm('Ort wirklich löschen?')">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="place_id" value="<?= $placeData['id'] ?>">
      <button type="submit" style="background:#e74c3c; color:#fff;">
        Ort löschen
      </button>
    </form>
  <?php endif; ?>
</fieldset>

<!-- 1) Neuen Ort anlegen -->
<fieldset>
  <legend>Neuen Ort anlegen</legend>
  <form method="post">
    <input type="hidden" name="action" value="create">

    <label>
      Name
      <input type="text" name="name" required>
    </label>

    <label>
      Parent (optional)
      <select name="parent_id">
        <option value="0">– kein Parent –</option>
        <?php foreach ($places as $p): ?>
          <option value="<?= $p['id'] ?>">
            <?= htmlspecialchars($p['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <?php for ($i = 1; $i <= 5; $i++): ?>
      <label>
        Text<?= $i ?> (optional)
        <textarea name="text<?= $i ?>"></textarea>
      </label>
    <?php endfor; ?>

    <button type="submit">Ort anlegen</button>
  </form>
</fieldset>

</body>
</html>
