<?php
require_once '../includes/config.php';

// Verificar se o usuário está logado
requireLogin();

$userId = $_SESSION['user_id'];

try {
    $pdo = getDbConnection();
    
    // Marcar todas as notificações como lidas
    $stmt = $pdo->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE user_id = :user_id AND is_read = 0
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    echo json_encode([
        'success' => true
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao marcar notificações como lidas: ' . $e->getMessage()
    ]);
}
