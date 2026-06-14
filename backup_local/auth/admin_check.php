<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user_id'])){
    header("Location: ../index.php");
    exit;
}

if($_SESSION['role'] != 'admin') {

    echo "
    <script>
        alert('Akses ditolak!');
        window.location='../user/dashboard.php';
    </script>
    ";

    exit;
}
?>