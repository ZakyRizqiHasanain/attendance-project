<?php
session_start();
include '../auth/admin_check.php';
include '../config/database.php';

date_default_timezone_set('Asia/Jakarta');

$admin_id = $_SESSION['user_id'];

$admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id='$admin_id'"));
$admin_photo = !empty($admin['photo']) ? $admin['photo'] : 'default.png';

// ========== STATISTIK ==========
$total_attendance = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance"))['total'];
$hadir = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance WHERE status = 'Hadir'"))['total'];
$terlambat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance WHERE status = 'Terlambat'"))['total'];
$alpha = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance WHERE status = 'Alpha'"))['total'];

// Data untuk grafik (7 hari terakhir)
$chart_labels = [];
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance WHERE DATE(check_in) = '$date'"))['total'];
    $chart_labels[] = date('d M', strtotime($date));
    $chart_data[] = $count;
}

// Query data attendance
$query = mysqli_query($conn, "
SELECT attendance.*, users.name
FROM attendance
JOIN users ON attendance.user_id = users.id
ORDER BY attendance.id DESC
");

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

        <!-- PROFILE DROPDOWN -->
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-white" id="adminDropdown" data-bs-toggle="dropdown">
                    <img src="../uploads/<?= $admin_photo; ?>" width="42" height="42" class="rounded-circle border border-2 border-white me-2" style="object-fit:cover;">
                    <span class="fw-semibold"><?= htmlspecialchars($admin['name']); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 py-2" style="border-radius:16px;min-width:250px;">
                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-bold"><?= htmlspecialchars($admin['name']); ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($admin['email']); ?></div>
                    </li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="users.php"><i class="bi bi-people-fill"></i> Users</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="attendance_data.php"><i class="bi bi-calendar-check"></i> Attendance</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="qr_generate.php"><i class="bi bi-qr-code"></i> QR Attendance</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="settings.php"><i class="bi bi-gear-fill"></i> Settings</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="leave.php"><i class="bi bi-journal-medical"></i>Pengajuan Izin / Sakit</a></li>
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

    <!-- STATS -->
    <div class="row g-4 mb-5">

        <div class="col-md-3">
            <div class="card card-stats">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-bg me-3"><i class="bi bi-database"></i></div>
                    <div>
                        <h6 class="text-muted">Total</h6>
                        <h3><?= $total_attendance ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-stats">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-bg text-success me-3"><i class="bi bi-check-circle"></i></div>
                    <div>
                        <h6 class="text-muted">Hadir</h6>
                        <h3><?= $hadir ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-stats">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-bg text-warning me-3"><i class="bi bi-clock"></i></div>
                    <div>
                        <h6 class="text-muted">Terlambat</h6>
                        <h3><?= $terlambat ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-stats">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-bg text-danger me-3"><i class="bi bi-x-circle"></i></div>
                    <div>
                        <h6 class="text-muted">Alpha</h6>
                        <h3><?= $alpha ?></h3>
                    </div>
                </div>
            </div>
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

                <?php $no=1; while($row = mysqli_fetch_assoc($query)) { ?>

                    <tr>
                        <td><?= $no++; ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($row['name']); ?></td>
                        <td><?= date('d M Y', strtotime($row['attendance_date'])) ?></td>
                        <td><?= $row['check_in'] ?></td>
                        <td><?= $row['check_out'] ?: '-' ?></td>
                        <td>
                            <span class="badge bg-success">
                                <?= $row['status'] ?>
                            </span>
                        </td>
                    </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<script>
$(document).ready(function(){
    $('#table').DataTable({
        pageLength: 10
    });
});
</script>

        </div>
    </div>
</nav>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
    new Chart(document.getElementById('attendanceChart'), {
        type: 'bar',
        data: { labels: <?= json_encode($chart_labels) ?>, datasets: [{ label: 'Jumlah Kehadiran', data: <?= json_encode($chart_data) ?>, backgroundColor: '#2c6e2f', borderRadius: 10, borderSkipped: false }] },
        options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'top' } } }
    });
    $(document).ready(function() { $('#attendanceTable').DataTable({ order: [[0,'desc']], pageLength: 10, language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' }, columnDefs: [{ orderable: false, targets: [5,6] }] }); });
</script>
</body>
</html>