<?php
require_once '../includes/config.php';

// Verificar se o usuário está logado
requireLogin();

// Retornar resposta como JSON
header('Content-Type: application/json');

// Inicializar resposta
$response = [
    'success' => false,
    'message' => 'Requisição inválida'
];

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode($response);
    exit;
}

// Obter parâmetros
$action = isset($_POST['action']) ? $_POST['action'] : '';
$targetUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$currentUserId = $_SESSION['user_id'];

// Validar parâmetros
if (empty($action) || $targetUserId <= 0 || $targetUserId === $currentUserId) {
    $response['message'] = 'Parâmetros inválidos';
    echo json_encode($response);
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Verificar se a tabela existe
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'friendships'");
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // Criar tabela de amizades
        $pdo->exec("CREATE TABLE friendships (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            friend_id INT NOT NULL,
            status ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_friendship (user_id, friend_id)
        )");
    }
    
    // Verificar se o usuário alvo existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = :id");
    $stmt->bindParam(':id', $targetUserId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        $response['message'] = 'Usuário não encontrado';
        echo json_encode($response);
        exit;
    }
    
    // Verificar relacionamento existente
    $stmt = $pdo->prepare("
        SELECT id, user_id, friend_id, status 
        FROM friendships 
        WHERE (user_id = :currentUserId AND friend_id = :targetUserId) 
           OR (user_id = :targetUserId AND friend_id = :currentUserId)
    ");
    $stmt->bindParam(':currentUserId', $currentUserId);
    $stmt->bindParam(':targetUserId', $targetUserId);
    $stmt->execute();
    
    $existingFriendship = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Processar a ação solicitada
    switch ($action) {
        case 'send_request':
            if ($existingFriendship) {
                if ($existingFriendship['status'] === 'accepted') {
                    $response['message'] = 'Vocês já são amigos';
                } elseif ($existingFriendship['status'] === 'pending') {
                    if ($existingFriendship['user_id'] == $currentUserId) {
                        $response['message'] = 'Solicitação já enviada';
                    } else {
                        // Se recebeu uma solicitação, aceita automaticamente
                        $stmt = $pdo->prepare("
                            UPDATE friendships 
                            SET status = 'accepted' 
                            WHERE id = :id
                        ");
                        $stmt->bindParam(':id', $existingFriendship['id']);
                        $stmt->execute();
                        
                        $response['success'] = true;
                        $response['message'] = 'Amizade aceita';
                    }
                } else {
                    // Reativar amizade rejeitada
                    $stmt = $pdo->prepare("
                        UPDATE friendships 
                        SET status = 'pending', user_id = :currentUserId, friend_id = :targetUserId 
                        WHERE id = :id
                    ");
                    $stmt->bindParam(':id', $existingFriendship['id']);
                    $stmt->bindParam(':currentUserId', $currentUserId);
                    $stmt->bindParam(':targetUserId', $targetUserId);
                    $stmt->execute();
                    
                    $response['success'] = true;
                    $response['message'] = 'Solicitação enviada';
                }
            } else {
                // Criar nova solicitação
                $stmt = $pdo->prepare("
                    INSERT INTO friendships (user_id, friend_id, status) 
                    VALUES (:currentUserId, :targetUserId, 'pending')
                ");
                $stmt->bindParam(':currentUserId', $currentUserId);
                $stmt->bindParam(':targetUserId', $targetUserId);
                $stmt->execute();
                
                $response['success'] = true;
                $response['message'] = 'Solicitação enviada';
                
                // TODO: Enviar notificação para o usuário alvo
            }
            break;
            
        case 'accept_request':
            if (!$existingFriendship || $existingFriendship['status'] !== 'pending') {
                $response['message'] = 'Não há solicitação pendente';
            } elseif ($existingFriendship['friend_id'] != $currentUserId) {
                $response['message'] = 'Você não pode aceitar esta solicitação';
            } else {
                // Aceitar solicitação
                $stmt = $pdo->prepare("
                    UPDATE friendships 
                    SET status = 'accepted' 
                    WHERE id = :id
                ");
                $stmt->bindParam(':id', $existingFriendship['id']);
                $stmt->execute();
                
                $response['success'] = true;
                $response['message'] = 'Amizade aceita';
                
                // TODO: Enviar notificação para o usuário que enviou a solicitação
            }
            break;
            
        case 'reject_request':
            if (!$existingFriendship || $existingFriendship['status'] !== 'pending') {
                $response['message'] = 'Não há solicitação pendente';
            } elseif ($existingFriendship['friend_id'] != $currentUserId) {
                $response['message'] = 'Você não pode rejeitar esta solicitação';
            } else {
                // Rejeitar solicitação
                $stmt = $pdo->prepare("
                    UPDATE friendships 
                    SET status = 'rejected' 
                    WHERE id = :id
                ");
                $stmt->bindParam(':id', $existingFriendship['id']);
                $stmt->execute();
                
                $response['success'] = true;
                $response['message'] = 'Solicitação rejeitada';
            }
            break;
            
        case 'remove_friend':
            if (!$existingFriendship || $existingFriendship['status'] !== 'accepted') {
                $response['message'] = 'Vocês não são amigos';
            } else {
                // Remover amizade
                $stmt = $pdo->prepare("
                    DELETE FROM friendships 
                    WHERE id = :id
                ");
                $stmt->bindParam(':id', $existingFriendship['id']);
                $stmt->execute();
                
                $response['success'] = true;
                $response['message'] = 'Amizade removida';
            }
            break;
            
        default:
            $response['message'] = 'Ação inválida';
    }
} catch (PDOException $e) {
    $response['message'] = 'Erro: ' . $e->getMessage();
}

echo json_encode($response);
?>
            $stmt->bindParam(':friend_id', $targetUserId);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                $response['message'] = 'Solicitação de amizade não encontrada';
            } else {
                // Excluir a solicitação
                $stmt = $pdo->prepare("
                    DELETE FROM friendships 
                    WHERE user_id = :user_id AND friend_id = :friend_id AND status = 'pending'
                ");
                $stmt->bindParam(':user_id', $currentUserId);
                $stmt->bindParam(':friend_id', $targetUserId);
                $stmt->execute();
                
                $response['success'] = true;
                $response['message'] = 'Solicitação de amizade cancelada';
            }
            break;
            
        case 'unfriend':
            // Remover amizade
            $stmt = $pdo->prepare("
                DELETE FROM friendships 
                WHERE (user_id = :user1 AND friend_id = :user2) 
                OR (user_id = :user2 AND friend_id = :user1)
            ");
            $stmt->bindParam(':user1', $currentUserId);
            $stmt->bindParam(':user2', $targetUserId);
            $stmt->execute();
            
            $response['success'] = true;
            $response['message'] = 'Amizade removida com sucesso';
            break;
            
        case 'block':
            // Bloquear usuário
            $stmt = $pdo->prepare("
                DELETE FROM friendships 
                WHERE (user_id = :user1 AND friend_id = :user2) 
                OR (user_id = :user2 AND friend_id = :user1)
            ");
            $stmt->bindParam(':user1', $currentUserId);
            $stmt->bindParam(':user2', $targetUserId);
            $stmt->execute();
            
            // Criar relação de bloqueio
            $stmt = $pdo->prepare("
                INSERT INTO friendships (user_id, friend_id, status) 
                VALUES (:user_id, :friend_id, 'blocked')
            ");
            $stmt->bindParam(':user_id', $currentUserId);
            $stmt->bindParam(':friend_id', $targetUserId);
            $stmt->execute();
            
            $response['success'] = true;
            $response['message'] = 'Usuário bloqueado com sucesso';
            break;
            
        case 'unblock':
            // Desbloquear usuário
            $stmt = $pdo->prepare("
                DELETE FROM friendships 
                WHERE user_id = :user_id AND friend_id = :friend_id AND status = 'blocked'
            ");
            $stmt->bindParam(':user_id', $currentUserId);
            $stmt->bindParam(':friend_id', $targetUserId);
            $stmt->execute();
            
            $response['success'] = true;
            $response['message'] = 'Usuário desbloqueado com sucesso';
            break;
            
        default:
            $response['message'] = 'Ação não reconhecida';
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Erro ao processar solicitação: ' . $e->getMessage();
}

echo json_encode($response);

// Função para adicionar notificação
function addNotification($pdo, $userId, $triggerUserId, $type, $message) {
    // Verificar se a tabela de notificações existe
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'notifications'");
    $stmt->execute();
    if ($stmt->rowCount() === 0) {
        // Criar tabela de notificações
        $pdo->exec("CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            trigger_user_id INT,
            type VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    // Inserir notificação
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, trigger_user_id, type, message) 
        VALUES (:user_id, :trigger_user_id, :type, :message)
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':trigger_user_id', $triggerUserId);
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':message', $message);
    $stmt->execute();
}
?>
