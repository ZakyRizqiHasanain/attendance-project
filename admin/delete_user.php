<?php
include '../auth/admin_check.php';
include '../config/database.php';

// Pastikan ada parameter id
if(!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "ID user tidak ditemukan!";
    header("Location: users.php");
    exit;
}

$id = $_GET['id'];

// Gunakan prepared statement untuk keamanan
$stmt = mysqli_prepare($conn, "SELECT role FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Cegah penghapusan admin
if($user && $user['role'] == 'admin') {
    $_SESSION['error'] = "Admin tidak bisa dihapus!";
    header("Location: users.php");
    exit;
}

// Jika user tidak ditemukan
if(!$user) {
    $_SESSION['error'] = "User tidak ditemukan!";
    header("Location: users.php");
    exit;
}

// Hapus user
$stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
$success = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if($success) {
    $_SESSION['success'] = "User berhasil dihapus!";
} else {
    $_SESSION['error'] = "Gagal menghapus user!";
}

header("Location: users.php");
exit;
?>