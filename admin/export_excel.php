<?php
date_default_timezone_set('Asia/Jakarta'); // Wajib agar tanggal sesuai
include '../config/database.php';

$today = date('Y-m-d'); // akan menjadi 2026-06-13 jika server benar

header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=attendance-data-" . $today . ".xls");

$query = mysqli_query($conn, "
SELECT attendance.*, users.name
FROM attendance
JOIN users ON attendance.user_id = users.id
ORDER BY attendance.id DESC
");
?>

<table border="1">
    <thead>
        <tr>
            <th>No</th>
            <th>Nama</th>
            <th>Check In</th>
            <th>Check Out</th>
            <th>Status</th>
            <th>Latitude</th>
            <th>Longitude</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        while($data = mysqli_fetch_assoc($query)):
        ?>
        <tr>
            <td><?= $no++; ?></td>
            <td><?= $data['name']; ?></td>
            <td><?= $data['check_in']; ?></td>
            <td><?= $data['check_out']; ?></td>
            <td><?= $data['status']; ?></td>
            <td><?= $data['latitude']; ?></td>
            <td><?= $data['longitude']; ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>