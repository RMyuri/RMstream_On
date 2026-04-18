<?php
require_once '../includes/config.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try {
    $pdo = getDbConnection();

    // Verificar se a tabela de grupos existe
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'chat_groups'");
    $stmt->execute();
    if ($stmt->rowCount() === 0) {
        // Criar tabelas necessárias
        $pdo->exec("CREATE TABLE IF NOT EXISTS `chat_groups` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `name` VARCHAR(100) NOT NULL,
            `description` TEXT NULL,
            `avatar` VARCHAR(255) NULL,
            `created_by` INT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        )");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS `group_members` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `group_id` INT NOT NULL,
            `user_id` INT NOT NULL,
            `role` ENUM('member', 'moderator', 'admin') DEFAULT 'member',
            `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES chat_groups(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY `unique_member` (`group_id`, `user_id`)
        )");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS `group_messages` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `group_id` INT NOT NULL,
            `sender_id` INT NOT NULL,
            `content` TEXT NOT NULL,
            `attachment` VARCHAR(255) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES chat_groups(id) ON DELETE CASCADE,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
        )");
    }

    switch ($action) {
        case 'create_group':
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $members = isset($_POST['members']) ? json_decode($_POST['members'], true) : [];
            
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Nome do grupo é obrigatório']);
                exit;
            }
            
            // Criar grupo
            $stmt = $pdo->prepare("
                INSERT INTO chat_groups (name, description, created_by)
                VALUES (:name, :description, :created_by)
            ");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':created_by', $userId);
            $stmt->execute();
            
            $groupId = $pdo->lastInsertId();
            
            // Adicionar criador como admin
            $stmt = $pdo->prepare("
                INSERT INTO group_members (group_id, user_id, role)
                VALUES (:group_id, :user_id, 'admin')
            ");
            $stmt->bindParam(':group_id', $groupId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            // Adicionar membros
            if (!empty($members)) {
                $validMembers = [];
                
                // Verificar se os membros são amigos
                $placeholders = implode(',', array_fill(0, count($members), '?'));
                $stmt = $pdo->prepare("
                    SELECT u.id FROM users u
                    JOIN friendships f ON (f.user_id = u.id AND f.friend_id = ?) 
                                     OR (f.friend_id = u.id AND f.user_id = ?)
                    WHERE f.status = 'accepted' AND u.id IN ($placeholders)
                ");
                
                $params = array_merge([$userId, $userId], $members);
                $stmt->execute($params);
                $validFriends = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Adicionar membros válidos
                if (!empty($validFriends)) {
                    $stmt = $pdo->prepare("
                        INSERT INTO group_members (group_id, user_id, role)
                        VALUES (:group_id, :user_id, 'member')
                    ");
                    
                    foreach ($validFriends as $friendId) {
                        $stmt->bindParam(':group_id', $groupId);
                        $stmt->bindParam(':user_id', $friendId);
                        $stmt->execute();
                        $validMembers[] = $friendId;
                    }
                }
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Grupo criado com sucesso',
                'group' => [
                    'id' => $groupId,
                    'name' => $name,
                    'description' => $description
                ],
                'members_added' => $validMembers ?? []
            ]);
            break;

        case 'add_members':
            $groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
            $members = isset($_POST['members']) ? json_decode($_POST['members'], true) : [];
            
            if (!$groupId || empty($members)) {
                echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
                exit;
            }
            
            // Verificar se o usuário é admin do grupo
            $stmt = $pdo->prepare("
                SELECT role FROM group_members 
                WHERE group_id = :group_id AND user_id = :user_id
            ");
            $stmt->bindParam(':group_id', $groupId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $membership = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$membership || $membership['role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Permissão negada']);
                exit;
            }
            
            // Verificar se os membros são amigos
            $validMembers = [];
            if (!empty($members)) {
                $placeholders = implode(',', array_fill(0, count($members), '?'));
                $stmt = $pdo->prepare("
                    SELECT u.id FROM users u
                    JOIN friendships f ON (f.user_id = u.id AND f.friend_id = ?) 
                                     OR (f.friend_id = u.id AND f.user_id = ?)
                    WHERE f.status = 'accepted' AND u.id IN ($placeholders)
                ");
                
                $params = array_merge([$userId, $userId], $members);
                $stmt->execute($params);
                $validFriends = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Adicionar membros válidos
                if (!empty($validFriends)) {
                    $stmt = $pdo->prepare("
                        INSERT IGNORE INTO group_members (group_id, user_id, role)
                        VALUES (:group_id, :user_id, 'member')
                    ");
                    
                    foreach ($validFriends as $friendId) {
                        $stmt->bindParam(':group_id', $groupId);
                        $stmt->bindParam(':user_id', $friendId);
                        $stmt->execute();
                        $validMembers[] = $friendId;
                    }
                }
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Membros adicionados com sucesso',
                'members_added' => $validMembers
            ]);
            break;

        case 'leave_group':
            $groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
            
            if (!$groupId) {
                echo json_encode(['success' => false, 'message' => 'Grupo inválido']);
                exit;
            }
            
            // Verificar se o usuário é o último admin
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM group_members 
                WHERE group_id = :group_id AND role = 'admin'
            ");
            $stmt->bindParam(':group_id', $groupId);
            $stmt->execute();
            $adminCount = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("
                SELECT role FROM group_members 
                WHERE group_id = :group_id AND user_id = :user_id
            ");
            $stmt->bindParam(':group_id', $groupId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $userRole = $stmt->fetchColumn();
            
            if ($adminCount <= 1 && $userRole === 'admin') {
                echo json_encode(['success' => false, 'message' => 'Você é o único administrador. Promova outro membro ou exclua o grupo.']);
                exit;
            }
            
            // Remover usuário do grupo
            $stmt = $pdo->prepare("
                DELETE FROM group_members 
                WHERE group_id = :group_id AND user_id = :user_id
            ");
            $stmt->bindParam(':group_id', $groupId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Você saiu do grupo'
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?>
