<?php
require 'utils.php';
$pdo = getPDO();

// 1. Lösch-Anfrage verarbeiten
$deleteError   = '';
$deleteSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delId = (int)$_POST['delete_id'];

    // Prüfen, ob Links existieren
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM links WHERE page_id = :id");
    $stmt->execute(['id' => $delId]);
    $linkCount = $stmt->fetchColumn();

    if ($linkCount == 0) {
        $delStmt = $pdo->prepare("DELETE FROM pages WHERE id = :id");
        if ($delStmt->execute(['id' => $delId])) {
            $deleteSuccess = "Seite ID $delId wurde erfolgreich gelöscht.";
        } else {
            $deleteError = "Löschen fehlgeschlagen für Seite ID $delId.";
        }
    } else {
        $deleteError = "Seite ID $delId kann nicht gelöscht werden, da noch Links existieren.";
    }
}

// 2. Alle Seiten laden (mit Link-Anzahl)
$sql = "
    SELECT
        p.id,
        p.text,
        p.comments,
        COUNT(l.id) AS link_count
    FROM pages p
    LEFT JOIN links l ON p.id = l.page_id
    GROUP BY p.id
    ORDER BY p.id
";
$pagesList = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT id, page_id FROM links";
$stmt = $pdo->prepare($sql);
$stmt->execute();

// Ergebnis als assoziatives Array strukturieren
$link_ids_with_page_id = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $pageId = $row['page_id'];
    $linkId = $row['id'];

    // Initialisiere Array für diese page_id, falls noch nicht vorhanden
    if (!isset($result[$pageId])) {
        $result[$pageId] = [];
    }

    // Füge link_id zur Liste hinzu
    $link_ids_with_page_id [$pageId][] = $linkId;
}

// 3. Aktuelle Seite zum Bearbeiten prüfen
$pageId = isset($_GET['id']) && ctype_digit($_GET['id'])
    ? (int) $_GET['id']
    : null;

// 4. Wenn Seite gewählt: Links und Inhalte laden
$linkinfos     = [];
$currentText   = '';
$currentComments = '';
$error         = '';
if ($pageId) {
    // Links abrufen
    $stmt = $pdo->prepare("
        SELECT 
            l.*,
            p.name      AS place_name,
            c.id        AS choice_id
        FROM links l
        LEFT JOIN places p  ON l.place_id  = p.id
        LEFT JOIN choice c  ON l.choice_id = c.id
        WHERE l.page_id = :pageId
    ");
    $stmt->execute(['pageId' => $pageId]);
    $linkinfos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Speichern-Logik
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['text'])) {
        $newText     = $_POST['text'];
        $newComments = $_POST['comments'] ?? '';

        $upStmt = $pdo->prepare("
            UPDATE pages
            SET text     = :text,
                comments = :comments
            WHERE id = :id
        ");
        if ($upStmt->execute([
            'text'     => $newText,
            'comments' => $newComments,
            'id'       => $pageId
        ])) {
            header("Location: pages.php?id=$pageId&saved=1");
            exit;
        } else {
            $error = 'Speichern fehlgeschlagen.';
        }
    }

    // Aktuelle Inhalte laden
    $stmt = $pdo->prepare("SELECT text, comments FROM pages WHERE id = :id");
    $stmt->execute(['id' => $pageId]);
    $pageData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pageData) {
        $currentText     = $pageData['text'];
        $currentComments = $pageData['comments'];
    } else {
        die('Seite mit dieser ID existiert nicht.');
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Seitenverwaltung</title>
  <style>
    body { font-family: sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 30px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    textarea { width: 100%; }
    .message { background: #e0ffe0; padding: 10px; margin-bottom: 15px; }
    .error   { background: #ffe0e0; padding: 10px; margin-bottom: 15px; }
    .linkinfo { background:#f8f8f8; border:1px solid #ccc; padding:12px; margin-bottom:16px; }
    form.inline { display: inline; }
  </style>
</head>
<body>

  <?php if ($pageId): ?>

    <?php

    $link_ids = $link_ids_with_page_id [$pageId];

    $link_ids_as_text = htmlspecialchars(implode(',',array_values($link_ids)));

    ?>

    <h1>Seite bearbeiten (ID <?= $pageId ?>)</h1>

    <?php if (!empty($linkinfos)): ?>
      <h2>Verbundene Links zu dieser Seite:</h2>
      <?php foreach ($linkinfos as $linkinfo): ?>
        <div class="linkinfo">
          <strong>Verbundener Ort:</strong>
            <?= htmlspecialchars($linkinfo['place_name'] ?? '-') ?><br>
          <strong>Choice:</strong>
            <?= htmlspecialchars(getChoiceName($linkinfo['choice_id']) ?? '-') ?><br>
            <strong>Link-IDs:</strong>
            <?= $link_ids_as_text ?><br>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if (isset($_GET['saved'])): ?>
      <div class="message">Änderungen wurden gespeichert.</div>
    <?php elseif ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <label for="text">Seiteninhalt:</label><br>
      <textarea name="text" id="text" rows="10"><?= htmlspecialchars($currentText) ?></textarea>
      <br><br>

      <label for="comments">Kommentare:</label><br>
      <textarea name="comments" id="comments" rows="5"><?= htmlspecialchars($currentComments) ?></textarea>
      <br><br>

      <button type="submit">Speichern</button>
    </form>
  <?php else: ?>
    <p>Bitte wähle oben eine Seite zum Bearbeiten aus.</p>
  <?php endif; ?>

  <h1>Alle Seiten</h1>

  <?php if ($deleteSuccess): ?>
    <div class="message"><?= htmlspecialchars($deleteSuccess) ?></div>
  <?php elseif ($deleteError): ?>
    <div class="error"><?= htmlspecialchars($deleteError) ?></div>
  <?php endif; ?>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Text (gekürzt)</th>
        <th>Kommentare (gekürzt)</th>
        <th>Links</th>
        <th>Löschen</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($pagesList as $row): 
        // 100 Zeichen kürzen
        $shortText     = mb_strlen($row['text']) > 100
            ? mb_substr($row['text'], 0, 100) . '…'
            : $row['text'];
        $shortComments = mb_strlen($row['comments'] ?? '') > 100
            ? mb_substr($row['comments'], 0, 100) . '…'
            : $row['comments'];
      ?>
      <tr>
        <td><?= $row['id'] ?></td>
        <td><a href="?id=<?= $row['id'] ?>"><?= htmlspecialchars($shortText) ?></a></td>
        <td><?= htmlspecialchars($shortComments ?? '') ?></td>
        <td>
          <?php
            foreach ((array)($link_ids_with_page_id [$row['id']] ?? []) as $linkId) {
              ?>
              <a onclick="parent.loadFrame('links.php?edit=<?=$linkId;?>')">[<?=$linkId;?>]</a>
              <?php
            }
          ?>
        </td>
        <td>
          <?php if ($row['link_count'] == 0): ?>
            <form class="inline" method="post">
              <button 
                type="submit" 
                name="delete_id" 
                value="<?= $row['id'] ?>"
                onclick="return confirm('Seite <?= $row['id'] ?> wirklich löschen?');"
              >Löschen</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

</body>
</html>
