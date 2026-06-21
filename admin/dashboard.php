<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

include '../auth/admin_check.php';
include '../config/database.php';

/* ================= ADMIN ================= */
$admin_id = isset($_SESSION['user_id'])
    ? (int)$_SESSION['user_id']
    : 0;

$stmt = $conn->prepare("
    SELECT *
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $admin_id);
$stmt->execute();

$result = $stmt->get_result();
$admin = $result->fetch_assoc();

$stmt->close();

if (!$admin) {
    die("Data admin tidak ditemukan.");
}

$admin_photo = !empty($admin['photo'])
    ? $admin['photo']
    : 'default.png'; 

/* ================= DATE ================= */
$today = date('Y-m-d');
$bulan_ini = date('Y-m');

/* =========================
   STATISTIK
========================= */
$statsQuery = mysqli_query($conn,"
SELECT
    (SELECT COUNT(*) FROM attendance) AS total,

    (SELECT COUNT(*) FROM attendance 
    WHERE attendance_date = CURDATE()
    AND status = 'Hadir') AS hadir,

    (SELECT COUNT(*) FROM attendance 
    WHERE attendance_date = CURDATE()
    AND status = 'Terlambat') AS terlambat,

    (SELECT COUNT(*) FROM attendance 
     WHERE attendance_date = CURDATE()
     AND status = 'Alpha') AS alpha,

    (SELECT COUNT(*) FROM attendance
     WHERE attendance_date = CURDATE()
     AND status = 'Izin') AS izin,

    (SELECT COUNT(*) FROM attendance
     WHERE attendance_date = CURDATE()
     AND status = 'Sakit') AS sakit
");

$stats = mysqli_fetch_assoc($statsQuery);

if (!$stats) {
    $stats = [
        'total' => 0,
        'hadir' => 0,
        'terlambat' => 0,
        'alpha' => 0
    ];
}

$total_attendance = $stats['total'] ?? 0;
$hadir            = $stats['hadir'] ?? 0;
$terlambat        = $stats['terlambat'] ?? 0;
$alpha            = $stats['alpha'] ?? 0;
$izin  = $stats['izin'] ?? 0;
$sakit = $stats['sakit'] ?? 0;

/* ================= BULAN INI ================= */
$stmt = $conn->prepare("
SELECT COUNT(DISTINCT user_id) AS total
FROM attendance
WHERE DATE_FORMAT(attendance_date, '%Y-%m') = ?
");
$stmt->bind_param("s", $bulan_ini);
$stmt->execute();
$hadir_bulan_ini = $stmt->get_result()->fetch_assoc()['total'];

$persentase = $total_attendance > 0 ? round(($hadir_bulan_ini / $total_attendance) * 100, 2) : 0;

/* ================= CHART 7 HARI (FIX GAP DATA) ================= */
$stmt = $conn->prepare("
SELECT attendance_date AS date, COUNT(*) AS total
FROM attendance
WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
GROUP BY attendance_date
ORDER BY attendance_date ASC
");
$stmt->execute();
$result = $stmt->get_result();

$data_map = [];
while ($row = $result->fetch_assoc()) {
    $data_map[$row['date']] = $row['total'];
}

$chart_labels = [];
$chart_data = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d M', strtotime($date));
    $chart_data[] = $data_map[$date] ?? 0;
}

/* ================= ATTENDANCE DATA ================= */
$query = mysqli_query($conn, "
SELECT 
    attendance.*,
    users.name,
    leave_requests.type AS leave_type,
    leave_requests.approval_status AS leave_status
FROM attendance
JOIN users ON attendance.user_id = users.id
LEFT JOIN leave_requests 
    ON attendance.user_id = leave_requests.user_id 
    AND attendance.attendance_date = leave_requests.leave_date
ORDER BY attendance.attendance_date DESC, attendance.id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <!-- Bootstrap & Libraries (TETAP) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="page-title"><i class="bi bi-calendar-check-fill me-2"></i> Dashboard Kehadiran</h2>
        <span class="badge bg-secondary px-3 py-2">
            <i class="bi bi-calendar3 me-1"></i> <?= date('l, d F Y') ?>
        </span>
    </div>

    <!-- STATISTIK -->
    <div class="row g-4 mb-5">

        <div class="col-md-4">
            <div class="card card-stats">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-bg me-3"><i class="bi bi-database"></i></div>
                    <div>
                        <h6 class="text-muted mb-1">Total Records</h6>
                        <h3 class="mb-0"><?= (int)$total_attendance ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-stats">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-bg me-3"><i class="bi bi-check-circle"></i></div>
                    <div>
                        <h6 class="text-muted mb-1">Hadir</h6>
                        <h3 class="mb-0"><?= (int)$hadir ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-stats">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-bg me-3"><i class="bi bi-clock"></i></div>
                    <div>
                        <h6 class="text-muted mb-1">Terlambat</h6>
                        <h3 class="mb-0"><?= (int)$terlambat ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-stats">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-bg me-3">
                        <i class="bi bi-person-x"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Alpha</h6>
                        <h3 class="mb-0"><?= (int)$alpha ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-stats">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-bg me-3">
                        <i class="bi bi-journal-check"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Izin</h6>
                        <h3 class="mb-0"><?= (int)$izin ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-stats">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-bg me-3">
                        <i class="bi bi-heart-pulse"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Sakit</h6>
                        <h3 class="mb-0"><?= (int)$sakit ?></h3>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- CHART -->
    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="card-grafik">
                <h5 class="mb-3"><i class="bi bi-graph-up me-2"></i> Tren Kehadiran (7 Hari Terakhir)</h5>
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card-grafik h-100 d-flex flex-column justify-content-center">
                <h5 class="mb-3"><i class="bi bi-percent me-2"></i> Kehadiran Bulan Ini</h5>

                <div class="text-center">
                    <div class="display-1 fw-bold text-success"><?= $persentase ?>%</div>
                    <p class="text-muted">dari <?= (int)$total_attendance ?> user</p>

                    <div class="progress mt-3" style="height: 12px;">
                        <div class="progress-bar bg-success" style="width: <?= $persentase ?>%;"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- TABLE -->
    <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0"><i class="bi bi-table me-2"></i> Data Kehadiran Detail</h5>
        </div>

        <div class="table-responsive shadow-sm rounded">
            <table id="attendanceTable" class="table table-hover align-middle nowrap">
                <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Tanggal Absen</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Status</th>
                    <th>Lokasi</th>
                    <th>Selfie In</th>
                    <th>Selfie Out</th>
                </tr>
                </thead>

                <tbody>
                <?php $no = 1; while($data = mysqli_fetch_assoc($query)) { ?>
                    <tr>
                        <td><?= $no++; ?></td>

                        <td class="fw-semibold">
                            <?= htmlspecialchars($data['name']); ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($data['attendance_date']); ?>
                        </td>

                        <?php
                        // Tentukan status terlebih dahulu
                        if (!empty($data['leave_type']) && $data['leave_status'] == 'Approved') {

                            $status = $data['leave_type']; // Izin / Sakit

                        } elseif (!empty($data['check_in'])) {

                            $jam_masuk = date('H:i:s', strtotime($data['check_in']));

                            $status = ($jam_masuk <= '08:00:00')
                                ? 'Hadir'
                                : 'Terlambat';

                        } else {

                            $status = 'Tidak Hadir';
                        }
                        ?>

                        <!-- CHECK IN -->
                        <td>
                            <?php if (in_array($status, ['Izin','Sakit','Tidak Hadir'])) { ?>
                                -
                            <?php } else { ?>
                                <?= htmlspecialchars($data['check_in']); ?>
                            <?php } ?>
                        </td>

                        <!-- CHECK OUT -->
                        <td>
                            <?php if (in_array($status, ['Izin','Sakit','Tidak Hadir'])) { ?>
                                -
                            <?php } elseif (!empty($data['check_out'])) { ?>
                                <?= htmlspecialchars($data['check_out']); ?>
                            <?php } else { ?>
                                <span class="text-danger">
                                    <i class="bi bi-x-circle"></i> Belum Checkout
                                </span>
                            <?php } ?>
                        </td>

                        <!-- STATUS -->
                        <td>
                            <?php
                            if ($status == 'Izin') {

                                echo "<span class='badge bg-primary px-3 py-2'>Izin</span>";

                            } elseif ($status == 'Sakit') {

                                echo "<span class='badge bg-info px-3 py-2'>Sakit</span>";

                            } elseif ($status == 'Hadir') {

                                echo "<span class='badge bg-success px-3 py-2'>Hadir</span>";

                            } elseif ($status == 'Terlambat') {

                                echo "<span class='badge bg-warning text-dark px-3 py-2'>Terlambat</span>";

                            } else {

                                echo "<span class='badge bg-danger px-3 py-2'>Tidak Hadir</span>";
                            }
                            ?>
                        </td>

                        <!-- LOKASI -->
                        <td>
                            <?php if (
                                !in_array($status, ['Izin','Sakit','Tidak Hadir']) &&
                                !empty($data['latitude']) &&
                                !empty($data['longitude'])
                            ) { ?>

                                <a href="https://www.google.com/maps?q=<?= $data['latitude']; ?>,<?= $data['longitude']; ?>"
                                target="_blank"
                                class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-map"></i> Maps
                                </a>

                            <?php } else { ?>
                                -
                            <?php } ?>
                        </td>

                        <!-- SELFIE IN -->
                        <td>
                            <?php if (in_array($status, ['Izin','Sakit','Tidak Hadir'])) { ?>

                                -

                            <?php } elseif (!empty($data['selfie'])) { ?>

                                <img src="../uploads/<?= htmlspecialchars($data['selfie']); ?>"
                                    class="img-thumbnail-custom shadow-sm">

                            <?php } else { ?>

                                <span class="text-muted">Tidak ada</span>

                            <?php } ?>
                        </td>

                        <!-- SELFIE OUT -->
                        <td>
                            <?php if (in_array($status, ['Izin','Sakit','Tidak Hadir'])) { ?>

                                -

                            <?php } elseif (!empty($data['selfie_checkout'])) { ?>

                                <img src="../uploads/<?= htmlspecialchars($data['selfie_checkout']); ?>"
                                    class="img-thumbnail-custom shadow-sm">

                            <?php } else { ?>

                                <span class="text-danger">Belum Checkout</span>

                            <?php } ?>
                        </td>

                    </tr>
                <?php } ?>
                </tbody>

            </table>
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
const ctx = document.getElementById('attendanceChart').getContext('2d');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Jumlah Kehadiran',
            data: <?= json_encode($chart_data) ?>,
            backgroundColor: '#2c6e2f',
            borderRadius: 10,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true
    }
});

$(document).ready(function () {
    $('#attendanceTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 10,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
        },
        columnDefs: [
            { orderable: false, targets: [5,6,7] }
        ]
    });
});
</script>

</body>
</html>