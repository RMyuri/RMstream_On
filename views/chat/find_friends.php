<?php
require_once '../../includes/config.php';

// Verificar se o usuário está logado
requireLogin();

$userId = $_SESSION['user_id'];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $pdo = getDbConnection();
    
    // Buscar solicitações pendentes recebidas
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.display_name, u.profile_image, f.created_at
        FROM friendships f
        JOIN users u ON f.user_id = u.id
        WHERE f.friend_id = :userId AND f.status = 'pending'
        ORDER BY f.created_at DESC
    ");
    $stmt->bindParam(':userId', $userId);
    $stmt->execute();
    $pendingRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar usuários para adicionar (que não são amigos e não têm solicitações pendentes)
    $query = "
        SELECT u.id, u.username, u.display_name, u.profile_image
        FROM users u
        WHERE u.id != :userId
        AND NOT EXISTS (
            SELECT 1 FROM friendships f
            WHERE ((f.user_id = :userId AND f.friend_id = u.id) OR (f.user_id = u.id AND f.friend_id = :userId))
            AND f.status IN ('accepted', 'pending', 'blocked')
        )
    ";
    
    // Adicionar filtro de busca se fornecido
    if (!empty($search)) {
        $query .= " AND (u.username LIKE :search OR u.display_name LIKE :search)";
    }
    
    $query .= " ORDER BY u.display_name, u.username LIMIT 50";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':userId', $userId);
    
    if (!empty($search)) {
        $searchParam = "%{$search}%";
        $stmt->bindParam(':search', $searchParam);
    }
    
    $stmt->execute();
    $usersToAdd = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar amigos atuais
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.display_name, u.profile_image, f.status
        FROM friendships f
        JOIN users u ON (f.user_id = u.id AND f.user_id != :userId) OR (f.friend_id = u.id AND f.friend_id != :userId)
        WHERE (f.user_id = :userId OR f.friend_id = :userId) AND f.status = 'accepted'
        ORDER BY u.display_name, u.username
    ");
    $stmt->bindParam(':userId', $userId);
    $stmt->execute();
    $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar solicitações enviadas
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.display_name, u.profile_image, f.created_at
        FROM friendships f
        JOIN users u ON f.friend_id = u.id
        WHERE f.user_id = :userId AND f.status = 'pending'
        ORDER BY f.created_at DESC
    ");
    $stmt->bindParam(':userId', $userId);
    $stmt->execute();
    $sentRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Erro ao carregar dados: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encontrar Amigos - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/RMStream/public/css/style.css">
    <link rel="stylesheet" href="/RMStream/public/css/nav.css">
    <link rel="stylesheet" href="/RMStream/public/css/friends.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <a href="/RMStream/views/chat/index.php" class="nav-link active">Chat</a>
            </div>
            <div class="nav-item">
                <a href="/RMStream/views/profile/index.php" class="nav-link">Perfil</a>
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
    
    <div class="friends-container">
        <div class="friends-header">
            <h1>Encontrar Amigos</h1>
            <div class="search-box">
                <form action="" method="GET">
                    <input type="text" name="search" placeholder="Buscar usuários..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
        </div>
        
        <div class="friends-tabs">
            <button class="friends-tab active" data-tab="requests">Solicitações</button>
            <button class="friends-tab" data-tab="add">Adicionar Amigos</button>
            <button class="friends-tab" data-tab="friends">Meus Amigos</button>
            <button class="friends-tab" data-tab="pending">Enviadas</button>
        </div>
        
        <!-- Solicitações de amizade pendentes -->
        <div class="friends-section" id="requestsSection">
            <h2>Solicitações Pendentes</h2>
            
            <?php if (empty($pendingRequests)): ?>
                <div class="empty-list">
                    <i class="fas fa-user-plus"></i>
                    <p>Você não tem solicitações de amizade pendentes.</p>
                </div>
            <?php else: ?>
                <div class="friend-requests-list">
                    <?php foreach ($pendingRequests as $request): ?>
                        <div class="friend-request-card">
                            <div class="friend-request-user">
                                <img src="<?php echo $request['profile_image'] ?? '/RMStream/public/images/default-avatar.png'; ?>" alt="Avatar" class="friend-avatar">
                                <div class="friend-info">
                                    <div class="friend-name"><?php echo htmlspecialchars($request['display_name'] ?? $request['username']); ?></div>
                                    <div class="friend-username">@<?php echo htmlspecialchars($request['username']); ?></div>
                                    <div class="friend-since">Solicitação enviada <?php echo timeAgo($request['created_at']); ?></div>
                                </div>
                            </div>
                            <div class="friend-actions">
                                <button class="friend-btn accept-btn" data-action="accept_request" data-user-id="<?php echo $request['id']; ?>">
                                    <i class="fas fa-check"></i> Aceitar
                                </button>
                                <button class="friend-btn reject-btn" data-action="reject_request" data-user-id="<?php echo $request['id']; ?>">
                                    <i class="fas fa-times"></i> Recusar
                                </button>
                                <a href="/RMStream/views/profile/index.php?id=<?php echo $request['id']; ?>" class="friend-btn view-profile-btn">
                                    <i class="fas fa-user"></i> Perfil
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Adicionar novos amigos -->
        <div class="friends-section" id="addSection" style="display: none;">
            <h2>Pessoas que você pode conhecer</h2>
            
            <?php if (empty($usersToAdd)): ?>
                <div class="empty-list">
                    <i class="fas fa-search"></i>
                    <p>Nenhum usuário encontrado. Use a busca para encontrar pessoas.</p>
                </div>
            <?php else: ?>
                <div class="users-grid">
                    <?php foreach ($usersToAdd as $user): ?>
                        <div class="user-card">
                            <div class="user-header">
                                <img src="<?php echo $user['profile_image'] ?? '/RMStream/public/images/default-avatar.png'; ?>" alt="Avatar" class="user-avatar">
                            </div>
                            <div class="user-body">
                                <div class="user-name"><?php echo htmlspecialchars($user['display_name'] ?? $user['username']); ?></div>
                                <div class="user-username">@<?php echo htmlspecialchars($user['username']); ?></div>
                                <div class="user-actions">
                                    <button class="user-btn add-friend-btn" data-action="send_request" data-user-id="<?php echo $user['id']; ?>">
                                        <i class="fas fa-user-plus"></i> Adicionar
                                    </button>
                                    <a href="/RMStream/views/profile/index.php?id=<?php echo $user['id']; ?>" class="user-btn view-profile-btn">
                                        <i class="fas fa-user"></i> Perfil
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Lista de amigos -->
        <div class="friends-section" id="friendsSection" style="display: none;">
            <h2>Meus Amigos</h2>
            
            <?php if (empty($friends)): ?>
                <div class="empty-list">
                    <i class="fas fa-user-friends"></i>
                    <p>Você ainda não tem amigos. Comece adicionando pessoas!</p>
                </div>
            <?php else: ?>
                <div class="friends-list">
                    <?php foreach ($friends as $friend): ?>
                        <div class="friend-card">
                            <div class="friend-card-user">
                                <img src="<?php echo $friend['profile_image'] ?? '/RMStream/public/images/default-avatar.png'; ?>" alt="Avatar" class="friend-avatar">
                                <div class="friend-info">
                                    <div class="friend-name"><?php echo htmlspecialchars($friend['display_name'] ?? $friend['username']); ?></div>
                                    <div class="friend-username">@<?php echo htmlspecialchars($friend['username']); ?></div>
                                </div>
                            </div>
                            <div class="friend-card-actions">
                                <a href="/RMStream/views/chat/conversation.php?user_id=<?php echo $friend['id']; ?>" class="friend-btn message-btn">
                                    <i class="fas fa-comment"></i> Mensagem
                                </a>
                                <button class="friend-btn unfriend-btn" data-action="unfriend" data-user-id="<?php echo $friend['id']; ?>">
                                    <i class="fas fa-user-minus"></i> Remover
                                </button>
                                <a href="/RMStream/views/profile/index.php?id=<?php echo $friend['id']; ?>" class="friend-btn view-profile-btn">
                                    <i class="fas fa-user"></i> Perfil
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Solicitações enviadas -->
        <div class="friends-section" id="pendingSection" style="display: none;">
            <h2>Solicitações Enviadas</h2>
            
            <?php if (empty($sentRequests)): ?>
                <div class="empty-list">
                    <i class="fas fa-paper-plane"></i>
                    <p>Você não enviou solicitações de amizade que estejam pendentes.</p>
                </div>
            <?php else: ?>
                <div class="sent-requests-list">
                    <?php foreach ($sentRequests as $request): ?>
                        <div class="sent-request-card">
                            <div class="sent-request-user">
                                <img src="<?php echo $request['profile_image'] ?? '/RMStream/public/images/default-avatar.png'; ?>" alt="Avatar" class="friend-avatar">
                                <div class="friend-info">
                                    <div class="friend-name"><?php echo htmlspecialchars($request['display_name'] ?? $request['username']); ?></div>
                                    <div class="friend-username">@<?php echo htmlspecialchars($request['username']); ?></div>
                                    <div class="friend-since">Enviada <?php echo timeAgo($request['created_at']); ?></div>
                                </div>
                            </div>
                            <div class="friend-actions">
                                <button class="friend-btn cancel-btn" data-action="cancel_request" data-user-id="<?php echo $request['id']; ?>">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                                <a href="/RMStream/views/profile/index.php?id=<?php echo $request['id']; ?>" class="friend-btn view-profile-btn">
                                    <i class="fas fa-user"></i> Perfil
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="/RMStream/public/js/friends.js"></script>
</body>
</html>

<?php
// Função para formatar tempo decorrido
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) {
        return $diff->y . ' ano' . ($diff->y > 1 ? 's' : '') . ' atrás';
    } elseif ($diff->m > 0) {
        return $diff->m . ' mês' . ($diff->m > 1 ? 'es' : '') . ' atrás';
    } elseif ($diff->d > 0) {
        return $diff->d . ' dia' . ($diff->d > 1 ? 's' : '') . ' atrás';
    } elseif ($diff->h > 0) {
        return $diff->h . ' hora' . ($diff->h > 1 ? 's' : '') . ' atrás';
    } elseif ($diff->i > 0) {
        return $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '') . ' atrás';
    } else {
        return 'agora mesmo';
    }
}
?>
