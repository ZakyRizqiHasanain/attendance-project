<?php
session_start();

include '../config/database.php';

date_default_timezone_set('Asia/Jakarta');

/* =========================
   CEK LOGIN
========================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

/* =========================
   CEK USER
========================= */
$stmt = $conn->prepare("
    SELECT id
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header("Location: ../index.php");
    exit;
}

/* =========================
   CEK STATUS HARI INI
========================= */
$today = date('Y-m-d');

$stmt = $conn->prepare("
    SELECT status
    FROM attendance
    WHERE user_id = ?
    AND attendance_date = ?
    LIMIT 1
");

$stmt->bind_param("is", $user_id, $today);
$stmt->execute();

$attendance_today = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (
    $attendance_today &&
    in_array(
        $attendance_today['status'],
        ['Izin', 'Sakit', 'Tidak Hadir']
    )
) {

    echo "
    <script>
        alert('Status kehadiran hari ini adalah {$attendance_today['status']}. Anda tidak dapat melakukan Check In.');
        window.location='dashboard.php';
    </script>
    ";
    exit;
}

/* =========================
   PROSES CHECK OUT
========================= */
if (isset($_POST['checkout'])) {

    $attendance_date = date('Y-m-d');
    $time = date("Y-m-d H:i:s");

    /* =========================
       CEK SUDAH CHECKIN HARI INI?
    ========================= */
    $stmt = $conn->prepare("
        SELECT id, check_out
        FROM attendance
        WHERE user_id = ?
        AND attendance_date = ?
        ORDER BY id DESC
        LIMIT 1
    ");

    $stmt->bind_param("is", $user_id, $attendance_date);
    $stmt->execute();

    $attendance = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$attendance) {
        echo "
        <script>
            alert('Anda belum melakukan Check In hari ini.');
            window.location='dashboard.php';
        </script>";
        exit;
    }

    if (!empty($attendance['check_out'])) {
        echo "
        <script>
            alert('Anda sudah melakukan Check Out hari ini.');
            window.location='dashboard.php';
        </script>";
        exit;
    }

    /* =========================
       VALIDASI FILE SELFIE
    ========================= */
    if (
        !isset($_FILES['selfie']) ||
        $_FILES['selfie']['error'] !== UPLOAD_ERR_OK
    ) {
        echo "
        <script>
            alert('Upload selfie gagal.');
            window.location='checkout.php';
        </script>";
        exit;
    }

    $allowed_types = [
        'image/jpeg',
        'image/png',
        'image/jpg'
    ];

    $allowed_extensions = [
        'jpg',
        'jpeg',
        'png'
    ];

    $ext = strtolower(
        pathinfo(
            $_FILES['selfie']['name'],
            PATHINFO_EXTENSION
        )
    );

    if (!in_array($ext, $allowed_extensions)) {

        echo "
        <script>
            alert('Format file harus JPG, JPEG, atau PNG.');
            window.location='checkout.php';
        </script>";
        exit;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $_FILES['selfie']['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_types)) {
        echo "
        <script>
            alert('File harus berupa JPG atau PNG.');
            window.location='checkout.php';
        </script>";
        exit;
    }

    if ($_FILES['selfie']['size'] > 5 * 1024 * 1024) {
        echo "
        <script>
            alert('Ukuran file maksimal 5 MB.');
            window.location='checkout.php';
        </script>";
        exit;
    }

    /* =========================
       SIMPAN FOTO
    ========================= */
    $ext = strtolower(
        pathinfo(
            $_FILES['selfie']['name'],
            PATHINFO_EXTENSION
        )
    );

    $filename =
        'checkout_' .
        $user_id . '_' .
        date('YmdHis') . '_' .
        bin2hex(random_bytes(5)) .
        '.' . $ext;

    $upload_dir = realpath(__DIR__ . '/../uploads');

    if (!$upload_dir) {
        die("Folder uploads tidak ditemukan.");
    }

    if (!is_dir($upload_dir) || !is_writable($upload_dir)) {
        die("Folder uploads tidak dapat ditulis.");
    }

    $upload_path = $upload_dir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file(
        $_FILES['selfie']['tmp_name'],
        $upload_path
    )) {
        echo "
        <script>
            alert('Gagal menyimpan foto.');
            window.location='checkout.php';
        </script>";
        exit;
    }

    /* =========================
       UPDATE CHECKOUT
    ========================= */
    $stmt = $conn->prepare("
        UPDATE attendance
        SET
            check_out = ?,
            selfie_checkout = ?
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param(
        "ssi",
        $time,
        $filename,
        $attendance['id']
    );

    if ($stmt->execute()) {

        echo "
        <script>
            alert('Check Out Berhasil');
            window.location='dashboard.php';
        </script>";

    } else {

        if (file_exists($upload_path)) {
            unlink($upload_path);
        }

        echo "
        <script>
            alert('Check Out gagal.');
            window.location='checkout.php';
        </script>";

    }

    $stmt->close();
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>

    <title>Check Out</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="../assets/style.css">

</head>

<body class="bg-light">

<div class="container mt-5">

    <div class="card shadow">

        <div class="card-body">

            <h3 class="mb-4">
                Check Out
            </h3>

            <form method="POST"
                  enctype="multipart/form-data">

                <div class="mb-3">

                    <label class="form-label">
                        Upload Selfie Check Out
                    </label>

                    <input type="file"
                        name="selfie"
                        class="form-control"
                        accept="image/*"
                        capture="user"
                        required>

                </div>

                <button type="submit"
                        name="checkout"
                        class="btn btn-warning">

                    Check Out

                </button>

                <a href="dashboard.php"
                   class="btn btn-secondary">

                    Kembali

                </a>

            </form>

        </div>

    </div>

</div>

</body>
</html>