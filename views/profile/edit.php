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
    $stmt = $pdo->prepare("SELECT username, email, display_name, bio, profile_image, banner_image, phone 
                           FROM users WHERE id = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Erro ao buscar dados do usuário: ' . $e->getMessage();
}

// Processar envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $displayName = trim($_POST['display_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    try {
        // Processar uploads de imagem
        $profileImage = $user['profile_image'];
        $bannerImage = $user['banner_image'];
        
        // Upload de foto de perfil
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['size'] > 0) {
            $uploadDir = '../../public/uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileExtension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                $error = 'Formato de imagem de perfil inválido. Use JPG, PNG ou GIF.';
            } else {
                $newFilename = $userId . '_profile_' . time() . '.' . $fileExtension;
                $targetFile = $uploadDir . $newFilename;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
                    $profileImage = '/RMStream/public/uploads/profiles/' . $newFilename;
                } else {
                    $error = 'Erro ao fazer upload da imagem de perfil.';
                }
            }
        }
        
        // Upload de imagem de capa
        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['size'] > 0) {
            $uploadDir = '../../public/uploads/banners/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileExtension = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                $error = 'Formato de imagem de capa inválido. Use JPG, PNG ou GIF.';
            } else {
                $newFilename = $userId . '_banner_' . time() . '.' . $fileExtension;
                $targetFile = $uploadDir . $newFilename;
                
                if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $targetFile)) {
                    $bannerImage = '/RMStream/public/uploads/banners/' . $newFilename;
                } else {
                    $error = 'Erro ao fazer upload da imagem de capa.';
                }
            }
        }
        
        // Se não houver erros, atualizar o perfil
        if (empty($error)) {
            $stmt = $pdo->prepare("UPDATE users SET 
                                  display_name = :display_name, 
                                  bio = :bio, 
                                  phone = :phone,
                                  profile_image = :profile_image,
                                  banner_image = :banner_image
                                  WHERE id = :id");
                                  
            $stmt->bindParam(':display_name', $displayName);
            $stmt->bindParam(':bio', $bio);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':profile_image', $profileImage);
            $stmt->bindParam(':banner_image', $bannerImage);
            $stmt->bindParam(':id', $userId);
            
            $stmt->execute();
            
            $success = 'Perfil atualizado com sucesso!';
            
            // Atualizar dados da sessão
            $_SESSION['profile_image'] = $profileImage;
            
            // Recarregar dados do usuário
            $stmt = $pdo->prepare("SELECT username, email, display_name, bio, profile_image, banner_image, phone 
                                   FROM users WHERE id = :id");
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $error = 'Erro ao atualizar perfil: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Perfil - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/RMStream/public/css/style.css">
    <link rel="stylesheet" href="/RMStream/public/css/nav.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos específicos para a página de edição */
        .edit-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .edit-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .edit-title {
            color: #1db954;
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .edit-subtitle {
            color: #aaa;
            font-size: 1.1em;
        }
        
        .edit-form {
            background: #232323;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            padding: 30px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 1em;
        }
        
        .alert-success {
            background: rgba(29, 185, 84, 0.1);
            border: 1px solid rgba(29, 185, 84, 0.3);
            color: #1db954;
        }
        
        .alert-danger {
            background: rgba(229, 57, 53, 0.1);
            border: 1px solid rgba(229, 57, 53, 0.3);
            color: #ff5555;
        }
        
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #333;
        }
        
        .form-section:last-child {
            border-bottom: none;
        }
        
        .section-title {
            font-size: 1.2em;
            color: #1db954;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #ddd;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border-radius: 6px;
            border: 1px solid #444;
            background: #333;
            color: #fff;
            font-size: 1em;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .form-control:focus {
            border-color: #1db954;
            outline: none;
            box-shadow: 0 0 0 2px rgba(29, 185, 84, 0.2);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-text {
            font-size: 0.85em;
            color: #999;
            margin-top: 5px;
        }
        
        .file-upload {
            margin-bottom: 15px;
        }
        
        .file-upload-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #ddd;
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            cursor: pointer;
        }
        
        .file-input-button {
            background: #333;
            color: #fff;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }
        
        .file-input-button:hover {
            background: #444;
        }
        
        .file-input-wrapper input[type="file"] {
            position: absolute;
            font-size: 100px;
            opacity: 0;
            right: 0;
            top: 0;
            cursor: pointer;
        }
        
        .file-name {
            margin-left: 10px;
            font-size: 0.9em;
            color: #aaa;
        }
        
        .preview-container {
            margin-top: 15px;
        }
        
        .preview-container h4 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #ddd;
            font-size: 0.9em;
            font-weight: normal;
        }
        
        .profile-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #1db954;
        }
        
        .banner-preview {
            width: 100%;
            max-width: 400px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-primary {
            background: #1db954;
            color: white;
        }
        
        .btn-primary:hover {
            background: #18a448;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(29, 185, 84, 0.4);
        }
        
        .btn-secondary {
            background: #333;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #444;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        /* Responsivo */
        @media (max-width: 768px) {
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
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
            <div class="nav-item nav-item-with-submenu">
                <a href="/RMStream/views/room.php" class="nav-link">Room</a>
                <div class="submenu">
                    <a href="#" class="submenu-item create-room">Criar Sala</a>
                    <a href="#" class="submenu-item join-room">Entrar em Sala</a>
                </div>
            </div>
            <div class="nav-item">
                <a href="/RMStream/views/chart.php" class="nav-link">Chart</a>
            </div>
            <div class="nav-item">
                <a href="/RMStream/views/profile/index.php" class="nav-link active">Perfil</a>
            </div>
        </div>
        <div class="nav-profile">
            <a href="/RMStream/views/auth/logout.php" class="nav-link">Sair</a>
        </div>
    </nav>
    
    <div class="edit-container">
        <div class="edit-header">
            <h1 class="edit-title">Editar Perfil</h1>
            <p class="edit-subtitle">Personalize seu perfil para mostrar quem você é</p>
        </div>
        
        <div class="edit-form">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data">
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-user"></i> Informações Básicas
                    </h3>
                    
                    <div class="form-group">
                        <label for="username">Nome de usuário</label>
                        <input type="text" id="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        <div class="form-text">O nome de usuário não pode ser alterado.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="display_name">Nome de exibição</label>
                        <input type="text" id="display_name" name="display_name" class="form-control" value="<?php echo htmlspecialchars($user['display_name'] ?? ''); ?>">
                        <div class="form-text">Este é o nome que aparecerá no seu perfil.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">Biografia</label>
                        <textarea id="bio" name="bio" class="form-control"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        <div class="form-text">Conte um pouco sobre você. O que gosta de assistir, seus interesses, etc.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Telefone</label>
                        <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Ex: (11) 98765-4321">
                        <div class="form-text">Opcional. Será visível apenas para você e seus amigos.</div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-image"></i> Imagens do Perfil
                    </h3>
                    
                    <div class="file-upload">
                        <span class="file-upload-label">Foto de perfil</span>
                        <div class="file-input-wrapper">
                            <button type="button" class="file-input-button">
                                <i class="fas fa-upload"></i> Escolher arquivo
                            </button>
                            <input type="file" name="profile_image" id="profile_image" accept="image/*">
                        </div>
                        <span class="file-name" id="profile-file-name">Nenhum arquivo selecionado</span>
                        
                        <?php if ($user['profile_image']): ?>
                            <div class="preview-container">
                                <h4>Foto atual:</h4>
                                <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Foto de perfil" class="profile-preview">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="file-upload">
                        <span class="file-upload-label">Imagem de capa</span>
                        <div class="file-input-wrapper">
                            <button type="button" class="file-input-button">
                                <i class="fas fa-upload"></i> Escolher arquivo
                            </button>
                            <input type="file" name="banner_image" id="banner_image" accept="image/*">
                        </div>
                        <span class="file-name" id="banner-file-name">Nenhum arquivo selecionado</span>
                        
                        <?php if ($user['banner_image']): ?>
                            <div class="preview-container">
                                <h4>Capa atual:</h4>
                                <img src="<?php echo htmlspecialchars($user['banner_image']); ?>" alt="Imagem de capa" class="banner-preview">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="/RMStream/views/profile/index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="/RMStream/public/js/nav.js"></script>
    <script>
        // Exibir nome do arquivo selecionado
        document.getElementById('profile_image').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'Nenhum arquivo selecionado';
            document.getElementById('profile-file-name').textContent = fileName;
        });
        
        document.getElementById('banner_image').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'Nenhum arquivo selecionado';
            document.getElementById('banner-file-name').textContent = fileName;
        });
    </script>
</body>
</html>
