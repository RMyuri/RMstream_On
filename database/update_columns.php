<?php
require_once '../includes/config.php';

try {
    $pdo = getDbConnection();
    
    // Verificar se as colunas já existem
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'display_name'");
    $stmt->execute();
    $displayNameExists = $stmt->rowCount() > 0;
    
    // Se a coluna display_name não existir, adicionar todas as colunas necessárias
    if (!$displayNameExists) {
        // Adicionar novas colunas à tabela users
        $pdo->exec("ALTER TABLE users 
            ADD COLUMN display_name VARCHAR(100) NULL,
            ADD COLUMN bio TEXT NULL,
            ADD COLUMN profile_image VARCHAR(255) NULL,
            ADD COLUMN banner_image VARCHAR(255) NULL,
            ADD COLUMN phone VARCHAR(20) NULL");
        
        echo "<div style='background: #1db954; color: white; padding: 20px; border-radius: 5px; margin: 20px; font-family: Arial;'>";
        echo "<h2>Tabela users atualizada com sucesso!</h2>";
        echo "<p>Colunas adicionadas: display_name, bio, profile_image, banner_image, phone</p>";
        echo "<p>Agora você pode usar a funcionalidade de perfil.</p>";
        echo "<p><a href='/RMStream/views/profile/index.php' style='color: white; text-decoration: underline;'>Ir para o perfil</a></p>";
        echo "</div>";
    } else {
        echo "<div style='background: #4a9eff; color: white; padding: 20px; border-radius: 5px; margin: 20px; font-family: Arial;'>";
        echo "<h2>As colunas já existem no banco de dados!</h2>";
        echo "<p>Não foi necessário fazer alterações.</p>";
        echo "<p><a href='/RMStream/views/profile/index.php' style='color: white; text-decoration: underline;'>Ir para o perfil</a></p>";
        echo "</div>";
    }
} catch (PDOException $e) {
    echo "<div style='background: #ff5555; color: white; padding: 20px; border-radius: 5px; margin: 20px; font-family: Arial;'>";
    echo "<h2>Erro ao atualizar o banco de dados</h2>";
    echo "<p>Erro: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
