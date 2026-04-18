<?php
require_once '../../includes/config.php';

// Verificar se o usuário está logado
requireLogin();

// Obter ID do usuário a visualizar (ou o próprio usuário)
$userId = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];
$isOwnProfile = ($userId === (int)$_SESSION['user_id']);

// Buscar dados do usuário
try {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare("SELECT id, username, email, display_name, bio, profile_image, banner_image, created_at, phone 
                           FROM users WHERE id = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Usuário não encontrado
        header('Location: ' . SITE_URL . '/views/index.php');
        exit;
    }
    
    // Inicializar contador de amigos
    $friendCount = 0;
    $friends = [];
    
    // Verificar se a tabela friendships existe antes de tentar contar amigos
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'friendships'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        // Contar número de amigos
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM friendships 
                              WHERE (user_id = :id OR friend_id = :id) 
                              AND status = 'accepted'");
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        
        $friendCount = $stmt->fetchColumn();
        
        // Buscar lista de amigos
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
    }
    
} catch (PDOException $e) {
    die('Erro ao buscar dados do usuário: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['display_name'] ?? $user['username']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/RMStream/public/css/style.css">
    <link rel="stylesheet" href="/RMStream/public/css/nav.css">
    <link rel="stylesheet" href="/RMStream/public/css/profile-revised.css">
    <link rel="stylesheet" href="/RMStream/public/css/notifications.css">
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
                <a href="/RMStream/views/profile/index.php" class="nav-link active">Perfil</a>
                <div class="submenu">
                    <a href="/RMStream/views/profile/edit.php" class="submenu-item">Editar Perfil</a>
                    <a href="/RMStream/views/profile/settings.php" class="submenu-item">Configurações</a>
                    <a href="/RMStream/views/chat/find_friends.php" class="submenu-item">Adicionar Amigos</a>
                    <a href="/RMStream/views/notifications.php" class="submenu-item">Notificações</a>
                </div>
            </div>
        </div>
        <div class="nav-profile">
            <div class="notifications-container">
                <button class="notifications-btn" id="notificationsBtn">
                    <i class="fas fa-bell"></i>
                    <span class="notifications-count" id="notificationsCount"></span>
                </button>
                <div class="notifications-dropdown" id="notificationsDropdown">
                    <div class="notifications-header">
                        <h3>Notificações</h3>
                        <button class="mark-all-read-btn" id="markAllReadBtn">Marcar todas como lidas</button>
                    </div>
                    <div class="notifications-list" id="notificationsList">
                        <div class="notification-loading">
                            <i class="fas fa-spinner fa-spin"></i> Carregando notificações...
                        </div>
                    </div>
                    <div class="notifications-footer">
                        <a href="/RMStream/views/notifications.php">Ver todas</a>
                    </div>
                </div>
            </div>
            <a href="/RMStream/views/profile/index.php">
                <img src="<?php echo isset($_SESSION['profile_image']) && $_SESSION['profile_image'] ? $_SESSION['profile_image'] : '/RMStream/public/images/default-avatar.png'; ?>" 
                     alt="Avatar" class="profile-avatar">
            </a>
            <a href="/RMStream/views/auth/logout.php" class="nav-link">Sair</a>
        </div>
    </nav>
    
    <main class="main-content">
        <div class="profile-container">
            <!-- Cabeçalho do perfil -->
            <div class="profile-header">
                <!-- Banner do perfil -->
                <div class="profile-banner">
                    <?php if ($user['banner_image']): ?>
                        <img src="<?php echo htmlspecialchars($user['banner_image']); ?>" alt="Capa de perfil" class="banner-image">
                    <?php endif; ?>
                    
                    <!-- Botões de ação do perfil (apenas para o próprio usuário) -->
                    <?php if ($isOwnProfile): ?>
                    <div class="profile-actions-top">
                        <a href="/RMStream/views/profile/edit.php" class="action-btn edit-btn">
                            <i class="fas fa-edit"></i> Editar Perfil
                        </a>
                        <a href="/RMStream/views/profile/settings.php" class="action-btn settings-btn">
                            <i class="fas fa-cog"></i> Configurações
                        </a>
                        <button id="requestsBtn" class="action-btn requests-btn">
                            <i class="fas fa-paper-plane"></i> Solicitações
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Informações básicas do perfil -->
                <div class="profile-main-info">
                    <div class="profile-avatar-wrapper">
                        <img src="<?php echo $user['profile_image'] ?? '/RMStream/public/images/default-avatar.png'; ?>" 
                             alt="Foto de perfil" class="profile-avatar">
                    </div>
                    
                    <div class="profile-user-info">
                        <h1 class="profile-name"><?php echo htmlspecialchars($user['display_name'] ?? $user['username']); ?></h1>
                        <p class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                    </div>
                </div>
                
                <!-- Detalhes do perfil -->
                <div class="profile-details">
                    <!-- Estatísticas do usuário -->
                    <div class="profile-stats">
                        <div class="stat-item">
                            <i class="fas fa-id-card"></i>
                            <span>ID: <?php echo $user['id']; ?></span>
                        </div>
                        
                        <div class="stat-item">
                            <i class="fas fa-user-friends"></i>
                            <span><?php echo $friendCount; ?> amigos</span>
                        </div>
                        
                        <div class="stat-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Membro desde <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                        </div>
                    </div>
                    
                    <!-- Biografia -->
                    <?php if ($user['bio']): ?>
                        <div class="profile-bio">
                            <h3 class="bio-title">Sobre mim</h3>
                            <div class="bio-content"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Informações de contato -->
                    <div class="profile-contact">
                        <?php if ($user['email']): ?>
                            <div class="contact-item">
                                <i class="fas fa-envelope"></i>
                                <span><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($user['phone'])): ?>
                            <div class="contact-item">
                                <i class="fas fa-phone"></i>
                                <span><?php echo htmlspecialchars($user['phone']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Seção de amigos -->
            <div class="friends-section">
                <h2 class="section-title">Meus Amigos</h2>
                
                <?php if (empty($friends)): ?>
                    <div class="empty-friends">
                        <p>Você ainda não adicionou nenhum amigo.</p>
                        <a href="/RMStream/views/chat/find_friends.php" class="primary-btn">Encontrar Amigos</a>
                    </div>
                <?php else: ?>
                    <div class="friends-table-wrapper">
                        <table class="friends-table">
                            <thead>
                                <tr>
                                    <th>Foto</th>
                                    <th>Nome</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($friends as $friend): ?>
                                <tr>
                                    <td class="friend-avatar-cell">
                                        <div class="friend-avatar">
                                            <img src="<?php echo $friend['profile_image'] ?? '/RMStream/public/images/default-avatar.png'; ?>" 
                                                 alt="Avatar" class="friend-img">
                                        </div>
                                    </td>
                                    <td class="friend-name">
                                        <?php echo htmlspecialchars($friend['display_name'] ?? $friend['username']); ?>
                                    </td>
                                    <td class="friend-actions">
                                        <a href="/RMStream/views/chat/conversation.php?user_id=<?php echo $friend['id']; ?>" 
                                           class="friend-btn chat-btn" title="Conversar">
                                            <i class="fas fa-comment"></i>
                                        </a>
                                        <button class="friend-btn block-btn" 
                                                data-user-id="<?php echo $friend['id']; ?>" 
                                                data-username="<?php echo htmlspecialchars($friend['display_name'] ?? $friend['username']); ?>"
                                                title="Bloquear">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                        <button class="friend-btn report-btn" 
                                                data-user-id="<?php echo $friend['id']; ?>" 
                                                data-username="<?php echo htmlspecialchars($friend['display_name'] ?? $friend['username']); ?>"
                                                title="Reportar">
                                            <i class="fas fa-flag"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Modal de Solicitações Enviadas -->
    <div id="sentRequestsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Solicitações de Amizade Enviadas</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="sentRequestsList">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i> Carregando solicitações...
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Bloqueio -->
    <div id="blockModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Bloquear Usuário</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja bloquear <span id="blockUsername"></span>?</p>
                <p>Este usuário não poderá mais enviar mensagens para você.</p>
                <div class="modal-actions">
                    <button id="confirmBlock" class="danger-btn">Bloquear</button>
                    <button class="secondary-btn modal-close-btn">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Denúncia -->
    <div id="reportModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reportar Usuário</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p>Reportar <span id="reportUsername"></span> por comportamento inadequado:</p>
                <form id="reportForm">
                    <input type="hidden" id="reportUserId" name="user_id">
                    <div class="form-group">
                        <label for="reportReason">Motivo:</label>
                        <select id="reportReason" name="reason" required>
                            <option value="">Selecione um motivo</option>
                            <option value="spam">Spam</option>
                            <option value="harassment">Assédio</option>
                            <option value="inappropriate">Conteúdo Impróprio</option>
                            <option value="other">Outro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="reportDescription">Descrição:</label>
                        <textarea id="reportDescription" name="description" rows="4" required></textarea>
                    </div>
                    <div class="modal-actions">
                        <button type="submit" class="danger-btn">Enviar Denúncia</button>
                        <button type="button" class="secondary-btn modal-close-btn">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="/RMStream/public/js/nav.js"></script>
    <script src="/RMStream/public/js/notifications.js"></script>
    <script src="/RMStream/public/js/profile-revised.js"></script>
</body>
</html>
