<?php
session_start();

if(!isset($_SESSION['user_id'])) {

    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Redirect ke Checkin
|--------------------------------------------------------------------------
*/

header("Location: checkin.php");
exit;
?>