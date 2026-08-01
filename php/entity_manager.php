<?php
require 'utils.php';
$pdo = getPDO(); // PDO-Verbindung aus utils.php

// Unterstützte Tabellen
$allowed_tables = ['skills', 'objects', 'characters'];

// Welche Tabelle bearbeiten?
$table = $_GET['table'] ?? 'skills';
if (!in_array($table, $allowed_tables)) {
    die("Ungültige Tabelle.");
}

// Löschen
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
}

// Bearbeiten
if (isset($_POST['update_id'])) {
    $stmt = $pdo->prepare("UPDATE $table SET name = ? WHERE id = ?");
    $stmt->execute([$_POST['name'], $_POST['update_id']]);
}

// Neu hinzufügen
if (isset($_POST['new_name'])) {
    $stmt = $pdo->prepare("INSERT INTO $table (name) VALUES (?)");
    $stmt->execute([$_POST['new_name']]);
}

// Alle Einträge laden
$entries = $pdo->query("SELECT id, name FROM $table ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verwalter: <?= htmlspecialchars($table) ?></title>
</head>
<body>
    <h1>Verwalte: <?= htmlspecialchars($table) ?></h1>

    <nav>
        <?php foreach ($allowed_tables as $t): ?>
            <a href="?table=<?= $t ?>"><?= ucfirst($t) ?></a> |
        <?php endforeach; ?>
    </nav>

    <h2>Neuen Eintrag hinzufügen</h2>
    <form method="post">
        <input type="text" name="new_name" placeholder="Name" required>
        <button type="submit">Speichern</button>
    </form>

    <h2>Liste der Einträge</h2>
    <table border="1" cellpadding="5">
        <tr><th>ID</th><th>Name</th><th>Aktionen</th></tr>
        <?php foreach ($entries as $entry): ?>
            <tr>
                <td><?= $entry['id'] ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="update_id" value="<?= $entry['id'] ?>">
                        <input type="text" name="name" value="<?= htmlspecialchars($entry['name']) ?>">
                        <button type="submit">Ändern</button>
                    </form>
                </td>
                <td>
                    <a href="?table=<?= $table ?>&delete=<?= $entry['id'] ?>" onclick="return confirm('Wirklich löschen?')">Löschen</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>

