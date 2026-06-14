<?php
include '../config/database.php';

session_start();
include '../auth/admin_check.php';

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

$query = mysqli_query($conn,"
SELECT leave_requests.*, users.name
FROM leave_requests
JOIN users ON users.id = leave_requests.user_id
ORDER BY leave_requests.id DESC
");

$total_leave = mysqli_num_rows(mysqli_query($conn,"SELECT * FROM leave_requests"));

$pending = mysqli_num_rows(mysqli_query($conn,"
SELECT * FROM leave_requests
WHERE approval_status='Pending'
"));

$approved = mysqli_num_rows(mysqli_query($conn,"
SELECT * FROM leave_requests
WHERE approval_status='Approved'
"));

$rejected = mysqli_num_rows(mysqli_query($conn,"
SELECT * FROM leave_requests
WHERE approval_status='Rejected'
"));

// APPROVE → kembali ke leave.php
if(isset($_GET['approve'])){

    $id = intval($_GET['approve']);

    // Ambil data izin/sakit
    $leave = mysqli_fetch_assoc(mysqli_query($conn,"
        SELECT *
        FROM leave_requests
        WHERE id='$id'
    "));

    $user_id = $leave['user_id'];
    $leave_date = $leave['leave_date'];
    $type = $leave['type']; // Izin atau Sakit

    // Approve pengajuan
    mysqli_query($conn,"
        UPDATE leave_requests
        SET
            approval_status='Approved',
            approved_by='{$_SESSION['user_id']}',
            approved_at=NOW()
        WHERE id='$id'
    ");

    // Cek apakah attendance sudah ada
    $check = mysqli_query($conn,"
        SELECT id
        FROM attendance
        WHERE user_id='$user_id'
        AND attendance_date='$leave_date'
    ");

    if(mysqli_num_rows($check) > 0){

        // Update status jika sudah ada
        mysqli_query($conn,"
            UPDATE attendance
            SET status='$type'
            WHERE user_id='$user_id'
            AND attendance_date='$leave_date'
        ");

    } else {

        // Buat record baru jika belum ada
        mysqli_query($conn,"
            INSERT INTO attendance(
                user_id,
                attendance_date,
                status
            )
            VALUES(
                '$user_id',
                '$leave_date',
                '$type'
            )
        ");
    }

    header("Location: leave.php");
    exit;
}

// REJECT → kembali ke leave.php
if(isset($_GET['reject'])){
    $id = intval($_GET['reject']);

    mysqli_query($conn,"
        UPDATE leave_requests
        SET approval_status='Rejected'
        WHERE id='$id'
    ");

    header("Location: leave.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Leave Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

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

                <a href="#"
                   class="d-flex align-items-center text-decoration-none dropdown-toggle text-white"
                   id="adminDropdown"
                   data-bs-toggle="dropdown">

                    <img src="../uploads/<?= $admin_photo; ?>"
                         width="42"
                         height="42"
                         class="rounded-circle border border-2 border-white me-2"
                         style="object-fit:cover;">

                    <span class="fw-semibold">
                        <?= htmlspecialchars($admin['name']); ?>
                    </span>

                </a>

                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 py-2"
                    style="border-radius:16px;min-width:250px;">

                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-bold">
                            <?= htmlspecialchars($admin['name']); ?>
                        </div>
                        <div class="text-muted small">
                            <?= htmlspecialchars($admin['email']); ?>
                        </div>
                    </li>

                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="dashboard.php">
                            <i class="bi bi-speedometer2"></i>
                            Dashboard
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="users.php">
                            <i class="bi bi-people-fill"></i>
                            Users
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="attendance_data.php">
                            <i class="bi bi-calendar-check"></i>
                            Attendance
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="qr_generate.php">
                            <i class="bi bi-qr-code"></i>
                            QR Attendance
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="settings.php">
                            <i class="bi bi-gear-fill"></i>
                            Settings
                        </a>
                    </li>

                    <!-- ACTIVE MENU -->
                    <li>
                        <a class="dropdown-item active d-flex align-items-center gap-2"
                           href="leave.php">
                            <i class="bi bi-journal-medical"></i>
                            Pengajuan Izin / Sakit
                        </a>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 text-danger"
                           href="../auth/logout.php"
                           onclick="return confirm('Yakin ingin keluar?');">
                            <i class="bi bi-box-arrow-right"></i>
                            Logout
                        </a>
                    </li>

                </ul>

            </div>

        </div>

    </div>
</nav>

<div class="container mt-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="page-title">
            <i class="bi bi-journal-medical me-2"></i>
            Manajemen Perizinan
        </h2>
    </div>

    <!-- STATISTIK -->
    <div class="row g-4 mb-5">

        <div class="col-md-3">
            <div class="card card-stats">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-bg bg-primary bg-opacity-10 text-primary me-3">
                        <i class="bi bi-journal-text"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Total Pengajuan</h6>
                        <h3 class="mb-0 fw-bold"><?= $total_leave ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-stats">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-bg bg-warning bg-opacity-10 text-warning me-3">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Pending</h6>
                        <h3 class="mb-0 fw-bold"><?= $pending ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-stats">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-bg bg-success bg-opacity-10 text-success me-3">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Approved</h6>
                        <h3 class="mb-0 fw-bold"><?= $approved ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-stats">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-bg bg-danger bg-opacity-10 text-danger me-3">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Rejected</h6>
                        <h3 class="mb-0 fw-bold"><?= $rejected ?></h3>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- TABEL -->
    <div class="table-container">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0">
                <i class="bi bi-table me-2"></i>
                Data Pengajuan Izin & Sakit
            </h5>

            <div class="d-flex gap-2">
                <span class="badge bg-warning text-dark">Pending</span>
                <span class="badge bg-success">Approved</span>
                <span class="badge bg-danger">Rejected</span>
            </div>
        </div>

        <div class="table-responsive">

            <table id="leaveTable" class="table table-hover align-middle">

                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Alasan</th>
                        <th>Bukti</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php
                $no=1;
                while($row=mysqli_fetch_assoc($query)){
                ?>

                <tr>

                    <td><?= $no++; ?></td>

                    <td class="fw-semibold">
                        <?= htmlspecialchars($row['name']); ?>
                    </td>

                    <td><?= $row['leave_date']; ?></td>

                    <td>
                        <?php if($row['type']=="Sakit"){ ?>
                            <span class="badge bg-danger">
                                <?= $row['type']; ?>
                            </span>
                        <?php } else { ?>
                            <span class="badge bg-primary">
                                <?= $row['type']; ?>
                            </span>
                        <?php } ?>
                    </td>

                    <td style="max-width:250px;">
                        <?= htmlspecialchars($row['reason']); ?>
                    </td>

                    <td>

                        <?php if(!empty($row['attachment'])){ ?>

                            <a href="../uploads/<?= $row['attachment']; ?>" target="_blank">

                                <img
                                    src="../uploads/<?= $row['attachment']; ?>"
                                    class="img-thumbnail-custom shadow-sm">

                            </a>

                        <?php } else { ?>

                            <span class="text-muted">
                                Tidak ada
                            </span>

                        <?php } ?>

                    </td>

                    <td>

                        <?php if($row['approval_status']=='Pending'){ ?>

                            <span class="badge bg-warning text-dark px-3 py-2">
                                Pending
                            </span>

                        <?php } elseif($row['approval_status']=='Approved'){ ?>

                            <span class="badge bg-success px-3 py-2">
                                Approved
                            </span>

                        <?php } else { ?>

                            <span class="badge bg-danger px-3 py-2">
                                Rejected
                            </span>

                        <?php } ?>

                    </td>

                    <td>

                        <?php if($row['approval_status']=='Pending'){ ?>

                        <a href="?approve=<?= $row['id']; ?>"
                           class="btn btn-success btn-sm"
                           onclick="return confirm('Approve permohonan ini?')">

                            <i class="bi bi-check-lg"></i>

                        </a>

                        <a href="?reject=<?= $row['id']; ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Reject permohonan ini?')">

                            <i class="bi bi-x-lg"></i>

                        </a>

                        <?php } else { ?>

                            <span class="text-muted">
                                Selesai
                            </span>

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

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function(){

    $('#leaveTable').DataTable({
        pageLength:10,
        order:[[0,'desc']],
        language:{
            url:'//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
        }
    });

});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>