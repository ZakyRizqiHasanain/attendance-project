<?php
include '../auth/admin_check.php';
include '../config/database.php';
include '../libs/phpqrcode/qrlib.php';

// URL untuk QR Code (sesuaikan dengan domain/IP Anda)
$qr_data = "http://10.69.9.215/attendance-project/user/scan_qr.php";

// Folder penyimpanan QR
$qr_dir = "../assets/qrcode/";
if (!file_exists($qr_dir)) {
    mkdir($qr_dir, 0777, true);
}
$file = $qr_dir . "attendance_qr.png";

// Generate QR code
QRcode::png($qr_data, $file, QR_ECLEVEL_H, 10);

// Cek apakah file berhasil dibuat
$qr_exists = file_exists($file);

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
    <title>QR Attendance - Attendance System</title>
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
        /* Navbar gelap */
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
        /* Card QR */
        .card-qr {
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
            text-align: center;
        }
        .qr-wrapper {
            background: white;
            padding: 20px;
            border-radius: 20px;
            display: inline-block;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .info-url {
            background-color: #f8fafc;
            border-radius: 16px;
            padding: 12px 15px;
            font-family: monospace;
            word-break: break-all;
            font-size: 0.85rem;
            color: #334155;
        }
        .btn-download {
            background-color: #2c6e2f;
            color: white;
            border: none;
            border-radius: 40px;
            padding: 8px 25px;
        }
        .btn-download:hover {
            background-color: #1f5422;
        }
        .btn-back {
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 40px;
            padding: 8px 25px;
        }
        .btn-back:hover {
            background-color: #5a6268;
        }
        .alert-light {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
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
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="attendance_data.php"><i class="bi bi-calendar-check"></i> Attendance</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 active" href="qr_generate.php"><i class="bi bi-qr-code"></i> QR Attendance</a></li>
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
            <div class="card card-qr">
                <div class="card-header-custom">
                    <h4><i class="bi bi-qr-code me-2"></i> QR Code Absensi</h4>
                    <p class="text-muted mb-0 mt-1">Gunakan QR ini untuk melakukan absensi oleh user</p>
                </div>
                <div class="card-body-custom">
                    <!-- QR Code Display -->
                    <?php if ($qr_exists): ?>
                        <div class="qr-wrapper mb-4">
                            <img src="<?= $file ?>?t=<?= time() ?>" 
                                 class="img-fluid" 
                                 style="width: 250px; height: auto;"
                                 alt="QR Code Attendance">
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i> QR Code gagal dibuat. Periksa folder permissions.
                        </div>
                    <?php endif; ?>

                    <!-- Informasi URL -->
                    <div class="info-url text-start mb-4">
                        <small class="text-muted"><i class="bi bi-link me-1"></i> URL tujuan:</small>
                        <code class="d-block mt-1"><?= htmlspecialchars($qr_data); ?></code>
                    </div>

                    <p class="text-muted">
                        <i class="bi bi-camera me-1"></i> Scan QR menggunakan aplikasi kamera atau Google Lens
                    </p>

                    <!-- Tombol Aksi -->
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <?php if ($qr_exists): ?>
                            <a href="<?= $file; ?>" download="attendance_qr.png" class="btn btn-download">
                                <i class="bi bi-download me-1"></i> Download QR
                            </a>
                        <?php endif; ?>
                        <a href="dashboard.php" class="btn btn-back">
                            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                        </a>
                    </div>

                    <hr class="my-4">

                    <div class="alert alert-light small">
                        <i class="bi bi-info-circle me-1"></i> 
                        QR ini statis. Jika URL berubah, silakan hapus file QR dan refresh halaman ini untuk membuat ulang.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>