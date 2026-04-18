<?php
require_once '../includes/config.php';

try {
    $pdo = getDbConnection();
    
    // Tabela de mensagens diretas (DMs)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `messages` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `sender_id` INT NOT NULL,
        `receiver_id` INT NOT NULL,
        `content` TEXT NOT NULL,
        `attachment` VARCHAR(255) NULL,
        `is_read` BOOLEAN DEFAULT FALSE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Tabela de grupos
    $pdo->exec("CREATE TABLE IF NOT EXISTS `chat_groups` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `name` VARCHAR(100) NOT NULL,
        `description` TEXT NULL,
        `avatar` VARCHAR(255) NULL,
        `created_by` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Tabela de membros do grupo
    $pdo->exec("CREATE TABLE IF NOT EXISTS `group_members` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `group_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `role` ENUM('member', 'moderator', 'admin') DEFAULT 'member',
        `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (group_id) REFERENCES chat_groups(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY `unique_member` (`group_id`, `user_id`)
    )");
    
    // Tabela de mensagens de grupo
    $pdo->exec("CREATE TABLE IF NOT EXISTS `group_messages` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `group_id` INT NOT NULL,
        `sender_id` INT NOT NULL,
        `content` TEXT NOT NULL,
        `attachment` VARCHAR(255) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (group_id) REFERENCES chat_groups(id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Tabela de chamadas
    $pdo->exec("CREATE TABLE IF NOT EXISTS `calls` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `caller_id` INT NOT NULL,
        `receiver_id` INT NULL,
        `group_id` INT NULL,
        `room_id` VARCHAR(100) NOT NULL,
        `status` ENUM('active', 'ended', 'missed') DEFAULT 'active',
        `type` ENUM('audio', 'video') DEFAULT 'audio',
        `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `ended_at` TIMESTAMP NULL,
        FOREIGN KEY (caller_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (group_id) REFERENCES chat_groups(id) ON DELETE SET NULL,
        CHECK (receiver_id IS NOT NULL OR group_id IS NOT NULL)
    )");
    
    echo "<div style='background: #1db954; color: white; padding: 20px; border-radius: 5px; margin: 20px; font-family: Arial;'>";
    echo "<h2>Tabelas do sistema de chat criadas com sucesso!</h2>";
    echo "<p>Agora você pode utilizar o sistema de chat completo.</p>";
    echo "<p><a href='/RMStream/views/chat/index.php' style='color: white; text-decoration: underline;'>Ir para o Chat</a></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='background: #ff5555; color: white; padding: 20px; border-radius: 5px; margin: 20px; font-family: Arial;'>";
    echo "<h2>Erro ao configurar tabelas do chat</h2>";
    echo "<p>Erro: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
