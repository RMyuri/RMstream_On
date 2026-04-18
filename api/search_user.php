<?php
require_once '../includes/config.php';

// Verificar se o usuário está logado
requireLogin();

// Verificar se foi fornecido um ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de usuário não fornecido'
    ]);
    exit;
}

$userId = (int)$_GET['id'];
$currentUserId = (int)$_SESSION['user_id'];

// Não permitir buscar a si mesmo
if ($userId === $currentUserId) {
    echo json_encode([
        'success' => false,
        'message' => 'Você não pode adicionar a si mesmo como amigo'
    ]);
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Buscar dados do usuário
    $stmt = $pdo->prepare("
        SELECT id, username, display_name, profile_image, banner_image, bio
        FROM users
        WHERE id = :userId
    ");
    $stmt->bindParam(':userId', $userId);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'Usuário não encontrado'
        ]);
        exit;
    }
    
    // Verificar status da amizade
    $stmt = $pdo->prepare("
        SELECT status FROM friendships 
        WHERE (user_id = :currentUserId AND friend_id = :userId) 
           OR (user_id = :userId AND friend_id = :currentUserId)
    ");
    $stmt->bindParam(':currentUserId', $currentUserId);
    $stmt->bindParam(':userId', $userId);
    $stmt->execute();
    
    $friendship = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($friendship) {
        $status = $friendship['status'];
        
        if ($status === 'accepted') {
            $user['friendship_status'] = 'accepted';
        } else {
            // Verificar quem enviou a solicitação
            $stmt = $pdo->prepare("
                SELECT * FROM friendships 
                WHERE user_id = :currentUserId AND friend_id = :userId AND status = 'pending'
            ");
            $stmt->bindParam(':currentUserId', $currentUserId);
            $stmt->bindParam(':userId', $userId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user['friendship_status'] = 'pending_sent';
            } else {
                $user['friendship_status'] = 'pending_received';
            }
        }
    } else {
        $user['friendship_status'] = 'none';
    }
    
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar usuário: ' . $e->getMessage()
    ]);
}
