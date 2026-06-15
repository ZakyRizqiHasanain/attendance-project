<?php
include '../auth/admin_check.php';
include '../config/database.php';

// Flash message
if(isset($_SESSION['success'])) {
    echo "<script>alert('".addslashes($_SESSION['success'])."');</script>";
    unset($_SESSION['success']);
}
if(isset($_SESSION['error'])) {
    echo "<script>alert('".addslashes($_SESSION['error'])."');</script>";
    unset($_SESSION['error']);
}

// Statistik
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"))['total'];
$total_admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'admin'"))['total'];
$total_regular = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'user'"))['total'];

$query = mysqli_query($conn, "SELECT * FROM users ORDER BY id ASC");

$admin_id = $_SESSION['user_id'];
$admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id='$admin_id'"));
$admin_photo = !empty($admin['photo']) ? $admin['photo'] : 'default.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body class="dashboard">


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
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 py-2" style="border-radius:16px;min-width:250px;">

    <li class="px-3 py-2 border-bottom">
        <div class="fw-bold"><?= htmlspecialchars($admin['name']); ?></div>
        <div class="text-muted small"><?= htmlspecialchars($admin['email']); ?></div>
    </li>

    <li><a class="dropdown-item d-flex align-items-center gap-2" href="dashboard.php">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a></li>

    <li><a class="dropdown-item d-flex align-items-center gap-2" href="users.php">
        <i class="bi bi-people-fill"></i> Users
    </a></li>

    <li><a class="dropdown-item d-flex align-items-center gap-2" href="attendance_data.php">
        <i class="bi bi-calendar-check"></i> Attendance
    </a></li>

    <li><a class="dropdown-item d-flex align-items-center gap-2" href="qr_generate.php">
        <i class="bi bi-qr-code"></i> QR Attendance
    </a></li>

    <li><a class="dropdown-item d-flex align-items-center gap-2" href="settings.php">
        <i class="bi bi-gear-fill"></i> Settings
    </a></li>

    <li><a class="dropdown-item d-flex align-items-center gap-2" href="leave.php">
        <i class="bi bi-journal-medical"></i> Pengajuan Izin / Sakit
    </a></li>

    <li><hr class="dropdown-divider"></li>

    <li>
        <a class="dropdown-item d-flex align-items-center gap-2 text-danger"
           href="../auth/logout.php"
           onclick="return confirm('Yakin ingin keluar?');">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </li>

</ul>
            </div>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <h2 class="page-title"><i class="bi bi-people-fill me-2"></i> Manage Users</h2>
        <a href="add_user.php" class="btn btn-add">
            <i class="bi bi-person-plus me-1"></i> Tambah User
        </a>
    </div>

    <!-- Kartu statistik -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card card-stats">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-bg me-3">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <h6>Total Users</h6>
                        <h3><?= $total_users ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stats">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-bg me-3">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <div>
                        <h6>Admin</h6>
                        <h3><?= $total_admin ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stats">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-bg me-3">
                        <i class="bi bi-person"></i>
                    </div>
                    <div>
                        <h6>Regular User</h6>
                        <h3><?= $total_regular ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel user -->
    <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0"><i class="bi bi-table me-2"></i> Daftar Semua User</h5>
            <small class="text-muted">*Klik kolom untuk sorting | Gunakan pencarian</small>
        </div>
        <div class="table-responsive">
            <table id="usersTable" class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Photo</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Password</th>
                        <th>Role</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    while($data = mysqli_fetch_assoc($query)) {
                        $photo = !empty($data['photo']) ? "../uploads/" . $data['photo'] : "../uploads/default.png";
                    ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><img src="<?= $photo ?>" class="avatar-circle" alt="Photo"></td>
                        <td class="fw-semibold"><?= htmlspecialchars($data['name']) ?></td>
                        <td><?= htmlspecialchars($data['email']) ?></td>
                        <td class="password-cell"><?= htmlspecialchars($data['password']) ?></td>
                        <td>
                            <?php if($data['role'] == 'admin') { ?>
                                <span class="badge-admin">Admin</span>
                            <?php } else { ?>
                                <span class="badge-user">User</span>
                            <?php } ?>
                        </td>
                        <td><?= date('d-m-Y H:i', strtotime($data['created_at'])) ?></td>
                        <td>
                            <?php if($data['role'] != 'admin') { ?>
                                <a href="delete_user.php?id=<?= $data['id'] ?>" 
                                   class="btn-delete" 
                                   onclick="return confirm('Yakin ingin menghapus user <?= htmlspecialchars($data['name']) ?>?')">
                                    <i class="bi bi-trash"></i> Delete
                                </a>
                            <?php } else { ?>
                                <span class="badge-protected"><i class="bi bi-shield-check"></i> Protected</span>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        $('#usersTable').DataTable({
            pageLength: 10,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
            },
            columnDefs: [
                { orderable: false, targets: [1, 4, 7] }
            ]
        });
    });
</script>

</body>
</html>