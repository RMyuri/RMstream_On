<?php
require_once '../includes/config.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Verificar se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$userId = $_SESSION['user_id'];
$groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$attachmentUrl = null;

// Validar dados
if (!$groupId) {
    echo json_encode(['success' => false, 'message' => 'Grupo não especificado']);
    exit;
}

if (empty($content) && !isset($_FILES['attachment'])) {
    echo json_encode(['success' => false, 'message' => 'Mensagem vazia']);
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
    
    // Processar upload de arquivo, se houver
    if (isset($_FILES['attachment']) && $_FILES['attachment']['size'] > 0) {
        $file = $_FILES['attachment'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Tipo de arquivo não permitido']);
            exit;
        }
        
        if ($file['size'] > $maxFileSize) {
            echo json_encode(['success' => false, 'message' => 'Arquivo muito grande (máximo 5MB)']);
            exit;
        }
        
        // Criar diretório para uploads se não existir
        $uploadDir = '../public/uploads/chat/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = 'group_' . $groupId . '_' . uniqid() . '_' . basename($file['name']);
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $attachmentUrl = '/RMStream/public/uploads/chat/' . $fileName;
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao fazer upload do arquivo']);
            exit;
        }
    }
    
    // Inserir mensagem no banco de dados
    $stmt = $pdo->prepare("
        INSERT INTO group_messages (group_id, sender_id, content, attachment)
        VALUES (:group_id, :sender_id, :content, :attachment)
    ");
    $stmt->bindParam(':group_id', $groupId);
    $stmt->bindParam(':sender_id', $userId);
    $stmt->bindParam(':content', $content);
    $stmt->bindParam(':attachment', $attachmentUrl);
    $stmt->execute();
    
    $messageId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Mensagem enviada com sucesso', 
        'message_id' => $messageId,
        'attachment_url' => $attachmentUrl
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao enviar mensagem: ' . $e->getMessage()]);
}
?>
