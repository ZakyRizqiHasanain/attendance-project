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
| Header Excel
|--------------------------------------------------------------------------
*/

header("Content-type: application/vnd-ms-excel");

header("Content-Disposition: attachment; filename=attendance_user.xls");

/*
|--------------------------------------------------------------------------
| Query Attendance User
|--------------------------------------------------------------------------
*/

$query = mysqli_query($conn, "
SELECT * FROM attendance
WHERE user_id='$user_id'
ORDER BY id DESC
");
?>

<table border="1">

    <tr>

        <th>No</th>

        <th>Check In</th>

        <th>Check Out</th>

        <th>Status</th>

        <th>Latitude</th>

        <th>Longitude</th>

    </tr>

    <?php
    $no = 1;

    while($data = mysqli_fetch_assoc($query)) {
    ?>

    <tr>

        <td>
            <?= $no++; ?>
        </td>

        <td>
            <?= $data['check_in']; ?>
        </td>

        <td>
            <?= $data['check_out']; ?>
        </td>

        <td>
            <?= $data['status']; ?>
        </td>

        <td>
            <?= $data['latitude']; ?>
        </td>

        <td>
            <?= $data['longitude']; ?>
        </td>

    </tr>

    <?php } ?>

</table>