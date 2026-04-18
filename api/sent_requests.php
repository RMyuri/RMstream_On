<?php
require_once '../includes/config.php';

// Verificar se o usuário está logado
requireLogin();

$userId = $_SESSION['user_id'];

try {
    $pdo = getDbConnection();
    
    // Buscar solicitações de amizade enviadas
    $stmt = $pdo->prepare("
        SELECT f.id, f.user_id, f.friend_id, f.status, f.created_at,
               u.username, u.display_name, u.profile_image
        FROM friendships f
        JOIN users u ON f.friend_id = u.id
        WHERE f.user_id = :user_id AND f.status = 'pending'
        ORDER BY f.created_at DESC
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'requests' => $requests
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar solicitações: ' . $e->getMessage()
    ]);
}
