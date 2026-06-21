<?php
session_start();

include '../auth/admin_check.php';
include '../config/database.php';
include '../libs/phpqrcode/qrlib.php';

/* =========================
   VALIDASI SESSION
========================= */
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

/* =========================
   QR DATA
========================= */
$qr_data = "http://10.69.9.215/attendance-project/user/scan_qr.php";

/* =========================
   QR DIRECTORY (SERVER PATH)
========================= */
$qr_dir_server = __DIR__ . "/../assets/qrcode/";

/* =========================
   QR DIRECTORY (URL PATH) ← FIX UTAMA
========================= */
$qr_dir_url = "../assets/qrcode/";

/* =========================
   CREATE FOLDER IF NOT EXISTS
========================= */
if (!is_dir($qr_dir_server)) {
    mkdir($qr_dir_server, 0755, true);
}

/* =========================
   FILE PATH (SERVER)
========================= */
$file_server = $qr_dir_server . "attendance_qr.png";

/* =========================
   FILE PATH (URL)
========================= */
$file_url = $qr_dir_url . "attendance_qr.png";

/* =========================
   GENERATE QR
========================= */
QRcode::png($qr_data, $file_server, QR_ECLEVEL_H, 10);

$qr_exists = file_exists($file_server);

/* =========================
   ADMIN DATA (SECURE)
========================= */
$admin_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT name, email, photo FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin_id = (int)$_SESSION['user_id'];
$admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$admin_id"));
$admin_photo = !empty($admin['photo']) ? $admin['photo'] : 'default.png';

if (!$admin) {
    die("Data admin tidak ditemukan.");
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Attendance - Attendance System</title>
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
                            <img src="<?= $file_url ?>?t=<?= time() ?>" 
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