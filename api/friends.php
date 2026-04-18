<?php
require_once '../includes/config.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $pdo = getDbConnection();
    
    // Buscar amigos do usuário
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.display_name, u.profile_image
        FROM friendships f
        JOIN users u ON (f.user_id = u.id AND f.user_id != :user_id) 
                      OR (f.friend_id = u.id AND f.friend_id != :user_id)
        WHERE (f.user_id = :user_id OR f.friend_id = :user_id) 
              AND f.status = 'accepted'
        ORDER BY u.display_name, u.username
    ");
    
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'friends' => $friends]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar amigos: ' . $e->getMessage()]);
}
?>
