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
    'notifications' => []
];

$userId = $_SESSION['user_id'];

try {
    $pdo = getDbConnection();
    
    // Verificar se a tabela existe
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'notifications'");
    $stmt->execute();
    if ($stmt->rowCount() === 0) {
        // Criar tabela
        $pdo->exec("CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            trigger_user_id INT,
            type VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            reference_id INT,
            reference_type VARCHAR(50),
            is_read BOOLEAN DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    // Processar ação se for POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        
        if ($action === 'mark_read') {
            // Marcar uma notificação específica como lida
            $notificationId = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
            
            if ($notificationId > 0) {
                $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id");
                $stmt->bindParam(':id', $notificationId);
                $stmt->bindParam(':user_id', $userId);
                $stmt->execute();
                
                $response['success'] = true;
                $response['message'] = 'Notificação marcada como lida';
            }
        } else if ($action === 'mark_all_read') {
            // Marcar todas as notificações como lidas
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0");
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $response['success'] = true;
            $response['message'] = 'Todas as notificações marcadas como lidas';
        }
    } else {
        // GET: Retornar notificações do usuário
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $unreadOnly = isset($_GET['unread_only']) ? (bool)$_GET['unread_only'] : false;
        
        // Construir query
        $sql = "
            SELECT n.*, u.username, u.display_name, u.profile_image
            FROM notifications n
            LEFT JOIN users u ON n.trigger_user_id = u.id
            WHERE n.user_id = :user_id
        ";
        
        if ($unreadOnly) {
            $sql .= " AND n.is_read = 0";
        }
        
        $sql .= " ORDER BY n.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Contar notificações não lidas
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $unreadCount = $stmt->fetchColumn();
        
        $response['success'] = true;
        $response['notifications'] = $notifications;
        $response['unread_count'] = $unreadCount;
        $response['has_more'] = count($notifications) === $limit;
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Erro: ' . $e->getMessage();
}

echo json_encode($response);
?>
            'data' => null,
            'is_read' => true,
            'created_at' => date('Y-m-d H:i:s', time() - 86400) // 1 dia atrás
        ];
        
        $notifications = $dummyNotifications;
        $unreadNotifications = $pendingFriendRequests + $unreadMessages;
    }
    
    // Preparar cada notificação para retorno JSON
    foreach ($notifications as &$notification) {
        // Decodificar o campo data se não for nulo
        if (!empty($notification['data']) && is_string($notification['data'])) {
            $notification['data'] = json_decode($notification['data'], true);
        }
        
        // Converter is_read para booleano
        $notification['is_read'] = (bool)$notification['is_read'];
    }
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unreadNotifications
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar notificações: ' . $e->getMessage()
    ]);
}
        }
        
    } catch (Exception $e) {
        $response = [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Adicionar função para criar notificação (a ser chamada de outros arquivos)
function createNotification($userId, $type, $title, $message, $url = null, $senderId = null) {
    try {
        $pdo = getDbConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO notifications 
            (user_id, type, title, message, url, sender_id)
            VALUES (:user_id, :type, :title, :message, :url, :sender_id)
        ");
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':url', $url);
        $stmt->bindParam(':sender_id', $senderId);
        
        return $stmt->execute();
        
    } catch (Exception $e) {
        error_log('Erro ao criar notificação: ' . $e->getMessage());
        return false;
    }
}

// Retornar resposta em formato JSON
header('Content-Type: application/json');
echo json_encode($response);
