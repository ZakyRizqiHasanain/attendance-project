<?php
session_start();
include '../config/database.php';

if(!isset($_SESSION['user_id'])){
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* =========================
   GET USER DATA
========================= */
$stmt = $conn->prepare("
    SELECT id, name, email, photo, password
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();

$user_data = $stmt->get_result()->fetch_assoc();
$user = $user_data;
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
   UPDATE PROFILE
========================= */
if (isset($_POST['update_profile'])) {

    $name = trim($_POST['name']);

    if (empty($name)) {
        echo "
        <script>
            alert('Nama tidak boleh kosong.');
            history.back();
        </script>";
        exit;
    }

$photo = $user_data['photo'];

    if (
        isset($_FILES['photo']) &&
        $_FILES['photo']['error'] === UPLOAD_ERR_OK
    ) {

        $allowed_ext = ['jpg','jpeg','png'];

        $extension = strtolower(
            pathinfo(
                $_FILES['photo']['name'],
                PATHINFO_EXTENSION
            )
        );

        if (!in_array($extension, $allowed_ext)) {

            echo "
            <script>
                alert('Format foto harus JPG, JPEG, atau PNG.');
                history.back();
            </script>";
            exit;
        }

        if ($_FILES['photo']['size'] > (5 * 1024 * 1024)) {

            echo "
            <script>
                alert('Ukuran foto maksimal 5 MB.');
                history.back();
            </script>";
            exit;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file(
            $finfo,
            $_FILES['photo']['tmp_name']
        );
        finfo_close($finfo);

        $allowed_mime = [
            'image/jpeg',
            'image/png'
        ];

        if (!in_array($mime, $allowed_mime)) {

            echo "
            <script>
                alert('File gambar tidak valid.');
                history.back();
            </script>";
            exit;
        }

        $photo =
            'profile_' .
            $user_id . '_' .
            date('YmdHis') . '_' .
            bin2hex(random_bytes(5)) .
            '.' . $extension;

        $upload_dir = realpath(__DIR__ . '/../uploads');

        if (!$upload_dir) {
            die('Folder uploads tidak ditemukan.');
        }

        $upload_path =
            $upload_dir .
            DIRECTORY_SEPARATOR .
            $photo;

        if (
            !move_uploaded_file(
                $_FILES['photo']['tmp_name'],
                $upload_path
            )
        ) {
            die('Gagal upload foto.');
        }

        if (
            !empty($user['photo']) &&
            $user['photo'] !== 'default.png'
        ) {

            $old_photo =
                $upload_dir .
                DIRECTORY_SEPARATOR .
                $user['photo'];

            if (file_exists($old_photo)) {
                unlink($old_photo);
            }
        }
    }

    $stmt = $conn->prepare("
        UPDATE users
        SET
            name = ?,
            photo = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "ssi",
        $name,
        $photo,
        $user_id
    );

    if ($stmt->execute()) {

        $_SESSION['name'] = $name;

        echo "
        <script>
            alert('Profil berhasil diperbarui.');
            window.location='settings.php';
        </script>";
        exit;
    }
    else {

        echo "
        <script>
            alert('Gagal memperbarui profil.');
        </script>
        ";

    }

    $stmt->close();
}

/* =========================
   CHANGE PASSWORD
========================= */
if (isset($_POST['change_password'])) {

    $old_password     = trim($_POST['old_password'] ?? '');
    $new_password     = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (
        empty($old_password) ||
        empty($new_password) ||
        empty($confirm_password)
    ) {

        echo "
        <script>
            alert('Semua field password wajib diisi.');
        </script>";
    }

    elseif (!password_verify($old_password, $user['password'])) {

        echo "
        <script>
            alert('Password lama salah.');
        </script>";
    }

    elseif ($new_password !== $confirm_password) {

        echo "
        <script>
            alert('Konfirmasi password tidak cocok.');
        </script>";
    }

    elseif (strlen($new_password) < 6) {

        echo "
        <script>
            alert('Password minimal 6 karakter.');
        </script>";
    }

    else {

        $new_hash = password_hash(
            $new_password,
            PASSWORD_DEFAULT
        );

        $stmt = $conn->prepare("
            UPDATE users
            SET password = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "si",
            $new_hash,
            $user_id
        );

        if ($stmt->execute()) {

            echo "
            <script>
                alert('Password berhasil diganti.');
                window.location='settings.php';
            </script>";
            exit;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings</title>

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

<!-- CONTENT -->
<div class="container mt-5">

    <div class="card mb-4">
        <div class="card-body p-4">
            <span class="badge bg-primary mb-2">SETTINGS</span>
            <h3 class="fw-bold">Pengaturan Akun</h3>
            <p class="text-muted mb-0">Kelola profil dan keamanan akun Anda</p>
        </div>
    </div>

    <div class="row g-4">

        <!-- PROFILE -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body p-4">

                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-person-circle text-success"></i> Edit Profil
                    </h5>

                    <form method="POST" enctype="multipart/form-data">

                        <div class="text-center mb-3">
                            <img src="../uploads/<?= htmlspecialchars($user_photo); ?>"
                                 width="130"
                                 height="130"
                                 class="rounded-circle border shadow"
                                 style="object-fit: cover;">
                        </div>

                        <input type="text"
                               name="name"
                               class="form-control mb-3"
                               maxlength="30"
                               value="<?= htmlspecialchars($user_data['name']); ?>">

                        <input
                            type="file"
                            name="photo"
                            class="form-control mb-3"
                            accept=".jpg,.jpeg,.png">

                        <button class="btn btn-success w-100" name="update_profile">
                            Simpan
                        </button>

                    </form>

                </div>
            </div>
        </div>

        <!-- PASSWORD -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body p-4">

                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-shield-lock text-primary"></i> Ganti Password
                    </h5>

                    <form method="POST">

                        <input type="password"
                               name="old_password"
                               class="form-control mb-3"
                               placeholder="Password lama"
                               required>

                        <input type="password"
                               name="new_password"
                               class="form-control mb-3"
                               placeholder="Password baru"
                               required>

                        <input type="password"
                               name="confirm_password"
                               class="form-control mb-3"
                               placeholder="Konfirmasi password"
                               required>

                        <button type="submit"
                                name="change_password"
                                class="btn btn-primary w-100">
                            Ganti Password
                        </button>

                    </form>

                </div>
            </div>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php
$conn->close();
?>
</body>
</html>