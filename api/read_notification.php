<?php
require_once '../includes/config.php';

// Verificar se o usuário está logado
requireLogin();

// Verificar se o ID da notificação foi enviado
if (!isset($_POST['notification_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID da notificação não fornecido'
    ]);
    exit;
}

$notificationId = $_POST['notification_id'];
$userId = $_SESSION['user_id'];

try {
    $pdo = getDbConnection();
    
    // Verificar se a notificação existe e pertence ao usuário
    $stmt = $pdo->prepare("
        SELECT id FROM notifications 
        WHERE id = :notification_id AND user_id = :user_id
    ");
    $stmt->bindParam(':notification_id', $notificationId);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // Para desenvolvimento, também verificamos IDs temporários
        if (strpos($notificationId, 'friend_') === 0 || strpos($notificationId, 'msg_') === 0) {
            echo json_encode([
                'success' => true
            ]);
            exit;
        }
        
        echo json_encode([
            'success' => false,
            'message' => 'Notificação não encontrada ou não pertence ao usuário'
        ]);
        exit;
    }
    
    // Marcar notificação como lida
    $stmt = $pdo->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE id = :notification_id
    ");
    $stmt->bindParam(':notification_id', $notificationId);
    $stmt->execute();
    
    echo json_encode([
        'success' => true
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao marcar notificação como lida: ' . $e->getMessage()
    ]);
}
