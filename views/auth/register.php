<?php
require_once '../../includes/config.php';

$error = '';
$success = '';

// Se já estiver logado, redireciona
if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/views/index.php');
    exit;
}

// Processar formulário de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Por favor, preencha todos os campos.';
    } elseif ($password !== $confirmPassword) {
        $error = 'As senhas não coincidem.';
    } elseif (strlen($password) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, insira um email válido.';
    } else {
        try {
            $pdo = getDbConnection();
            
            // Verificar se o usuário ou email já existe
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username OR email = :email');
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                $error = 'Nome de usuário ou email já está em uso.';
            } else {
                // Gerar token de verificação
                $verificationToken = bin2hex(random_bytes(32));
                
                // Hash da senha
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Inserir novo usuário
                $stmt = $pdo->prepare('INSERT INTO users (username, email, password, verification_token) VALUES (:username, :email, :password, :token)');
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashedPassword);
                $stmt->bindParam(':token', $verificationToken);
                $stmt->execute();
                
                // Enviar email de verificação (simplificado para este exemplo)
                $verificationLink = SITE_URL . '/views/auth/verify.php?token=' . $verificationToken;
                
                // Em um projeto real, você enviaria um email com o link
                // mail($email, 'Verifique sua conta', "Clique no link para verificar sua conta: $verificationLink");
                
                $success = 'Registro concluído! Para simular a verificação por email, use este link: <a href="' . $verificationLink . '">Verificar conta</a>';
            }
        } catch (PDOException $e) {
            $error = 'Erro ao processar registro: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Registrar - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/RMStream/public/css/style.css">
    <link rel="stylesheet" href="/RMStream/public/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1 class="auth-logo">RMStream</h1>
            <h2>Criar Conta</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php else: ?>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="form-group">
                    <label for="username">Nome de Usuário</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmar Senha</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Registrar</button>
                </div>
                
                <div class="auth-links">
                    <a href="login.php">Já tem uma conta? Faça login</a>
                </div>
            </form>
            
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
