<?php
include '../auth/admin_check.php';
include '../config/database.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Koneksi database tidak valid.");
}

/* =========================
   AMBIL DATA STATISTIK (AMAN)
========================= */
$total_attendance = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM attendance")
)['total'] ?? 0;

$hadir = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM attendance WHERE status = 'Hadir'")
)['total'] ?? 0;

$terlambat = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM attendance WHERE status = 'Terlambat'")
)['total'] ?? 0;

$alpha = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM attendance WHERE status = 'Alpha'")
)['total'] ?? 0;

$izin = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM attendance WHERE status = 'Izin'")
)['total'] ?? 0;

$sakit = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM attendance WHERE status = 'Sakit'")
)['total'] ?? 0;

/* =========================
   QUERY DATA ATTENDANCE
========================= */
$query = mysqli_query($conn, "
    SELECT a.*, u.name  
    FROM attendance a
    INNER JOIN users u ON a.user_id = u.id
    ORDER BY a.id DESC
");

/* =========================
   ADMIN DATA (AMAN)
========================= */
$admin_id = $_SESSION['user_id'];

$stmtAdmin = $conn->prepare("SELECT name, email, photo FROM users WHERE id = ?");
$stmtAdmin->bind_param("i", $admin_id);
$stmtAdmin->execute();
$adminResult = $stmtAdmin->get_result();
$admin_id = (int)$_SESSION['user_id'];
$admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$admin_id"));
$admin_photo = !empty($admin['photo']) ? $admin['photo'] : 'default.png';

if (!$admin) {
    die("Data admin tidak ditemukan.");
}
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
        <h2 class="page-title">
            <i class="bi bi-file-earmark-excel me-2"></i> Export Data Kehadiran
        </h2>

        <a href="export_excel.php" class="btn btn-excel">
            <i class="bi bi-download me-1"></i> Download Excel (XLS)
        </a>
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

    <!-- TABLE -->
    <div class="table-container">

        <h5 class="mb-3">
            <i class="bi bi-table me-2"></i> Preview Data Attendance (akan diexport)
        </h5>

        <div class="table-responsive">
            <table id="exportTable" class="table table-hover align-middle">

                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Status</th>
                        <th>Latitude</th>
                        <th>Longitude</th>
                    </tr>
                </thead>

                <tbody>
                <?php
                $no = 1;

                if ($query && mysqli_num_rows($query) > 0):
                    while ($data = mysqli_fetch_assoc($query)):
                ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($data['name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($data['check_in'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($data['check_out'] ?: '-') ?></td>
                        <td>
                        <?php
                        $status = $data['status'] ?? '-';

                        if ($status == 'Alpha') {
                            echo "<span class='badge bg-danger'>Tidak Hadir</span>";
                        } elseif ($status == 'Hadir') {
                            echo "<span class='badge bg-success'>Hadir</span>";
                        } elseif ($status == 'Terlambat') {
                            echo "<span class='badge bg-warning text-dark'>Terlambat</span>";
                        } elseif ($status == 'Izin') {
                            echo "<span class='badge bg-primary'>Izin</span>";
                        } elseif ($status == 'Sakit') {
                            echo "<span class='badge bg-info'>Sakit</span>";
                        } else {
                            echo "-";
                        }
                        ?>
                        </td>
                        <td><?= htmlspecialchars($data['latitude'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($data['longitude'] ?? '-') ?></td>
                    </tr>
                <?php
                    endwhile;
                else:
                ?>
                    <tr>
                        <td colspan="7" class="text-center">Belum ada data attendance</td>
                    </tr>
                <?php endif; ?>
                </tbody>

            </table>
        </div>

    </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function () {
    $('#exportTable').DataTable({
        paging: false,
        info: false,
        ordering: true,
        searching: true,
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