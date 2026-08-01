<!DOCTYPE html>
<html>
<head>
    <title>Gamebook Dashboard</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            font-family: sans-serif;
            display: flex;
            flex-direction: column;
        }

        .todo {
            padding: 10px;
            background-color: #f0f0f0;
            font-size: 14px;
        }

        .frame-container {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        .sidebar {
            width: 200px;
            background-color: #2c3e50;
            color: white;
            padding: 10px;
            box-sizing: border-box;
        }

        .sidebar button {
            display: block;
            width: 100%;
            margin-bottom: 10px;
            padding: 10px;
            background-color: #34495e;
            border: none;
            color: white;
            cursor: pointer;
            text-align: left;
        }

        .sidebar button:hover {
            background-color: #1abc9c;
        }

        .main-frame {
            flex: 1;
            overflow: auto;
        }

        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
    <script>
        function loadFrame(src) {
            document.getElementById("mainIframe").src = src;
        }
    </script>
</head>
<body>
    <div class="todo"  style='display:none'>
        <strong>todo:</strong>
        <pre>
            - choice löschen: auch link löschen (und eigentlich auch die page)
            - decision löschen: auch die choices löschen

            - steuerbefehl für seitenumbruch in page-texten? nope, automatik! nav/choices beriech immer eine halbe seite, dadurch ist max_lines klar definiert.

            - SPÄTER: decision_name könnte eine auflistung aller links sein, die zu dieser decision gehören
        </pre>
    </div>

    <div class="frame-container">
        <div class="sidebar">
            <button onclick="loadFrame('db_manager.php')">DB</button>
            <button onclick="loadFrame('decisions.php')">Decisions</button>
            <button onclick="loadFrame('entity_manager.php')">Entities</button>
            <button onclick="loadFrame('pages.php')">Pages</button>
            <button onclick="loadFrame('links.php')">Links</button>
            <button onclick="loadFrame('places.php')">Places</button>
            <button onclick="loadFrame('network_admin.php')">Ways</button>
            <button onclick="loadFrame('net.php')">Ways Network</button>
            <button onclick="loadFrame('generate.php')">Generate</button>
        </div>
        <div class="main-frame">
            <iframe id="mainIframe" src="decisions.php" title="main content"></iframe>
        </div>
    </div>
</body>
</html>
