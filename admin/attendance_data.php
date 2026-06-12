<?php
session_start();
include '../config/database.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

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
    <title>Attendance Data - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { user-select: text; -webkit-user-select: text; }
        body { background-color: #f4f6f9; font-family: 'Segoe UI', 'Roboto', sans-serif; }
        .navbar-dark.bg-dark { background-color: #1e2a3a !important; }
        .navbar-brand, .navbar-brand i { color: white !important; }
        .btn-outline-light { color: white; border-color: white; }
        .btn-outline-light:hover { background-color: rgba(255,255,255,0.1); }
        .card-stats { border: none; border-radius: 20px; background: white; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .card-stats:hover { transform: translateY(-5px); }
        .icon-bg { width: 50px; height: 50px; border-radius: 14px; background-color: #eef2f5; color: #2c3e50; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .table-container { background: white; border-radius: 24px; padding: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .page-title { font-weight: 700; color: #1e2a3a; }
        .badge.bg-success { background-color: #2c6e2f !important; }
        .badge.bg-warning { background-color: #e6a017 !important; color: #1e1e1e !important; }
        .badge.bg-danger { background-color: #b91c1c !important; }
        .btn-outline-info { color: #2c3e50; border-color: #cbd5e1; }
        .btn-outline-info:hover { background-color: #eef2f5; color: #1e2a3a; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #eef2f5 !important; color: #1e2a3a !important; border: 1px solid #d1d9e6 !important; }
        .dataTables_filter input { border-radius: 30px; border: 1px solid #cbd5e1; padding: 6px 12px; }
        table.dataTable thead th { border-bottom: 1px solid #e2e8f0; color: #334155; font-weight: 600; }
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
                    <li class="px-3 py-2 border-bottom"><div class="fw-bold"><?= htmlspecialchars($admin['name']); ?></div><div class="text-muted small"><?= htmlspecialchars($admin['email']); ?></div></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="users.php"><i class="bi bi-people-fill"></i> Users</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 active" href="attendance.php"><i class="bi bi-calendar-check"></i> Attendance</a></li>
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
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <h2 class="page-title"><i class="bi bi-calendar-check me-2"></i> Data Kehadiran</h2>
        <span class="badge bg-secondary fs-6 px-3 py-2"><i class="bi bi-database me-1"></i> Total: <?= $total_attendance ?></span>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3 col-sm-6"><div class="card card-stats"><div class="card-body d-flex align-items-center"><div class="icon-bg me-3"><i class="bi bi-check-circle"></i></div><div><h6 class="text-muted mb-1">Hadir</h6><h3 class="mb-0 fw-bold"><?= $hadir ?></h3></div></div></div></div>
        <div class="col-md-3 col-sm-6"><div class="card card-stats"><div class="card-body d-flex align-items-center"><div class="icon-bg me-3"><i class="bi bi-clock"></i></div><div><h6 class="text-muted mb-1">Terlambat</h6><h3 class="mb-0 fw-bold"><?= $terlambat ?></h3></div></div></div></div>
        <div class="col-md-3 col-sm-6"><div class="card card-stats"><div class="card-body d-flex align-items-center"><div class="icon-bg me-3"><i class="bi bi-x-circle"></i></div><div><h6 class="text-muted mb-1">Alpha</h6><h3 class="mb-0 fw-bold"><?= $alpha ?></h3></div></div></div></div>
        <div class="col-md-3 col-sm-6"><div class="card card-stats"><div class="card-body d-flex align-items-center"><div class="icon-bg me-3"><i class="bi bi-graph-up"></i></div><div><h6 class="text-muted mb-1">Total Attendance</h6><h3 class="mb-0 fw-bold"><?= $total_attendance ?></h3></div></div></div></div>
    </div>

    <div class="table-container mb-5">
        <h5 class="mb-3"><i class="bi bi-graph-up me-2"></i> Tren Kehadiran (7 Hari Terakhir)</h5>
        <canvas id="attendanceChart" style="max-height: 300px;"></canvas>
    </div>

    <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0"><i class="bi bi-table me-2"></i> Data Detail Kehadiran</h5>
            <small class="text-muted">*Klik kolom untuk sorting | Gunakan pencarian</small>
        </div>
        <div class="table-responsive">
            <table id="attendanceTable" class="table table-hover align-middle">
                <thead class="table-light"><tr><th>No</th><th>Nama</th><th>Check In</th><th>Check Out</th><th>Status</th><th>Lokasi</th><th>Selfie</th></tr></thead>
                <tbody>
                    <?php $no=1; while($data = mysqli_fetch_assoc($query)): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($data['name']) ?></td>
                        <td><?= $data['check_in'] ?></td>
                        <td><?= $data['check_out'] ?: '<span class="text-danger">Belum Checkout</span>' ?></td>
                        <td><?php if($data['status']=='Hadir'): ?><span class="badge bg-success px-3 py-2">Hadir</span><?php elseif($data['status']=='Terlambat'): ?><span class="badge bg-warning text-dark px-3 py-2">Terlambat</span><?php else: ?><span class="badge bg-danger px-3 py-2">Alpha</span><?php endif; ?></td>
                        <td><a href="https://www.google.com/maps?q=<?= $data['latitude']; ?>,<?= $data['longitude']; ?>" target="_blank" class="btn btn-sm btn-outline-info"><i class="bi bi-map"></i> Maps</a></td>
                        <td><?php if($data['selfie']): ?><img src="../uploads/<?= $data['selfie'] ?>" width="60" height="60" class="rounded shadow-sm" style="object-fit: cover;"><?php else: ?><span class="text-muted">Tidak ada</span><?php endif; ?></td>
                    </tr>
                    <?php endwhile; ?>
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
    new Chart(document.getElementById('attendanceChart'), {
        type: 'bar',
        data: { labels: <?= json_encode($chart_labels) ?>, datasets: [{ label: 'Jumlah Kehadiran', data: <?= json_encode($chart_data) ?>, backgroundColor: '#2c6e2f', borderRadius: 10, borderSkipped: false }] },
        options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'top' } } }
    });
    $(document).ready(function() { $('#attendanceTable').DataTable({ order: [[0,'desc']], pageLength: 10, language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' }, columnDefs: [{ orderable: false, targets: [5,6] }] }); });
</script>
</body>
</html>