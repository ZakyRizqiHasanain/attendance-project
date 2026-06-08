<?php
session_start();
include 'config/database.php';

if(isset($_POST['login'])) {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = mysqli_query($conn,
        "SELECT * FROM users WHERE email='$email'"
    );

    if(mysqli_num_rows($query) > 0) {

        $user = mysqli_fetch_assoc($query);

        // LOGIN TANPA HASH
        if($password == $user['password']) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            if($user['role'] == 'admin') {

                header("Location: admin/dashboard.php");

            } else {

                header("Location: user/dashboard.php");

            }

            exit;

        } else {

            echo "
            <script>
                alert('Password salah!');
                window.location='index.php';
            </script>
            ";

        }

    } else {

        echo "
        <script>
            alert('Email tidak ditemukan!');
            window.location='index.php';
        </script>
        ";

    }
}
?>

<!DOCTYPE html>
<html>
<head>

    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/style.css">

</head>

<body class="login-body">

<div class="container d-flex justify-content-center align-items-center vh-100">

    <div class="card login-card">

        <div class="login-avatar">
            <svg viewBox="0 0 24 24">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
        </div>

        <div class="card-body p-0">

            <form method="POST">

                <!-- EMAIL -->
                <div class="login-input-group">
                    <i class="bi bi-person"></i>
                    <input type="email"
                           name="email"
                           class="form-control"
                           placeholder="Username"
                           required>
                </div>

                <!-- PASSWORD -->
                <div class="login-input-group">
                    <i class="bi bi-lock"></i>
                    <input type="password"
                           name="password"
                           class="form-control"
                           placeholder="Password"
                           required>
                </div>

                <!-- REMEMBER ME & FORGOT PASSWORD -->
                <div class="d-flex justify-content-between align-items-center mb-4 login-footer-links">
                    <div class="form-check m-0">
                        <input class="form-check-input" type="checkbox" id="rememberMe">
                        <label class="form-check-label m-0" for="rememberMe" style="text-transform: none; font-size: 13px; font-weight: 400; color: #94a3b8; letter-spacing: normal;">
                            Remember me
                        </label>
                    </div>
                    <a href="#" style="font-size: 13px;">Forgot Password?</a>
                </div>

                <!-- SUBMIT BUTTON -->
                <button type="submit"
                        name="login"
                        class="btn btn-login-submit">
                    Login
                </button>

            </form>

        </div>

    </div>

</div>

</body>
</html>