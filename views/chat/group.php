<?php
require_once '../../includes/config.php';

// Verificar se o usuário está logado
requireLogin();

$userId = $_SESSION['user_id'];
$groupId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$groupId) {
    header('Location: ' . SITE_URL . '/views/chat/index.php');
    exit;
}

// Verificar se o grupo existe e o usuário é membro
try {
    $pdo = getDbConnection();
    
    // Buscar dados do grupo
    $stmt = $pdo->prepare("
        SELECT g.*, gm.role as user_role
        FROM chat_groups g
        JOIN group_members gm ON g.id = gm.group_id
        WHERE g.id = :group_id AND gm.user_id = :user_id
    ");
    $stmt->bindParam(':group_id', $groupId);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    $group = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$group) {
        header('Location: ' . SITE_URL . '/views/chat/index.php');
        exit;
    }
    
    // Buscar membros do grupo
    $stmt = $pdo->prepare("
        SELECT gm.user_id, gm.role, u.username, u.display_name, u.profile_image
        FROM group_members gm
        JOIN users u ON gm.user_id = u.id
        WHERE gm.group_id = :group_id
        ORDER BY gm.role DESC, u.display_name, u.username
    ");
    $stmt->bindParam(':group_id', $groupId);
    $stmt->execute();
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar mensagens do grupo
    $stmt = $pdo->prepare("
        SELECT gm.id, gm.sender_id, gm.content, gm.attachment, gm.created_at,
               u.username, u.display_name, u.profile_image
        FROM group_messages gm
        JOIN users u ON gm.sender_id = u.id
        WHERE gm.group_id = :group_id
        ORDER BY gm.created_at
        LIMIT 50
    ");
    $stmt->bindParam(':group_id', $groupId);
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar amigos para adicionar ao grupo (se for admin)
    $friends = [];
    if ($group['user_role'] === 'admin') {
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.display_name, u.profile_image
            FROM users u
            JOIN friendships f ON (f.user_id = u.id AND f.friend_id = :user_id) 
                             OR (f.friend_id = u.id AND f.user_id = :user_id)
            WHERE f.status = 'accepted'
            AND u.id NOT IN (
                SELECT gm.user_id FROM group_members gm WHERE gm.group_id = :group_id
            )
            ORDER BY u.display_name, u.username
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':group_id', $groupId);
        $stmt->execute();
        $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    $error = 'Erro ao carregar grupo: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($group['name']); ?> - Chat - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/RMStream/public/css/style.css">
    <link rel="stylesheet" href="/RMStream/public/css/nav.css">
    <link rel="stylesheet" href="/RMStream/public/css/chat.css">
    <link rel="stylesheet" href="/RMStream/public/css/modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .group-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .group-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .group-avatar {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: #333;
            object-fit: cover;
        }
        
        .group-info {
            margin-right: auto;
        }
        
        .group-name {
            font-weight: 600;
            font-size: 1.1em;
        }
        
        .group-members-count {
            font-size: 0.85em;
            color: #aaa;
        }
        
        .message-sender {
            font-size: 0.85em;
            color: #1db954;
            margin-bottom: 2px;
            font-weight: 500;
        }
        
        .message.outgoing .message-sender {
            text-align: right;
        }
        
        .message-role {
            font-size: 0.75em;
            color: #fff;
            background: #555;
            padding: 1px 6px;
            border-radius: 10px;
            margin-left: 5px;
            display: inline-block;
        }
        
        .message-role.admin {
            background: #1db954;
        }
        
        .message-role.moderator {
            background: #4a9eff;
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
    
    <div class="chat-container">
        <!-- Barra lateral com membros do grupo -->
        <div class="chat-sidebar">
            <div class="sidebar-header">
                <h2 class="sidebar-title">Membros</h2>
                <?php if ($group['user_role'] === 'admin' && !empty($friends)): ?>
                    <button class="new-chat-btn" id="addMembersBtn" title="Adicionar membros">
                        <i class="fas fa-user-plus"></i>
                    </button>
                <?php endif; ?>
            </div>
            
            <div class="chat-list" id="membersList">
                <?php foreach ($members as $member): ?>
                    <div class="chat-item" data-user-id="<?php echo $member['user_id']; ?>">
                        <img src="<?php echo $member['profile_image'] ?? '/RMStream/public/images/default-avatar.png'; ?>" alt="Avatar" class="chat-avatar">
                        <div class="chat-info">
                            <div class="chat-name">
                                <?php echo htmlspecialchars($member['display_name'] ?? $member['username']); ?>
                                <?php if ($member['role'] !== 'member'): ?>
                                    <span class="message-role <?php echo $member['role']; ?>"><?php echo ucfirst($member['role']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="chat-last-message">
                                <?php if ($member['user_id'] == $userId): ?>
                                    Você
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Área principal de chat -->
        <div class="chat-main">
            <!-- Cabeçalho do grupo -->
            <div class="chat-header">
                <div class="group-header">
                    <div class="group-title">
                        <img src="<?php echo $group['avatar'] ?? '/RMStream/public/images/default-group.png'; ?>" 
                             alt="Avatar" class="group-avatar">
                        <div class="group-info">
                            <div class="group-name"><?php echo htmlspecialchars($group['name']); ?></div>
                            <div class="group-members-count"><?php echo count($members); ?> membros</div>
                        </div>
                    </div>
                    
                    <div class="chat-actions">
                        <?php if ($group['user_role'] === 'admin'): ?>
                            <button class="chat-action-btn" id="editGroupBtn" title="Editar grupo">
                                <i class="fas fa-cog"></i>
                            </button>
                        <?php endif; ?>
                        <button class="chat-action-btn" id="leaveGroupBtn" title="Sair do grupo">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Mensagens -->
            <div class="chat-messages" id="messageContainer">
                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $message): 
                        $isCurrentUser = $message['sender_id'] == $userId;
                        // Encontrar o papel do remetente
                        $senderRole = '';
                        foreach ($members as $member) {
                            if ($member['user_id'] == $message['sender_id']) {
                                $senderRole = $member['role'];
                                break;
                            }
                        }
                    ?>
                        <div class="message <?php echo $isCurrentUser ? 'outgoing' : 'incoming'; ?>" data-id="<?php echo $message['id']; ?>">
                            <img src="<?php echo $message['profile_image'] ?? '/RMStream/public/images/default-avatar.png'; ?>" 
                                 alt="Avatar" class="message-avatar">
                            <div class="message-content">
                                <div class="message-sender">
                                    <?php if ($isCurrentUser): ?>
                                        Você
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($message['display_name'] ?? $message['username']); ?>
                                    <?php endif; ?>
                                    
                                    <?php if ($senderRole !== 'member'): ?>
                                        <span class="message-role <?php echo $senderRole; ?>"><?php echo ucfirst($senderRole); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="message-text"><?php echo htmlspecialchars($message['content']); ?></div>
                                <?php if ($message['attachment']): ?>
                                    <img src="<?php echo htmlspecialchars($message['attachment']); ?>" alt="Anexo" class="message-attachment">
                                <?php endif; ?>
                                <div class="message-time"><?php echo date('H:i', strtotime($message['created_at'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-messages">
                        <p>Nenhuma mensagem ainda. Seja o primeiro a dizer olá!</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Formulário de entrada -->
            <div class="chat-input-container">
                <div class="chat-input-wrapper">
                    <textarea id="messageInput" class="chat-input" placeholder="Digite uma mensagem..." rows="1"></textarea>
                    <div class="chat-toolbar">
                        <button class="toolbar-btn" id="emojiBtn" title="Emoji">
                            <i class="far fa-smile"></i>
                        </button>
                        <button class="toolbar-btn" id="attachmentBtn" title="Anexar arquivo">
                            <i class="fas fa-paperclip"></i>
                        </button>
                    </div>
                </div>
                <button id="sendBtn" class="send-btn" title="Enviar">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal para adicionar membros -->
    <?php if ($group['user_role'] === 'admin' && !empty($friends)): ?>
    <div id="addMembersModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Adicionar Membros</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p>Selecione amigos para adicionar ao grupo:</p>
                <div class="friend-list">
                    <?php foreach ($friends as $friend): ?>
                        <div class="friend-item">
                            <label class="friend-select">
                                <input type="checkbox" name="selected_friends[]" value="<?php echo $friend['id']; ?>">
                                <img src="<?php echo $friend['profile_image'] ?? '/RMStream/public/images/default-avatar.png'; ?>" alt="Avatar" class="friend-avatar">
                                <span class="friend-name"><?php echo htmlspecialchars($friend['display_name'] ?? $friend['username']); ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button id="addSelectedBtn" class="modal-btn modal-btn-primary">Adicionar Selecionados</button>
                <button class="modal-btn modal-btn-secondary modal-close">Cancelar</button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Modal para confirmar saída do grupo -->
    <div id="leaveGroupModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Sair do Grupo</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja sair do grupo "<?php echo htmlspecialchars($group['name']); ?>"?</p>
                <p>Você não poderá ver as mensagens do grupo depois de sair.</p>
            </div>
            <div class="modal-footer">
                <button id="confirmLeaveBtn" class="modal-btn modal-btn-primary">Sim, sair do grupo</button>
                <button class="modal-btn modal-btn-secondary modal-close">Cancelar</button>
            </div>
        </div>
    </div>
    
    <form id="uploadForm" style="display: none;">
        <input type="file" id="fileInput" accept="image/*">
    </form>
    
    <script>
        // Dados do grupo
        const groupData = {
            id: <?php echo $groupId; ?>,
            name: "<?php echo htmlspecialchars($group['name']); ?>",
            userRole: "<?php echo $group['user_role']; ?>",
            currentUser: {
                id: <?php echo $userId; ?>,
                name: "<?php echo htmlspecialchars($_SESSION['username']); ?>",
                avatar: "<?php echo isset($_SESSION['profile_image']) && $_SESSION['profile_image'] ? $_SESSION['profile_image'] : '/RMStream/public/images/default-avatar.png'; ?>"
            }
        };
    </script>
    <script src="/RMStream/public/js/group-chat.js"></script>
</body>
</html>
