<?php
require_once '../../includes/config.php';

$error = '';
$success = '';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Token de verificação inválido.';
} else {
    try {
        $pdo = getDbConnection();
        
        // Buscar usuário com o token de verificação
        $stmt = $pdo->prepare('SELECT id, is_verified FROM users WHERE verification_token = :token');
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $error = 'Token de verificação inválido ou expirado.';
        } elseif ($user['is_verified']) {
            $success = 'Conta já verificada. Você pode fazer login agora.';
        } else {
            // Ativar a conta
            $stmt = $pdo->prepare('UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = :id');
            $stmt->bindParam(':id', $user['id']);
            $stmt->execute();
            
            $success = 'Sua conta foi verificada com sucesso! Agora você pode fazer login.';
        }
    } catch (PDOException $e) {
        $error = 'Erro ao processar verificação: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Verificação de Conta - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/RMStream/public/css/style.css">
    <link rel="stylesheet" href="/RMStream/public/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1 class="auth-logo">RMStream</h1>
            <h2>Verificação de Conta</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="auth-links">
                <a href="login.php">Ir para a página de login</a>
            </div>
        </div>
    </div>
</body>
</html>
