<?php
session_start();

include '../auth/admin_check.php';
include '../config/database.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Koneksi database tidak valid.");
}

/* =========================
   ADMIN DATA (SECURE)
========================= */
$admin_id = (int)$_SESSION['user_id'];

$stmtAdmin = $conn->prepare("
    SELECT name, email, photo
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmtAdmin->bind_param("i", $admin_id);
$stmtAdmin->execute();

$admin = $stmtAdmin->get_result()->fetch_assoc();

$stmtAdmin->close();

if (!$admin) {
    die("Data admin tidak ditemukan.");
}

$admin_photo = !empty($admin['photo'])
    ? basename($admin['photo'])
    : 'default.png';

/* =========================
   APPROVE ACTION (SECURE)
========================= */
if (isset($_GET['approve'])) {

    $id = (int) $_GET['approve'];

    $stmt = $conn->prepare("SELECT * FROM leave_requests WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $leave = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($leave) {

        $user_id = $leave['user_id'];
        $leave_date = $leave['leave_date'];
        $type = $leave['type'];

        // update leave status
        $stmt = $conn->prepare("
            UPDATE leave_requests
            SET approval_status='Approved',
                approved_by=?,
                approved_at=NOW()
            WHERE id=?
        ");
        $stmt->bind_param("ii", $admin_id, $id);
        $stmt->execute();
        $stmt->close();

        // cek attendance
        $stmt = $conn->prepare("
            SELECT id FROM attendance
            WHERE user_id=? AND attendance_date=?
        ");
        $stmt->bind_param("is", $user_id, $leave_date);
        $stmt->execute();
        $check = $stmt->get_result();
        $exists = $check->num_rows > 0;

        $check->free();
        $stmt->close();

        if ($exists) {

            $stmt = $conn->prepare("
                UPDATE attendance
                SET status=?
                WHERE user_id=? AND attendance_date=?
            ");
            $stmt->bind_param("sis", $type, $user_id, $leave_date);
            $stmt->execute();
            $stmt->close();

        } else {

            $stmt = $conn->prepare("
                INSERT INTO attendance (user_id, attendance_date, status)
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("iss", $user_id, $leave_date, $type);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: leave.php");
    exit;
}

/* =========================
   REJECT ACTION (SECURE)
========================= */
if (isset($_GET['reject'])) {

    $id = (int) $_GET['reject'];

    $stmt = $conn->prepare("
        UPDATE leave_requests
        SET approval_status='Rejected'
        WHERE id=?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: leave.php");
    exit;
}

/* =========================
   STATISTICS (SAFE)
========================= */
$total_leave = $conn->query("SELECT COUNT(*) AS total FROM leave_requests")
    ->fetch_assoc()['total'] ?? 0;

$pending = $conn->query("SELECT COUNT(*) AS total FROM leave_requests WHERE approval_status='Pending'")
    ->fetch_assoc()['total'] ?? 0;

$approved = $conn->query("SELECT COUNT(*) AS total FROM leave_requests WHERE approval_status='Approved'")
    ->fetch_assoc()['total'] ?? 0;

$rejected = $conn->query("SELECT COUNT(*) AS total FROM leave_requests WHERE approval_status='Rejected'")
    ->fetch_assoc()['total'] ?? 0;

/* =========================
   DATA LIST
========================= */
$query = $conn->query("
    SELECT lr.*, u.name
    FROM leave_requests lr
    JOIN users u ON u.id = lr.user_id
    ORDER BY lr.id DESC
");
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

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="page-title">
            <i class="bi bi-journal-medical me-2"></i>
            Manajemen Perizinan
        </h2>
    </div>

    <!-- STATISTICS -->
    <div class="row g-4 mb-5">

        <div class="col-md-3"><div class="card card-stats"><div class="card-body"><h6>Total</h6><h3><?= $total_leave ?></h3></div></div></div>
        <div class="col-md-3"><div class="card card-stats"><div class="card-body"><h6>Pending</h6><h3><?= $pending ?></h3></div></div></div>
        <div class="col-md-3"><div class="card card-stats"><div class="card-body"><h6>Approved</h6><h3><?= $approved ?></h3></div></div></div>
        <div class="col-md-3"><div class="card card-stats"><div class="card-body"><h6>Rejected</h6><h3><?= $rejected ?></h3></div></div></div>

    </div>

    <!-- TABLE -->
    <div class="table-container">

        <table class="table table-hover align-middle">
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
            <?php $no = 1; while ($row = $query->fetch_assoc()): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['leave_date']) ?></td>
                    <td><?= htmlspecialchars($row['type']) ?></td>
                    <td><?= htmlspecialchars($row['reason']) ?></td>
                    <td>
                        <?php if (!empty($row['attachment'])): ?>
                            <a href="../uploads/<?= htmlspecialchars($row['attachment']) ?>" target="_blank">View</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['approval_status']) ?></td>
                    <td>
                        <?php if ($row['approval_status'] == 'Pending'): ?>
                            <a href="?approve=<?= $row['id'] ?>" class="btn btn-success btn-sm">✔</a>
                            <a href="?reject=<?= $row['id'] ?>" class="btn btn-danger btn-sm">✖</a>
                        <?php else: ?>
                            <span class="text-muted">Done</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>

        </table>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>