<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

include '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Koneksi database gagal.");
}

$user_id = (int)$_SESSION['user_id'];

/* =========================
   USER DATA
========================= */
$stmt = $conn->prepare("
    SELECT id, name, email, photo
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();

$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user_data) {
    session_destroy();
    header("Location: ../index.php");
    exit;
}

$user_photo = 'default.png';
if (
    !empty($user_data['photo']) &&
    file_exists("../uploads/" . basename($user_data['photo']))
) {
    $user_photo = basename($user_data['photo']);
}

/* =========================
   DATE
========================= */
$today = date('Y-m-d');

/* =========================
   ATTENDANCE TODAY
========================= */
$stmt = $conn->prepare("
    SELECT *
    FROM attendance
    WHERE user_id = ?
    AND attendance_date = ?
    LIMIT 1
");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();

$attendance_today = $stmt->get_result()->fetch_assoc();

$stmt->close();

/* =========================
   LEAVE TODAY
========================= */
$stmt = $conn->prepare("
    SELECT *
    FROM leave_requests
    WHERE user_id = ?
    AND leave_date = ?
    AND approval_status = 'Approved'
    LIMIT 1
");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();

$leave_today = $stmt->get_result()->fetch_assoc();

$stmt->close();

/* =========================
   GREETING
========================= */
$hour = (int)date('H');

if ($hour >= 5 && $hour < 11) {
    $greeting = "Selamat Pagi";
} elseif ($hour >= 11 && $hour < 15) {
    $greeting = "Selamat Siang";
} elseif ($hour >= 15 && $hour < 18) {
    $greeting = "Selamat Sore";
} else {
    $greeting = "Selamat Malam";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="dashboard">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="#">
            <i class="bi bi-fingerprint text-primary fs-3"></i>
            <span>Attendance System</span>
        </a>

        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-white" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="../uploads/<?= htmlspecialchars($user_photo) ?>"
                     width="42"
                     height="42"
                     class="rounded-circle border border-2 border-white me-2"
                     style="object-fit: cover;">
                <span class="fw-semibold"><?= htmlspecialchars($user_data['name']); ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 py-2" aria-labelledby="profileDropdown" style="border-radius: 16px; min-width: 200px;">
                <li class="px-3 py-2 border-bottom mb-2">
                    <div class="fw-bold text-dark"><?= htmlspecialchars($user_data['name']); ?></div>
                    <div class="text-muted small text-truncate" style="max-width: 170px;"><?= htmlspecialchars($user_data['email']); ?></div>
                </li>
                <li><a class="dropdown-item" href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                <li><a class="dropdown-item" href="history.php"><i class="bi bi-calendar-range me-2"></i> History</a></li>
                <li><a class="dropdown-item" href="leave.php"><i class="bi bi-journal-plus me-2"></i> Pengajuan Izin / Sakit</a></li>
                <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i> Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger" href="../auth/logout.php" onclick="return confirm('Yakin ingin logout?');">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- MAIN CONTENT -->
<div class="container mt-5">
    <div class="row g-4">
        
        <!-- LEFT COLUMN: Main Greeting & Absen Actions -->
        <div class="col-lg-8">
            
            <!-- Greeting Banner -->
            <div class="card border-0 shadow-sm mb-4 bg-white overflow-hidden" style="border-radius: 20px;">
                <div style="background: var(--primary-gradient); height: 8px;"></div>
                <div class="card-body p-4">
                    <span class="badge bg-primary mb-2 text-uppercase">Dashboard Karyawan</span>
                    <h2 class="dashboard-title mb-1"><?= $greeting; ?>, <?= htmlspecialchars($user_data['name']); ?>!</h2>
                    <p class="text-muted mb-0">Silakan lakukan absensi masuk atau pulang untuk mencatat kehadiran harian Anda.</p>
                </div>
            </div>

            <!-- Attendance Action Cards -->
            <div class="row g-4">
                
                <!-- Card Checkin -->
                <div class="col-md-6">
                    <div class="card text-center border-0 h-100 shadow-sm" style="border-radius: 20px;">
                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <div class="icon-box mx-auto mb-3 bg-success bg-opacity-10 text-success">
                                    <i class="bi bi-box-arrow-in-right fs-1"></i>
                                </div>
                                <h4 class="fw-bold mb-2 text-dark">Check In</h4>
                                <p class="text-muted small mb-4">Lakukan absensi masuk untuk mencatat jam mulai kerja Anda hari ini.</p>
                            </div>
                            <div>
                                <?php if ($leave_today) { ?>
                                    <button class="btn btn-primary w-100 disabled"
                                            style="opacity:.7;cursor:not-allowed;">
                                        <i class="bi bi-check-circle-fill"></i>
                                        Izin / Sakit Disetujui
                                    </button>
                                <?php } elseif ($attendance_today) { ?>
                                    <button class="btn btn-success w-100 disabled"
                                            style="opacity:.7;cursor:not-allowed;">
                                        <i class="bi bi-check-circle-fill"></i>
                                        Sudah Absen Masuk
                                    </button>
                                <?php } else { ?>
                                    <a href="checkin.php" class="btn btn-success w-100 py-2">
                                        <i class="bi bi-box-arrow-in-right me-1"></i>
                                        Absen Masuk
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Checkout -->
                <div class="col-md-6">
                    <div class="card text-center border-0 h-100 shadow-sm" style="border-radius: 20px;">
                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <div class="icon-box mx-auto mb-3 bg-warning bg-opacity-10 text-warning">
                                    <i class="bi bi-box-arrow-right fs-1"></i>
                                </div>
                                <h4 class="dashboard-title mb-2">Check Out</h4>
                                <p class="text-muted small mb-4">Lakukan absensi pulang untuk mengakhiri jam kerja Anda hari ini.</p>
                            </div>
                            <div>
                                <?php if ($leave_today) { ?>
                                    <button class="btn btn-secondary w-100 disabled">
                                        Izin / Sakit
                                    </button>
                                <?php } elseif (!$attendance_today) { ?>
                                    <button class="btn btn-warning w-100 disabled text-white"
                                            style="opacity:.6;cursor:not-allowed;">
                                        Belum Absen Masuk
                                    </button>
                                <?php } elseif (!empty($attendance_today['check_out'])) { ?>
                                    <button class="btn btn-warning w-100 disabled text-white"
                                            style="opacity:.6;cursor:not-allowed;">
                                        <i class="bi bi-check-circle-fill"></i>
                                        Sudah Absen Pulang
                                    </button>
                                <?php } else { ?>
                                    <a href="checkout.php" class="btn btn-warning w-100 py-2 text-white">
                                        Absen Pulang
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- RIGHT COLUMN: Clock Widget & Daily Status Details -->
        <div class="col-lg-4">
            
            <!-- Digital Clock Widget -->
            <div class="card border-0 shadow-sm text-center mb-4 overflow-hidden" style="border-radius: 20px;">
                <div class="card-body p-4">
                    <h5 class="text-muted small text-uppercase mb-3 fw-bold">Waktu Saat Ini</h5>
                    <div class="clock-container mb-2">
                        <span class="clock-dot"></span>
                        <h4 id="clock" class="mb-0"></h4>
                    </div>
                    <p class="text-muted small mb-0 mt-2">Zona Waktu: Asia/Jakarta (WIB)</p>
                </div>
            </div>

            <!-- Today's Attendance Status Detail -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 20px;">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4 text-dark">
                        <i class="bi bi-info-circle text-primary me-2"></i>Status Hari Ini
                    </h5>
                    
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-light text-secondary me-3">
                            <i class="bi bi-box-arrow-in-right fs-5"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Jam Masuk</div>
                            <div class="fw-bold text-dark">
                                <?= $attendance_today ? date('H:i:s', strtotime($attendance_today['check_in'])) : '-'; ?>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-light p-2 rounded-3 me-3 text-secondary">
                            <i class="bi bi-box-arrow-right fs-5"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Jam Pulang</div>
                            <div class="fw-bold text-dark">
                                <?= ($attendance_today && $attendance_today['check_out']) ? date('H:i:s', strtotime($attendance_today['check_out'])) : '-'; ?>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center">
                        <div class="bg-light p-2 rounded-3 me-3 text-secondary">
                            <i class="bi bi-shield-check fs-5"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Status Kehadiran</div>
                            <div>
                                <?php 
                                if ($leave_today) {

                                    echo '<span class="badge bg-primary">Izin / Sakit</span>';

                                } elseif (!$attendance_today) {

                                    echo '<span class="badge bg-danger">Belum Absen</span>';

                                } else {

                                    $status = $attendance_today['status'] ?? '';

                                    switch ($status) {

                                        case 'Hadir':
                                            echo '<span class="badge bg-success">Hadir</span>';
                                            break;

                                        case 'Terlambat':
                                            echo '<span class="badge bg-warning text-dark">Terlambat</span>';
                                            break;

                                        case 'Izin':
                                            echo '<span class="badge bg-primary">Izin</span>';
                                            break;

                                        case 'Sakit':
                                            echo '<span class="badge bg-info text-dark">Sakit</span>';
                                            break;

                                        default:
                                            echo '<span class="badge bg-secondary">'.$status.'</span>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>



        </div>

    </div>
</div>

<script>
function updateClock() {
    const now = new Date();
    const time = now.toLocaleTimeString('id-ID', {
        timeZone: 'Asia/Jakarta',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    document.getElementById('clock').innerHTML = time;
}
setInterval(updateClock, 1000);
updateClock();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>