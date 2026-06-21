<?php
    session_start();
    include '../config/database.php';

    if(!isset($_SESSION['user_id'])){
        header("Location: ../index.php");
        exit;
    }

    $user_id = $_SESSION['user_id'];

    /* =========================
            USER DATA
    ========================= */
    $stmt = $conn->prepare("
        SELECT id, name, email, photo
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $user_data = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$user_data) {
        session_destroy();
        header("Location: ../index.php");
        exit;
    }

    $user_photo = 'default.png';
    if (
        !empty($user_data['photo']) &&
        file_exists("../uploads/" . basename($user_data['photo']))
    ) {
        $user_photo = basename($user_data['photo']);
    }

    /* =========================
    SUBMIT LEAVE
    ========================= */
    if (isset($_POST['submit'])) {

        $date   = trim($_POST['leave_date']);
        $type   = trim($_POST['type']);
        $reason = trim($_POST['reason']);

        /* VALIDASI TANGGAL */
        if (strtotime($date) < strtotime(date('Y-m-d'))) {
            echo "
            <script>
                alert('Tanggal pengajuan tidak boleh kurang dari hari ini.');
                history.back();
            </script>
            ";
            exit;
        }

        /* VALIDASI JENIS */
        if (!in_array($type, ['Izin', 'Sakit'])) {
            echo "
            <script>
                alert('Jenis pengajuan tidak valid.');
                history.back();
            </script>
            ";
            exit;
        }

        /* VALIDASI ALASAN */
        if (empty($reason)) {
            echo "
            <script>
                alert('Alasan wajib diisi.');
                history.back();
            </script>
            ";
            exit;
        }

        /* =========================
        UPLOAD LAMPIRAN
        ========================= */
        $file = null;

        if (
            isset($_FILES['attachment']) &&
            $_FILES['attachment']['error'] === UPLOAD_ERR_OK
        ) {

            $allowed_ext = [
                'jpg',
                'jpeg',
                'png',
                'pdf'
            ];

            $extension = strtolower(
                pathinfo(
                    $_FILES['attachment']['name'],
                    PATHINFO_EXTENSION
                )
            );

            if (!in_array($extension, $allowed_ext)) {

                echo "
                <script>
                    alert('File harus JPG, JPEG, PNG atau PDF.');
                    history.back();
                </script>
                ";
                exit;
            }

            if ($_FILES['attachment']['size'] > (5 * 1024 * 1024)) {

                echo "
                <script>
                    alert('Ukuran file maksimal 5 MB.');
                    history.back();
                </script>
                ";
                exit;
            }

            $upload_dir = realpath(__DIR__ . '/../uploads');

            if (!$upload_dir) {
                die('Folder uploads tidak ditemukan.');
            }

            $file =
                'leave_' .
                $user_id . '_' .
                date('YmdHis') . '_' .
                bin2hex(random_bytes(5)) . '.' .
                $extension;

            $upload_path =
                $upload_dir .
                DIRECTORY_SEPARATOR .
                $file;

            if (
                !move_uploaded_file(
                    $_FILES['attachment']['tmp_name'],
                    $upload_path
                )
            ) {

                echo "
                <script>
                    alert('Gagal mengupload lampiran.');
                    history.back();
                </script>
                ";
                exit;
            }
        }

        /* =========================
        SIMPAN DATABASE
        ========================= */
        $stmt = $conn->prepare("
            INSERT INTO leave_requests
            (
                user_id,
                leave_date,
                type,
                reason,
                attachment,
                approval_status
            )
            VALUES
            (
                ?, ?, ?, ?, ?, 'Pending'
            )
        ");

        if (!$stmt) {
            die($conn->error);
        }

        $stmt->bind_param(
            "issss",
            $user_id,
            $date,
            $type,
            $reason,
            $file
        );

        if ($stmt->execute()) {

            echo "
            <script>
                alert('Pengajuan berhasil.');
                window.location='leave.php';
            </script>
            ";
            exit;

        } else {

            if (
                !empty($file) &&
                file_exists($upload_path)
            ) {
                unlink($upload_path);
            }

            echo "
            <script>
                alert('Pengajuan gagal: " . addslashes($stmt->error) . "');
                history.back();
            </script>
            ";
        }

        $stmt->close();
    }

    // HISTORY
    $stmt = $conn->prepare("
        SELECT *
        FROM leave_requests
        WHERE user_id = ?
        ORDER BY id DESC
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $history = $stmt->get_result();
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

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="#">
            <i class="bi bi-fingerprint text-primary fs-3"></i>
            <span>Attendance System</span>
        </a>

        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-white" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="../uploads/<?= htmlspecialchars($user_photo) ?>"
                     width="42"
                     height="42"
                     class="rounded-circle border border-2 border-white me-2"
                     style="object-fit: cover;">
                <span class="fw-semibold"><?= htmlspecialchars($user_data['name']); ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 py-2" aria-labelledby="profileDropdown" style="border-radius: 16px; min-width: 200px;">
                <li class="px-3 py-2 border-bottom mb-2">
                    <div class="fw-bold text-dark"><?= htmlspecialchars($user_data['name']); ?></div>
                    <div class="text-muted small text-truncate" style="max-width: 170px;"><?= htmlspecialchars($user_data['email']); ?></div>
                </li>
                <li><a class="dropdown-item" href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                <li><a class="dropdown-item" href="history.php"><i class="bi bi-calendar-range me-2"></i> History</a></li>
                <li><a class="dropdown-item" href="leave.php"><i class="bi bi-journal-plus me-2"></i> Pengajuan Izin / Sakit</a></li>
                <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i> Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger" href="../auth/logout.php" onclick="return confirm('Yakin ingin logout?');">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
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

    <button type="submit" name="submit" class="btn btn-primary">
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
    <th>Lampiran</th>
</tr>
</thead>

<tbody>
<?php if ($history->num_rows > 0): ?>
    <?php while ($row = $history->fetch_assoc()): ?>
    <tr>
        <td>
            <?= date('d-m-Y', strtotime($row['leave_date'])); ?>
        </td>
        <td>
            <?= htmlspecialchars($row['type']); ?>
        </td>
        <td>
            <?= htmlspecialchars($row['reason']); ?>
        </td>

        <td>
            <?php if ($row['approval_status'] == 'Pending'): ?>
                <span class="badge bg-warning text-dark">
                    Pending
                </span>
            <?php elseif ($row['approval_status'] == 'Approved'): ?>
                <span class="badge bg-success">
                    Approved
                </span>
            <?php elseif ($row['approval_status'] == 'Rejected'): ?>
                <span class="badge bg-danger">
                    Rejected
                </span>
            <?php else: ?>
                <span class="badge bg-secondary">
                    -
                </span>
            <?php endif; ?>
        </td>

        <td>
            <?php if (!empty($row['attachment'])): ?>

                <a href="../uploads/<?= htmlspecialchars($row['attachment']); ?>"
                   target="_blank"
                   class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-paperclip"></i>
                    Lihat
                </a>
            <?php else: ?>
                -
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="5" class="text-center text-muted">
            Belum ada riwayat pengajuan
        </td>
    </tr>
<?php endif; ?>
</tbody>

</table>
</div>

</div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>