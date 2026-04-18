<?php
require_once '../includes/config.php';

// Verificar se o usuário está logado
requireLogin();

// Retornar em formato JSON
header('Content-Type: application/json');

// Inicializar resposta
$response = [
    'success' => false,
    'message' => 'Requisição inválida',
    'messages' => []
];

$userId = $_SESSION['user_id'];
$type = isset($_GET['type']) ? $_GET['type'] : 'direct';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sinceId = isset($_GET['since_id']) ? (int)$_GET['since_id'] : 0;

if ($id <= 0) {
    $response['message'] = 'ID inválido';
    echo json_encode($response);
    exit;
}

try {
    $pdo = getDbConnection();
    
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
        
        // Buscar novas mensagens
        $stmt = $pdo->prepare("
            SELECT m.id, m.sender_id, m.content, m.media_type, m.media_url, m.created_at, 
                   u.username, u.display_name, u.profile_image
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE ((m.sender_id = :userId AND m.receiver_id = :otherId) OR (m.sender_id = :otherId AND m.receiver_id = :userId))
            AND m.id > :sinceId
            ORDER BY m.created_at ASC
        ");
        $stmt->bindParam(':userId', $userId);
        $stmt->bindParam(':otherId', $id);
        $stmt->bindParam(':sinceId', $sinceId);
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Marcar mensagens como lidas
        $stmt = $pdo->prepare("
            UPDATE messages
            SET is_read = 1
            WHERE sender_id = :otherId AND receiver_id = :userId AND is_read = 0
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
        
        // Buscar novas mensagens
        $stmt = $pdo->prepare("
            SELECT gm.id, gm.sender_id, gm.content, gm.media_type, gm.media_url, gm.created_at, 
                   u.username, u.display_name, u.profile_image
            FROM group_messages gm
            JOIN users u ON gm.sender_id = u.id
            WHERE gm.group_id = :groupId AND gm.id > :sinceId
            ORDER BY gm.created_at ASC
        ");
        $stmt->bindParam(':groupId', $id);
        $stmt->bindParam(':sinceId', $sinceId);
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Marcar mensagens como lidas
        $stmt = $pdo->prepare("
            UPDATE group_messages
            SET is_read = 1
            WHERE group_id = :groupId AND sender_id != :userId AND is_read = 0
        ");
        $stmt->bindParam(':groupId', $id);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
    }
    
    $response['success'] = true;
    $response['messages'] = $messages;
    
} catch (PDOException $e) {
    $response['message'] = 'Erro: ' . $e->getMessage();
}

echo json_encode($response);
?>
