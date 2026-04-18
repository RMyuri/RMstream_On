<?php
require_once '../../includes/config.php';

// Verificar se o usuário está logado
requireLogin();

$userId = $_SESSION['user_id'];

try {
    $pdo = getDbConnection();
    
    // Buscar amigos e suas últimas mensagens
    $friends = [];
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
    
    // Obter últimas mensagens para cada amigo
    foreach ($friends as &$friend) {
        $stmt = $pdo->prepare("
            SELECT content, created_at, sender_id, is_read
            FROM messages
            WHERE (sender_id = :userId AND receiver_id = :friendId) OR (sender_id = :friendId AND receiver_id = :userId)
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->bindParam(':userId', $userId);
        $stmt->bindParam(':friendId', $friend['id']);
        $stmt->execute();
        $lastMessage = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lastMessage) {
            $friend['last_message'] = $lastMessage['content'];
            $friend['last_message_time'] = $lastMessage['created_at'];
            $friend['unread'] = ($lastMessage['sender_id'] != $userId && !$lastMessage['is_read']) ? true : false;
        } else {
            $friend['last_message'] = 'Nenhuma mensagem ainda';
            $friend['last_message_time'] = null;
            $friend['unread'] = false;
        }
    }
    
    // Ordenar amigos por mensagem mais recente
    usort($friends, function($a, $b) {
        if (empty($a['last_message_time']) && empty($b['last_message_time'])) return 0;
        if (empty($a['last_message_time'])) return 1;
        if (empty($b['last_message_time'])) return -1;
        return strtotime($b['last_message_time']) - strtotime($a['last_message_time']);
    });
    
    // Buscar grupos do usuário
    $groups = [];
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'chat_groups'");
    $stmt->execute();
    $groupTableExists = $stmt->rowCount() > 0;
    
    if ($groupTableExists) {
        // Buscar grupos que o usuário participa
        $stmt = $pdo->prepare("
            SELECT g.id, g.name, g.avatar, g.created_at
            FROM chat_groups g
            JOIN group_members gm ON g.id = gm.group_id
            WHERE gm.user_id = :userId
            ORDER BY g.name
        ");
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar última mensagem de cada grupo
        foreach ($groups as &$group) {
            $stmt = $pdo->prepare("
                SELECT gm.content, gm.created_at, u.username, u.display_name
                FROM group_messages gm
                JOIN users u ON gm.sender_id = u.id
                WHERE gm.group_id = :groupId
                ORDER BY gm.created_at DESC
                LIMIT 1
            ");
            $stmt->bindParam(':groupId', $group['id']);
            $stmt->execute();
            $lastMessage = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($lastMessage) {
                $senderName = $lastMessage['display_name'] ?? $lastMessage['username'];
                $group['last_message'] = "$senderName: " . $lastMessage['content'];
                $group['last_message_time'] = $lastMessage['created_at'];
            } else {
                $group['last_message'] = 'Nenhuma mensagem ainda';
                $group['last_message_time'] = null;
            }
            
            // Verificar mensagens não lidas (simplificado)
            $group['unread'] = false;
        }
    }
    
} catch (PDOException $e) {
    $error = 'Erro ao carregar conversas: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/RMStream/public/css/style.css">
    <link rel="stylesheet" href="/RMStream/public/css/nav.css">
    <link rel="stylesheet" href="/RMStream/public/css/chat.css">
    <link rel="stylesheet" href="/RMStream/public/css/modal.css">
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
                <a href="/RMStream/views/chat/index.php" class="nav-link active">Chat</a>
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
    
    <!-- Container principal do chat -->
    <div class="chat-container">
        <!-- Sidebar com listas de conversas -->
        <div class="chat-sidebar">
            <!-- Cabeçalho da sidebar -->
            <div class="sidebar-header">
                <h2 class="sidebar-title">Conversas</h2>
                <div class="sidebar-actions">
                    <a href="/RMStream/views/chat/find_friends.php" class="find-friends-btn" title="Adicionar Amigos">
                        <i class="fas fa-user-plus"></i>
                    </a>
                    <button class="new-chat-btn" id="newChatBtn" title="Nova conversa">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            
            <!-- Abas de chats diretos e grupos -->
            <div class="chat-tabs">
                <div class="chat-tab active" data-tab="direct">Direto</div>
                <div class="chat-tab" data-tab="groups">Grupos</div>
            </div>
            
            <!-- Lista de conversas diretas -->
            <div class="chat-list" id="directChatList">
                <?php if (empty($friends)): ?>
                    <div class="empty-chat-list">
                        <p>Nenhum amigo encontrado. Adicione amigos para começar a conversar.</p>
                        <a href="/RMStream/views/chat/find_friends.php" class="add-friends-btn">
                            <i class="fas fa-user-plus"></i> Adicionar Amigos
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($friends as $friend): ?>
                        <div class="chat-item" data-user-id="<?php echo $friend['id']; ?>">
                            <img src="<?php echo $friend['profile_image'] ?? '/RMStream/public/images/default-avatar.png'; ?>" alt="Avatar" class="chat-avatar">
                            <div class="chat-info">
                                <div class="chat-name"><?php echo htmlspecialchars($friend['display_name'] ?? $friend['username']); ?></div>
                                <div class="chat-last-message"><?php echo htmlspecialchars(substr($friend['last_message'], 0, 40) . (strlen($friend['last_message']) > 40 ? '...' : '')); ?></div>
                            </div>
                            <div class="chat-meta">
                                <?php if ($friend['last_message_time']): ?>
                                    <span class="chat-time"><?php echo date('H:i', strtotime($friend['last_message_time'])); ?></span>
                                <?php endif; ?>
                                <?php if ($friend['unread']): ?>
                                    <span class="chat-badge">1</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Lista de grupos -->
            <div class="chat-list" id="groupChatList" style="display: none;">
                <?php if (empty($groups)): ?>
                    <div class="empty-chat-list">
                        <p>Nenhum grupo encontrado. Crie ou participe de um grupo para começar.</p>
                        <a href="/RMStream/views/chat/create_group.php" class="create-group-btn">
                            <i class="fas fa-users"></i> Criar novo grupo
                        </a>
                    </div>
                <?php else: ?>
                    <div class="group-actions">
                        <a href="/RMStream/views/chat/create_group.php" class="create-group-link">
                            <i class="fas fa-plus"></i> Criar novo grupo
                        </a>
                    </div>
                    <?php foreach ($groups as $group): ?>
                        <div class="chat-item" data-group-id="<?php echo $group['id']; ?>">
                            <img src="<?php echo $group['avatar'] ?? '/RMStream/public/images/default-group.png'; ?>" alt="Avatar" class="chat-avatar">
                            <div class="chat-info">
                                <div class="chat-name"><?php echo htmlspecialchars($group['name']); ?></div>
                                <div class="chat-last-message"><?php echo htmlspecialchars(substr($group['last_message'], 0, 40) . (strlen($group['last_message']) > 40 ? '...' : '')); ?></div>
                            </div>
                            <div class="chat-meta">
                                <?php if ($group['last_message_time']): ?>
                                    <span class="chat-time"><?php echo date('H:i', strtotime($group['last_message_time'])); ?></span>
                                <?php endif; ?>
                                <?php if ($group['unread']): ?>
                                    <span class="chat-badge">1</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Área principal de chat -->
        <div class="chat-main">
            <!-- Estado vazio (quando nenhuma conversa está selecionada) -->
            <div class="empty-state" id="emptyState">
                <div class="empty-state-icon">
                    <i class="far fa-comments"></i>
                </div>
                <h2 class="empty-state-title">Suas mensagens</h2>
                <p class="empty-state-text">
                    Selecione uma conversa ou inicie uma nova para começar a enviar mensagens.
                </p>
                <button class="empty-state-btn" id="startNewChat">
                    <i class="fas fa-plus"></i> Nova conversa
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal para nova conversa -->
    <div id="newChatModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Nova conversa</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="search-box">
                    <input type="text" class="search-input" placeholder="Buscar amigos..." id="searchFriends">
                </div>
                <div class="friend-list" id="friendList">
                    <div class="loading-indicator">
                        <i class="fas fa-spinner fa-spin"></i> Carregando amigos...
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script>
        // Dados do usuário atual
        const currentUser = {
            id: <?php echo $userId; ?>,
            name: "<?php echo htmlspecialchars($_SESSION['username']); ?>",
            avatar: "<?php echo isset($_SESSION['profile_image']) && $_SESSION['profile_image'] ? $_SESSION['profile_image'] : '/RMStream/public/images/default-avatar.png'; ?>"
        };
    </script>
    <script src="/RMStream/public/js/chat.js"></script>
</body>
</html>
    <script>
        // Dados do usuário atual
        const currentUser = {
            id: <?php echo $userId; ?>,
            name: "<?php echo htmlspecialchars($_SESSION['username']); ?>",
            avatar: "<?php echo isset($_SESSION['profile_image']) && $_SESSION['profile_image'] ? $_SESSION['profile_image'] : '/RMStream/public/images/default-avatar.png'; ?>"
        };
    </script>
    <script src="/RMStream/public/js/chat.js"></script>
    <script src="/RMStream/public/js/notifications.js"></script>
</body>
</html>
