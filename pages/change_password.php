<?php
require '../function.php';

if (!isset($_SESSION["log"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id_old'];
    if (updateProfile($userId, $_POST, $_FILES)) {
        unset($_SESSION["errors"]);
        $_SESSION["success"] = "Successfully updated profile.";
        header("Location: profile.php");
        exit;
    }
}

$user_id = $_SESSION['user']['user_id'];

if($user_id){
    $user = detailUser($user_id);
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Baind | Change Password</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="img/favicon.svg" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans&family=Space+Grotesk&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">


    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/extensions/iziToast/css/iziToast.min.css">


    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <!-- Spinner Start -->
    <div id="spinner"
        class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-grow text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->

    <!-- Navbar Start -->
    <div class="container-fluid sticky-top">
        <div class="container">
            <nav class="navbar navbar-expand-lg navbar-light border-bottom border-2 border-white">
                <a href="index.php" class="navbar-brand">
                    <h1>Baind</h1>
                </a>
                <button type="button" class="navbar-toggler ms-auto me-0" data-bs-toggle="collapse"
                    data-bs-target="#navbarCollapse">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarCollapse">
                    <div class="navbar-nav ms-auto">
                        <a href="index.php" class="nav-item nav-link">Home</a>
                        <a href="books.php" class="nav-item nav-link">Book</a>
                        <a href="videos.php" class="nav-item nav-link">Video</a>
                        <?php
                        if (isset($_SESSION["log"])) {

                            $role = $_SESSION["user"]["role_id"]; // ambil role user
                            $username = $_SESSION["user"]["name"]; // ambil role user

                            echo "<div class='nav-item dropdown'>";
                            echo " <a href='#' class='nav-link dropdown-toggle' data-bs-toggle='dropdown'> " . $username . " </a>";
                            echo "<div class='dropdown-menu bg-light mt-2'>";

                            // Jika role 1 atau 2 -> tampilkan dashboard
                            if ($role == '1' || $role == '2') {
                                echo "<a href='../dashboard/dashboard.php' class='dropdown-item'>
                                        <i class='bi bi-grid-fill me-2'></i> Dashboard
                                    </a>";
                            }

                            // Jika role 3 -> tampilkan profile & change password
                            if ($role == '3') {
                                echo "<a href='profile.php' class='dropdown-item'>
                                        <i class='bi bi-person-fill me-2'></i> Profile
                                    </a>";

                                echo "<a href='change_password.php' class='dropdown-item'>
                                        <i class='bi bi-key-fill me-2'></i> Change Password
                                    </a>";
                            }

                            // Tombol logout (semua role dapat)
                            echo "<a href='#' class='dropdown-item logout-btn'>
                                    <i class='bi bi-door-open-fill me-2'></i> Logout
                                </a>";

                            echo " </div>";
                            echo " </div>";

                        } else {
                            echo "<a href='login.php' class='nav-item nav-link'>Login</a>";
                        }
                        ?>
                    </div>
                </div>
            </nav>
        </div>
    </div>
    <!-- Navbar End -->


    <!-- Hero Start -->
    <div class="container-fluid pb-5 bg-primary hero-header">
        <div class="container py-5">
            <div class="row g-3 align-items-center">
                <div class="col-lg-6 text-center text-lg-start">
                    <h1 class="display-1 mb-0 animated slideInLeft">Change Password</h1>
                </div>
                <div class="col-lg-6 animated slideInRight">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center justify-content-lg-end mb-0">
                            <li class="breadcrumb-item"><a class="text-primary" href="#!">Home</a></li>
                            <li class="breadcrumb-item text-secondary active" aria-current="page">Change Password</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- Hero End -->


    <!-- About Start -->
    <div class="container-fluid py-5">
        <div class="container py-5">
            <div class="row">
                <div class="col-lg-4">
                    <?php if ($user['photo']): ?>
                    <img class="img-fluid w-75 shadow-lg " style="border-radius: 50%; aspect-ratio: 1/1;"
                        src="../uploads/<?= $user['photo']; ?>" alt="">
                    <?php else: ?>
                    <img class="img-fluid w-75 shadow-lg" src="../assets/static/images/faces/1.jpg" alt="">
                    <?php endif; ?>
                </div>
                <div class="col-lg-8">
                    <form action="" method="post" enctype="multipart/form-data">
                        <input type="text" name="user_id_old" value="<?= $user["user_id"] ?>" hidden>
                        <input type="text" name="role_id" value="<?= $user["role_id"] ?>" hidden>
                        <input type="text" name="password" value="<?= $user["password"] ?>" hidden>

                        <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                            <div class="row m-2">
                                <!-- USER ID -->
                                <div class="col-12">
                                    <div class="form-group mandatory position-relative has-icon-left">
                                        <label for="user_id" class="form-label">NIM</label>
                                        <input type="number" id="user_id" name="user_id"
                                            class="form-control form-control-lg <?= isset($_SESSION["errors"]["user_id"]) ? 'is-invalid' : '' ?>"
                                            placeholder="e.g 241730042" value="<?= $user["user_id"] ?>" readonly>
                                        <div class="form-control-icon" style="top: 38px">
                                            <i class="bi bi-person-exclamation"></i>
                                        </div>
                                        <?php if (isset($_SESSION["errors"]["user_id"])): ?>
                                        <div class="invalid-feedback">
                                            <?= $_SESSION["errors"]["user_id"]; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- NAME -->
                                <div class="col-md-6 col-12 mt-2">
                                    <div class="form-group mandatory position-relative has-icon-left">
                                        <label for="name" class="form-label">Name</label>
                                        <input type="text" id="name" name="name"
                                            class="form-control form-control-lg <?= isset($_SESSION["errors"]["name"]) ? 'is-invalid' : '' ?>"
                                            placeholder="e.g Fadli Hifziansyah" value="<?= $user["name"] ?>" required>
                                        <?php if (isset($_SESSION["errors"]["name"])): ?>
                                        <div class="invalid-feedback">
                                            <?= $_SESSION["errors"]["name"]; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- EMAIL -->
                                <div class="col-md-6 col-12 mt-2">
                                    <div class="form-group mandatory position-relative has-icon-left">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" id="email" name="email"
                                            class="form-control form-control-lg <?= isset($_SESSION["errors"]["email"]) ? 'is-invalid' : '' ?>"
                                            placeholder="e.g fadlihifziansyah153@gmail.com"
                                            value="<?= $user["email"] ?>" required>
                                        <?php if (isset($_SESSION["errors"]["email"])): ?>
                                        <div class="invalid-feedback">
                                            <?= $_SESSION["errors"]["email"]; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- PHONE -->
                                <div class="col-12 mt-2">
                                    <div class="form-group mandatory position-relative has-icon-left">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="text" id="phone" name="phone"
                                            class="form-control form-control-lg <?= isset($_SESSION["errors"]["phone"]) ? 'is-invalid' : '' ?>"
                                            placeholder="e.g 0878 2738 2281" value="<?= $user["phone"] ?>" required>
                                        <?php if (isset($_SESSION["errors"]["phone"])): ?>
                                        <div class="invalid-feedback">
                                            <?= $_SESSION["errors"]["phone"]; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- STATUS -->
                                <div class="col-12 mt-2">
                                    <div class="form-group mandatory position-relative has-icon-left">
                                        <label for="status" class="form-label">Status</label>
                                        <select id="status"
                                            class="choices form-control form-select <?= isset($_SESSION["errors"]["status"]) ? 'is-invalid' : '' ?>"
                                            name="status" required>
                                            <option value="">-- Select Status --</option>
                                            <option value="active"
                                                <?= (($user["status"] ?? '') === 'active') ? 'selected' : '' ?>>
                                                Active</option>
                                            <option value="inactive"
                                                <?= (($user['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>
                                                Inactive</option>
                                        </select>
                                        <?php if (isset($_SESSION["errors"]["status"])): ?>
                                        <div class="invalid-feedback">
                                            <?= $_SESSION["errors"]["status"]; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- PASSWORD SECTION -->
                                <div class="col-12 mt-2">
                                    <div class="">
                                        <div class="mt-2 mb-3 px-2 py-3 rounded-3 shadow-sm"
                                            style="background: rgba(0, 123, 255, 0.15); backdrop-filter: blur(6px); border: 1px solid rgba(255,255,255,0.2);">
                                            <h6 class="mb-1 text-primary">
                                                <i class="bi bi-shield-lock"></i> Change Password
                                                (Optional)
                                            </h6>
                                            <p class="text-white text-muted mb-0">
                                                Leave these fields blank if you don't want to change
                                                your password.
                                            </p>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 col-12">
                                                <div class="form-group position-relative has-icon-left">
                                                    <label for="old_password" class="form-label">Old
                                                        Password</label>
                                                    <input type="password" id="old_password" name="old_password"
                                                        class="form-control form-control-lg"
                                                        placeholder="Enter your current password">
                                                </div>
                                            </div>
                                            <div class="col-md-6 col-12">
                                                <div class="form-group position-relative has-icon-left">
                                                    <label for="new_password" class="form-label">New
                                                        Password</label>
                                                    <input type="password" id="new_password" name="new_password"
                                                        class="form-control form-control-lg"
                                                        placeholder="Enter new password">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- PHOTO -->
                                <div class="col-12 mb-1 mt-2">
                                    <fieldset>
                                        <label class="mb-1" for="photo">Photo</label>
                                        <div class="input-group">
                                            <input type="file" class="form-control" id="photo" name="photo"
                                                accept="image/*">
                                            <button class="btn btn-primary z-0" type="button"
                                                id="inputGroupFileAddon04">Upload</button>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER BUTTONS -->
                        <div class="modal-footer mx-2 mt-4">
                            <a href="javascript:history.back()" class="btn btn-secondary me-2 text-light"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="Back">
                                <span><i class="bi bi-arrow-90deg-left"></i> Back</span>
                            </a>
                            <a href="profile.php" class="btn btn-info me-2 text-white" data-bs-toggle="tooltip"
                                data-bs-placement="top" title="Profile">
                                <span><i class="bi bi-eye"></i> Profile</span>
                            </a>
                            <button type="submit" class="btn btn-primary me-2 text-white" data-bs-toggle="tooltip"
                                data-bs-placement="top" title="Edit User">
                                <span><i class="bi bi-check-circle"></i> Edit</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- About End -->

    <!-- Newsletter Start -->
    <div class="container-fluid bg-primary newsletter p-0">
        <div class="container p-0">
            <div class="row g-0 align-items-center">
                <div class="col-md-5 ps-lg-0 text-start wow fadeIn" data-wow-delay="0.2s">
                    <img class="img-fluid w-100" src="img/newsletter.jpg" alt="">
                </div>
                <div class="col-md-7 py-5 newsletter-text wow fadeIn" data-wow-delay="0.5s">
                    <div class="p-5">
                        <h1 class="mb-5">Subscribe the <span
                                class="text-uppercase text-primary bg-white px-2">Newsletter</span>
                        </h1>
                        <div class="position-relative w-100 mb-2">
                            <input class="form-control border-0 w-100 ps-4 pe-5" type="text"
                                placeholder="Enter Your Email" style="height: 60px;">
                            <button type="button" class="btn shadow-none position-absolute top-0 end-0 mt-2 me-2"><i
                                    class="fa fa-paper-plane text-primary fs-4"></i></button>
                        </div>
                        <p class="mb-0">Diam sed sed dolor stet amet eirmod</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Newsletter End -->


    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-white-50 footer pt-5">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-md-6 col-lg-3 wow fadeIn" data-wow-delay="0.1s">
                    <a href="index.php" class="d-inline-block mb-3">
                        <h1 class="text-white">Baind</h1>
                    </a>
                    <p class="mb-0">Tempor erat elitr rebum at clita. Diam dolor diam ipsum et
                        tempor sit. Aliqu diam
                        amet diam et eos labore. Clita erat ipsum et lorem et sit, sed stet no
                        labore lorem sit. Sanctus
                        clita duo justo et tempor</p>
                </div>
                <div class="col-md-6 col-lg-3 wow fadeIn" data-wow-delay="0.3s">
                    <h5 class="text-white mb-4">Get In Touch</h5>
                    <p><i class="fa fa-map-marker-alt me-3"></i>123 Street, New York, USA</p>
                    <p><i class="fa fa-phone-alt me-3"></i>+012 345 67890</p>
                    <p><i class="fa fa-envelope me-3"></i>info@example.com</p>
                    <div class="d-flex pt-2">
                        <a class="btn btn-outline-primary btn-square border-2 me-2" href="#!"><i
                                class="fab fa-twitter"></i></a>
                        <a class="btn btn-outline-primary btn-square border-2 me-2" href="#!"><i
                                class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-outline-primary btn-square border-2 me-2" href="#!"><i
                                class="fab fa-youtube"></i></a>
                        <a class="btn btn-outline-primary btn-square border-2 me-2" href="#!"><i
                                class="fab fa-instagram"></i></a>
                        <a class="btn btn-outline-primary btn-square border-2 me-2" href="#!"><i
                                class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 wow fadeIn" data-wow-delay="0.5s">
                    <h5 class="text-white mb-4">Fast Link</h5>
                    <a class="btn btn-link" href="index.php">Home</a>
                    <a class="btn btn-link" href="books.php">Books</a>
                    <a class="btn btn-link" href="videos.php">Videos</a>
                </div>
                <div class="col-md-6 col-lg-3 wow fadeIn" data-wow-delay="0.5s">
                    <h5 class="text-white mb-4">Setting Link</h5>
                    <a class="btn btn-link" href="profile.php">Profile</a>
                    <a class="btn btn-link" href="change_password.php">Change Password</a>
                </div>
            </div>
        </div>
        <div class="container wow fadeIn" data-wow-delay="0.1s">
            <div class="copyright">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        &copy; <a class="border-bottom" href="#!">Baind</a>, All Right
                        Reserved.

                        <!--/*** This template is free as long as you keep the below author’s credit link/attribution link/backlink. ***/-->
                        <!--/*** If you'd like to use the template without the below author’s credit link/attribution link/backlink, ***/-->
                        <!--/*** you can purchase the Credit Removal License from "https://htmlcodex.com/credit-removal". ***/-->
                        Designed By <a class="border-bottom" href="https://htmlcodex.com">HTML
                            Codex</a>. Distributed by
                        <a class="border-bottom" href="https://themewagon.com" target="_blank">ThemeWagon</a>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <div class="footer-menu">
                            <a href="#!">Home</a>
                            <a href="#!">Cookies</a>
                            <a href="#!">Help</a>
                            <a href="#!">FAQs</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->


    <!-- Back to Top -->
    <a href="#!" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

    <script src="../assets/extensions/iziToast/js/iziToast.min.js"></script>

    <script>
    <?php if (isset($_SESSION["success"])): ?>
    $(document).ready(function() {
        iziToast.success({
            title: 'Success',
            message: "<?= $_SESSION["success"]; ?>",
            position: 'topRight'
        })
    });
    <?php unset($_SESSION["success"]); // hapus setelah ditampilkan ?>
    <?php endif; ?>
    </script>

    <script>
    <?php if (isset($_SESSION["error"])): ?>
    $(document).ready(function() {
        iziToast.error({
            title: 'Error',
            message: "<?= $_SESSION["error"]; ?>",
            position: 'topRight'
        })
    });
    <?php unset($_SESSION["error"]); // hapus setelah ditampilkan ?>
    <?php endif; ?>
    </script>
    <!-- logout -->
    <script>
    document.body.addEventListener('click', function(e) {
        const element = e.target.closest(
            '.logout-btn'); // cari elemen logout-btn terdekat
        if (!element) return;

        e.preventDefault(); // cegah langsung berpindah halaman

        Swal.fire({
            title: "Sure Wanna logout?",
            text: "You will be logged out.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Ya, logout",
            cancelButtonText: "Reject"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "../pages/logout.php";
            }
        });
    });
    </script>

    <!-- JavaScript Libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js">
    </script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
</body>

</html>