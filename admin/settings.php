<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

/* =========================
   GET USER (SECURE)
========================= */
$stmt = $conn->prepare("SELECT id, name, email, photo, password FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) {
    die("User tidak ditemukan.");
}
$user_photo = !empty($user['photo']) ? $user['photo'] : 'default.png';

$admin_id = (int)$_SESSION['user_id'];
$admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$admin_id"));
$admin_photo = !empty($admin['photo']) ? $admin['photo'] : 'default.png';

if (!$admin) {
    die("Data admin tidak ditemukan.");
}

/* =========================
   UPDATE PROFILE
========================= */
if (isset($_POST['update_profile'])) {

    $name = trim($_POST['name']);

    if ($name === '') {
        echo "<script>alert('Nama tidak boleh kosong');</script>";
    } else {

        $photo = $user['photo'];

        /* upload photo */
        if (!empty($_FILES['photo']['name'])) {

            $file = $_FILES['photo'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png'];

            if (in_array($ext, $allowed)) {

                $newName = "profile_" . $user_id . "_" . time() . "." . $ext;
                $uploadPath = "../uploads/" . $newName;

                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $photo = $newName;
                }
            }
        }

        $stmt = $conn->prepare("UPDATE users SET name=?, photo=? WHERE id=?");
        $stmt->bind_param("ssi", $name, $photo, $user_id);
        $stmt->execute();

        $_SESSION['name'] = $name;

        echo "<script>alert('Profil berhasil diperbarui'); location='settings.php';</script>";
        exit;
    }
}

/* =========================
   CHANGE PASSWORD (SECURE)
========================= */
if (isset($_POST['change_password'])) {

    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!password_verify($old_password, $user['password'])) {
        echo "<script>alert('Password lama salah!');</script>";
    }

    elseif (
        strlen($new_password) < 8 ||
        !preg_match('/[A-Z]/', $new_password) ||
        !preg_match('/[a-z]/', $new_password) ||
        !preg_match('/[0-9]/', $new_password) ||
        !preg_match('/[^A-Za-z0-9]/', $new_password)
    ) {
        echo "<script>alert('Password harus minimal 8 karakter dan mengandung huruf besar, kecil, angka, dan simbol!');</script>";
    }

    elseif ($new_password !== $confirm_password) {
        echo "<script>alert('Konfirmasi password tidak cocok!');</script>";
    }

    else {

        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param("si", $hashed, $user_id);
        $stmt->execute();

        echo "<script>alert('Password berhasil diganti!'); location='settings.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Akun - Attendance System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body class="dashboard">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="#">
            <i class="bi bi-fingerprint text-primary fs-3"></i>
            <span>Attendance System</span>
        </a>

        <div class="d-flex align-items-center">
            <a href="export.php" class="btn btn-outline-light btn-sm me-3">
                Export Excel
            </a>

            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-white" data-bs-toggle="dropdown">
                    <img src="../uploads/<?= htmlspecialchars($admin_photo); ?>" width="42" height="42" class="rounded-circle border border-2 border-white me-2" style="object-fit:cover;">
                    <span class="fw-semibold"><?= htmlspecialchars($admin['name'] ?? 'Admin'); ?></span>
                </a>

                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 py-2" style="border-radius:16px;min-width:250px;">
                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-bold"><?= htmlspecialchars($admin['name'] ?? 'Admin'); ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($admin['email'] ?? ''); ?></div>
                    </li>

                    <li><a class="dropdown-item" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li><a class="dropdown-item" href="users.php"><i class="bi bi-people-fill"></i> Users</a></li>
                    <li><a class="dropdown-item" href="attendance_data.php"><i class="bi bi-calendar-check"></i> Attendance</a></li>
                    <li><a class="dropdown-item" href="qr_generate.php"><i class="bi bi-qr-code"></i> QR Attendance</a></li>
                    <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear-fill"></i> Settings</a></li>
                    <li><a class="dropdown-item" href="leave.php"><i class="bi bi-journal-medical"></i> Pengajuan Izin / Sakit</a></li>

                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="../auth/logout.php" onclick="return confirm('Yakin ingin keluar?');">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

        </div>
    </div>
</nav>

<div class="container mt-5">

    <div class="row g-4">

        <!-- PROFILE -->
        <div class="col-lg-6">
            <div class="card card-settings h-100">
                <div class="card-body p-4">

                    <h4 class="fw-bold mb-4">
                        <i class="bi bi-person-circle text-success me-2"></i> Edit Profil
                    </h4>

                    <form method="POST" enctype="multipart/form-data">

                        <div class="text-center mb-4">
                            <img src="../uploads/<?= htmlspecialchars($user_photo); ?>"
                                 class="profile-img">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Lengkap</label>
                            <input type="text" name="name"
                                   class="form-control"
                                   value="<?= htmlspecialchars($user['name']); ?>"
                                   required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Foto Profil</label>
                            <input type="file" name="photo"
                                   class="form-control"
                                   accept=".jpg,.jpeg,.png">
                        </div>

                        <button type="submit" name="update_profile"
                                class="btn btn-success w-100 py-2">
                            <i class="bi bi-save me-1"></i> Simpan Profil
                        </button>

                    </form>
                </div>
            </div>
        </div>

        <!-- PASSWORD -->
        <div class="col-lg-6">
            <div class="card card-settings h-100">
                <div class="card-body p-4">

                    <h4 class="fw-bold mb-4">
                        <i class="bi bi-shield-lock text-primary me-2"></i> Ganti Password
                    </h4>

                    <form method="POST">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password Lama</label>
                            <input type="password" name="old_password"
                                   class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password Baru</label>
                            <input type="password" name="new_password"
                                   class="form-control" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Konfirmasi Password</label>
                            <input type="password" name="confirm_password"
                                   class="form-control" required>
                        </div>

                        <button type="submit" name="change_password"
                                class="btn btn-primary w-100 py-2">
                            <i class="bi bi-shield-check me-1"></i> Ganti Password
                        </button>

                    </form>

                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>