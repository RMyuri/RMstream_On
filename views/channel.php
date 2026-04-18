<?php
require_once '../includes/config.php';

// Redirecionar usuários não logados para a página de login
requireLogin();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Perfil do Canal - RMStream</title>
    <link rel="stylesheet" href="/RMStream/public/css/channel.css">
    <link rel="stylesheet" href="/RMStream/public/css/nav.css">
</head>
<body>
    <nav>
        <a href="/RMStream/views/index.php" class="logo">RMStream</a>
        <div class="nav-container">
            <div class="nav-item">
                <a href="/RMStream/views/index.php" class="nav-link">Início</a>
            </div>
            <div class="nav-item nav-item-with-submenu">
                <a href="/RMStream/views/room.php" class="nav-link">Room</a>
                <div class="submenu">
                    <a href="#" class="submenu-item create-room">Criar Sala</a>
                    <a href="#" class="submenu-item join-room">Entrar em Sala</a>
                </div>
            </div>
            <div class="nav-item">
                <a href="/RMStream/views/chart.php" class="nav-link">Chart</a>
            </div>
            <div class="nav-item">
                <a href="/RMStream/views/chat/index.php" class="nav-link">Chat</a>
            </div>
            <div class="nav-item">
                <a href="/RMStream/views/profile/index.php" class="nav-link">Meu Perfil</a>
            </div>
        </div>
        <div class="nav-profile">
            <a href="/RMStream/views/profile/index.php">
                <img src="<?php echo isset($_SESSION['profile_image']) && $_SESSION['profile_image'] ? $_SESSION['profile_image'] : '/RMStream/public/images/default-avatar.png'; ?>" 
                     alt="Avatar" class="profile-avatar">
            </a>
            <a href="/RMStream/views/auth/logout.php" class="nav-link">Sair</a>
        </div>
    </nav>
    
    <main>
        <div id="apiStatus" class="api-status"></div>
        <section id="channelProfile"></section>
        <section>
            <h2>Vídeos do Canal</h2>
            <div class="channel-videos" id="channelVideos"></div>
        </section>
    </main>
    
    <script>
        window.YOUTUBE_API_KEY = "AIzaSyBRVg9qK01Uf0iou5ts3bSyTi-FAO1bXNw";
    </script>
    <script src="/RMStream/public/js/channel.js"></script>
    <script src="/RMStream/public/js/nav.js"></script>
</body>
</html>
