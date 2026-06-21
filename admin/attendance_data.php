<?php
session_start();

header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), camera=(), microphone=()");

include '../auth/admin_check.php';
include '../config/database.php';
if (!$conn) {
    die("Database connection failed");
}

date_default_timezone_set('Asia/Jakarta');

/* =========================
   ADMIN DATA
========================= */
$admin_id = $_SESSION['user_id'] ?? null;

if (!$admin_id) {
    header("Location: ../auth/login.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $admin_id);
if (!$stmt->execute()) {
    die("Query error: " . $stmt->error);
}

$result = $stmt->get_result();
if (!$result) {
    die("Gagal mengambil data admin");
}

$admin_id = (int)$_SESSION['user_id'];
$admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$admin_id"));
$admin_photo = !empty($admin['photo']) ? $admin['photo'] : 'default.png';

if (!$admin) {
    die("Data admin tidak ditemukan.");
}
$stmt->close();


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


/* =========================
   CHART DATA (7 HARI)
========================= */
$chart_labels = [];
$chart_data = [];

$dataChart = [];

$queryChart = mysqli_query($conn,"
SELECT
DATE(attendance_date) AS tanggal,
COUNT(*) AS total
FROM attendance
WHERE attendance_date >= CURDATE() - INTERVAL 6 DAY
GROUP BY DATE(attendance_date)
");

if($queryChart){
    while($row = mysqli_fetch_assoc($queryChart)){
        $dataChart[$row['tanggal']] = $row['total'];
    }
}

for($i=6;$i>=0;$i--)
{
    $date = date('Y-m-d', strtotime("-$i days"));

    $chart_labels[] = date('d M', strtotime($date));

    $chart_data[] = $dataChart[$date] ?? 0;
}

if (empty($chart_labels)) {
    $chart_labels = array_fill(0, 7, '-');
    $chart_data   = array_fill(0, 7, 0);
}


/* =========================
   ATTENDANCE DATA
========================= */
$stmt = $conn->prepare("
    SELECT
        a.id,
        a.attendance_date,
        a.check_in,
        a.check_out,
        a.status,
        u.name
    FROM attendance a
    INNER JOIN users u ON a.user_id = u.id
    ORDER BY a.attendance_date DESC, a.id DESC
    LIMIT 200
");

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

if (!$stmt->execute()) {
    die("Query error: " . $stmt->error);
}

$result = $stmt->get_result();

if (!$result) {
    die("Gagal mengambil data attendance");
}

$query = $result;
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance Data</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">

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

<!-- CONTENT -->
<div class="container mt-4">

    <!-- TITLE -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="page-title">
            <i class="bi bi-calendar-check me-2"></i> Data Attendance
        </h2>
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

    <!-- CHART (TARUH DI SINI) -->
    <div class="card mb-4">
        <div class="card-body">
            <canvas id="attendanceChart" style="max-height:300px;"></canvas>
        </div>
    </div>

    <!-- TABLE -->
    <div class="table-container">
        <h5 class="mb-3">
            <i class="bi bi-table me-2"></i> Detail Attendance
        </h5>

        <div class="table-responsive">
            <table id="table" class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Tanggal</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                <?php $no = 1; while($row = mysqli_fetch_assoc($query)) { ?>
                    <?php
                        $status = $row['status'] ?? 'Unknown';
                        $badge = match($status) {
                            'Hadir' => 'bg-success',
                            'Terlambat' => 'bg-warning text-dark',
                            'Alpha' => 'bg-danger',
                            'Izin'       => 'bg-primary',
                            'Sakit'      => 'bg-info',
                            default => 'bg-secondary'
                        };
                        $checkOut = $row['check_out'] ?? '-';
                    ?>

                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-order="<?= htmlspecialchars($row['attendance_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <?= !empty($row['attendance_date'])
                                ? date('d M Y', strtotime($row['attendance_date']))
                                : '-'; ?>
                        </td>
                        <td><?= htmlspecialchars($row['check_in'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($checkOut, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <span class="badge <?= htmlspecialchars($badge, ENT_QUOTES, 'UTF-8'); ?>">
                                <?= $status === 'Alpha' ? 'Tidak Hadir' : $status ?>
                            </span>
                        </td>
                    </tr>

                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function () {
    $('#table').DataTable({
        pageLength: 10,
        order: [[2, 'desc']]
    });
});

/* CHART */
new Chart(document.getElementById('attendanceChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels ?: ['-','-','-','-','-','-','-']) ?>,
        datasets: [{
            label: 'Jumlah Kehadiran',
            data: <?= json_encode($chart_data ?: [0,0,0,0,0,0,0]) ?>,
            backgroundColor: '#2c6e2f',
            borderRadius: 10
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        }
    }
});
</script>
</body>
</html>