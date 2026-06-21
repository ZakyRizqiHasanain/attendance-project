<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role'])
) {
    header("Location: ../index.php");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../user/dashboard.php");
    exit;
}

if (
    ($_SESSION['ip'] ?? '') !== $_SERVER['REMOTE_ADDR']
    ||
    ($_SESSION['ua'] ?? '') !== $_SERVER['HTTP_USER_AGENT']
) {
    session_destroy();
    header("Location: ../index.php");
    exit;
}
?>