<?php
include '../auth/admin_check.php';
include '../config/database.php';

if(isset($_POST['save'])) {

    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    /*
    |--------------------------------------------------------------------------
    | Upload Photo
    |--------------------------------------------------------------------------
    */
    $photo = $_FILES['photo']['name'];
    $tmp   = $_FILES['photo']['tmp_name'];

    if($photo != '') {
        $ext = pathinfo($photo, PATHINFO_EXTENSION);
        $photo = time() . '_' . uniqid() . '.' . $ext;
        move_uploaded_file($tmp, "../uploads/" . $photo);
    } else {
        $photo = 'default.png';
    }

    /*
    |--------------------------------------------------------------------------
    | Check Email
    |--------------------------------------------------------------------------
    */
    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if(mysqli_num_rows($check) > 0) {
        echo "<script>alert('Email sudah digunakan!'); window.location='add_user.php';</script>";
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Insert User
    |--------------------------------------------------------------------------
    */
    mysqli_query($conn, "INSERT INTO users(name, email, password, photo, role) VALUES('$name', '$email', '$password', '$photo', '$role')");

    echo "<script>alert('User berhasil ditambahkan!'); window.location='users.php';</script>";
    exit;
}

// Ambil data admin untuk dropdown profile
$admin_id = $_SESSION['user_id'];
$admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id='$admin_id'"));
$admin_photo = !empty($admin['photo']) ? $admin['photo'] : 'default.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah User - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        * {
            user-select: text;
            -webkit-user-select: text;
        }
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', sans-serif;
            color: #1a1e24;
        }
        /* Navbar gelap (sama dengan dashboard) */
        .navbar-dark.bg-dark {
            background-color: #1e2a3a !important;
        }
        .navbar-brand, .navbar-brand i {
            color: white !important;
        }
        .btn-outline-light {
            color: white;
            border-color: white;
        }
        .btn-outline-light:hover {
            background-color: rgba(255,255,255,0.1);
        }
        /* Card form */
        .card-form {
            border: none;
            border-radius: 24px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .card-header-custom {
            background-color: #f8fafc;
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
        }
        .card-header-custom h4 {
            margin: 0;
            font-weight: 700;
            color: #1e2a3a;
        }
        .card-body-custom {
            padding: 30px;
            background: white;
        }
        .form-label {
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }
        .form-control, .form-select {
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            padding: 10px 15px;
            transition: all 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #94a3b8;
            box-shadow: 0 0 0 3px rgba(100,116,139,0.1);
        }
        .btn-custom {
            border-radius: 40px;
            padding: 10px 25px;
            font-weight: 500;
        }
        .btn-primary-custom {
            background-color: #2c6e2f;
            color: white;
            border: none;
        }
        .btn-primary-custom:hover {
            background-color: #1f5422;
        }
        .btn-secondary-custom {
            background-color: #6c757d;
            color: white;
            border: none;
        }
        .btn-secondary-custom:hover {
            background-color: #5a6268;
        }
        .preview-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #e2e8f0;
            margin-top: 10px;
            display: none;
        }
        .form-text {
            color: #6c757d;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <i class="bi bi-fingerprint fs-3 me-2"></i> Attendance System
        </a>
        <div class="d-flex align-items-center">
            <a href="export.php" class="btn btn-outline-light btn-sm me-3">Export Excel</a>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-white" data-bs-toggle="dropdown">
                    <img src="../uploads/<?= $admin_photo; ?>" width="42" height="42" class="rounded-circle border border-2 border-white me-2" style="object-fit:cover;">
                    <span class="fw-semibold"><?= htmlspecialchars($admin['name']); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 py-2">
                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-bold"><?= htmlspecialchars($admin['name']); ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($admin['email']); ?></div>
                    </li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="users.php"><i class="bi bi-people-fill"></i> Users</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="qr_generate.php"><i class="bi bi-qr-code"></i> QR Attendance</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="settings.php"><i class="bi bi-gear-fill"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="../auth/logout.php" onclick="return confirm('Yakin ingin keluar?');"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-form">
                <div class="card-header-custom">
                    <h4><i class="bi bi-person-plus me-2"></i> Tambah User Baru</h4>
                    <p class="text-muted mb-0 mt-1">Isi data lengkap user di bawah ini</p>
                </div>
                <div class="card-body-custom">
                    <form method="POST" enctype="multipart/form-data">
                        <!-- NAMA -->
                        <div class="mb-4">
                            <label class="form-label"><i class="bi bi-person me-1"></i> Nama Lengkap</label>
                            <input type="text" name="name" class="form-control" placeholder="Masukkan nama user" required>
                        </div>

                        <!-- EMAIL -->
                        <div class="mb-4">
                            <label class="form-label"><i class="bi bi-envelope me-1"></i> Alamat Email</label>
                            <input type="email" name="email" class="form-control" placeholder="contoh@email.com" required>
                        </div>

                        <!-- PASSWORD -->
                        <div class="mb-4">
                            <label class="form-label"><i class="bi bi-lock me-1"></i> Password</label>
                            <input type="text" name="password" class="form-control" placeholder="Masukkan password" required>
                            <div class="form-text">Password akan tersimpan dalam bentuk teks (sesuai kebutuhan admin).</div>
                        </div>

                        <!-- ROLE -->
                        <div class="mb-4">
                            <label class="form-label"><i class="bi bi-tag me-1"></i> Role / Hak Akses</label>
                            <select name="role" class="form-select">
                                <option value="user">User (Regular)</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <!-- FOTO -->
                        <div class="mb-4">
                            <label class="form-label"><i class="bi bi-image me-1"></i> Foto Profil</label>
                            <input type="file" name="photo" id="photoInput" class="form-control" accept="image/*">
                            <div class="mt-3 text-center">
                                <img id="photoPreview" class="preview-img" alt="Preview Foto">
                            </div>
                            <div class="form-text">Kosongkan jika tidak ingin mengubah foto (akan menggunakan default).</div>
                        </div>

                        <!-- BUTTONS -->
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" name="save" class="btn btn-primary-custom btn-custom">
                                <i class="bi bi-save me-1"></i> Simpan User
                            </button>
                            <a href="users.php" class="btn btn-secondary-custom btn-custom">
                                <i class="bi bi-arrow-left me-1"></i> Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Preview foto
    document.getElementById('photoInput').addEventListener('change', function(e) {
        const preview = document.getElementById('photoPreview');
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(ev) {
                preview.src = ev.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
        }
    });
</script>

</body>
</html>