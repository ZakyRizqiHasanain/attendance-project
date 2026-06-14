<<<<<<< HEAD
<?php
session_start();
include '../config/database.php';

if(!isset($_SESSION['user_id'])){
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$user = mysqli_fetch_assoc(
    mysqli_query($conn,
    "SELECT * FROM users WHERE id='$user_id'")
);

/*
|--------------------------------------------------------------------------
| UPDATE PROFILE
|--------------------------------------------------------------------------
*/
if(isset($_POST['update_profile'])){
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $photo = $user['photo'];

    if(!empty($_FILES['photo']['name'])){
        $file = $_FILES['photo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png'];
        if(in_array($ext, $allowed)){
            $photo = "profile_".$user_id."_".time().".".$ext;
            move_uploaded_file($file['tmp_name'], "../uploads/".$photo);
        }
    }

    mysqli_query($conn, "UPDATE users SET name='$name', photo='$photo' WHERE id='$user_id'");
    $_SESSION['name'] = $name;

    echo "<script>alert('Profil berhasil diperbarui'); location='settings.php';</script>";
    exit;
}

/*
|--------------------------------------------------------------------------
| CHANGE PASSWORD
|--------------------------------------------------------------------------
*/
if(isset($_POST['change_password'])){
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $check = mysqli_query($conn, "SELECT * FROM users WHERE id='$user_id'");
    $data = mysqli_fetch_assoc($check);

    if($old_password != $data['password']){
        echo "<script>alert('Password lama salah!');</script>";
    }
    elseif(strlen($new_password) < 8 ||
           !preg_match('/[A-Z]/', $new_password) ||
           !preg_match('/[a-z]/', $new_password) ||
           !preg_match('/[0-9]/', $new_password) ||
           !preg_match('/[^A-Za-z0-9]/', $new_password)){
        echo "<script>alert('Password harus minimal 8 karakter dan mengandung huruf besar, huruf kecil, angka, serta simbol!');</script>";
    }
    elseif($new_password != $confirm_password){
        echo "<script>alert('Konfirmasi password tidak cocok!');</script>";
    }
    else{
        mysqli_query($conn, "UPDATE users SET password='$new_password' WHERE id='$user_id'");
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
    <!-- Bootstrap 5 + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="dashboard">

<!-- NAVBAR (sama dengan dashboard modern) -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="#">
            <i class="bi bi-fingerprint text-primary fs-3"></i>
            <span>Attendance System</span>
        </a>
        <div class="d-flex align-items-center">
            <!-- EXPORT EXCEL (opsional, bisa dipertahankan) -->
            <a href="export.php" class="btn btn-success btn-sm me-3">Export Excel</a>

            <!-- PROFILE DROPDOWN -->
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-white" id="adminDropdown" data-bs-toggle="dropdown">
                    <img src="../uploads/<?= $user['photo'] ?: 'default.png'; ?>" width="42" height="42" class="rounded-circle border border-2 border-white me-2" style="object-fit:cover;">
                    <span class="fw-semibold"><?= htmlspecialchars($user['name']); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 py-2" style="border-radius:16px;min-width:250px;">
                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-bold"><?= htmlspecialchars($user['name']); ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($user['email']); ?></div>
                    </li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="users.php"><i class="bi bi-people-fill"></i> Users</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="attendance_data.php"><i class="bi bi-calendar-check"></i> Attendance</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="qr_generate.php"><i class="bi bi-qr-code"></i> QR Attendance</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="settings.php"><i class="bi bi-gear-fill"></i> Settings</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="leave.php"><i class="bi bi-journal-medical"></i>Pengajuan Izin / Sakit</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="../auth/logout.php" onclick="return confirm('Yakin ingin keluar?');"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <!-- Header Halaman -->
    <div class="card border-0 shadow-sm mb-4 overflow-hidden" style="border-radius:20px;">
        <div style="background: linear-gradient(135deg, #1e2a3a, #0f1724); height:6px;"></div>
        <div class="card-body p-4">
            <span class="badge bg-primary mb-2">PENGATURAN</span>
            <h2 class="fw-bold mb-1"><i class="bi bi-sliders2 me-2"></i> Pengaturan Akun</h2>
            <p class="text-muted mb-0">Kelola profil dan keamanan akun Anda.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- FORM EDIT PROFIL -->
        <div class="col-lg-6">
            <div class="card card-settings h-100">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4"><i class="bi bi-person-circle text-success me-2"></i> Edit Profil</h4>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="text-center mb-4">
                            <img src="../uploads/<?= $user['photo'] ?: 'default.png'; ?>" class="profile-img" alt="Foto Profil">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Lengkap</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Foto Profil</label>
                            <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png">
                            <div class="form-text">Kosongkan jika tidak ingin mengubah foto.</div>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-success w-100 py-2">
                            <i class="bi bi-save me-1"></i> Simpan Profil
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- FORM GANTI PASSWORD -->
        <div class="col-lg-6">
            <div class="card card-settings h-100">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4"><i class="bi bi-shield-lock text-primary me-2"></i> Ganti Password</h4>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password Lama</label>
                            <div class="input-group">
                                <input type="password" name="old_password" id="old_password" class="form-control" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('old_password', this)"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password Baru</label>
                            <div class="input-group">
                                <input type="password" name="new_password" id="new_password" class="form-control" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('new_password', this)"><i class="bi bi-eye"></i></button>
                            </div>
                            <small class="text-muted">Minimal 8 karakter, huruf besar, huruf kecil, angka, dan simbol.</small>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Konfirmasi Password Baru</label>
                            <div class="input-group">
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_password', this)"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary w-100 py-2">
                            <i class="bi bi-shield-check me-1"></i> Ganti Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePassword(id, button) {
    let input = document.getElementById(id);
    let icon = button.querySelector('i');
    if(input.type === "password") {
        input.type = "text";
        icon.classList.remove("bi-eye");
        icon.classList.add("bi-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
    }
}
</script>
</body>
=======
<?php
session_start();
include '../config/database.php';

if(!isset($_SESSION['user_id'])){
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$user = mysqli_fetch_assoc(
    mysqli_query($conn,
    "SELECT * FROM users WHERE id='$user_id'")
);

/*
|--------------------------------------------------------------------------
| UPDATE PROFILE
|--------------------------------------------------------------------------
*/
if(isset($_POST['update_profile'])){
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $photo = $user['photo'];

    if(!empty($_FILES['photo']['name'])){
        $file = $_FILES['photo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png'];
        if(in_array($ext, $allowed)){
            $photo = "profile_".$user_id."_".time().".".$ext;
            move_uploaded_file($file['tmp_name'], "../uploads/".$photo);
        }
    }

    mysqli_query($conn, "UPDATE users SET name='$name', photo='$photo' WHERE id='$user_id'");
    $_SESSION['name'] = $name;

    echo "<script>alert('Profil berhasil diperbarui'); location='settings.php';</script>";
    exit;
}

/*
|--------------------------------------------------------------------------
| CHANGE PASSWORD
|--------------------------------------------------------------------------
*/
if(isset($_POST['change_password'])){
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $check = mysqli_query($conn, "SELECT * FROM users WHERE id='$user_id'");
    $data = mysqli_fetch_assoc($check);

    if($old_password != $data['password']){
        echo "<script>alert('Password lama salah!');</script>";
    }
    elseif(strlen($new_password) < 8 ||
           !preg_match('/[A-Z]/', $new_password) ||
           !preg_match('/[a-z]/', $new_password) ||
           !preg_match('/[0-9]/', $new_password) ||
           !preg_match('/[^A-Za-z0-9]/', $new_password)){
        echo "<script>alert('Password harus minimal 8 karakter dan mengandung huruf besar, huruf kecil, angka, serta simbol!');</script>";
    }
    elseif($new_password != $confirm_password){
        echo "<script>alert('Konfirmasi password tidak cocok!');</script>";
    }
    else{
        mysqli_query($conn, "UPDATE users SET password='$new_password' WHERE id='$user_id'");
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
    <!-- Bootstrap 5 + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        .dropdown-menu {
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .card-settings {
            border: none;
            border-radius: 24px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .card-settings:hover {
            transform: translateY(-5px);
        }
        .btn-back {
            border-radius: 40px;
            padding: 10px 25px;
        }
        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .form-control, .input-group-text {
            border-radius: 12px;
        }
        .btn {
            border-radius: 12px;
        }
    </style>
</head>
<body>

<!-- NAVBAR (sama dengan dashboard modern) -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <i class="bi bi-fingerprint text-primary fs-3 me-2"></i> Attendance System
        </a>
        <div class="d-flex align-items-center">
            <!-- EXPORT EXCEL (opsional, bisa dipertahankan) -->
            <a href="export.php" class="btn btn-success btn-sm me-3">Export Excel</a>

            <!-- PROFILE DROPDOWN -->
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-white" id="adminDropdown" data-bs-toggle="dropdown">
                    <img src="../uploads/<?= $user['photo'] ?: 'default.png'; ?>" width="42" height="42" class="rounded-circle border border-2 border-white me-2" style="object-fit:cover;">
                    <span class="fw-semibold"><?= htmlspecialchars($user['name']); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 py-2" style="border-radius:16px;min-width:250px;">
                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-bold"><?= htmlspecialchars($user['name']); ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($user['email']); ?></div>
                    </li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="dashboard.php"><i class="bi bi-speedometer2 text-primary"></i> Dashboard</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="users.php"><i class="bi bi-people-fill text-primary"></i> Users</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="qr_generate.php"><i class="bi bi-qr-code text-warning"></i> QR Attendance</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 active" href="settings.php"><i class="bi bi-gear-fill text-success"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="../auth/logout.php" onclick="return confirm('Yakin ingin keluar?');"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <!-- Header Halaman -->
    <div class="card border-0 shadow-sm mb-4 overflow-hidden" style="border-radius:20px;">
        <div style="background: linear-gradient(135deg, #1e2a3a, #0f1724); height:6px;"></div>
        <div class="card-body p-4">
            <span class="badge bg-primary mb-2">PENGATURAN</span>
            <h2 class="fw-bold mb-1"><i class="bi bi-sliders2 me-2"></i> Pengaturan Akun</h2>
            <p class="text-muted mb-0">Kelola profil dan keamanan akun Anda.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- FORM EDIT PROFIL -->
        <div class="col-lg-6">
            <div class="card card-settings h-100">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4"><i class="bi bi-person-circle text-success me-2"></i> Edit Profil</h4>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="text-center mb-4">
                            <img src="../uploads/<?= $user['photo'] ?: 'default.png'; ?>" class="profile-img" alt="Foto Profil">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Lengkap</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Foto Profil</label>
                            <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png">
                            <div class="form-text">Kosongkan jika tidak ingin mengubah foto.</div>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-success w-100 py-2">
                            <i class="bi bi-save me-1"></i> Simpan Profil
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- FORM GANTI PASSWORD -->
        <div class="col-lg-6">
            <div class="card card-settings h-100">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4"><i class="bi bi-shield-lock text-primary me-2"></i> Ganti Password</h4>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password Lama</label>
                            <div class="input-group">
                                <input type="password" name="old_password" id="old_password" class="form-control" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('old_password', this)"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password Baru</label>
                            <div class="input-group">
                                <input type="password" name="new_password" id="new_password" class="form-control" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('new_password', this)"><i class="bi bi-eye"></i></button>
                            </div>
                            <small class="text-muted">Minimal 8 karakter, huruf besar, huruf kecil, angka, dan simbol.</small>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Konfirmasi Password Baru</label>
                            <div class="input-group">
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_password', this)"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary w-100 py-2">
                            <i class="bi bi-shield-check me-1"></i> Ganti Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tombol Kembali -->
    <div class="mt-4 text-center">
        <a href="dashboard.php" class="btn btn-secondary btn-back">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePassword(id, button) {
    let input = document.getElementById(id);
    let icon = button.querySelector('i');
    if(input.type === "password") {
        input.type = "text";
        icon.classList.remove("bi-eye");
        icon.classList.add("bi-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
    }
}
</script>
</body>
>>>>>>> 2a72f8ff5e33512f46cc6d52663c013b2c3068c0
</html>