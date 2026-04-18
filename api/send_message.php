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
$receiverId = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$attachmentUrl = null;

// Validar dados
if (!$receiverId) {
    echo json_encode(['success' => false, 'message' => 'Destinatário não especificado']);
    exit;
}

if (empty($content) && !isset($_FILES['attachment'])) {
    echo json_encode(['success' => false, 'message' => 'Mensagem vazia']);
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Verificar se o destinatário existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = :id");
    $stmt->bindParam(':id', $receiverId);
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Destinatário não encontrado']);
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
        
        $fileName = uniqid() . '_' . basename($file['name']);
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $attachmentUrl = '/RMStream/public/uploads/chat/' . $fileName;
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao fazer upload do arquivo']);
            exit;
        }
    }
    
    // Inserir mensagem no banco de dados
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content, attachment) VALUES (:sender_id, :receiver_id, :content, :attachment)");
    $stmt->bindParam(':sender_id', $userId);
    $stmt->bindParam(':receiver_id', $receiverId);
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
