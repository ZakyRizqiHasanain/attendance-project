<?php
session_start();
include '../config/database.php';

if(!isset($_SESSION['user_id'])){
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// USER DATA
$user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM users WHERE id='$user_id'"));
$user_photo = !empty($user['photo']) ? $user['photo'] : 'default.png';

// STATISTIK USER
$total_attendance = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as total 
FROM attendance 
WHERE user_id='$user_id'
"))['total'];

$hadir = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as total 
FROM attendance 
WHERE user_id='$user_id' AND status='Hadir'
"))['total'];

$terlambat = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as total 
FROM attendance 
WHERE user_id='$user_id' AND status='Terlambat'
"))['total'];

// DATA ATTENDANCE USER
$query = mysqli_query($conn,"
SELECT * FROM attendance
WHERE user_id='$user_id'
ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Export User Attendance</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<link rel="stylesheet" href="../assets/style.css">
</head>

<body class="dashboard">

<!-- NAVBAR + DROPDOWN -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
<div class="container-fluid px-4">

    <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="#">
            <i class="bi bi-fingerprint text-primary fs-3"></i>
            <span>Attendance System</span>
        </a>

    <div class="dropdown">

        <a class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
           data-bs-toggle="dropdown">

            <img src="../uploads/<?= $user_photo ?>"
                 width="40"
                 height="40"
                 class="rounded-circle border border-2 border-white me-2"
                 style="object-fit:cover;">

            <span><?= $_SESSION['name']; ?></span>
        </a>

        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">

            <li class="px-3 py-2 border-bottom">
                <div class="fw-bold"><?= $_SESSION['name']; ?></div>
                <small class="text-muted"><?= $user['email']; ?></small>
            </li>

            <li><a class="dropdown-item" href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
            <li><a class="dropdown-item" href="history.php"><i class="bi bi-calendar-range me-2"></i> History</a></li>
            <li><a class="dropdown-item" href="leave.php"><i class="bi bi-journal-plus me-2"></i> Pengajaun Izin / Sakit</a></li>
            <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i> Settings</a></li>

            <li><hr class="dropdown-divider"></li>

            <li>
                <a class="dropdown-item text-danger" href="../auth/logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a>
            </li>

        </ul>
    </div>

</div>
</nav>

<div class="container mt-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="page-title">
            <i class="bi bi-file-earmark-excel me-2"></i> Export Data Saya
        </h2>

        <a href="export_excel.php" class="btn btn-export">
            <i class="bi bi-download me-1"></i> Download Excel
        </a>
    </div>

    <!-- STATS -->
    <div class="row g-4 mb-4">

        <div class="col-md-4">
            <div class="card card-stats">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-bg me-3"><i class="bi bi-database"></i></div>
                    <div>
                        <h6 class="text-muted">Total Absen</h6>
                        <h3><?= $total_attendance ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-stats">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-bg me-3"><i class="bi bi-check-circle"></i></div>
                    <div>
                        <h6 class="text-muted">Hadir</h6>
                        <h3><?= $hadir ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-stats">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-bg me-3"><i class="bi bi-clock"></i></div>
                    <div>
                        <h6 class="text-muted">Terlambat</h6>
                        <h3><?= $terlambat ?></h3>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- TABLE -->
    <div class="table-container">

        <h5 class="mb-3">
            <i class="bi bi-table me-2"></i> Data Attendance Saya
        </h5>

        <div class="table-responsive">

            <table id="exportTable" class="table table-hover align-middle">

                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Status</th>
                        <th>Latitude</th>
                        <th>Longitude</th>
                    </tr>
                </thead>

                <tbody>

                <?php $no=1; while($data=mysqli_fetch_assoc($query)) { ?>

                    <tr>
                        <td><?= $no++ ?></td>

                        <td><?= date('d M Y', strtotime($data['attendance_date'])) ?></td>

                        <td><?= $data['check_in'] ? date('H:i:s', strtotime($data['check_in'])) : '-' ?></td>

                        <td><?= $data['check_out'] ? date('H:i:s', strtotime($data['check_out'])) : '-' ?></td>

                        <td>
                            <?php
                            if($data['status']=='Hadir'){
                                echo '<span class="badge bg-success">Hadir</span>';
                            } elseif($data['status']=='Terlambat'){
                                echo '<span class="badge bg-warning text-dark">Terlambat</span>';
                            } elseif($data['status']=='Izin'){
                                echo '<span class="badge bg-primary">Izin</span>';
                            } elseif($data['status']=='Sakit'){
                                echo '<span class="badge bg-info">Sakit</span>';
                            } else {
                                echo '<span class="badge bg-danger">Tidak Hadir</span>';
                            }
                            ?>
                        </td>

                        <td><?= $data['latitude'] ?></td>
                        <td><?= $data['longitude'] ?></td>
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
$(document).ready(function(){
    $('#exportTable').DataTable({
        paging:false,
        info:false,
        searching:true,
        ordering:true
    });
});
</script>

</body>
</html>