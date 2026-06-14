<?php
session_start();
date_default_timezone_set('Asia/Jakarta'); // set zona waktu Indonesia

include '../auth/admin_check.php';
include '../config/database.php';

$admin_id = $_SESSION['user_id'];

$admin = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT * FROM users WHERE id='$admin_id'"
    )
);

$admin_photo = !empty($admin['photo'])
    ? $admin['photo']
    : 'default.png';

// ========== STATISTIK ==========
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"))['total'];

$today = date('Y-m-d');
$hadir_hari_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance WHERE DATE(check_in) = '$today' AND status = 'Hadir'"))['total'];
$terlambat_hari_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance WHERE DATE(check_in) = '$today' AND status = 'Terlambat'"))['total'];
$belum_checkout = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance WHERE DATE(check_in) = '$today' AND check_out IS NULL"))['total'];

// Persentase kehadiran bulan ini
$bulan_ini = date('Y-m');
$hadir_bulan_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) as total FROM attendance WHERE DATE(check_in) LIKE '$bulan_ini%'"))['total'];
$persentase = $total_users > 0 ? round(($hadir_bulan_ini / $total_users) * 100, 2) : 0;

// Data grafik (7 hari terakhir)
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
SELECT 
    attendance.*,
    users.name,
    leave_requests.type as leave_type,
    leave_requests.approval_status as leave_status
FROM attendance
JOIN users ON attendance.user_id = users.id
LEFT JOIN leave_requests 
    ON users.id = leave_requests.user_id 
    AND leave_requests.leave_date = attendance.attendance_date
ORDER BY attendance.attendance_date DESC, attendance.id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Bootstrap 5 + Icons + Chart.js + DataTables -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="../auth/logout.php" onclick="return confirm('Yakin ingin keluar?');"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- CONTENT UTAMA -->
<div class="container mt-4">
    <!-- Header Tanggal -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="page-title"><i class="bi bi-calendar-check-fill me-2"></i> Dashboard Kehadiran</h2>
        <span class="badge bg-secondary px-3 py-2"><i class="bi bi-calendar3 me-1"></i> <?= date('l, d F Y') ?></span>
    </div>

    <!-- Kartu Statistik -->
    <div class="row g-4 mb-5">
        <div class="col-md-3 col-sm-6">
            <div class="card card-stats">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-bg bg-secondary bg-opacity-10 text-secondary me-3">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Total Users</h6>
                        <h3 class="mb-0 fw-bold"><?= $total_users ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card card-stats">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-bg bg-success bg-opacity-10 text-success me-3">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Hadir Hari Ini</h6>
                        <h3 class="mb-0 fw-bold"><?= $hadir_hari_ini ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card card-stats">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-bg bg-warning bg-opacity-10 text-warning me-3">
                        <i class="bi bi-clock"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Terlambat</h6>
                        <h3 class="mb-0 fw-bold"><?= $terlambat_hari_ini ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card card-stats">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-bg bg-danger bg-opacity-10 text-danger me-3">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Belum Checkout</h6>
                        <h3 class="mb-0 fw-bold"><?= $belum_checkout ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik dan Persentase -->
    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="card-grafik">
                <h5 class="mb-3"><i class="bi bi-graph-up me-2"></i> Tren Kehadiran (7 Hari Terakhir)</h5>
                <canvas id="attendanceChart" style="max-height: 300px;"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-grafik h-100 d-flex flex-column justify-content-center">
                <h5 class="mb-3"><i class="bi bi-percent me-2"></i> Kehadiran Bulan Ini</h5>
                <div class="text-center">
                    <div class="display-1 fw-bold text-success"><?= $persentase ?>%</div>
                    <p class="text-muted">dari <?= $total_users ?> user</p>
                    <div class="progress mt-3" style="height: 12px;">
                        <div class="progress-bar bg-success" style="width: <?= $persentase ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Data Attendance -->
    <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0"><i class="bi bi-table me-2"></i> Data Kehadiran Detail</h5>
            <small class="text-muted">*Klik kolom untuk sorting | Gunakan search</small>
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
                    <?php
                    $no = 1;
                    while($data = mysqli_fetch_assoc($query)) {
                    ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($data['name']); ?></td>
                        <td><?= $data['attendance_date']; ?></td>
                        <td><?= $data['check_in']; ?></td>
                        <td>
                            <?php if($data['check_out']) {
                                echo $data['check_out'];
                            } else {
                                echo "<span class='text-danger'><i class='bi bi-x-circle'></i> Belum Checkout</span>";
                            } ?>
                        </td>
                        <td>
                            <?php
if(!empty($data['leave_type']) && $data['leave_status'] == 'Approved'){

    if($data['leave_type'] == 'Izin'){
        echo "<span class='badge bg-primary px-3 py-2'>Izin</span>";
    } else {
        echo "<span class='badge bg-info px-3 py-2'>Sakit</span>";
    }

}
elseif(!empty($data['check_in'])){

    if($data['check_in'] <= "08:00:00"){
        echo "<span class='badge bg-success px-3 py-2'>Hadir</span>";
    } else {
        echo "<span class='badge bg-warning text-dark px-3 py-2'>Terlambat</span>";
    }

}
else{
    echo "<span class='badge bg-danger px-3 py-2'>Alpha</span>";
}
?>
                        </td>
                        <td>
                            <a href="https://www.google.com/maps?q=<?= $data['latitude']; ?>,<?= $data['longitude']; ?>"
                               target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-map"></i> Maps
                            </a>
                        </td>
                        <td>
                            <?php if($data['selfie']) { ?>
                                <img src="../uploads/<?= $data['selfie']; ?>" class="img-thumbnail-custom shadow-sm">
                            <?php } else { echo "<span class='text-muted'>Tidak ada</span>"; } ?>
                        </td>
                        <td>
                            <?php if($data['selfie_checkout']) { ?>
                                <img src="../uploads/<?= $data['selfie_checkout']; ?>" class="img-thumbnail-custom shadow-sm">
                            <?php } else { echo "<span class='text-danger'>Belum Checkout</span>"; } ?>
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
    // Chart - warna hijau (tanpa biru)
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
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top' }
            }
        }
    });

    // DataTable
    $(document).ready(function() {
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