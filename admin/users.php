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
    <style>
        /* --- Reset gaya agar teks bisa di-copy dengan mudah --- */
        * {
            user-select: text; /* Memastikan teks bisa dipilih/disalin */
            -webkit-user-select: text;
        }
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', sans-serif;
            font-size: 0.95rem;
            line-height: 1.5;
            color: #1a1e24;
        }
        /* Navbar gelap */
        .navbar-dark.bg-dark {
            background-color: #1e2a3a !important;
        }
        .navbar-brand, .navbar-brand i, .navbar-brand span {
            color: white !important;
        }
        .btn-outline-light {
            color: white;
            border-color: white;
        }
        .btn-outline-light:hover {
            background-color: rgba(255,255,255,0.1);
        }
        /* Card statistik */
        .card-stats {
            border: none;
            border-radius: 20px;
            background-color: #ffffff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: 0.2s;
        }
        .icon-bg {
            width: 52px;
            height: 52px;
            background-color: #eef2f5;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #2c3e50;
        }
        .card-stats h6 {
            color: #5a6874;
            font-weight: 500;
            margin-bottom: 4px;
        }
        .card-stats h3 {
            font-weight: 700;
            color: #1e2a3a;
            margin: 0;
        }
        /* Table container */
        .table-container {
            background: #ffffff;
            border-radius: 24px;
            padding: 20px 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }
        .page-title {
            font-weight: 700;
            color: #1e2a3a;
        }
        .page-title i {
            color: #2c3e50;
        }
        /* Avatar */
        .avatar-circle {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        /* Kolom password */
        .password-cell {
            font-family: 'SF Mono', 'Courier New', monospace;
            font-size: 0.85rem;
            background-color: #f8fafc;
            color: #1e293b;
            letter-spacing: 0.3px;
        }
        /* Tombol tambah user */
        .btn-add {
            background-color: #2c6e2f;
            border-radius: 40px;
            padding: 8px 20px;
            font-weight: 500;
            color: white;
            border: none;
        }
        .btn-add:hover {
            background-color: #1f5422;
        }
        /* Badge role */
        .badge-admin {
            background-color: #b91c1c;
            color: white;
            padding: 5px 12px;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .badge-user {
            background-color: #4b5563;
            color: white;
            padding: 5px 12px;
            border-radius: 40px;
            font-size: 0.75rem;
        }
        .badge-protected {
            background-color: #6b7280;
            color: white;
            padding: 5px 12px;
            border-radius: 40px;
            font-size: 0.75rem;
        }
        /* Tombol delete */
        .btn-delete {
            background-color: #b91c1c;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 4px 12px;
            font-size: 0.75rem;
            transition: 0.2s;
        }
        .btn-delete:hover {
            background-color: #991b1b;
        }
        /* DataTables styling (netral) */
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #eef2f5 !important;
            color: #1e2a3a !important;
            border: 1px solid #d1d9e6 !important;
        }
        .dataTables_filter input {
            border-radius: 30px;
            border: 1px solid #cbd5e1;
            padding: 6px 12px;
        }
        table.dataTable thead th {
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
            font-weight: 600;
        }
        table.dataTable tbody td {
            color: #1e293b;
            vertical-align: middle;
        }
        /* Pastikan teks bisa di-copy dan tidak ada efek blur */
        td, th, span, div, p {
            user-select: text;
            -webkit-user-select: text;
        }
        a {
            color: #2c3e50;
            text-decoration: none;
        }
        a:hover {
            color: #1e2a3a;
        }
        /* Hilangkan biru default pada link di dropdown */
        .dropdown-item {
            color: #1e293b !important;
        }
        .dropdown-item:hover {
            background-color: #f1f5f9;
        }
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
                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-bold"><?= htmlspecialchars($admin['name']); ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($admin['email']); ?></div>
                    </li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 active" href="users.php"><i class="bi bi-people-fill"></i> Users</a></li>
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