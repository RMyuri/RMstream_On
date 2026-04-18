<?php
require_once '../includes/config.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];
$groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

// Validar dados
if (!$groupId) {
    echo json_encode(['success' => false, 'message' => 'Grupo não especificado']);
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Verificar se o usuário é membro do grupo
    $stmt = $pdo->prepare("
        SELECT * FROM group_members 
        WHERE group_id = :group_id AND user_id = :user_id
    ");
    $stmt->bindParam(':group_id', $groupId);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Você não é membro deste grupo']);
        exit;
    }
    
    // Buscar novas mensagens
    $stmt = $pdo->prepare("
        SELECT gm.id, gm.group_id, gm.sender_id, gm.content, gm.attachment, gm.created_at,
               u.username, u.display_name, u.profile_image
        FROM group_messages gm
        JOIN users u ON gm.sender_id = u.id
        WHERE gm.group_id = :group_id AND gm.id > :last_id
        ORDER BY gm.created_at ASC
    ");
    
    $stmt->bindParam(':group_id', $groupId);
    $stmt->bindParam(':last_id', $lastId);
    $stmt->execute();
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'messages' => $messages]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar mensagens: ' . $e->getMessage()]);
}
?>
