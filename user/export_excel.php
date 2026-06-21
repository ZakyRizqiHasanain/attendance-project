<?php
session_start();

include '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Koneksi database gagal.");
}

$user_id = (int) $_SESSION['user_id'];

/* =========================
   HEADER EXCEL
========================= */
$filename = "attendance_history_" . date('Ymd_His') . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

/* =========================
   GET ATTENDANCE DATA
========================= */
$stmt = $conn->prepare("
    SELECT
        id,
        attendance_date,
        check_in,
        check_out,
        status,
        latitude,
        longitude
    FROM attendance
    WHERE user_id = ?
    ORDER BY attendance_date DESC, id DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body>

<h3>Riwayat Absensi User</h3>

<p>
Tanggal Export :
<?= date('d-m-Y H:i:s'); ?>
</p>

<table border="1">

    <thead>
        <tr style="background:#d9d9d9;font-weight:bold;">
            <th>No</th>
            <th>Tanggal</th>
            <th>Check In</th>
            <th>Check Out</th>
            <th>Status Kehadiran</th>
            <th>Lokasi</th>
        </tr>
    </thead>

    <tbody>

        <?php
        $no = 1;

        if ($result->num_rows > 0) :

            while ($row = $result->fetch_assoc()) :

                switch ($row['status']) {

                    case 'Hadir':
                        $status = 'Hadir';
                        break;

                    case 'Terlambat':
                        $status = 'Terlambat';
                        break;

                    case 'Izin':
                        $status = 'Izin';
                        break;

                    case 'Sakit':
                        $status = 'Sakit';
                        break;

                    case 'Alpha':
                        $status = 'Tidak Hadir';
                        break;

                    default:
                        $status = '-';
                }

                $lokasi = (
                    !empty($row['latitude']) &&
                    !empty($row['longitude'])
                )
                ? $row['latitude'] . ', ' . $row['longitude']
                : '-';
        ?>

        <tr>
            <td><?= $no++; ?></td>

            <td>
                <?= date('d-m-Y', strtotime($row['attendance_date'])); ?>
            </td>

            <td>
                <?= !empty($row['check_in'])
                    ? date('d-m-Y H:i:s', strtotime($row['check_in']))
                    : '-'; ?>
            </td>

            <td>
                <?= !empty($row['check_out'])
                    ? date('d-m-Y H:i:s', strtotime($row['check_out']))
                    : '-'; ?>
            </td>

            <td>
                <?= htmlspecialchars($status); ?>
            </td>

            <td>
                <?= htmlspecialchars($lokasi); ?>
            </td>
        </tr>

        <?php
            endwhile;

        else:
        ?>

        <tr>
            <td colspan="6" align="center">
                Tidak ada data absensi
            </td>
        </tr>

        <?php endif; ?>

        </tbody>

</table>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>