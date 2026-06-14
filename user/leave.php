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

// SUBMIT LEAVE
if(isset($_POST['submit'])){

    $date = $_POST['leave_date'];
    $type = $_POST['type'];
    $reason = $_POST['reason'];

    $file = $_FILES['attachment']['name'] ?? '';
    $tmp  = $_FILES['attachment']['tmp_name'] ?? '';

    if(!empty($file)){
        $file = time().'_'.$file;
        move_uploaded_file($tmp, "../uploads/".$file);
    } else {
        $file = null;
    }

    mysqli_query($conn,"
        INSERT INTO leave_requests (
            user_id,
            leave_date,
            type,
            reason,
            attachment,
            approval_status
        ) VALUES (
            '$user_id',
            '$date',
            '$type',
            '$reason',
            '$file',
            'Pending'
        )
    ");

    echo "<script>alert('Pengajuan berhasil');window.location='leave.php';</script>";
}

// HISTORY
$history = mysqli_query($conn,"
SELECT * FROM leave_requests
WHERE user_id='$user_id'
ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Leave Request</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<link rel="stylesheet" href="../assets/style.css">
</head>

<body class="dashboard">

<!-- NAVBAR (SAMA ADMIN STYLE + DROPDOWN) -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
<div class="container-fluid px-4">

    <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="#">
            <i class="bi bi-fingerprint text-primary fs-3"></i>
            <span>Attendance System</span>
        </a>

    <div class="d-flex align-items-center">

        <!-- DROPDOWN USER -->
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

            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 py-2"
                    style="border-radius: 16px; min-width: 220px;">

                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-bold"><?= $_SESSION['name']; ?></div>
                        <div class="text-muted small"><?= $user['email']; ?></div>
                    </li>

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

<div class="container py-4" style="max-width:900px;">

<!-- HEADER -->
<div class="card mb-4">
    <div class="top-bar"></div>
    <div class="card-body p-4">
        <span class="badge title-badge mb-2">PERIZINAN</span>
        <h3 class="fw-bold mb-1">Ajukan Izin / Sakit</h3>
        <p class="text-muted mb-0">Form pengajuan izin kerja karyawan</p>
    </div>
</div>

<!-- FORM -->
<div class="card mb-4">
<div class="card-body p-4">

<form method="POST" enctype="multipart/form-data">

    <div class="mb-3">
        <label class="fw-semibold">Tanggal</label>
        <input type="date" name="leave_date" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="fw-semibold">Jenis</label>
        <select name="type" class="form-select">
            <option value="Izin">Izin</option>
            <option value="Sakit">Sakit</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="fw-semibold">Alasan</label>
        <textarea name="reason" class="form-control" rows="3" required></textarea>
    </div>

    <div class="mb-3">
        <label class="fw-semibold">Bukti</label>
        <input type="file" name="attachment" class="form-control">
    </div>

    <button class="btn btn-primary">
        <i class="bi bi-send"></i> Kirim
    </button>

    <a href="dashboard.php" class="btn btn-light border">
        Kembali
    </a>

</form>

</div>
</div>

<!-- HISTORY -->
<div class="card">
<div class="card-body p-4">

<h5 class="fw-bold mb-3">Riwayat Pengajuan</h5>

<div class="table-responsive">
<table class="table table-hover align-middle">

<thead>
<tr>
    <th>Tanggal</th>
    <th>Jenis</th>
    <th>Alasan</th>
    <th>Status</th>
</tr>
</thead>

<tbody>

<?php while($row = mysqli_fetch_assoc($history)) { ?>

<tr>
    <td><?= $row['leave_date'] ?></td>
    <td><?= $row['type'] ?></td>
    <td><?= $row['reason'] ?></td>
    <td>
        <?php if($row['approval_status']=='Pending'){ ?>
            <span class="badge bg-warning text-dark">Pending</span>
        <?php } elseif($row['approval_status']=='Approved'){ ?>
            <span class="badge bg-success">Approved</span>
        <?php } else { ?>
            <span class="badge bg-danger">Rejected</span>
        <?php } ?>
    </td>
</tr>

<?php } ?>

</tbody>

</table>
</div>

</div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>