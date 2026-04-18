<?php
require_once '../includes/config.php';

try {
    $pdo = getDbConnection();
    
    // Atualizar tabela de usuários para incluir campos adicionais
    $pdo->exec("ALTER TABLE users 
        ADD COLUMN bio TEXT NULL,
        ADD COLUMN profile_image VARCHAR(255) NULL,
        ADD COLUMN banner_image VARCHAR(255) NULL,
        ADD COLUMN phone VARCHAR(20) NULL,
        ADD COLUMN display_name VARCHAR(100) NULL,
        ADD COLUMN last_login DATETIME NULL");
    
    // Criar tabela de amizades
    $pdo->exec("CREATE TABLE IF NOT EXISTS friendships (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        friend_id INT NOT NULL,
        status ENUM('pending', 'accepted', 'blocked') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_friendship (user_id, friend_id)
    )");
    
    // Criar tabela de mensagens de chat
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT PRIMARY KEY AUTO_INCREMENT,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        content TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Criar tabela de denúncias
    $pdo->exec("CREATE TABLE IF NOT EXISTS reports (
        id INT PRIMARY KEY AUTO_INCREMENT,
        reporter_id INT NOT NULL,
        reported_id INT NOT NULL,
        reason VARCHAR(50) NOT NULL,
        description TEXT NULL,
        evidence VARCHAR(255) NULL,
        status ENUM('pending', 'reviewed', 'resolved') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (reported_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    echo "<div style='background: #1db954; color: white; padding: 20px; border-radius: 5px; margin: 20px; font-family: Arial;'>";
    echo "<h2>Esquema do banco de dados atualizado com sucesso!</h2>";
    echo "<p>Todas as tabelas foram criadas ou atualizadas conforme necessário.</p>";
    echo "<p><a href='/RMStream/views/auth/login.php' style='color: white; text-decoration: underline;'>Ir para a página de login</a></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='background: #ff5555; color: white; padding: 20px; border-radius: 5px; margin: 20px; font-family: Arial;'>";
    echo "<h2>Erro de banco de dados</h2>";
    echo "<p>Erro: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
