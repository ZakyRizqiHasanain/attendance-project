<?php
session_start();
include '../config/database.php';

if(!isset($_SESSION['user_id'])) {

    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Ambil Data Attendance User
|--------------------------------------------------------------------------
*/

$query = mysqli_query($conn, "
SELECT * FROM attendance
WHERE user_id='$user_id'
ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>History Attendance</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">

</head>

<body class="bg-light">

<!-- NAVBAR -->
<nav class="navbar navbar-dark bg-dark">

    <div class="container-fluid">

        <span class="navbar-brand fw-bold">
            History Attendance
        </span>

        <div>

            <!-- BACK -->
            <a href="dashboard.php"
               class="btn btn-primary btn-sm">

               Kembali

            </a>

            <!-- EXPORT -->
            <a href="export.php"
               class="btn btn-success btn-sm">

               Download Excel

            </a>

            <!-- LOGOUT -->
            <a href="../auth/logout.php"
               class="btn btn-danger btn-sm">

               Logout

            </a>

        </div>

    </div>

</nav>

<!-- CONTENT -->
<div class="container mt-4">

    <div class="card shadow">

        <div class="card-body">

            <h3 class="mb-4 fw-bold">

                Riwayat Absensi

            </h3>

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle">

                    <thead class="table-dark">

                        <tr>

                            <th>No</th>

                            <th>Check In</th>

                            <th>Check Out</th>

                            <th>Status</th>

                            <th>Lokasi</th>

                            <th>Selfie In</th>

                            <th>Selfie Out</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php
                    $no = 1;

                    while($data = mysqli_fetch_assoc($query)) {
                    ?>

                        <tr>

                            <!-- NO -->
                            <td>
                                <?= $no++; ?>
                            </td>

                            <!-- CHECK IN -->
                            <td>
                                <?= $data['check_in']; ?>
                            </td>

                            <!-- CHECK OUT -->
                            <td>

                                <?php
                                if($data['check_out']) {

                                    echo $data['check_out'];

                                } else {

                                    echo "
                                    <span class='text-danger'>
                                        Belum Checkout
                                    </span>
                                    ";

                                }
                                ?>

                            </td>

                            <!-- STATUS -->
                            <td>

                                <?php
                                if($data['status'] == 'Hadir') {
                                ?>

                                    <span class="badge bg-success">
                                        Hadir
                                    </span>

                                <?php
                                } else {
                                ?>

                                    <span class="badge bg-warning text-dark">
                                        Terlambat
                                    </span>

                                <?php } ?>

                            </td>

                            <!-- MAPS -->
                            <td>

                                <a
                                href="https://www.google.com/maps?q=<?= $data['latitude']; ?>,<?= $data['longitude']; ?>"
                                target="_blank"
                                class="btn btn-info btn-sm">

                                    Maps

                                </a>

                            </td>

                            <!-- SELFIE CHECKIN -->
                            <td>

                                <?php
                                if($data['selfie']) {
                                ?>

                                    <img
                                    src="../uploads/<?= $data['selfie']; ?>"
                                    width="80"
                                    class="img-thumbnail">

                                <?php
                                } else {

                                    echo "Tidak ada";

                                }
                                ?>

                            </td>

                            <!-- SELFIE CHECKOUT -->
                            <td>

                                <?php
                                if($data['selfie_checkout']) {
                                ?>

                                    <img
                                    src="../uploads/<?= $data['selfie_checkout']; ?>"
                                    width="80"
                                    class="img-thumbnail">

                                <?php
                                } else {

                                    echo "
                                    <span class='text-danger'>
                                        Belum Checkout
                                    </span>
                                    ";

                                }
                                ?>

                            </td>

                        </tr>

                    <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

</body>
</html>