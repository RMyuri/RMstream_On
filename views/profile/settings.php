<?php
require_once '../../includes/config.php';

// Verificar se o usuário está logado
requireLogin();

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Obter dados atuais do usuário
try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Erro ao buscar dados do usuário: ' . $e->getMessage();
}

// Processar alteração de e-mail
if (isset($_POST['update_email'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['current_password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor, preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, insira um e-mail válido.';
    } else {
        try {
            // Verificar senha atual
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($password, $userData['password'])) {
                $error = 'Senha atual incorreta.';
            } else {
                // Verificar se o e-mail já está em uso
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email AND id != :id");
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':id', $userId);
                $stmt->execute();
                
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Este e-mail já está em uso por outro usuário.';
                } else {
                    // Atualizar e-mail
                    $stmt = $pdo->prepare("UPDATE users SET email = :email WHERE id = :id");
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':id', $userId);
                    $stmt->execute();
                    
                    $success = 'E-mail atualizado com sucesso!';
                    $user['email'] = $email;
                }
            }
        } catch (PDOException $e) {
            $error = 'Erro ao atualizar e-mail: ' . $e->getMessage();
        }
    }
}

// Processar alteração de senha
if (isset($_POST['update_password'])) {
    $currentPassword = $_POST['current_password_for_pw'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Por favor, preencha todos os campos.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'As senhas não coincidem.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'A nova senha deve ter pelo menos 6 caracteres.';
    } else {
        try {
            // Verificar senha atual
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($currentPassword, $userData['password'])) {
                $error = 'Senha atual incorreta.';
            } else {
                // Atualizar senha
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmt->bindParam(':password', $hashedPassword);
                $stmt->bindParam(':id', $userId);
                $stmt->execute();
                
                $success = 'Senha atualizada com sucesso!';
            }
        } catch (PDOException $e) {
            $error = 'Erro ao atualizar senha: ' . $e->getMessage();
        }
    }
}

// Processar exclusão de conta
if (isset($_POST['delete_account'])) {
    $password = $_POST['password_for_delete'] ?? '';
    
    if (empty($password)) {
        $error = 'Por favor, digite sua senha para confirmar a exclusão.';
    } else {
        try {
            // Verificar senha
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($password, $userData['password'])) {
                $error = 'Senha incorreta.';
            } else {
                // Excluir conta
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
                $stmt->bindParam(':id', $userId);
                $stmt->execute();
                
                // Encerrar sessão
                session_unset();
                session_destroy();
                
                // Redirecionar para a página inicial
                header('Location: ' . SITE_URL . '/views/auth/login.php?deleted=1');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Erro ao excluir conta: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Configurações da Conta - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/RMStream/public/css/style.css">
    <link rel="stylesheet" href="/RMStream/public/css/nav.css">
    <link rel="stylesheet" href="/RMStream/public/css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav>
        <a href="/RMStream/views/index.php" class="logo">RMStream</a>
        <div class="nav-container">
            <div class="nav-item">
                <a href="/RMStream/views/index.php" class="nav-link">Início</a>
            </div>
            <div class="nav-item nav-item-with-submenu">
                <a href="/RMStream/views/room.php" class="nav-link">Room</a>
                <div class="submenu">
                    <a href="#" class="submenu-item create-room">Criar Sala</a>
                    <a href="#" class="submenu-item join-room">Entrar em Sala</a>
                </div>
            </div>
            <div class="nav-item">
                <a href="/RMStream/views/chat/index.php" class="nav-link">Chat</a>
            </div>
            <div class="nav-item">
                <a href="/RMStream/views/profile/index.php" class="nav-link active">Perfil</a>
            </div>
        </div>
        <form id="navSearchForm" class="nav-search" action="/RMStream/views/search.php" method="get">
            <input type="text" name="q" id="navSearchInput" placeholder="Pesquisar no YouTube...">
            <button type="submit">Pesquisar</button>
        </form>
    </nav>
    
    <div class="profile-container">
        <h1>Configurações da Conta</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="tabs-container">
            <div class="tabs-nav">
                <div class="tab-item active" data-tab="account">Conta</div>
                <div class="tab-item" data-tab="security">Segurança</div>
                <div class="tab-item" data-tab="danger">Zona de Perigo</div>
            </div>
            
            <div class="tab-content">
                <!-- Aba de Conta -->
                <div class="tab-panel active" id="tab-account">
                    <form method="post">
                        <div class="form-group">
                            <label for="username">Nome de usuário</label>
                            <input type="text" id="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            <small>O nome de usuário não pode ser alterado.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">E-mail</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="current_password">Senha atual (para confirmar a alteração)</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="update_email" class="profile-btn profile-btn-primary">Atualizar E-mail</button>
                        </div>
                    </form>
                </div>
                
                <!-- Aba de Segurança -->
                <div class="tab-panel" id="tab-security">
                    <form method="post">
                        <div class="form-group">
                            <label for="current_password_for_pw">Senha atual</label>
                            <input type="password" id="current_password_for_pw" name="current_password_for_pw" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Nova senha</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirmar nova senha</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="update_password" class="profile-btn profile-btn-primary">Atualizar Senha</button>
                        </div>
                    </form>
                </div>
                
                <!-- Aba de Zona de Perigo -->
                <div class="tab-panel" id="tab-danger">
                    <div class="alert alert-danger">
                        <strong>Atenção!</strong> A exclusão da conta é permanente e irreversível. Todos os seus dados serão excluídos.
                    </div>
                    
                    <form method="post" onsubmit="return confirm('ATENÇÃO: Esta ação é irreversível! Tem certeza que deseja excluir sua conta permanentemente?');">
                        <div class="form-group">
                            <label for="password_for_delete">Digite sua senha para confirmar a exclusão</label>
                            <input type="password" id="password_for_delete" name="password_for_delete" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="delete_account" class="profile-btn profile-btn-danger">
                                <i class="fas fa-trash-alt"></i> Excluir minha conta permanentemente
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/RMStream/public/js/nav.js"></script>
    <script>
        // Script para as abas
        const tabItems = document.querySelectorAll('.tab-item');
        const tabPanels = document.querySelectorAll('.tab-panel');
        
        tabItems.forEach(item => {
            item.addEventListener('click', function() {
                // Remover classe active de todas as abas
                tabItems.forEach(i => i.classList.remove('active'));
                tabPanels.forEach(p => p.classList.remove('active'));
                
                // Adicionar classe active na aba clicada
                this.classList.add('active');
                
                // Mostrar painel correspondente
                const tabId = this.getAttribute('data-tab');
                document.getElementById('tab-' + tabId).classList.add('active');
            });
        });
    </script>
</body>
</html>
