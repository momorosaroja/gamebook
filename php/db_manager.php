<?php
require_once 'config.php';

// 1) DB-Liste dynamisch aus dem MySQL-Server auslesen (nur DBs, die mit "gamebook_" anfangen)
$dbError = null;
try {
    $adminPdo = new PDO(
        "mysql:host=".DB_HOST.";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $adminPdo->query("SHOW DATABASES LIKE 'gamebook\\_%'");
    $dbList = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    if (!is_array($dbList)) {
        $dbList = [];
    }
} catch (Exception $e) {
    $dbList = [];
    $dbError = 'Fehler beim Auslesen der Datenbanken: ' . $e->getMessage();
}

// Pfad zu mysqldump und Verzeichnis für Dumps (verwende absoluten Pfad für Zuverlässigkeit)
$mysqldumpPath = '/usr/bin/mysqldump';
$dumpDir = __DIR__ . '/dumps';
if (!is_dir($dumpDir)) {
    @mkdir($dumpDir, 0755, true);
}

// 2) Aktuell in config.php gesetzte DB auslesen
$configFile    = 'config.php';
$configContent = file_get_contents($configFile);
$currentDb     = null;
if (preg_match("/define\('DB_NAME',\s*'(.+?)'\);/", $configContent, $m)) {
    $currentDb = $m[1];
}

$message = '';
if (!empty($dbError)) {
    $message = htmlspecialchars($dbError);
}

// 3) Formular-Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // A) Config-DB setzen
    if (isset($_POST['apply_db'])) {
        $newDb = $_POST['selected_db'];
        if (in_array($newDb, $dbList, true)) {
            $configContent = preg_replace(
                "/define\('DB_NAME',\s*'.+?'\);/",
                "define('DB_NAME', '$newDb');",
                $configContent
            );
            file_put_contents($configFile, $configContent);
            $currentDb = $newDb;
            $message   = "Aktive Datenbank gesetzt auf <strong>$newDb</strong>.";
        }
    }

    // B) Dump, Kopieren oder Umbenennen
    if (isset($_POST['db_action'])) {
        $action  = $_POST['action'];
        $db      = $_POST['db'];
        $newName = trim($_POST['new_db'] ?? '');

        try {
            // ADMIN-Verbindung (ohne default DB)
            $adminPdo = new PDO(
                "mysql:host=".DB_HOST.";charset=utf8mb4",
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            switch ($action) {
                case 'dump':
                    // mysqldump-Aufruf
                    $passPart = DB_PASS !== '' ? '-p'.escapeshellarg(DB_PASS) : '';
                    $cmd = sprintf(
                        "mysqldump -h %s -u %s %s %s > %s.sql",
                        escapeshellarg(DB_HOST),
                        escapeshellarg(DB_USER),
                        $passPart,
                        escapeshellarg($db),
                        escapeshellarg($db)
                    );
                    // Capture stderr as well for better diagnostics
                    $outFile = $dumpDir . '/' . $db . '.sql';
                    // Run mysqldump in a minimal/clean environment to avoid picking up XAMPP libs
                    $cmdFull = sprintf(
                        'env -i PATH=/usr/bin:/bin LD_LIBRARY_PATH= LD_PRELOAD= %s --protocol=TCP -h %s -u %s %s %s > %s 2>&1',
                        escapeshellarg($mysqldumpPath),
                        escapeshellarg(DB_HOST),
                        escapeshellarg(DB_USER),
                        $passPart,
                        escapeshellarg($db),
                        escapeshellarg($outFile)
                    );
                    // Log command for debugging (no sensitive data expected here)
                    @file_put_contents($dumpDir . '/last_mysqldump_cmd.txt', $cmdFull);
                    exec($cmdFull, $_o, $ret);
                    if ($ret === 0) {
                        $message = "Dump von <strong>$db</strong> erfolgreich als <strong>" . basename($outFile) . "</strong> im dumps-Verzeichnis.";
                    } else {
                        $outText = htmlspecialchars(implode("\n", $_o));
                        $message = "Fehler beim Dump von $db (Code $ret). Ausgabe: <pre>$outText</pre>";
                    }
                    break;

                case 'delete':
                    // 1) Erzeuge automatischen Dump mit Zeitstempel
                    $dumpFile = $db . '_' . date('Ymd_His') . '.sql';
                    $passPart = DB_PASS !== '' ? '-p'.escapeshellarg(DB_PASS) : '';
                    $cmd = sprintf(
                        "mysqldump -h %s -u %s %s %s > %s",
                        escapeshellarg(DB_HOST),
                        escapeshellarg(DB_USER),
                        $passPart,
                        escapeshellarg($db),
                        escapeshellarg($dumpFile)
                    );
                    // Capture stderr to get the mysqldump error message
                    $outFile = $dumpDir . '/' . $dumpFile;
                    // Run mysqldump in a minimal/clean environment to avoid picking up XAMPP libs
                    $cmdFull = sprintf(
                        'env -i PATH=/usr/bin:/bin LD_LIBRARY_PATH= LD_PRELOAD= %s --protocol=TCP -h %s -u %s %s %s > %s 2>&1',
                        escapeshellarg($mysqldumpPath),
                        escapeshellarg(DB_HOST),
                        escapeshellarg(DB_USER),
                        $passPart,
                        escapeshellarg($db),
                        escapeshellarg($outFile)
                    );
                    // Log command for debugging
                    @file_put_contents($dumpDir . '/last_mysqldump_cmd.txt', $cmdFull);
                    exec($cmdFull, $_o, $ret);
                    if ($ret !== 0) {
                        $outText = implode("\n", $_o);
                        throw new Exception("Dump vor Löschung fehlgeschlagen (Code $ret). Ausgabe: " . $outText);
                    }

                    // 2) DB löschen
                    $adminPdo->exec("DROP DATABASE `$db`");
                    $message = "Datenbank <strong>$db</strong> wurde gelöscht. Vorheriger Dump: <strong>$dumpFile</strong>.";
                    break;

                case 'copy':
                    if ($newName === '') {
                        throw new Exception('Neuer DB-Name fehlt.');
                    }
                    if (!preg_match('/^gamebook_/', $newName)) {
                        throw new Exception("Neuer DB-Name muss mit 'gamebook_' beginnen.");
                    }
                    // 1) Neue DB anlegen
                    $adminPdo->exec(
                        "CREATE DATABASE `$newName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
                    );
                    // 2) Alle Tabellen kopieren
                    $tables = $adminPdo
                        ->query("SELECT table_name FROM information_schema.tables WHERE table_schema='$db'")
                        ->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($tables as $table) {
                        $adminPdo->exec("CREATE TABLE `$newName`.`$table` LIKE `$db`.`$table`");
                        $adminPdo->exec("INSERT INTO `$newName`.`$table` SELECT * FROM `$db`.`$table`");
                    }
                    $message = "Datenbank <strong>$db</strong> kopiert nach <strong>$newName</strong>.";
                    break;

                case 'rename':
                    if ($newName === '') {
                        throw new Exception('Neuer DB-Name fehlt.');
                    }
                    if (!preg_match('/^gamebook_/', $newName)) {
                        throw new Exception("Neuer DB-Name muss mit 'gamebook_' beginnen.");
                    }
                    // 1) Neue DB anlegen
                    $adminPdo->exec(
                        "CREATE DATABASE `$newName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
                    );
                    // 2) Alle Tabellen per RENAME TABLE verschieben
                    $tables = $adminPdo
                        ->query("SELECT table_name FROM information_schema.tables WHERE table_schema='$db'")
                        ->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($tables as $table) {
                        $adminPdo->exec(
                            "RENAME TABLE `$db`.`$table` TO `$newName`.`$table`"
                        );
                    }
                    // 3) Alte DB löschen
                    $adminPdo->exec("DROP DATABASE `$db`");

                    // Config updaten, falls aktuell
                    if ($db === $currentDb) {
                        $configContent = preg_replace(
                            "/define\('DB_NAME',\s*'.+?'\);/",
                            "define('DB_NAME', '$newName');",
                            $configContent
                        );
                        file_put_contents($configFile, $configContent);
                        $currentDb = $newName;
                    }

                    $message = "Datenbank <strong>$db</strong> umbenannt in <strong>$newName</strong>.";
                    break;

                default:
                    throw new Exception('Unbekannte Aktion.');
            }
        } catch (Exception $e) {
            $message = "Fehler: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>DB-Manager</title>
    <style>
        body { font-family: sans-serif; margin: 30px; }
        form { margin-bottom: 20px; }
        select, input, button { margin: 4px 0; padding: 6px; }
        .message { color: green; margin-top: 10px; }
    </style>
</head>
<body>

<h2>1) Aktive Datenbank in config.php setzen</h2>
<form method="post">
    <label>Auswahl:</label>
    <select name="selected_db">
        <?php foreach ($dbList as $db): ?>
            <option value="<?= htmlspecialchars($db) ?>"
                <?= $db === $currentDb ? 'selected' : '' ?>>
                <?= htmlspecialchars($db) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button name="apply_db">Übernehmen</button>
</form>

<hr>

<h2>2) Datenbank verwalten</h2>
<form method="post">
    <label>DB:</label>
    <select name="db">
        <?php foreach ($dbList as $db): ?>
            <option value="<?= htmlspecialchars($db) ?>"><?= htmlspecialchars($db) ?></option>
        <?php endforeach; ?>
    </select>

    <label>Aktion:</label>
    <select name="action" id="actionSelect">
        <option value="dump">Dump</option>
        <option value="copy">Kopieren</option>
        <option value="rename">Umbenennen</option>
        <option value="delete">Löschen</option>
    </select>

    <label>Neuer Name:</label>
    <input type="text" id="newDbInput" name="new_db" pattern="^gamebook_.*" title="Der Name muss mit 'gamebook_' beginnen" placeholder="Nur für Kopie/Umbenennung">

    <button name="db_action">Ausführen</button>
</form>

<script>
document.addEventListener('DOMContentLoaded', function(){
    var form = document.querySelector('form[method="post"]');
    var actionSelect = document.getElementById('actionSelect');
    form.addEventListener('submit', function(e){
        var action = actionSelect.value;
        if (action === 'delete') {
            var db = form.querySelector('select[name="db"]').value;
            if (!confirm('Datenbank "' + db + '" wirklich löschen? Es wird vorher automatisch ein Dump erstellt.')) {
                e.preventDefault();
                return false;
            }
        }
        // Wenn neue DB gefüllt ist, prüfe clientseitig das Prefix
        var newDb = document.getElementById('newDbInput').value.trim();
        if (newDb !== '' && !/^gamebook_/.test(newDb)) {
            alert("Der neue Datenbankname muss mit 'gamebook_' beginnen.");
            e.preventDefault();
            return false;
        }
    });
});
</script>

<?php if ($message): ?>
    <div class="message"><?= $message ?></div>
<?php endif; ?>

<p>Aktuell in config.php: <strong><?= htmlspecialchars($currentDb) ?></strong></p>

</body>
</html>
