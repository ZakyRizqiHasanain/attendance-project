<?php
session_start();
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

require_once __DIR__ . '/../config/database.php';

if (!$conn) {
    die("Database connection failed");
}

/* =========================
   LOGIN PROCESS
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo "<script>alert('Email dan password wajib diisi!');window.location='login.php';</script>";
        exit;
    }

    // AMAN: prepared statement
    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
    if (!$stmt) {
        error_log($conn->error);
        die("Terjadi kesalahan sistem.");
    }

    $stmt->bind_param("s", $email);

    if (!$stmt->execute()) {
        error_log($stmt->error);
        die("Terjadi kesalahan sistem.");
    }

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {

        $user = $result->fetch_assoc();

        // CEK PASSWORD HASH (AMAN)
        if (password_verify($password, $user['password'])) {

            session_regenerate_id(true);    
            $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['ua'] = $_SERVER['HTTP_USER_AGENT'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            // redirect sesuai role
            if ($user['role'] === 'admin') {
                header("Location: ../admin/dashboard.php");
            } else {
                header("Location: ../user/dashboard.php");
            }
            exit;

        } else {
            echo "<script>
            alert('Email atau password salah!');
            window.location='login.php';
            </script>";
            exit;
        }

    } else {
        echo "<script>
            alert('Email atau password salah!');
            window.location='login.php';
            </script>";
            exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login - Attendance System</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
body {
    margin: 0;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #0f172a, #1e2a3a, #0f172a);
    overflow: hidden;
}

body::before {
    content: "";
    position: absolute;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(44,110,47,0.4), transparent 60%);
    top: -200px;
    left: -200px;
    filter: blur(60px);
}

body::after {
    content: "";
    position: absolute;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(59,130,246,0.3), transparent 60%);
    bottom: -200px;
    right: -200px;
    filter: blur(60px);
}

.login-card {
    width: 100%;
    max-width: 380px;
    padding: 40px;
    border-radius: 24px;
    background: rgba(255,255,255,0.08);
    backdrop-filter: blur(18px);
    border: 1px solid rgba(255,255,255,0.15);
    box-shadow: 0 20px 60px rgba(0,0,0,0.4);
    color: #fff;
    animation: fadeIn 0.6s ease;
}

@keyframes fadeIn {
    from {opacity: 0; transform: translateY(20px);}
    to {opacity: 1; transform: translateY(0);}
}

.login-title {
    font-weight: 700;
    font-size: 22px;
    margin-bottom: 5px;
}

.login-subtitle {
    font-size: 13px;
    opacity: 0.7;
    margin-bottom: 25px;
}

.form-control {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    color: #fff;
    border-radius: 12px;
    padding: 12px 14px 12px 40px;
}

.form-control:focus {
    background: rgba(255,255,255,0.15);
    color: #fff;
    border-color: #2c6e2f;
    box-shadow: none;
}

.input-group-icon {
    position: relative;
    margin-bottom: 15px;
}

.input-group-icon i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255,255,255,0.6);
}

.btn-login {
    width: 100%;
    padding: 12px;
    border-radius: 12px;
    border: none;
    background: linear-gradient(135deg, #2c6e2f, #22c55e);
    color: white;
    font-weight: 600;
    transition: 0.2s;
}

.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(34,197,94,0.3);
}

.footer-text {
    text-align: center;
    margin-top: 15px;
    font-size: 12px;
    opacity: 0.6;
}
</style>

</head>

<body>

<div class="login-card">

    <div class="text-center mb-4">
        <div class="login-title">Attendance System</div>
        <div class="login-subtitle">Silakan login untuk melanjutkan</div>
    </div>

    <form method="POST">

        <div class="input-group-icon">
            <i class="bi bi-person"></i>
            <input type="email" name="email" class="form-control" placeholder="Email" required>
        </div>

        <div class="input-group-icon">
            <i class="bi bi-lock"></i>
            <input type="password" name="password" class="form-control" placeholder="Password" required>
        </div>

        <button type="submit" name="login" class="btn-login">
            Login
        </button>

    </form>

    <div class="footer-text">
        © <?= date('Y') ?> Attendance System
    </div>

</div>

</body>
</html>