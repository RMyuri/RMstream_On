<?php
require_once '../../includes/config.php';

// Encerra a sessão
session_unset();
session_destroy();

// Redireciona para a página de login
header('Location: ' . SITE_URL . '/views/auth/login.php');
exit;
