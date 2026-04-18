<?php
require_once '../includes/config.php';

// Verificar se o usuário está logado
requireLogin();

// Retornar em formato JSON
header('Content-Type: application/json');

// Inicializar resposta
$response = [
    'success' => false,
    'message' => 'Requisição inválida'
];

$userId = $_SESSION['user_id'];

try {
    $pdo = getDbConnection();
    
    // Buscar mensagens de uma conversa
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $type = isset($_GET['type']) ? $_GET['type'] : 'direct';
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $limit = 50; // Número de mensagens por carregamento
        
        if ($id <= 0) {
            $response['message'] = 'ID inválido';
            echo json_encode($response);
            exit;
        }
        
        if ($type === 'direct') {
            // Verificar se são amigos
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM friendships
                WHERE ((user_id = :userId AND friend_id = :otherId) OR (user_id = :otherId AND friend_id = :userId))
                AND status = 'accepted'
            ");
            $stmt->bindParam(':userId', $userId);
            $stmt->bindParam(':otherId', $id);
            $stmt->execute();
            
            $areFriends = ($stmt->fetchColumn() > 0);
            
            if (!$areFriends) {
                $response['message'] = 'Você não tem permissão para ver esta conversa';
                echo json_encode($response);
                exit;
            }
            
            // Buscar mensagens diretas
            $stmt = $pdo->prepare("
                SELECT m.id, m.sender_id, m.content, m.media_type, m.media_url, m.created_at, 
                       u.username, u.display_name, u.profile_image
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE (m.sender_id = :userId AND m.receiver_id = :otherId) OR (m.sender_id = :otherId AND m.receiver_id = :userId)
                ORDER BY m.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindParam(':userId', $userId);
            $stmt->bindParam(':otherId', $id);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Marcar mensagens como lidas
            $stmt = $pdo->prepare("
                UPDATE messages
                SET is_read = 1
                WHERE sender_id = :otherId AND receiver_id = :userId
            ");
            $stmt->bindParam(':otherId', $id);
            $stmt->bindParam(':userId', $userId);
            $stmt->execute();
            
        } else if ($type === 'group') {
            // Verificar se o usuário é membro do grupo
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM group_members
                WHERE group_id = :groupId AND user_id = :userId
            ");
            $stmt->bindParam(':groupId', $id);
            $stmt->bindParam(':userId', $userId);
            $stmt->execute();
            
            $isMember = ($stmt->fetchColumn() > 0);
            
            if (!$isMember) {
                $response['message'] = 'Você não tem permissão para ver este grupo';
                echo json_encode($response);
                exit;
            }
            
            // Buscar mensagens do grupo
            $stmt = $pdo->prepare("
                SELECT gm.id, gm.sender_id, gm.content, gm.media_type, gm.media_url, gm.created_at, 
                       u.username, u.display_name, u.profile_image
                FROM group_messages gm
                JOIN users u ON gm.sender_id = u.id
                WHERE gm.group_id = :groupId
                ORDER BY gm.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindParam(':groupId', $id);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Marcar mensagens como lidas
            $stmt = $pdo->prepare("
                UPDATE group_messages
                SET is_read = 1
                WHERE group_id = :groupId AND sender_id != :userId
            ");
            $stmt->bindParam(':groupId', $id);
            $stmt->bindParam(':userId', $userId);
            $stmt->execute();
        }
        
        // Inverter a ordem para cronológica (mais antigas primeiro)
        $messages = array_reverse($messages);
        
        $response['success'] = true;
        $response['messages'] = $messages;
        $response['has_more'] = count($messages) === $limit;
        
    } 
    // Enviar uma nova mensagem
    else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $type = isset($_POST['type']) ? $_POST['type'] : 'direct';
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $content = isset($_POST['content']) ? trim($_POST['content']) : '';
        
        // Verificar se há conteúdo ou arquivo anexado
        if (empty($content) && empty($_FILES['attachment']['name'])) {
            $response['message'] = 'Mensagem vazia';
            echo json_encode($response);
            exit;
        }
        
        // Processar anexo, se houver
        $mediaType = null;
        $mediaUrl = null;
        
        if (!empty($_FILES['attachment']['name'])) {
            $file = $_FILES['attachment'];
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            $fileSize = $file['size'];
            $fileError = $file['error'];
            
            // Verificar erros no upload
            if ($fileError !== 0) {
                $response['message'] = 'Erro ao fazer upload do arquivo';
                echo json_encode($response);
                exit;
            }
            
            // Verificar tamanho (limite de 10MB)
            if ($fileSize > 10 * 1024 * 1024) {
                $response['message'] = 'Arquivo muito grande (máximo 10MB)';
                echo json_encode($response);
                exit;
            }
            
            // Obter extensão e gerar nome único
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $newFileName = uniqid('attachment_') . '.' . $fileExt;
            
            // Definir pasta de destino e URL
            $uploadDir = '../uploads/chat/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $destination = $uploadDir . $newFileName;
            $mediaUrl = '/RMStream/uploads/chat/' . $newFileName;
            
            // Determinar tipo de mídia
            $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $videoTypes = ['mp4', 'webm', 'ogg', 'mov'];
            
            if (in_array($fileExt, $imageTypes)) {
                $mediaType = 'image';
            } else if (in_array($fileExt, $videoTypes)) {
                $mediaType = 'video';
            } else {
                $mediaType = 'file';
            }
            
            // Mover arquivo para destino
            if (!move_uploaded_file($fileTmpName, $destination)) {
                $response['message'] = 'Erro ao salvar o arquivo';
                echo json_encode($response);
                exit;
            }
        }
        
        if ($type === 'direct') {
            // Verificar se são amigos
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM friendships
                WHERE ((user_id = :userId AND friend_id = :otherId) OR (user_id = :otherId AND friend_id = :userId))
                AND status = 'accepted'
            ");
            $stmt->bindParam(':userId', $userId);
            $stmt->bindParam(':otherId', $id);
            $stmt->execute();
            
            $areFriends = ($stmt->fetchColumn() > 0);
            
            if (!$areFriends) {
                $response['message'] = 'Você não pode enviar mensagens para este usuário';
                echo json_encode($response);
                exit;
            }
            
            // Verificar se a tabela existe
            $stmt = $pdo->prepare("SHOW TABLES LIKE 'messages'");
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                // Criar tabela de mensagens
                $pdo->exec("CREATE TABLE messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    sender_id INT NOT NULL,
                    receiver_id INT NOT NULL,
                    content TEXT,
                    media_type ENUM('image', 'video', 'file') NULL,
                    media_url VARCHAR(255) NULL,
                    is_read BOOLEAN DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
            }
            
            // Enviar mensagem direta
            $stmt = $pdo->prepare("
                INSERT INTO messages (sender_id, receiver_id, content, media_type, media_url)
                VALUES (:senderId, :receiverId, :content, :mediaType, :mediaUrl)
            ");
            $stmt->bindParam(':senderId', $userId);
            $stmt->bindParam(':receiverId', $id);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':mediaType', $mediaType);
            $stmt->bindParam(':mediaUrl', $mediaUrl);
            $stmt->execute();
            
            $messageId = $pdo->lastInsertId();
            
            // Buscar dados do remetente para a resposta
            $stmt = $pdo->prepare("SELECT username, display_name, profile_image FROM users WHERE id = :id");
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            $sender = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $response['success'] = true;
            $response['message'] = 'Mensagem enviada com sucesso';
            $response['messageData'] = [
                'id' => $messageId,
                'sender_id' => $userId,
                'content' => $content,
                'media_type' => $mediaType,
                'media_url' => $mediaUrl,
                'created_at' => date('Y-m-d H:i:s'),
                'username' => $sender['username'],
                'display_name' => $sender['display_name'],
                'profile_image' => $sender['profile_image']
            ];
            
        } else if ($type === 'group') {
            // Verificar se o usuário é membro do grupo
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM group_members
                WHERE group_id = :groupId AND user_id = :userId
            ");
            $stmt->bindParam(':groupId', $id);
            $stmt->bindParam(':userId', $userId);
            $stmt->execute();
            
            $isMember = ($stmt->fetchColumn() > 0);
            
            if (!$isMember) {
                $response['message'] = 'Você não tem permissão para enviar mensagens neste grupo';
                echo json_encode($response);
                exit;
            }
            
            // Verificar se a tabela existe
            $stmt = $pdo->prepare("SHOW TABLES LIKE 'group_messages'");
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                // Criar tabela de mensagens de grupo
                $pdo->exec("CREATE TABLE group_messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    group_id INT NOT NULL,
                    sender_id INT NOT NULL,
                    content TEXT,
                    media_type ENUM('image', 'video', 'file') NULL,
                    media_url VARCHAR(255) NULL,
                    is_read BOOLEAN DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
            }
            
            // Enviar mensagem de grupo
            $stmt = $pdo->prepare("
                INSERT INTO group_messages (group_id, sender_id, content, media_type, media_url)
                VALUES (:groupId, :senderId, :content, :mediaType, :mediaUrl)
            ");
            $stmt->bindParam(':groupId', $id);
            $stmt->bindParam(':senderId', $userId);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':mediaType', $mediaType);
            $stmt->bindParam(':mediaUrl', $mediaUrl);
            $stmt->execute();
            
            $messageId = $pdo->lastInsertId();
            
            // Buscar dados do remetente para a resposta
            $stmt = $pdo->prepare("SELECT username, display_name, profile_image FROM users WHERE id = :id");
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            $sender = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $response['success'] = true;
            $response['message'] = 'Mensagem enviada com sucesso';
            $response['messageData'] = [
                'id' => $messageId,
                'sender_id' => $userId,
                'content' => $content,
                'media_type' => $mediaType,
                'media_url' => $mediaUrl,
                'created_at' => date('Y-m-d H:i:s'),
                'username' => $sender['username'],
                'display_name' => $sender['display_name'],
                'profile_image' => $sender['profile_image']
            ];
        }
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Erro: ' . $e->getMessage();
}

echo json_encode($response);
?>
