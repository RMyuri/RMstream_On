<?php
require_once '../../includes/config.php';

// Verifica se o usuário está logado e é admin
requireAdmin();

// Buscar estatísticas
try {
    $pdo = getDbConnection();
    
    // Total de usuários
    $stmt = $pdo->query('SELECT COUNT(*) FROM users');
    $totalUsers = $stmt->fetchColumn();
    
    // Usuários verificados
    $stmt = $pdo->query('SELECT COUNT(*) FROM users WHERE is_verified = 1');
    $verifiedUsers = $stmt->fetchColumn();
    
    // Usuários administradores
    $stmt = $pdo->query('SELECT COUNT(*) FROM users WHERE role = "admin"');
    $admins = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $error = 'Erro ao buscar dados: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel de Administração - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/RMStream/public/css/style.css">
    <link rel="stylesheet" href="/RMStream/public/css/admin.css">
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
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="users.php">Gerenciar Usuários</a></li>
                <li><a href="settings.php">Configurações</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <h1>Dashboard de Administração</h1>
            
            <div class="admin-cards">
                <div class="admin-card">
                    <h3>Total de Usuários</h3>
                    <div class="admin-card-value"><?php echo $totalUsers; ?></div>
                </div>
                
                <div class="admin-card">
                    <h3>Usuários Verificados</h3>
                    <div class="admin-card-value"><?php echo $verifiedUsers; ?></div>
                </div>
                
                <div class="admin-card">
                    <h3>Administradores</h3>
                    <div class="admin-card-value"><?php echo $admins; ?></div>
                </div>
            </div>
            
            <div class="admin-welcome">
                <h2>Bem-vindo, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
                <p>Este é o painel de administração do RMStream. Aqui você pode gerenciar usuários, configurações e outras funcionalidades administrativas.</p>
            </div>
        </div>
    </div>
</body>
</html>
