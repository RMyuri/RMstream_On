<?php

session_start();
$inRoom = !empty($_SESSION['room']);
$room   = $_SESSION['room'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Resultados | S-Stream</title>
  <link rel="stylesheet" href="css/topnav.css">
  <link rel="stylesheet" href="css/sidebar.css">
  <link rel="stylesheet" href="css/results.css">
</head>
<body>
  <div class="app-grid">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main">
      <div id="results-container" class="results-container">
        <p class="info">Buscando resultados…</p>
      </div>
    </main>
  </div>

  <script>
    // sua chave exposta só aqui no front-end
    const YT_API_KEY = 'AIzaSyBRVg9qK01Uf0iou5ts3bSyTi-FAO1bXNw';
  </script>
  <script src="js/topnav.js"></script>
  <script src="js/results.js"></script>
</body>
</html>