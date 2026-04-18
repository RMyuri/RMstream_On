<?php
require_once '../../includes/config.php';

// Verifica se o usuário está logado e é admin
requireAdmin();

$message = '';
$error = '';

// Processar ações
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $userId = (int)$_GET['id'];
    
    try {
        $pdo = getDbConnection();
        
        switch ($action) {
            case 'verify':
                $stmt = $pdo->prepare('UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = :id');
                $stmt->bindParam(':id', $userId);
                $stmt->execute();
                $message = 'Usuário verificado com sucesso!';
                break;
                
            case 'unverify':
                $stmt = $pdo->prepare('UPDATE users SET is_verified = 0 WHERE id = :id');
                $stmt->bindParam(':id', $userId);
                $stmt->execute();
                $message = 'Verificação do usuário removida!';
                break;
                
            case 'delete':
                // Certifique-se de que o admin não está tentando excluir sua própria conta
                if ($userId != $_SESSION['user_id']) {
                    $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
                    $stmt->bindParam(':id', $userId);
                    $stmt->execute();
                    $message = 'Usuário excluído com sucesso!';
                } else {
                    $error = 'Você não pode excluir sua própria conta!';
                }
                break;
                
            case 'make_admin':
                $stmt = $pdo->prepare('UPDATE users SET role = "admin" WHERE id = :id');
                $stmt->bindParam(':id', $userId);
                $stmt->execute();
                $message = 'Usuário promovido a administrador!';
                break;
                
            case 'remove_admin':
                // Certifique-se de que o admin não está tentando rebaixar a si mesmo
                if ($userId != $_SESSION['user_id']) {
                    $stmt = $pdo->prepare('UPDATE users SET role = "user" WHERE id = :id');
                    $stmt->bindParam(':id', $userId);
                    $stmt->execute();
                    $message = 'Privilégios de administrador removidos!';
                } else {
                    $error = 'Você não pode remover seus próprios privilégios!';
                }
                break;
        }
    } catch (PDOException $e) {
        $error = 'Erro ao processar ação: ' . $e->getMessage();
    }
}

// Buscar lista de usuários
try {
    $pdo = getDbConnection();
    $stmt = $pdo->query('SELECT id, username, email, role, is_verified, created_at FROM users ORDER BY created_at DESC');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Erro ao buscar usuários: ' . $e->getMessage();
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Usuários - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/RMStream/public/css/style.css">
    <link rel="stylesheet" href="/RMStream/public/css/admin.css">
    <style>
        .action-links {
            display: flex;
            gap: 8px;
        }
        .action-links a {
            color: #1db954;
            text-decoration: none;
            font-size: 0.9em;
        }
        .action-links a:hover {
            text-decoration: underline;
        }
        .action-links .delete {
            color: #ff5555;
        }
        .admin-alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 24px;
        }
        .admin-alert-success {
            background: rgba(29, 185, 84, 0.1);
            color: #1db954;
            border: 1px solid rgba(29, 185, 84, 0.3);
        }
        .admin-alert-error {
            background: rgba(229, 57, 53, 0.1);
            color: #ff5555;
            border: 1px solid rgba(229, 57, 53, 0.3);
        }
    </style>
</head>
<body>
    <nav>
        <a href="/RMStream/views/index.php" class="logo">RMStream</a>
        <div class="nav-container">
            <div class="nav-item">
                <a href="/RMStream/views/index.php" class="nav-link">Início</a>
            </div>
            <div class="nav-item">
                <a href="/RMStream/views/admin/dashboard.php" class="nav-link active">Admin</a>
            </div>
            <div class="nav-item">
                <a href="/RMStream/views/auth/logout.php" class="nav-link">Sair</a>
            </div>
        </div>
    </nav>
    
    <div class="admin-container">
        <div class="admin-sidebar">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="users.php" class="active">Gerenciar Usuários</a></li>
                <li><a href="settings.php">Configurações</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <h1>Gerenciar Usuários</h1>
            
            <?php if ($message): ?>
                <div class="admin-alert admin-alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="admin-alert admin-alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuário</th>
                        <th>Email</th>
                        <th>Função</th>
                        <th>Verificado</th>
                        <th>Data de Registro</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo $user['role'] === 'admin' ? 'Administrador' : 'Usuário'; ?></td>
                        <td><?php echo $user['is_verified'] ? 'Sim' : 'Não'; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                        <td>
                            <div class="action-links">
                                <?php if ($user['is_verified']): ?>
                                    <a href="?action=unverify&id=<?php echo $user['id']; ?>">Desverificar</a>
                                <?php else: ?>
                                    <a href="?action=verify&id=<?php echo $user['id']; ?>">Verificar</a>
                                <?php endif; ?>
                                
                                <?php if ($user['role'] === 'admin'): ?>
                                    <a href="?action=remove_admin&id=<?php echo $user['id']; ?>">Remover Admin</a>
                                <?php else: ?>
                                    <a href="?action=make_admin&id=<?php echo $user['id']; ?>">Tornar Admin</a>
                                <?php endif; ?>
                                
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <a href="?action=delete&id=<?php echo $user['id']; ?>" class="delete" onclick="return confirm('Tem certeza que deseja excluir este usuário?')">Excluir</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">Nenhum usuário encontrado.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
