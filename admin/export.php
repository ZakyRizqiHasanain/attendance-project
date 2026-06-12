<?php
include '../auth/admin_check.php';
include '../config/database.php';

// ========== DATA UNTUK STATISTIK ==========
$total_attendance = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance"))['total'];
$hadir = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance WHERE status = 'Hadir'"))['total'];
$terlambat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance WHERE status = 'Terlambat'"))['total'];

// Query data attendance
$query = mysqli_query($conn, "
SELECT attendance.*, users.name
FROM attendance
JOIN users ON attendance.user_id = users.id
ORDER BY attendance.id DESC
");

// Admin data untuk navbar
$admin_id = $_SESSION['user_id'];
$admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id='$admin_id'"));
$admin_photo = !empty($admin['photo']) ? $admin['photo'] : 'default.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Data - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        * { user-select: text; -webkit-user-select: text; }
        body { background-color: #f4f6f9; font-family: 'Segoe UI', 'Roboto', sans-serif; }
        .navbar-dark.bg-dark { background-color: #1e2a3a !important; }
        .navbar-brand, .navbar-brand i { color: white !important; }
        .btn-outline-light { color: white; border-color: white; }
        .btn-outline-light:hover { background-color: rgba(255,255,255,0.1); }
        .card-stats { border: none; border-radius: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); background: white; }
        .icon-bg { width: 50px; height: 50px; border-radius: 14px; background-color: #eef2f5; color: #2c3e50; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .table-container { background: white; border-radius: 24px; padding: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .page-title { font-weight: 700; color: #1e2a3a; }
        .btn-excel { background-color: #2c6e2f; color: white; border-radius: 40px; padding: 8px 25px; border: none; }
        .btn-excel:hover { background-color: #1f5422; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="dashboard.php"><i class="bi bi-fingerprint fs-3 me-2"></i> Attendance System</a>
        <div class="d-flex align-items-center">
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-white" data-bs-toggle="dropdown">
                    <img src="../uploads/<?= $admin_photo; ?>" width="42" height="42" class="rounded-circle border border-2 border-white me-2" style="object-fit:cover;">
                    <span class="fw-semibold"><?= htmlspecialchars($admin['name']); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 py-2">
                    <li class="px-3 py-2 border-bottom"><div class="fw-bold"><?= htmlspecialchars($admin['name']); ?></div><div class="text-muted small"><?= htmlspecialchars($admin['email']); ?></div></li>
                    <li><a class="dropdown-item" href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                    <li><a class="dropdown-item" href="users.php"><i class="bi bi-people-fill me-2"></i> Users</a></li>
                    <li><a class="dropdown-item" href="attendance_data.php"><i class="bi bi-calendar-check me-2"></i> Attendance</a></li>
                    <li><a class="dropdown-item" href="qr_generate.php"><i class="bi bi-qr-code me-2"></i> QR Attendance</a></li>
                    <li><a class="dropdown-item active" href="export.php"><i class="bi bi-file-earmark-excel me-2"></i> Export Excel</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="../auth/logout.php" onclick="return confirm('Yakin ingin keluar?');"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="page-title"><i class="bi bi-file-earmark-excel me-2"></i> Export Data Kehadiran</h2>
        <a href="export_excel.php" class="btn btn-excel"><i class="bi bi-download me-1"></i> Download Excel (XLS)</a>
    </div>

    <!-- Statistik sederhana (hanya 3 card) -->
    <div class="row g-4 mb-5">
        <div class="col-md-4"><div class="card card-stats"><div class="card-body d-flex align-items-center"><div class="icon-bg me-3"><i class="bi bi-database"></i></div><div><h6 class="text-muted mb-1">Total Records</h6><h3 class="mb-0"><?= $total_attendance ?></h3></div></div></div></div>
        <div class="col-md-4"><div class="card card-stats"><div class="card-body d-flex align-items-center"><div class="icon-bg me-3"><i class="bi bi-check-circle"></i></div><div><h6 class="text-muted mb-1">Hadir</h6><h3 class="mb-0"><?= $hadir ?></h3></div></div></div></div>
        <div class="col-md-4"><div class="card card-stats"><div class="card-body d-flex align-items-center"><div class="icon-bg me-3"><i class="bi bi-clock"></i></div><div><h6 class="text-muted mb-1">Terlambat</h6><h3 class="mb-0"><?= $terlambat ?></h3></div></div></div></div>
    </div>

    <!-- Tabel preview data (tanpa pagination dan tanpa info) -->
    <div class="table-container">
        <h5 class="mb-3"><i class="bi bi-table me-2"></i> Preview Data Attendance (akan diexport)</h5>
        <div class="table-responsive">
            <table id="exportTable" class="table table-hover align-middle">
                <thead class="table-light">
                    <tr><th>No</th><th>Nama</th><th>Check In</th><th>Check Out</th><th>Status</th><th>Latitude</th><th>Longitude</th></tr>
                </thead>
                <tbody>
                    <?php $no=1; while($data = mysqli_fetch_assoc($query)) { ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($data['name']); ?></td>
                        <td><?= $data['check_in']; ?></td>
                        <td><?= $data['check_out'] ?: '-'; ?></td>
                        <td><?= $data['status']; ?></td>
                        <td><?= $data['latitude']; ?></td>
                        <td><?= $data['longitude']; ?></td>
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
<script>
    $(document).ready(function() {
        $('#exportTable').DataTable({
            paging: false,      // Hilangkan pagination (Sebelumnya 1 Selanjutnya)
            info: false,        // Hilangkan teks "Menampilkan 1 sampai 1 dari 1 entri"
            ordering: true,     // Tetap bisa sorting kolom
            searching: true,    // Tetap ada kotak pencarian
            language: {
                search: "Cari:",
                zeroRecords: "Tidak ada data ditemukan",
                emptyTable: "Belum ada data attendance"
            }
        });
    });
</script>
</body>
</html>