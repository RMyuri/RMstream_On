<?php
// Configurações de conexão com o banco de dados
$host = 'localhost';
$dbname = 'rmstream';
$username = 'root';
$password = ''; // XAMPP normalmente usa senha vazia

try {
    // Conecta ao servidor MySQL
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Cria o banco de dados se não existir
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Conecta ao banco de dados
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Cria tabela de usuários
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `username` VARCHAR(50) UNIQUE NOT NULL,
        `email` VARCHAR(100) UNIQUE NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `role` ENUM('user', 'admin') DEFAULT 'user',
        `is_verified` BOOLEAN DEFAULT FALSE,
        `verification_token` VARCHAR(100),
        `reset_token` VARCHAR(100),
        `reset_token_expiry` DATETIME,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Cria usuário admin com as credenciais específicas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `role` = 'admin'");
    $stmt->execute();
    $adminExists = (int)$stmt->fetchColumn();
    
    if ($adminExists === 0) {
        $adminUser = 'yuri.admin'; // Nome de usuário especificado
        $adminEmail = 'admin@rmstream.local';
        $adminPass = password_hash('pY@71764861', PASSWORD_DEFAULT); // Senha especificada
        
        $stmt = $pdo->prepare("INSERT INTO `users` (`username`, `email`, `password`, `role`, `is_verified`) 
                              VALUES (:username, :email, :password, 'admin', TRUE)");
        $stmt->bindParam(':username', $adminUser);
        $stmt->bindParam(':email', $adminEmail);
        $stmt->bindParam(':password', $adminPass);
        $stmt->execute();
        
        echo "Usuário admin criado! <br>";
        echo "Login: yuri.admin <br>";
        echo "Senha: pY@71764861 <br>";
        echo "<strong>IMPORTANTE: Esta senha foi configurada conforme solicitado.</strong><br>";
    }
    
    echo "Configuração do banco de dados concluída com sucesso!";
    
} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
