<?php
require_once '../../includes/config.php';

// Verificar se o usuário está logado
requireLogin();

$userId = $_SESSION['user_id'];
$errorMsg = '';
$successMsg = '';

// Processar criação de grupo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $groupName = isset($_POST['group_name']) ? trim($_POST['group_name']) : '';
    $groupDescription = isset($_POST['group_description']) ? trim($_POST['group_description']) : '';
    $selectedMembers = isset($_POST['members']) ? $_POST['members'] : [];
    
    // Validações
    if (empty($groupName)) {
        $errorMsg = 'O nome do grupo é obrigatório';
    } else if (strlen($groupName) > 100) {
        $errorMsg = 'O nome do grupo deve ter no máximo 100 caracteres';
    } else if (count($selectedMembers) < 1) {
        $errorMsg = 'Você deve selecionar pelo menos 1 membro para o grupo';
    } else {
        try {
            $pdo = getDbConnection();
            
            // Verificar se as tabelas existem
            $stmt = $pdo->prepare("SHOW TABLES LIKE 'chat_groups'");
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                // Criar tabela de grupos
                $pdo->exec("CREATE TABLE chat_groups (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    description TEXT,
                    avatar VARCHAR(255),
                    created_by INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
            }
            
            $stmt = $pdo->prepare("SHOW TABLES LIKE 'group_members'");
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                // Criar tabela de membros
                $pdo->exec("CREATE TABLE group_members (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    group_id INT NOT NULL,
                    user_id INT NOT NULL,
                    role ENUM('admin', 'member') DEFAULT 'member',
                    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_membership (group_id, user_id)
                )");
            }
            
            // Processar upload de avatar, se houver
            $avatarPath = null;
            if (!empty($_FILES['group_avatar']['name'])) {
                $avatar = $_FILES['group_avatar'];
                $fileName = $avatar['name'];
                $fileTmpName = $avatar['tmp_name'];
                $fileSize = $avatar['size'];
                $fileError = $avatar['error'];
                
                // Verificar erros no upload
                if ($fileError === 0) {
                    // Verificar tamanho (limite de 2MB)
                    if ($fileSize <= 2 * 1024 * 1024) {
                        // Obter extensão e gerar nome único
                        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
                        
                        if (in_array($fileExt, $allowedExts)) {
                            $newFileName = uniqid('group_') . '.' . $fileExt;
                            $uploadDir = '../../uploads/groups/';
                            
                            if (!file_exists($uploadDir)) {
                                mkdir($uploadDir, 0777, true);
                            }
                            
                            $destination = $uploadDir . $newFileName;
                            
                            if (move_uploaded_file($fileTmpName, $destination)) {
                                $avatarPath = '/RMStream/uploads/groups/' . $newFileName;
                            } else {
                                $errorMsg = 'Erro ao fazer upload do avatar';
                            }
                        } else {
                            $errorMsg = 'Formato de imagem inválido. Use JPG, PNG ou GIF';
                        }
                    } else {
                        $errorMsg = 'Avatar muito grande. O tamanho máximo é 2MB';
                    }
                } else {
                    $errorMsg = 'Erro ao fazer upload do avatar';
                }
            }
            
            // Se não houver erros, criar o grupo
            if (empty($errorMsg)) {
                // Iniciar transação
                $pdo->beginTransaction();
                
                // Inserir grupo
                $stmt = $pdo->prepare("
                    INSERT INTO chat_groups (name, description, avatar, created_by)
                    VALUES (:name, :description, :avatar, :created_by)
                ");
                $stmt->bindParam(':name', $groupName);
                $stmt->bindParam(':description', $groupDescription);
                $stmt->bindParam(':avatar', $avatarPath);
                $stmt->bindParam(':created_by', $userId);
                $stmt->execute();
                
                $groupId = $pdo->lastInsertId();
                
                // Adicionar criador como administrador
                $stmt = $pdo->prepare("
                    INSERT INTO group_members (group_id, user_id, role)
                    VALUES (:group_id, :user_id, 'admin')
                ");
                $stmt->bindParam(':group_id', $groupId);
                $stmt->bindParam(':user_id', $userId);
                $stmt->execute();
                
                // Adicionar membros selecionados
                foreach ($selectedMembers as $memberId) {
                    if ($memberId != $userId) { // Evitar duplicação do criador
                        $stmt = $pdo->prepare("
                            INSERT INTO group_members (group_id, user_id, role)
                            VALUES (:group_id, :user_id, 'member')
                        ");
                        $stmt->bindParam(':group_id', $groupId);
                        $stmt->bindParam(':user_id', $memberId);
                        $stmt->execute();
                    }
                }
                
                // Confirmar transação
                $pdo->commit();
                
                // Redirecionar para a conversa do grupo
                header('Location: /RMStream/views/chat/conversation.php?group_id=' . $groupId);
                exit;
            }
            
        } catch (PDOException $e) {
            // Reverter transação em caso de erro
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            $errorMsg = 'Erro ao criar grupo: ' . $e->getMessage();
        }
    }
}

// Buscar amigos para adicionar ao grupo
try {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.display_name, u.profile_image
        FROM friendships f
        JOIN users u ON (f.user_id = u.id AND f.user_id != :userId) OR (f.friend_id = u.id AND f.friend_id != :userId)
        WHERE (f.user_id = :userId OR f.friend_id = :userId) AND f.status = 'accepted'
        ORDER BY u.display_name, u.username
    ");
    $stmt->bindParam(':userId', $userId);
    $stmt->execute();
    $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $errorMsg = 'Erro ao carregar amigos: ' . $e->getMessage();
    $friends = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Grupo - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/RMStream/public/css/style.css">
    <link rel="stylesheet" href="/RMStream/public/css/nav.css">
    <link rel="stylesheet" href="/RMStream/public/css/chat.css">
    <link rel="stylesheet" href="/RMStream/public/css/groups.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .create-group-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: #232323;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        
        .create-group-title {
            color: #1db954;
            margin-top: 0;
            margin-bottom: 24px;
            font-size: 1.8em;
            text-align: center;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section-title {
            font-size: 1.2em;
            color: #1db954;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #444;
            background: #333;
            border-radius: 6px;
            color: #fff;
            font-size: 1em;
        }
        
        .form-control:focus {
            border-color: #1db954;
            outline: none;
            box-shadow: 0 0 0 2px rgba(29, 185, 84, 0.2);
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-help {
            font-size: 0.85em;
            color: #aaa;
            margin-top: 4px;
        }
        
        .avatar-upload {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .avatar-preview {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            background: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .avatar-preview .placeholder {
            color: #666;
            font-size: 3em;
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        
        .file-input-button {
            background: #333;
            color: #fff;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .file-input-button:hover {
            background: #444;
        }
        
        .file-input-wrapper input[type="file"] {
            position: absolute;
            font-size: 100px;
            opacity: 0;
            right: 0;
            top: 0;
            cursor: pointer;
        }
        
        .members-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .member-item {
            background: #333;
            border-radius: 8px;
            padding: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .member-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .member-info {
            flex: 1;
        }
        
        .member-name {
            font-weight: 500;
        }
        
        .member-checkbox {
            transform: scale(1.2);
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #1db954;
            color: white;
        }
        
        .btn-primary:hover {
            background: #18a448;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(29, 185, 84, 0.4);
        }
        
        .btn-secondary {
            background: #555;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #666;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background: rgba(229, 57, 53, 0.1);
            border: 1px solid rgba(229, 57, 53, 0.3);
            color: #ff5555;
        }
        
        .alert-success {
            background: rgba(29, 185, 84, 0.1);
            border: 1px solid rgba(29, 185, 84, 0.3);
            color: #1db954;
        }
        
        .no-friends {
            text-align: center;
            padding: 20px;
            background: #333;
            border-radius: 8px;
            color: #aaa;
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
    
    <div class="create-group-container">
        <div class="create-group-header">
            <h1>Criar Novo Grupo</h1>
            <a href="/RMStream/views/chat/index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
        
        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $errorMsg; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $successMsg; ?>
            </div>
        <?php endif; ?>
        
        <form class="create-group-form" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="group_name">Nome do Grupo *</label>
                <input type="text" id="group_name" name="group_name" required maxlength="100" 
                       value="<?php echo isset($_POST['group_name']) ? htmlspecialchars($_POST['group_name']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="group_description">Descrição</label>
                <textarea id="group_description" name="group_description" rows="3"><?php echo isset($_POST['group_description']) ? htmlspecialchars($_POST['group_description']) : ''; ?></textarea>
                <p class="form-hint">Uma breve descrição sobre o grupo (opcional)</p>
            </div>
            
            <div class="form-group">
                <label for="group_avatar">Avatar do Grupo</label>
                <div class="avatar-upload">
                    <div class="avatar-preview" id="avatarPreview">
                        <img src="/RMStream/public/images/default-group.png" alt="Group Avatar" id="avatarImage">
                    </div>
                    <div class="avatar-edit">
                        <input type="file" id="group_avatar" name="group_avatar" accept="image/*">
                        <label for="group_avatar">Escolher Imagem</label>
                    </div>
                </div>
                <p class="form-hint">Tamanho máximo: 2MB. Formatos: JPG, PNG, GIF</p>
            </div>
            
            <div class="form-group">
                <label>Membros do Grupo *</label>
                <div class="search-box">
                    <input type="text" id="searchMembers" placeholder="Buscar amigos...">
                </div>
                
                <?php if (empty($friends)): ?>
                    <div class="no-friends-message">
                        <p>Você não tem amigos para adicionar ao grupo.</p>
                        <a href="/RMStream/views/chat/find_friends.php" class="btn">Adicionar Amigos</a>
                    </div>
                <?php else: ?>
                    <div class="members-selection">
                        <?php foreach ($friends as $friend): ?>
                            <div class="member-option">
                                <input type="checkbox" id="member_<?php echo $friend['id']; ?>" name="members[]" value="<?php echo $friend['id']; ?>">
                                <label for="member_<?php echo $friend['id']; ?>" class="member-label">
                                    <img src="<?php echo $friend['profile_image'] ?? '/RMStream/public/images/default-avatar.png'; ?>" alt="Avatar" class="member-avatar">
                                    <span class="member-name"><?php echo htmlspecialchars($friend['display_name'] ?? $friend['username']); ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="create-group-btn" <?php echo empty($friends) ? 'disabled' : ''; ?>>
                    <i class="fas fa-users"></i> Criar Grupo
                </button>
                <a href="/RMStream/views/chat/index.php" class="cancel-btn">Cancelar</a>
            </div>
        </form>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Preview de imagem para avatar do grupo
            const avatarInput = document.getElementById('group_avatar');
            const avatarPreview = document.getElementById('avatarPreview');
            const avatarImage = document.getElementById('avatarImage');
            
            if (avatarInput) {
                avatarInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    
                    if (file) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            avatarImage.src = e.target.result;
                        }
                        
                        reader.readAsDataURL(file);
                    }
                });
            }
            
            // Busca de membros
            const searchMembersInput = document.getElementById('searchMembers');
            const memberOptions = document.querySelectorAll('.member-option');
            
            if (searchMembersInput) {
                searchMembersInput.addEventListener('input', function() {
                    const searchValue = this.value.toLowerCase();
                    
                    memberOptions.forEach(option => {
                        const memberName = option.querySelector('.member-name').textContent.toLowerCase();
                        
                        if (memberName.includes(searchValue)) {
                            option.style.display = '';
                        } else {
                            option.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>
