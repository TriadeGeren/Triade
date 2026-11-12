<?php
require_once __DIR__ . '/config.php';
unset($_SESSION['usuario']);
session_destroy();
header('Location: ' . BASE_PATH . '/index.php');
exit;
