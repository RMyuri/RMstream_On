<?php
require_once '../../includes/config.php';

$error = '';
$success = '';

// Se já estiver logado, redireciona
if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/views/index.php');
    exit;
}

// Processar formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare('SELECT id, username, password, role, is_verified, profile_image FROM users WHERE username = :username OR email = :email');
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $username); // Permite login com email ou username
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                if ($user['is_verified']) {
                    // Autenticação bem-sucedida
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['profile_image'] = $user['profile_image'];
                    
                    // Redireciona para a página adequada
                    if ($user['role'] === 'admin') {
                        header('Location: ' . SITE_URL . '/views/admin/dashboard.php');
                    } else {
                        header('Location: ' . SITE_URL . '/views/index.php');
                    }
                    exit;
                } else {
                    $error = 'Sua conta ainda não foi verificada. Por favor, verifique seu email.';
                }
            } else {
                $error = 'Nome de usuário ou senha incorretos.';
            }
        } catch (PDOException $e) {
            $error = 'Erro ao processar login: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/RMStream/public/css/style.css">
    <link rel="stylesheet" href="/RMStream/public/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1 class="auth-logo">RMStream</h1>
            <h2>Login</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="form-group">
                    <label for="username">Usuário ou Email</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Entrar</button>
                </div>
                
                <div class="auth-links">
                    <a href="register.php">Não tem uma conta? Registre-se</a>
                    <a href="forgot-password.php">Esqueceu sua senha?</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
