<?php
session_start();
include '../config/database.php';

date_default_timezone_set('Asia/Jakarta');

if(isset($_POST['checkin'])) {

    $user_id = $_SESSION['user_id'];

    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];

    $time = date("Y-m-d H:i:s");
    $current_time = date("H:i:s");

    $file = $_FILES['selfie']['name'];
    $tmp  = $_FILES['selfie']['tmp_name'];

    move_uploaded_file($tmp, "../uploads/".$file);

    if($current_time <= "08:00:00") {

        $status = "Hadir";

    } else {

        $status = "Terlambat";

    }

    mysqli_query($conn, "
    INSERT INTO attendance(
        user_id,
        check_in,
        selfie,
        latitude,
        longitude,
        status
    )
    VALUES(
        '$user_id',
        '$time',
        '$file',
        '$latitude',
        '$longitude',
        '$status'
    )
    ");

    echo "
    <script>
        alert('Check In Berhasil');
        window.location='dashboard.php';
    </script>
    ";
}
?>

<!DOCTYPE html>
<html>
<head>

    <title>Check In</title>
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

navigator.geolocation.getCurrentPosition(function(position) {

    document.getElementById('latitude').value =
        position.coords.latitude;

    document.getElementById('longitude').value =
        position.coords.longitude;

});

</script>

</body>
</html>