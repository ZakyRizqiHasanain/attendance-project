<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = new mysqli(
    "localhost",
    "root",
    "",
    "attendance_system"
);

$conn->set_charset("utf8mb4");

if(!$conn) {

    die("Connection Failed: " . mysqli_connect_error());

}
?>