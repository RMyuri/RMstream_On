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
    'friends' => []
];

$userId = $_SESSION['user_id'];

try {
    $pdo = getDbConnection();
    
    // Buscar amigos do usuário
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
    
    $response['success'] = true;
    $response['friends'] = $friends;
    
} catch (PDOException $e) {
    $response['message'] = 'Erro ao buscar amigos: ' . $e->getMessage();
}

echo json_encode($response);
?>
