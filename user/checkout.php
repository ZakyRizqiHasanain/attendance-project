<?php
session_start();
include '../config/database.php';

date_default_timezone_set('Asia/Jakarta');

if(isset($_POST['checkout'])) {

    $user_id = $_SESSION['user_id'];

    $time = date("Y-m-d H:i:s");

    $file = $_FILES['selfie']['name'];
    $tmp  = $_FILES['selfie']['tmp_name'];

    move_uploaded_file($tmp, "../uploads/".$file);

    mysqli_query($conn, "
    UPDATE attendance
    SET
        check_out='$time',
        selfie_checkout='$file'
    WHERE user_id='$user_id'
    ORDER BY id DESC
    LIMIT 1
    ");

    echo "
    <script>
        alert('Check Out Berhasil');
        window.location='dashboard.php';
    </script>
    ";
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