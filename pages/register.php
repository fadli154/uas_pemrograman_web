<?php
    require '../function.php';

    if(isset($_POST['submit_login'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $credentials = mysqli_query($connection, "SELECT * FROM users WHERE email = '$email' AND password = '$password'");

        $result = mysqli_num_rows($credentials);

        
        if($result > 0) {
            header("Location: ../dashboard/dashboard.php");
        } else {
            header("Location: login.php");
        }
    }

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mazer Admin Dashboard</title>
    <link rel="shortcut icon" href="../assets/compiled/svg/favicon.svg" type="image/x-icon">
    <link rel="stylesheet" href="../assets/compiled/css/app.css">
    <link rel="stylesheet" href="../assets/compiled/css/app-dark.css">
    <link rel="stylesheet" href="../assets/compiled/css/auth.css">
</head>

<body>
    <script src="../assets/static/js/initTheme.js"></script>
    <div id="auth">
        <div class="row h-100">
            <div class="col-lg-5 col-12 d-flex flex-column justify-content-center align-items-center">
                <div id="auth-left">
                    <div class="auth-logo">
                        <a href="index.php"><img src="../assets/compiled/svg/logo.svg" alt="Logo"></a>
                    </div>
                    <h1 class="auth-title ">Log in.</h1>
                    <p class="auth-subtitle mb-5">Log in with your data that you entered during registration.</p>

                    <form action="" method="post" id="login">
                        <div class="form-group position-relative has-icon-left mb-4">
                            <input type="email" name="email" id="email" class="form-control form-control-xl"
                                placeholder="Email" required>
                            <label class="form-control-icon d-inline-block" for="email">
                                <i class="bi bi-envelope"></i>
                            </label>
                        </div>
                        <div class="form-group position-relative has-icon-left mb-4">
                            <input type="password" name="password" id="password" class="form-control form-control-xl"
                                placeholder="Password" required>
                            <label class="form-control-icon" for="password">
                                <i class="bi bi-shield-lock"></i>
                            </label>
                        </div>
                        <button class="btn btn-primary btn-block btn-lg shadow-lg mt-3" type="submit"
                            name="submit_login">Log
                            in</button>
                    </form>
                    <div class="text-center mt-3 text-md fs-8">
                        <p class="text-gray-600">Don't have an account? <a href="auth-register.html"
                                class="font-bold">Sign
                                up</a>.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-7 d-none d-lg-block">
                <div id="auth-right">

                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
</body>

</html>