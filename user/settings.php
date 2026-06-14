<?php
session_start();
include '../config/database.php';

if(!isset($_SESSION['user_id'])){
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* =========================
   GET USER DATA
========================= */
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id='$user_id'");
$user = mysqli_fetch_assoc($user_query);

$user_photo = !empty($user['photo']) ? $user['photo'] : 'default.png';

/* =========================
   UPDATE PROFILE
========================= */
if(isset($_POST['update_profile'])){

    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $photo = $user['photo'];

    if(!empty($_FILES['photo']['name'])){

        $file = $_FILES['photo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        $allowed = ['jpg','jpeg','png'];

        if(in_array($ext,$allowed)){

            $photo = "profile_".$user_id."_".time().".".$ext;

            move_uploaded_file($file['tmp_name'], "../uploads/".$photo);
        }
    }

    mysqli_query($conn,"
        UPDATE users
        SET name='$name', photo='$photo'
        WHERE id='$user_id'
    ");

    $_SESSION['name'] = $name;

    echo "<script>alert('Profil berhasil diperbarui');location='settings.php';</script>";
    exit;
}

/* =========================
   CHANGE PASSWORD
========================= */
if(isset($_POST['change_password'])){

    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if($old_password != $user['password']){

        echo "<script>alert('Password lama salah!');</script>";

    } elseif($new_password != $confirm_password){

        echo "<script>alert('Konfirmasi password tidak cocok!');</script>";

    } else {

        mysqli_query($conn,"
            UPDATE users
            SET password='$new_password'
            WHERE id='$user_id'
        ");

        echo "<script>alert('Password berhasil diganti!');location='settings.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<link rel="stylesheet" href="../assets/style.css">

</head>

<body class="dashboard">

<!-- NAVBAR + DROPDOWN (ADMIN STYLE) -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid px-4">

        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="#">
            <i class="bi bi-fingerprint text-primary fs-3"></i>
            <span>Attendance System</span>
        </a>

        <div class="d-flex align-items-center">

            <!-- DROPDOWN -->
            <div class="dropdown">

                <a class="d-flex align-items-center text-decoration-none dropdown-toggle text-white"
                   data-bs-toggle="dropdown">

                    <img src="../uploads/<?= $user_photo; ?>"
                         width="40"
                         height="40"
                         class="rounded-circle border border-2 border-white me-2"
                         style="object-fit: cover;">

                    <span class="fw-semibold"><?= $_SESSION['name']; ?></span>

                </a>

                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 py-2"
                    style="border-radius: 16px; min-width: 220px;">

                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-bold"><?= $_SESSION['name']; ?></div>
                        <div class="text-muted small"><?= $user['email']; ?></div>
                    </li>

                    <li><a class="dropdown-item" href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                    <li><a class="dropdown-item" href="history.php"><i class="bi bi-calendar-range me-2"></i> History</a></li>
                    <li><a class="dropdown-item" href="leave.php"><i class="bi bi-journal-plus me-2"></i> Pengajuan Izin / Sakit</a></li>
                    <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger" href="../auth/logout.php">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>

            </div>

        </div>

    </div>
</nav>

<!-- CONTENT -->
<div class="container mt-5">

    <div class="card mb-4">
        <div class="card-body p-4">
            <span class="badge bg-primary mb-2">SETTINGS</span>
            <h3 class="fw-bold">Pengaturan Akun</h3>
            <p class="text-muted mb-0">Kelola profil dan keamanan akun Anda</p>
        </div>
    </div>

    <div class="row g-4">

        <!-- PROFILE -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body p-4">

                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-person-circle text-success"></i> Edit Profil
                    </h5>

                    <form method="POST" enctype="multipart/form-data">

                        <div class="text-center mb-3">
                            <img src="../uploads/<?= $user_photo; ?>"
                                 width="130"
                                 height="130"
                                 class="rounded-circle border shadow"
                                 style="object-fit: cover;">
                        </div>

                        <input type="text"
                               name="name"
                               class="form-control mb-3"
                               value="<?= $user['name']; ?>">

                        <input type="file"
                               name="photo"
                               class="form-control mb-3">

                        <button class="btn btn-success w-100" name="update_profile">
                            Simpan
                        </button>

                    </form>

                </div>
            </div>
        </div>

        <!-- PASSWORD -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body p-4">

                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-shield-lock text-primary"></i> Ganti Password
                    </h5>

                    <form method="POST">

                        <input type="password"
                               name="old_password"
                               class="form-control mb-3"
                               placeholder="Password lama">

                        <input type="password"
                               name="new_password"
                               class="form-control mb-3"
                               placeholder="Password baru">

                        <input type="password"
                               name="confirm_password"
                               class="form-control mb-3"
                               placeholder="Konfirmasi password">

                        <button class="btn btn-primary w-100" name="change_password">
                            Ganti Password
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