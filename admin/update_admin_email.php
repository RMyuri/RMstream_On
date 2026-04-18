<?php
require_once '../includes/config.php';

try {
    $pdo = getDbConnection();
    
    // Atualiza o email do usuário admin (yuri.admin)
    $stmt = $pdo->prepare("UPDATE users SET email = :email WHERE username = 'yuri.admin' AND role = 'admin'");
    $email = 'yuriruzbarbosa.07@gmail.com';
    $stmt->bindParam(':email', $email);
    
    if ($stmt->execute()) {
        echo "<div style='background: #1db954; color: white; padding: 20px; border-radius: 5px; margin: 20px; font-family: Arial;'>";
        echo "<h2>Email do administrador atualizado com sucesso!</h2>";
        echo "<p>O email do administrador foi atualizado para: $email</p>";
        echo "<p><a href='/RMStream/views/auth/login.php' style='color: white; text-decoration: underline;'>Ir para a página de login</a></p>";
        echo "</div>";
    } else {
        echo "<div style='background: #ff5555; color: white; padding: 20px; border-radius: 5px; margin: 20px; font-family: Arial;'>";
        echo "<h2>Erro ao atualizar o email do administrador</h2>";
        echo "<p>Não foi possível atualizar o email. Verifique se o usuário admin existe no banco de dados.</p>";
        echo "</div>";
    }
} catch (PDOException $e) {
    echo "<div style='background: #ff5555; color: white; padding: 20px; border-radius: 5px; margin: 20px; font-family: Arial;'>";
    echo "<h2>Erro de banco de dados</h2>";
    echo "<p>Erro: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
