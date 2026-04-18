<?php
// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'rmstream');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configurações da aplicação
define('SITE_NAME', 'RMStream');
define('SITE_URL', 'http://localhost/RMStream');
define('ADMIN_EMAIL', 'yuriruzbarbosa.07@gmail.com');

// Configurações de segurança
define('SECRET_KEY', 'rm_stream_security_key_2024'); // Altere para uma chave segura
define('TOKEN_EXPIRY', 24 * 60 * 60); // 24 horas em segundos

// Iniciar sessão se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuração de fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Função para conexão com o banco de dados
function getDbConnection() {
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die('Erro de conexão com o banco de dados: ' . $e->getMessage());
    }
}

// Verificar se o usuário está autenticado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Verificar se o usuário é admin
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Redirecionar se não estiver logado
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/views/auth/login.php');
        exit;
    }
}

// Redirecionar se não for admin
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/views/index.php');
        exit;
    }
}
