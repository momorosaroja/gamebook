<?php require 'utils.php'; ?>
<!DOCTYPE html>
<html>
<head>
  <title>Entscheidungsbaum</title>
  <script src="https://d3js.org/d3.v7.min.js"></script>
  <script src="js/d3tree.js"></script>
  
  <style>
  /* Baum-Container füllt den Viewport, zentriert den Inhalt und zeigt Scrollbalken */
  #tree {
    width: 100%;
    height: 80vh;
    overflow: auto;
    display: flex;
    justify-content: center;
    align-items: center;
    background: #f9f9f9;
  }

  /* SVG darf nicht schrumpfen, sonst zentriert Flexbox sinnlos */
  #tree svg {
    flex-shrink: 0;
  }
  
  #tree svg {
  transform: scale(0.6);       /* Faktor <1 macht alles kleiner */
  transform-origin: 0 0;        /* Skalierung ab linkem oberen Eck */
  display: block;               /* Verhindert unnötige Leerzeilen */
}

#tree {
  overflow: auto;               /* Scrollbars, falls’s zu winzig wird */
}

#tree svg {
  overflow: visible;       /* Text darf über den Viewport hinausragen */
}

#tree {
  overflow: auto;          /* optional: behält Scrollbars bei */
}

</style>

  
</head>
<body>
  <h1>Entscheidungsbaum</h1>

  <form>
    <select id="linkSelector">
      <option value="">– Link wählen –</option>
      <?php
      $stmt = $pdo->query("SELECT DISTINCT links.id, CONCAT('Link #', links.id) AS label FROM links JOIN decision ON links.id = decision.link_id");
      foreach ($stmt as $row) {
        echo "<option value=\"{$row['id']}\">{$row['label']}</option>";
      }
      ?>
    </select>
  </form>

  <div id="tree"></div>

  <script>
  document.getElementById('linkSelector').addEventListener('change', function() {
    const linkId = this.value;
    document.getElementById("tree").innerHTML = ""; // clear
    if (!linkId) return;

    fetch("tree.php?link_id=" + linkId)
      .then(res => res.json())
      .then(data => renderTree(data));
  });
  </script>
</body>
</html>

