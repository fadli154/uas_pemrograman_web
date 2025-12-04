<?php
require '../function.php';

// --- Ambil Parameter Search & Filter ---
$search     = isset($_GET['search']) ? $_GET['search'] : '';
$categoryId = isset($_GET['category']) ? $_GET['category'] : '';

// --- Pagination ---
$perPage = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $perPage;

// Base query
$query = "FROM videos v";

// Join jika ada filter kategori
if ($categoryId !== "") {
    $query .= " 
    JOIN categories_videos cv ON cv.video_id = v.video_id 
    JOIN categories c ON c.category_id = cv.category_id";
}

// WHERE
$where = [];

if ($search !== "") {
    $s = trim($search);
    $where[] = "(v.title LIKE '%$s%' OR v.description LIKE '%$s%')";
}

if ($categoryId !== "") {
    $where[] = "c.category_id = '$categoryId'";
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

// Hitung total video
$totalVideos = count(select("SELECT v.video_id $query"));

// Hitung total halaman
$totalPages = ceil($totalVideos / $perPage);

// Ambil data video sesuai filter
$videos = select("SELECT v.* $query LIMIT $start, $perPage");

// Ambil semua kategori untuk dropdown
$categories = select("SELECT * FROM categories");
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Baind | Videos</title>
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
                        <a href="books.php" class="nav-item nav-link">Books</a>
                        <a href="videos.php" class="nav-item nav-link active">Videos</a>
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
                    <h1 class="display-1 mb-0 animated slideInLeft">Videos</h1>
                </div>
                <div class="col-lg-6 animated slideInRight">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center justify-content-lg-end mb-0">
                            <li class="breadcrumb-item"><a class="text-primary" href="#!">Home</a></li>
                            <li class="breadcrumb-item text-secondary active" aria-current="page">Videos</li>
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
            <!-- Search & Filter Video -->
            <form class="row mb-5" method="GET">

                <!-- Search -->
                <div class="col-md-6 mb-2">
                    <input type="text" name="search" class="form-control" placeholder="Cari video..."
                        value="<?= htmlspecialchars($search); ?>">
                </div>

                <!-- Filter Category -->
                <div class="col-md-4 mb-2">
                    <select class="form-select" name="category">
                        <option value="">Semua Kategori</option>
                        <?php foreach($categories as $cat): ?>
                        <option value="<?= $cat['category_id']; ?>"
                            <?= ($categoryId == $cat['category_id'] ? 'selected' : '') ?>>
                            <?= $cat['category_name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Submit -->
                <div class="col-md-2 mb-2">
                    <button class="btn btn-primary w-100">Filter</button>
                </div>

            </form>

            <div class="row g-4">
                <?php foreach($videos as $video): ?>
                <div class="col-md-6 col-lg-3 wow fadeIn" data-wow-delay="0.7s">
                    <div class="team-item position-relative overflow-hidden">
                        <a href="detail_video.php?video_id=<?= $video['video_id'] ?>" style="z-index: 1000;">
                            <img class="img-fluid" style="max-height: 420px !important; width: 100% !important;"
                                src="../thumbnail/<?= $video['thumbnail_url']; ?>" alt="">
                            <div class="team-overlay">
                                <small class="mb-2"><?= $video['title']; ?></small>
                                <div class="d-flex justify-content-center">
                                </div>
                                <a type="button" href="<?= $video['youtube_url']; ?>"
                                    class="d-inline-block border border-2 border-white py-2 px-3 mb-0 text-white animated slideInRight"
                                    target="_blank">
                                    Tonton <i class="fa fa-eye ms-2"></i>
                                </a>
                            </div>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- Pagination -->
            <div class="container mt-5">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">

                        <!-- Previous -->
                        <?php if($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link"
                                href="?page=<?= $page - 1; ?>&search=<?= urlencode($search); ?>&category=<?= $categoryId; ?>">
                                Previous
                            </a>
                        </li>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php for($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($page == $i ? 'active' : '') ?>">
                            <a class="page-link"
                                href="?page=<?= $i; ?>&search=<?= urlencode($search); ?>&category=<?= $categoryId; ?>">
                                <?= $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>

                        <!-- Next -->
                        <?php if($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link"
                                href="?page=<?= $page + 1; ?>&search=<?= urlencode($search); ?>&category=<?= $categoryId; ?>">
                                Next
                            </a>
                        </li>
                        <?php endif; ?>

                    </ul>
                </nav>
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
                                class="fab fa-facevideo-f"></i></a>
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
                    <a class="btn btn-link" href="videos.php">Videos</a>
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