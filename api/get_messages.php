<?php
require_once '../includes/config.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];
$otherUserId = isset($_GET['user']) ? (int)$_GET['user'] : 0;
$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

// Validar dados
if (!$otherUserId) {
    echo json_encode(['success' => false, 'message' => 'Usuário não especificado']);
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Buscar novas mensagens
    $stmt = $pdo->prepare("
        SELECT m.id, m.sender_id, m.receiver_id, m.content, m.attachment, m.is_read, m.created_at
        FROM messages m
        WHERE ((m.sender_id = :user_id AND m.receiver_id = :other_user_id) 
            OR (m.sender_id = :other_user_id AND m.receiver_id = :user_id))
            AND m.id > :last_id
        ORDER BY m.created_at ASC
    ");
    
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':other_user_id', $otherUserId);
    $stmt->bindParam(':last_id', $lastId);
    $stmt->execute();
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Marcar mensagens como lidas
    if (!empty($messages)) {
        $stmt = $pdo->prepare("
            UPDATE messages 
            SET is_read = 1 
            WHERE sender_id = :other_user_id AND receiver_id = :user_id AND is_read = 0
        ");
        $stmt->bindParam(':other_user_id', $otherUserId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
    }
    
    echo json_encode(['success' => true, 'messages' => $messages]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar mensagens: ' . $e->getMessage()]);
}
?>
