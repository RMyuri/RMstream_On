<?php
require_once '../includes/config.php';

// Redirecionar usuários não logados para a página de login
requireLogin();

// Verifica se um ID de vídeo foi fornecido na URL
$videoId = isset($_GET['v']) ? htmlspecialchars($_GET['v']) : '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>RMStream Room</title>
    <link rel="stylesheet" href="/RMStream/public/css/room.css">
    <link rel="stylesheet" href="/RMStream/public/css/nav.css">
    <link rel="stylesheet" href="/RMStream/public/css/notifications.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .search-toggle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #333;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            margin-right: 15px;
        }
        
        .search-toggle:hover {
            background-color: #1db954;
        }
        
        .search-container {
            position: absolute;
            top: 60px;
            right: 20px;
            background-color: #232323;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            width: 300px;
            display: none;
            z-index: 1000;
        }
        
        .search-container.active {
            display: block;
            animation: fadeInDown 0.3s;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .search-form {
            display: flex;
            gap: 10px;
        }
        
        .search-input {
            flex: 1;
            padding: 10px;
            border-radius: 4px;
            border: none;
            background-color: #333;
            color: white;
        }
        
        .search-submit {
            background-color: #1db954;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 10px 15px;
            cursor: pointer;
        }
        
        .search-submit:hover {
            background-color: #18a448;
        }
    </style>
</head>
<body>
    <nav>
        <a href="/RMStream/views/index.php" class="logo">RMStream</a>
        <div class="nav-container">
            <div class="nav-item">
                <a href="/RMStream/views/index.php" class="nav-link">Início</a>
            </div>
            <div class="nav-item nav-item-with-submenu">
                <a href="/RMStream/views/room.php" class="nav-link active">Room</a>
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
            <button class="search-toggle" id="searchToggle" title="Pesquisar">
                <i class="fas fa-search"></i>
            </button>
            
            <a href="/RMStream/views/profile/index.php">
                <img src="<?php echo isset($_SESSION['profile_image']) && $_SESSION['profile_image'] ? $_SESSION['profile_image'] : '/RMStream/public/images/default-avatar.png'; ?>" 
                     alt="Avatar" class="profile-avatar">
            </a>
            <a href="/RMStream/views/auth/logout.php" class="nav-link">Sair</a>
        </div>
    </nav>
    
    <!-- Container de pesquisa -->
    <div class="search-container" id="searchContainer">
        <form action="/RMStream/views/search.php" method="GET" class="search-form">
            <input type="text" name="q" placeholder="Buscar..." class="search-input" required>
            <button type="submit" class="search-submit">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
    
    <div class="room-container">
        <div class="video-section">
            <?php if ($videoId): ?>
                <!-- Se tiver um ID de vídeo, mostra o player -->
                <div class="video-player">
                    <iframe id="mainVideo" 
                            src="https://www.youtube.com/embed/<?php echo $videoId; ?>?autoplay=1" 
                            frameborder="0" allowfullscreen allow="autoplay"></iframe>
                </div>
            <?php else: ?>
                <!-- Se não tiver ID de vídeo, mostra a mensagem de instruções -->
                <div class="no-video-message">
                    <div class="message-content">
                        <h2>Nenhum vídeo selecionado</h2>
                        <p>Para assistir um vídeo, você pode:</p>
                        <ul>
                            <li>Pesquisar por vídeos no YouTube usando a barra de pesquisa acima</li>
                            <li>Selecionar um vídeo da página inicial</li>
                            <li>Ou colar um link do YouTube com o parâmetro <code>?v=ID_DO_VIDEO</code></li>
                        </ul>
                        <a href="/RMStream/views/search.php?q=música" class="search-example-btn">Procurar músicas populares</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Armazenando o ID do vídeo atual para referência
        window.CURRENT_VIDEO_ID = "<?php echo $videoId; ?>";
    </script>
    <script src="/RMStream/public/js/nav.js"></script>
    <!-- Scripts -->
    <script src="/RMStream/public/js/notifications.js"></script>
    <script>
        // Script para controlar a barra de pesquisa
        document.addEventListener('DOMContentLoaded', function() {
            const searchToggle = document.getElementById('searchToggle');
            const searchContainer = document.getElementById('searchContainer');
            const searchInput = document.querySelector('.search-input');
            
            // Abrir/fechar a barra de pesquisa
            if (searchToggle) {
                searchToggle.addEventListener('click', function() {
                    searchContainer.classList.toggle('active');
                    if (searchContainer.classList.contains('active')) {
                        searchInput.focus();
                    }
                });
            }
            
            // Fechar a barra de pesquisa ao clicar fora
            document.addEventListener('click', function(event) {
                if (!searchContainer.contains(event.target) && event.target !== searchToggle) {
                    searchContainer.classList.remove('active');
                }
            });
            
            // Fechar a barra de pesquisa ao pressionar ESC
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    searchContainer.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
