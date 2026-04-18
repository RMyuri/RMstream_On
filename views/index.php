<?php
require_once '../includes/config.php';

// Redirecionar usuários não logados para a página de login
requireLogin();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>RMStream - Início</title>
    <link rel="stylesheet" href="/RMStream/public/css/style.css">
    <link rel="stylesheet" href="/RMStream/public/css/nav.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav>
        <a href="/RMStream/views/index.php" class="logo">RMStream</a>
        <div class="nav-container">
            <div class="nav-item">
                <a href="/RMStream/views/index.php" class="nav-link active">Início</a>
            </div>
            <div class="nav-item nav-item-with-submenu">
                <a href="/RMStream/views/room.php" class="nav-link">Room</a>
                <div class="submenu">
                    <a href="#" class="submenu-item create-room">Criar Sala</a>
                    <a href="#" class="submenu-item join-room">Entrar em Sala</a>
                </div>
            </div>
            <div class="nav-item">
                <a href="/RMStream/views/chat/index.php" class="nav-link">Chat</a>
            </div>
            <div class="nav-item nav-item-with-submenu">
                <a href="/RMStream/views/profile/index.php" class="nav-link">Perfil</a>
                <div class="submenu">
                    <a href="/RMStream/views/profile/edit.php" class="submenu-item">Editar Perfil</a>
                    <a href="/RMStream/views/profile/settings.php" class="submenu-item">Configurações</a>
                    <a href="/RMStream/views/chat/find_friends.php" class="submenu-item">Adicionar Amigos</a>
                    <a href="/RMStream/views/notifications.php" class="submenu-item">Notificações</a>
                </div>
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
    
    <!-- Conteúdo principal -->
    <main>
        <section class="descricao">
            <h1>Bem-vindo ao RMStream</h1>
            <p>
                O RMStream é uma plataforma de streaming inspirada no YouTube, com tema escuro e detalhes em verde. 
                Navegue facilmente entre as principais páginas:
            </p>
            <div class="paginas">
                <div class="pagina">
                    <h2>Room</h2>
                    <p>Assista vídeos em um player moderno, com controles completos e sugestões de vídeos relacionados.</p>
                    <a class="btn" href="/RMStream/views/room.php">Ir para Room</a>
                </div>
                <div class="pagina">
                    <h2>Buscar</h2>
                    <p>Encontre vídeos do YouTube, canais e conteúdo relacionado com nossa busca avançada.</p>
                    <a class="btn" href="/RMStream/views/search.php">Ir para Busca</a>
                </div>
            </div>
        </section>
    </main>
    
    <script src="/RMStream/public/js/nav.js"></script>
    <script src="/RMStream/public/js/notifications.js"></script>
</body>
</html>