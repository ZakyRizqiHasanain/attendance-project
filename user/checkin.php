<?php
session_start();

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

date_default_timezone_set('Asia/Jakarta');

$user_id = (int)$_SESSION['user_id'];

/* =========================
   CEK USER
========================= */
$stmt = $conn->prepare("
    SELECT id, name
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User tidak ditemukan.");
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
   CHECK IN PROCESS
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkin'])) {

    $latitude  = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);
    $longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);

    if ($latitude === false || $longitude === false) {
        echo "
        <script>
            alert('Lokasi tidak valid.');
            history.back();
        </script>
        ";
        exit;
    }

    $attendance_date = date('Y-m-d');
    $checkin_time    = date('Y-m-d H:i:s');
    $current_time    = date('H:i:s');

    /* =========================
       CEK SUDAH ABSEN HARI INI
    ========================= */
    $stmt = $conn->prepare("
        SELECT id, status
        FROM attendance
        WHERE user_id = ?
        AND attendance_date = ?
        LIMIT 1
    ");

    $data_today = $stmt->get_result()->fetch_assoc();
    if ($data_today) {
        if (
            in_array(
                $data_today['status'],
                ['Izin','Sakit','Tidak Hadir']
            )
        ) {
            echo "
            <script>
                alert('Status hari ini adalah {$data_today['status']}. Check In tidak tersedia.');
                window.location='dashboard.php';
            </script>
            ";
            exit;
        }
        echo "
        <script>
            alert('Anda sudah melakukan Check In hari ini.');
            window.location='dashboard.php';
        </script>
        ";
        exit;
    }

    /* =========================
       VALIDASI SELFIE
    ========================= */
    if (
        !isset($_FILES['selfie']) ||
        $_FILES['selfie']['error'] !== UPLOAD_ERR_OK
    ) {

        echo "
        <script>
            alert('Selfie wajib diupload.');
        </script>
        ";
        exit;
    }

    $file = $_FILES['selfie'];

    $allowed_extensions = [
        'jpg',
        'jpeg',
        'png'
    ];

    $extension = strtolower(
        pathinfo(
            $file['name'],
            PATHINFO_EXTENSION
        )
    );

    if (!in_array($extension, $allowed_extensions)) {

        echo "
        <script>
            alert('Format file harus JPG, JPEG, atau PNG.');
        </script>
        ";
        exit;
    }

    if ($file['size'] > (5 * 1024 * 1024)) {

        echo "
        <script>
            alert('Ukuran file maksimal 5 MB.');
        </script>
        ";
        exit;
    }

    /* =========================
       CEK MIME TYPE
    ========================= */
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed_mime = [
        'image/jpeg',
        'image/png'
    ];

    if (!in_array($mime, $allowed_mime)) {

        echo "
        <script>
            alert('File bukan gambar yang valid.');
        </script>
        ";
        exit;
    }

    /* =========================
       BUAT NAMA FILE AMAN
    ========================= */
    $photo = 'checkin_' .
             $user_id . '_' .
             date('YmdHis') . '_' .
             bin2hex(random_bytes(5)) .
             '.' .
             $extension;

    $upload_dir = realpath(__DIR__ . '/../uploads');
    if (!$upload_dir) {
        die("Folder uploads tidak ditemukan.");
    }

    if (!is_dir($upload_dir) || !is_writable($upload_dir)) {
        die("Folder uploads tidak dapat ditulis.");
    }

    $upload_path = $upload_dir . DIRECTORY_SEPARATOR . $photo;

    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {

        echo "
        <script>
            alert('Gagal mengupload selfie.');
        </script>
        ";
        exit;
    }

    /* =========================
       STATUS KEHADIRAN
    ========================= */
$status = strtotime($current_time) <= strtotime('08:00:00')
        ? "Hadir"
        : "Terlambat";

    /* =========================
       SIMPAN ABSENSI
    ========================= */
    $stmt = $conn->prepare("
        INSERT INTO attendance
        (
            user_id,
            attendance_date,
            check_in,
            selfie,
            latitude,
            longitude,
            status
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?, ?
        )
    ");

    if (!$stmt) {
        die($conn->error);
    }

    $stmt->bind_param(
        "isssdds",
        $user_id,
        $attendance_date,
        $checkin_time,
        $photo,
        $latitude,
        $longitude,
        $status
    );

    if ($stmt->execute()) {

        echo "
        <script>
            alert('Check In Berhasil');
            window.location='dashboard.php';
        </script>
        ";
        exit;

    } else {

        if (file_exists($upload_path)) {
            unlink($upload_path);
        }

        echo "
        <script>
            alert('Gagal menyimpan absensi.');
        </script>
        ";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>

    <title>Check In</title>

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet"
          href="../assets/style.css">

</head>

<body class="bg-light">

<div class="container mt-5">

    <div class="card shadow">

        <div class="card-body">

            <h3 class="mb-4">
                Check In
            </h3>

            <form method="POST"
                  enctype="multipart/form-data">

                <div class="mb-3">

                    <label class="form-label">
                        Upload Selfie Check In
                    </label>

                    <input type="file"
                        name="selfie"
                        class="form-control"
                        accept="image/*"
                        capture="user"
                        required>

                </div>

                <input type="hidden"
                       name="latitude"
                       id="latitude">

                <input type="hidden"
                       name="longitude"
                       id="longitude">

                <button type="submit"
                        name="checkin"
                        class="btn btn-success">

                    Check In

                </button>

                <a href="dashboard.php"
                   class="btn btn-secondary">

                    Kembali

                </a>

            </form>

        </div>

    </div>

</div>

<script>

if (navigator.geolocation) {

    navigator.geolocation.getCurrentPosition(
    function(position){
        document.getElementById('latitude').value =
            position.coords.latitude;

        document.getElementById('longitude').value =
            position.coords.longitude;
    },
    function(error){
        alert('Mohon aktifkan GPS untuk melakukan absensi.');
    },
    {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
    }
);

} else {

    alert(
        'Browser tidak mendukung geolocation.'
    );

}

</script>

</body>
</html>