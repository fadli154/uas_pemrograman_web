<?php
require '../function.php';

if (isset($_POST['submit_login'])) {
    $user_id = $_POST['user_id'];
    $password = $_POST['password'];

    // Ambil user berdasarkan user_id saja
    $query = "
        SELECT * FROM users 
        LEFT JOIN roles ON users.role_id = roles.role_id 
        WHERE users.user_id = '$user_id' 
        LIMIT 1
    ";
    $result = mysqli_query($connection, $query);
    $user = mysqli_fetch_assoc($result);

    if ($user) {
        // Verifikasi password menggunakan bcrypt
        if (password_verify($password, $user['password'])) {
            // Set session login
            $_SESSION["log"] = true;
            $_SESSION["sweetalert"] = true;

            $_SESSION['user'] = [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'photo' => $user['photo'],
                'role'  => $user['role_name'] ?? 'User'
            ];

            header("Location: ../dashboard/dashboard.php");
            exit;
        } 
    } else {
       $_SESSION["error"] = "NIM/NIK/Password is incorrect";
            header("Location: login.php");
            exit;
    }
}

// cek kalau sudah login ke dashboard
if (isset($_SESSION["log"])) {
    header("Location: ../dashboard/dashboard.php");
    exit;
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
                    <h1 class="auth-title">Log in.</h1>
                    <p class="auth-subtitle mb-5">Log in with your data that you entered during registration.</p>

                    <form action="" method="post" id="login">
                        <div class="form-group position-relative has-icon-left mb-4">
                            <input type="text" name="user_id" id="user_id"
                                class="form-control form-control-xl  <?= isset($_SESSION["error"]) ? 'is-invalid' : '' ?>"
                                placeholder="NIM or NIK" required>
                            <label class="form-control-icon d-inline-block" for="user_id">
                                <i class="bi bi-person-lock"></i>
                            </label>
                        </div>
                        <div class="form-group position-relative has-icon-left mb-4">
                            <input type="password" name="password" id="password"
                                class="form-control form-control-xl <?= isset($_SESSION["error"]) ? 'is-invalid' : '' ?>"
                                placeholder="Password" required>
                            <label class="form-control-icon" for="password">
                                <i class="bi bi-shield-lock"></i>
                            </label>
                            <?php if (isset($_SESSION["error"])): ?>
                            <div class="invalid-feedback text-center mt-3">
                                <?= $_SESSION["error"]; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <button class="btn btn-primary btn-block btn-lg shadow-lg mt-3" type="submit"
                            name="submit_login">Log in</button>
                    </form>
                    <div class="text-center mt-3 text-md fs-8">
                        <p class="text-gray-600">Don't have an account? <a href="register.php" class="font-bold">Sign
                                up</a>.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-7 d-none d-lg-block">
                <div id="auth-right"></div>
            </div>
        </div>
    </div>

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

    <script>
    window.onload = function() {
        <?php if(isset($_SESSION["error"])): ?>
        Swal.fire({
            icon: 'error',
            title: 'Login Failed',
            text: 'Email or password is incorrect. Please try again.',
            confirmButtonText: 'OK'
        });
        <?php unset($_SESSION["error"]); ?>
        <?php endif; ?>
    };
    </script>

    <script>
    window.onload = function() {
        <?php if (isset($_SESSION["logout"])): ?>
        Swal.fire({
            icon: 'success',
            title: 'Logout Success',
            text: 'You have been logout!',
            confirmButtonText: 'OK'
        });
        <?php unset($_SESSION["logout"]); // hapus setelah ditampilkan ?>
        <?php endif; ?>
    };
    </script>
</body>

</html>