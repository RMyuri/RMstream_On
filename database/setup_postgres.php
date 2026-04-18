<?php
// Setup para PostgreSQL (Render)
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'rmstream';
$username = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASS') ?: '';

try {
    // Conecta ao PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Cria tabela de usuários
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(10) DEFAULT 'user',
        is_verified BOOLEAN DEFAULT FALSE,
        verification_token VARCHAR(100),
        reset_token VARCHAR(100),
        reset_token_expiry TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Cria usuário admin
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    $adminExists = (int)$stmt->fetchColumn();
    
    if ($adminExists === 0) {
        $adminUser = 'yuri.admin';
        $adminEmail = 'admin@rmstream.local';
        $adminPass = password_hash('pY@71764861', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, is_verified) 
                              VALUES (:username, :email, :password, 'admin', TRUE)");
        $stmt->bindParam(':username', $adminUser);
        $stmt->bindParam(':email', $adminEmail);
        $stmt->bindParam(':password', $adminPass);
        $stmt->execute();
        
        echo "Usuário admin criado! <br>";
        echo "Login: yuri.admin <br>";
        echo "Senha: pY@71764861 <br>";
    }
    
    echo "Configuração do banco PostgreSQL concluída!";
    
} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
