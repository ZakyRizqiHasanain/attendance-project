<?php
date_default_timezone_set('Asia/Jakarta');

require_once '../config/database.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Koneksi database tidak valid.");
}

/* =========================
   BULAN FILTER
========================= */
$bulan = $_GET['bulan'] ?? date('Y-m');

/* =========================
   HEADER EXCEL
========================= */
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"attendance-$bulan.xls\"");
header("Cache-Control: max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

/* =========================
   QUERY DATA (WITH LEAVE)
========================= */
$sql = "
    SELECT 
        a.attendance_date,
        u.name,
        a.check_in,
        a.check_out,
        a.latitude,
        a.longitude,
        l.type AS leave_type,
        l.approval_status
    FROM attendance a
    INNER JOIN users u ON a.user_id = u.id
    LEFT JOIN leave_requests l 
        ON a.user_id = l.user_id 
        AND a.attendance_date = l.leave_date
    WHERE DATE_FORMAT(a.attendance_date, '%Y-%m') = ?
    ORDER BY a.attendance_date DESC, a.id DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}

$stmt->bind_param("s", $bulan);
$stmt->execute();
$result = $stmt->get_result();

/* =========================
   HTML EXPORT
========================= */
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body>

<h3>Rekap Attendance Bulan: <?= htmlspecialchars($bulan) ?></h3>

<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr style="background:#f2f2f2;">
            <th>No</th>
            <th>Tanggal</th>
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

        if ($result && $result->num_rows > 0):
            while ($data = $result->fetch_assoc()):

                /* =========================
                   STATUS LOGIC FINAL
                ========================= */
                if (!empty($data['leave_type']) && $data['approval_status'] === 'Approved') {

                    $status = $data['leave_type']; // Izin / Sakit

                } elseif (!empty($data['check_in'])) {

                    $status = ($data['check_in'] <= "08:00:00")
                        ? "Hadir"
                        : "Terlambat";

                } else {

                    $status = "Alpha";
                }
        ?>

        <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($data['attendance_date'] ?? '-') ?></td>
            <td><?= htmlspecialchars($data['name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($data['check_in'] ?? '-') ?></td>
            <td><?= htmlspecialchars($data['check_out'] ?? '-') ?></td>
            <td><?= htmlspecialchars($status) ?></td>
            <td><?= htmlspecialchars($data['latitude'] ?? '-') ?></td>
            <td><?= htmlspecialchars($data['longitude'] ?? '-') ?></td>
        </tr>

        <?php
            endwhile;
        else:
        ?>

        <tr>
            <td colspan="8" style="text-align:center;">
                Tidak ada data pada bulan ini
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