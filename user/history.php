<?php
session_start();
include '../config/database.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

date_default_timezone_set('Asia/Jakarta');

/* =========================
   AMBIL DATA USER
========================= */
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id='$user_id'");
$user_data = mysqli_fetch_assoc($user_query);

$user_photo = !empty($user_data['photo']) ? $user_data['photo'] : 'default.png';

$today = date('Y-m-d');

/*
|----------------------------------------------------------------
| DATA ATTENDANCE
|----------------------------------------------------------------
*/
$query = mysqli_query($conn, "
SELECT * FROM attendance
WHERE user_id='$user_id'
ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>History Attendance</title>

<!-- BOOTSTRAP -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- ICONS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<link rel="stylesheet" href="../assets/style.css">

</head>
<body class="dashboard">

<!-- NAVBAR (STYLE ADMIN) -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid px-4">

        <!-- BRAND -->
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="#">
            <i class="bi bi-fingerprint text-primary fs-3"></i>
            <span>Attendance System</span>
        </a>

        <!-- RIGHT MENU -->
        <div class="d-flex align-items-center">

            <!-- EXPORT -->
            <a href="export.php" class="btn btn-outline-light btn-sm me-3">
                Export Excel
            </a>

            <!-- DROPDOWN PROFILE -->
            <div class="dropdown">

                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-white"
                   data-bs-toggle="dropdown">

                    <img src="../uploads/<?= $user_photo; ?>"
                         width="40"
                         height="40"
                         class="rounded-circle border border-2 border-white me-2"
                         style="object-fit: cover;">

                    <span class="fw-semibold"><?= $_SESSION['name']; ?></span>

                </a>

                <!-- DROPDOWN MENU -->
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 py-2"
                    style="border-radius: 16px; min-width: 220px;">

                    <!-- PROFILE HEADER -->
                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-bold text-dark"><?= $_SESSION['name']; ?></div>
                        <div class="text-muted small text-truncate">
                            <?= $user_data['email']; ?>
                        </div>
                    </li>

                    <!-- MENU -->
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
<div class="container mt-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark">
            <i class="bi bi-calendar-range me-2"></i>
            Riwayat Absensi
        </h3>

        <span class="badge bg-secondary px-3 py-2">
            Total: <?= mysqli_num_rows($query); ?>
        </span>
    </div>

    <!-- TABLE -->
    <div class="table-container">
        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Status</th>
                        <th>Lokasi</th>
                        <th>Selfie Masuk</th>
                        <th>Selfie Pulang</th>
                    </tr>
                </thead>

                <tbody>

                <?php $no=1; while($data=mysqli_fetch_assoc($query)) { ?>

                    <tr>

                        <td><?= $no++; ?></td>

                        <!-- TANGGAL -->
                        <td>
                            <?= date('d F Y', strtotime($data['attendance_date'])); ?>
                        </td>

                        <!-- JAM MASUK -->
                        <td>
                            <?= $data['check_in'] ? date('H:i:s', strtotime($data['check_in'])) : '-'; ?>
                        </td>

                        <!-- JAM PULANG -->
                        <td>
                            <?= $data['check_out'] ? date('H:i:s', strtotime($data['check_out'])) : '-'; ?>
                        </td>

                        <!-- STATUS -->
                        <td>
                            <?php if($data['status']=='Hadir') { ?>
                                <span class="badge bg-success">Hadir</span>

                            <?php } elseif($data['status']=='Terlambat') { ?>
                                <span class="badge bg-warning text-dark">Terlambat</span>

                            <?php } elseif($data['status']=='Izin') { ?>
                                <span class="badge bg-primary">Izin</span>

                            <?php } elseif($data['status']=='Sakit') { ?>
                                <span class="badge bg-info text-dark">Sakit</span>

                            <?php } else { ?>
                                <span class="badge bg-danger">Tidak Hadir</span>
                            <?php } ?>
                        </td>

                        <!-- LOKASI -->
                        <td>
                            <?php if($data['latitude'] && $data['longitude']) { ?>
                                <a href="https://www.google.com/maps?q=<?= $data['latitude']; ?>,<?= $data['longitude']; ?>"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-map"></i>
                                </a>
                            <?php } else { echo "-"; } ?>
                        </td>

                        <!-- SELFIE MASUK -->
                        <td>
                            <?php if($data['selfie']) { ?>
                                <img src="../uploads/<?= $data['selfie']; ?>" class="img-thumb">
                            <?php } else { echo "-"; } ?>
                        </td>

                        <!-- SELFIE PULANG -->
                        <td>
                            <?php if($data['selfie_checkout']) { ?>
                                <img src="../uploads/<?= $data['selfie_checkout']; ?>" class="img-thumb">
                            <?php } else { echo "-"; } ?>
                        </td>

                    </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>