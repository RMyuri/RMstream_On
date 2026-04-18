<?php
require_once '../includes/config.php';

// Verificar se o usuário está logado
requireLogin();

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$hasResults = false;
$results = [];

// Processar pesquisa se houver query
if (!empty($query)) {
    try {
        $pdo = getDbConnection();
        
        // Buscar usuários
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.display_name, u.profile_image, u.bio, 
                   CASE 
                       WHEN u.id = :currentUserId THEN 'self'
                       ELSE (
                           SELECT f.status 
                           FROM friendships f 
                           WHERE ((f.user_id = :currentUserId AND f.friend_id = u.id) 
                                OR (f.user_id = u.id AND f.friend_id = :currentUserId))
                           LIMIT 1
                       )
                   END as friendship_status,
                   CASE 
                       WHEN EXISTS(SELECT 1 FROM friendships f 
                                  WHERE f.user_id = :currentUserId AND f.friend_id = u.id AND f.status = 'pending') 
                       THEN 'initiator' 
                       ELSE NULL 
                   END as initiator
            FROM users u
            WHERE (u.username LIKE :query OR u.display_name LIKE :query) AND u.id != :currentUserId
            LIMIT 20
        ");
        
        $searchParam = "%$query%";
        $stmt->bindParam(':query', $searchParam);
        $stmt->bindParam(':currentUserId', $_SESSION['user_id']);
        $stmt->execute();
        
        $userResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar salas (se existir a tabela)
        $roomResults = [];
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'rooms'");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("
                SELECT * FROM rooms
                WHERE name LIKE :query OR description LIKE :query
                LIMIT 10
            ");
            $stmt->bindParam(':query', $searchParam);
            $stmt->execute();
            $roomResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Combinar resultados
        $results = [
            'users' => $userResults,
            'rooms' => $roomResults
        ];
        
        $hasResults = !empty($userResults) || !empty($roomResults);
        
    } catch (PDOException $e) {
        $error = "Erro ao realizar pesquisa: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo !empty($query) ? "Busca: $query - " : "Pesquisar - "; ?><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/RMStream/public/css/style.css">
    <link rel="stylesheet" href="/RMStream/public/css/nav.css">
    <link rel="stylesheet" href="/RMStream/public/css/search.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Barra de navegação -->
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
            <button class="search-toggle active" id="searchToggle" title="Pesquisar">
                <i class="fas fa-search"></i>
            </button>
            
            <a href="/RMStream/views/profile/index.php">
                <img src="<?php echo isset($_SESSION['profile_image']) && $_SESSION['profile_image'] ? $_SESSION['profile_image'] : '/RMStream/public/images/default-avatar.png'; ?>" 
                     alt="Avatar" class="profile-avatar">
            </a>
            <a href="/RMStream/views/auth/logout.php" class="nav-link">Sair</a>
        </div>
    </nav>
    
    <!-- Container principal de pesquisa -->
    <div class="search-page-container">
        <!-- Seção de destaque com barra de pesquisa -->
        <div class="search-hero">
            <div class="search-container">
                <form action="/RMStream/views/search.php" method="GET" class="search-form">
                    <input type="text" name="q" placeholder="Buscar usuários, salas, conteúdo..." class="search-input" 
                           value="<?php echo htmlspecialchars($query); ?>" required>
                    <button type="submit" class="search-submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Conteúdo principal da pesquisa -->
        <div class="search-content">
            <?php if (!empty($query)): ?>
                <h1 class="search-title">Resultados para "<?php echo htmlspecialchars($query); ?>"</h1>
                
                <?php if (isset($error)): ?>
                    <div class="search-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <p><?php echo $error; ?></p>
                    </div>
                <?php elseif ($hasResults): ?>
                    <!-- Resultados de usuários -->
                    <?php if (!empty($results['users'])): ?>
                        <div class="search-results-section">
                            <h2 class="search-section-title">Usuários</h2>
                            <div class="search-results-grid">
                                <?php foreach ($results['users'] as $user): ?>
                                    <div class="search-result-card user-card">
                                        <div class="search-result-header">
                                            <img src="<?php echo $user['profile_image'] ?? '/RMStream/public/images/default-avatar.png'; ?>" 
                                                 alt="Avatar" class="search-result-avatar">
                                            <div class="search-result-info">
                                                <div class="search-result-name"><?php echo htmlspecialchars($user['display_name'] ?? $user['username']); ?></div>
                                                <div class="search-result-meta">@<?php echo htmlspecialchars($user['username']); ?></div>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($user['bio'])): ?>
                                            <div class="search-result-body">
                                                <div class="search-result-text"><?php echo htmlspecialchars(substr($user['bio'], 0, 100) . (strlen($user['bio']) > 100 ? '...' : '')); ?></div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="search-result-footer">
                                            <div class="search-result-actions">
                                                <?php if ($user['friendship_status'] === null): ?>
                                                    <!-- Não são amigos -->
                                                    <button class="search-result-btn primary add-friend-btn" data-user-id="<?php echo $user['id']; ?>">
                                                        <i class="fas fa-user-plus"></i> Adicionar Amigo
                                                    </button>
                                                <?php elseif ($user['friendship_status'] === 'pending' && $user['initiator'] === 'initiator'): ?>
                                                    <!-- Solicitação enviada -->
                                                    <button class="search-result-btn secondary pending-btn" disabled>
                                                        <i class="fas fa-clock"></i> Solicitação Enviada
                                                    </button>
                                                <?php elseif ($user['friendship_status'] === 'pending'): ?>
                                                    <!-- Solicitação recebida -->
                                                    <button class="search-result-btn primary accept-btn" data-user-id="<?php echo $user['id']; ?>">
                                                        <i class="fas fa-check"></i> Aceitar
                                                    </button>
                                                    <button class="search-result-btn secondary reject-btn" data-user-id="<?php echo $user['id']; ?>">
                                                        <i class="fas fa-times"></i> Recusar
                                                    </button>
                                                <?php elseif ($user['friendship_status'] === 'accepted'): ?>
                                                    <!-- Já são amigos -->
                                                    <a href="/RMStream/views/chat/conversation.php?user_id=<?php echo $user['id']; ?>" class="search-result-btn primary">
                                                        <i class="fas fa-comment"></i> Mensagem
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a href="/RMStream/views/profile/index.php?id=<?php echo $user['id']; ?>" class="search-result-btn secondary">
                                                    <i class="fas fa-user"></i> Ver Perfil
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Resultados de salas -->
                    <?php if (!empty($results['rooms'])): ?>
                        <div class="search-results-section">
                            <h2 class="search-section-title">Salas</h2>
                            <div class="search-results-grid">
                                <?php foreach ($results['rooms'] as $room): ?>
                                    <div class="search-result-card room-card">
                                        <div class="search-result-header">
                                            <div class="room-icon">
                                                <i class="fas fa-video"></i>
                                            </div>
                                            <div class="search-result-info">
                                                <div class="search-result-name"><?php echo htmlspecialchars($room['name']); ?></div>
                                                <div class="search-result-meta">
                                                    <i class="fas fa-users"></i> 
                                                    <?php echo isset($room['participants']) ? $room['participants'] : '0'; ?> participantes
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($room['description'])): ?>
                                            <div class="search-result-body">
                                                <div class="search-result-text">
                                                    <?php echo htmlspecialchars(substr($room['description'], 0, 100) . (strlen($room['description']) > 100 ? '...' : '')); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="search-result-footer">
                                            <div class="search-result-actions">
                                                <a href="/RMStream/views/room.php?id=<?php echo $room['id']; ?>" class="search-result-btn primary">
                                                    <i class="fas fa-sign-in-alt"></i> Entrar na Sala
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <!-- Nenhum resultado encontrado -->
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>Nenhum resultado encontrado</h3>
                        <p>Não encontramos nenhum resultado para "<?php echo htmlspecialchars($query); ?>".</p>
                        
                        <div class="search-tips">
                            <strong>Dicas de pesquisa:</strong>
                            <ul>
                                <li>Verifique se há erros de digitação</li>
                                <li>Use palavras-chave mais gerais</li>
                                <li>Tente buscar por nomes de usuário ou IDs específicos</li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- Estado inicial de pesquisa -->
                <div class="search-initial">
                    <div class="search-illustration">
                        <i class="fas fa-search"></i>
                    </div>
                    <h2>Busque no RMStream</h2>
                    <p>Digite uma consulta de pesquisa para encontrar usuários, salas ou conteúdo.</p>
                    
                    <div class="search-suggestions">
                        <h3>Sugestões de pesquisa</h3>
                        <div class="suggestion-tags">
                            <a href="?q=música" class="suggestion-tag">música</a>
                            <a href="?q=jogos" class="suggestion-tag">jogos</a>
                            <a href="?q=filmes" class="suggestion-tag">filmes</a>
                            <a href="?q=programação" class="suggestion-tag">programação</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="/RMStream/public/js/search.js"></script>
</body>
</html>
          
