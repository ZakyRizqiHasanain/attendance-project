<?php
session_start();

include '../auth/admin_check.php';
include '../config/database.php';
if (!isset($conn) || !$conn instanceof mysqli) {
    die('Koneksi database gagal.');
}

/* ================= VALIDASI ID ================= */
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    $_SESSION['error'] = "ID user tidak valid.";
    header("Location: users.php");
    exit;
}

/* ================= CEGAH HAPUS DIRI SENDIRI ================= */
$current_admin_id = (int) ($_SESSION['user_id'] ?? 0);

if ($id === $current_admin_id) {
    $_SESSION['error'] = "Anda tidak dapat menghapus akun sendiri.";
    header("Location: users.php");
    exit;
}

/* ================= CEK USER ================= */
$stmt = $conn->prepare("
    SELECT id, role, photo
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $id);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* ================= USER TIDAK DITEMUKAN ================= */
if (!$user) {
    $_SESSION['error'] = "User tidak ditemukan.";
    header("Location: users.php");
    exit;
}

/* ================= CEGAH HAPUS ADMIN ================= */
if ($user['role'] === 'admin') {
    $_SESSION['error'] = "Admin tidak dapat dihapus.";
    header("Location: users.php");
    exit;
}

/* ================= HAPUS FOTO USER ================= */
if (
    !empty($user['photo']) &&
    $user['photo'] !== 'default.png'
) {
    $photo_path = realpath(__DIR__ . '/../uploads/' . $user['photo']);

    $uploads_dir = realpath(__DIR__ . '/../uploads');

    if (
        $photo_path &&
        strpos($photo_path, $uploads_dir) === 0 &&
        file_exists($photo_path)
    ) {
        if (!unlink($photo_path)) {
            error_log("Gagal menghapus foto user: " . $photo_path);
        }
    }
}

/* ================= HAPUS USER ================= */
$stmt = $conn->prepare("
    DELETE FROM users
    WHERE id = ?
    LIMIT 1

");

$stmt->bind_param("i", $id);

if ($stmt->execute()) {

    if ($stmt->affected_rows > 0) {
        $_SESSION['success'] = "User berhasil dihapus.";
    } else {
        $_SESSION['error'] = "User tidak ditemukan.";
    }

} else {
    $_SESSION['error'] = "Gagal menghapus user.";
}

$stmt->close();

header("Location: users.php");
exit;
?>