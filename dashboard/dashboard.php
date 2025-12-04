<?php

require '../function.php';
require '../check.php';

$name = htmlspecialchars($_SESSION['user']['name']);
$role = htmlspecialchars($_SESSION['user']['role']);

$photo = $_SESSION['user']['photo'] ?? null;

// path foto default
$defaultPhoto = "../assets/compiled/jpg/1.jpg";

$photoPath = (!empty($photo)) ? "../uploads/" . htmlspecialchars($photo) : $defaultPhoto;

$books_count  = select("SELECT COUNT(*) AS total FROM books")[0]['total'];
$users_count  = select("SELECT COUNT(*) AS total FROM users")[0]['total'];
$videos_count = select("SELECT COUNT(*) AS total FROM videos")[0]['total'];
$roles_count  = select("SELECT COUNT(*) AS total FROM roles")[0]['total'];

$chartData = select("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') AS bulan,
        COUNT(*) AS total
    FROM books
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY bulan ASC
");

// siapkan data untuk chart
$categories = []; // contoh: ["2025-01", "2025-02", ...]
$totals = [];

foreach ($chartData as $row) {
    // konversi ke format lebih cantik, misal Jan 2025
    $formatBulan = date("M Y", strtotime($row['bulan'] . "-01"));
    $categories[] = $formatBulan;
    $totals[] = $row['total'];
}

$categories_json = json_encode($categories);
$totals_json = json_encode($totals);

// ambil jumlah user per role
$rolesChart = select("
    SELECT r.role_name AS role, COUNT(u.user_id) AS total
    FROM roles r
    LEFT JOIN users u ON u.role_id = r.role_id
    GROUP BY r.role_id
");

$roleLabels = [];
$roleTotals = [];

foreach ($rolesChart as $r) {
    $roleLabels[] = $r['role'];
    $roleTotals[] = $r['total'];
}

$roleLabels_json = json_encode($roleLabels);
$roleTotals_json = json_encode($roleTotals);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard</title>
    <link rel="shortcut icon" href="../assets/compiled/svg/favicon.svg" type="image/x-icon" />
    <link rel="stylesheet" href="../assets/compiled/css/app.css" />
    <link rel="stylesheet" href="../assets/compiled/css/app-dark.css" />
    <link rel="stylesheet" crossorigin="" href="../assets/compiled/css/iconly.css">
    <style>
    #roles-donut-chart {
        width: 100%;
        height: 300px;
        /* atau 350–400px supaya proporsional */
    }

    .chart-container {
        flex: 1;
        min-height: 300px;
    }
    </style>
</head>

<body>
    <script src="../assets/static/js/initTheme.js"></script>
    <div id="app">
        <div id="sidebar">
            <div class="sidebar-wrapper active">
                <div class="sidebar-header position-relative">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="logo">
                            <a href="../pages/index.php"><img src="../assets/compiled/svg/logo.svg" alt="Logo"
                                    srcset="" /></a>
                        </div>
                        <div class="theme-toggle d-flex gap-2 align-items-center justify-content-center mt-2">
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                aria-hidden="true" role="img" class="iconify iconify--system-uicons" width="20"
                                height="20" preserveAspectRatio="xMidYMid meet" viewBox="0 0 21 21">
                                <g fill="none" fill-rule="evenodd" stroke="currentColor" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path
                                        d="M10.5 14.5c2.219 0 4-1.763 4-3.982a4.003 4.003 0 0 0-4-4.018c-2.219 0-4 1.781-4 4c0 2.219 1.781 4 4 4zM4.136 4.136L5.55 5.55m9.9 9.9l1.414 1.414M1.5 10.5h2m14 0h2M4.135 16.863L5.55 15.45m9.899-9.9l1.414-1.415M10.5 19.5v-2m0-14v-2"
                                        opacity=".3"></path>
                                    <g transform="translate(-210 -1)">
                                        <path d="M220.5 2.5v2m6.5.5l-1.5 1.5"></path>
                                        <circle cx="220.5" cy="11.5" r="4"></circle>
                                        <path d="m214 5l1.5 1.5m5 14v-2m6.5-.5l-1.5-1.5M214 18l1.5-1.5m-4-5h2m14 0h2">
                                        </path>
                                    </g>
                                </g>
                            </svg>
                            <div class="form-check form-switch d-flex justify-content-center mb-0 fs-6">
                                <input class="form-check-input me-0" type="checkbox" id="toggle-dark"
                                    style="cursor: pointer" />
                                <label class="form-check-label"></label>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                aria-hidden="true" role="img" class="iconify iconify--mdi" width="20" height="20"
                                preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24">
                                <path fill="currentColor"
                                    d="m17.75 4.09l-2.53 1.94l.91 3.06l-2.63-1.81l-2.63 1.81l.91-3.06l-2.53-1.94L12.44 4l1.06-3l1.06 3l3.19.09m3.5 6.91l-1.64 1.25l.59 1.98l-1.7-1.17l-1.7 1.17l.59-1.98L15.75 11l2.06-.05L18.5 9l.69 1.95l2.06.05m-2.28 4.95c.83-.08 1.72 1.1 1.19 1.85c-.32.45-.66.87-1.08 1.27C15.17 23 8.84 23 4.94 19.07c-3.91-3.9-3.91-10.24 0-14.14c.4-.4.82-.76 1.27-1.08c.75-.53 1.93.36 1.85 1.19c-.27 2.86.69 5.83 2.89 8.02a9.96 9.96 0 0 0 8.02 2.89m-1.64 2.02a12.08 12.08 0 0 1-7.8-3.47c-2.17-2.19-3.33-5-3.49-7.82c-2.81 3.14-2.7 7.96.31 10.98c3.02 3.01 7.84 3.12 10.98.31Z">
                                </path>
                            </svg>
                        </div>
                        <div class="sidebar-toggler x">
                            <a href="#" class="sidebar-hide d-xl-none d-block"><i class="bi bi-x bi-middle"></i></a>
                        </div>
                    </div>
                </div>
                <div class="sidebar-menu">
                    <ul class="menu">
                        <li class="sidebar-title">Menu</li>

                        <li class="sidebar-item active">
                            <a href="#" class="sidebar-link">
                                <i class="bi bi-grid-fill"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>

                        <li class="sidebar-title">Aplication</li>

                        <li class="sidebar-item">
                            <a href="../dashboard/roles/roles-index.php" class="sidebar-link">
                                <i class="bi bi-person-exclamation"></i>
                                <span>Roles</span>
                            </a>
                        </li>

                        <li class="sidebar-item">
                            <a href="../dashboard/users/users-index.php" class="sidebar-link">
                                <i class="bi bi-person-badge"></i>
                                <span>Users</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="../dashboard/categories/categories-index.php" class="sidebar-link">
                                <i class="bi bi-book"></i>
                                <span>Categories</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="../dashboard/books/books-index.php" class="sidebar-link">
                                <i class="bi bi-book-half"></i>
                                <span>Books</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="../dashboard/videos/videos-index.php" class="sidebar-link">
                                <i class="bi bi-camera-video"></i>
                                <span>Videos</span>
                            </a>
                        </li>

                        <li class="sidebar-title">Settings</li>

                        <li class="sidebar-item">
                            <a href="profile.php" class="sidebar-link">
                                <i class="bi bi-person-gear"></i>
                                <span>Profile</span>
                            </a>
                        </li>

                        <li class="sidebar-item">
                            <a href="change-password.php" class="sidebar-link">
                                <i class="bi bi-person-lock"></i>
                                <span>Change Password</span>
                            </a>
                        </li>

                        <li class="sidebar-item logout-btn">
                            <a href="#" class="sidebar-link logout-btn">
                                <i class="bi bi-door-open-fill logout-btn"></i>
                                <span class="logout-btn">logout</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div id="main" class="layout-navbar navbar-fixed">
            <header>
                <nav class="navbar navbar-expand navbar-light navbar-top">
                    <div class="container-fluid">
                        <a href="#" class="burger-btn d-block">
                            <i class="bi bi-justify fs-3"></i>
                        </a>

                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                            data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                            aria-expanded="false" aria-label="Toggle navigation">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="navbarSupportedContent">
                            <ul class="navbar-nav ms-auto mb-lg-0"></ul>
                            <div class="dropdown">
                                <a href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="user-menu d-flex">
                                        <div class="user-name text-end me-3">
                                            <h6 class="mb-0 text-gray-600 text-capitalize">
                                                <?= $name ?></h6>
                                            <p class="mb-0 text-sm text-gray-600 text-capitalize">
                                                <?= $role ?></p>
                                        </div>
                                        <div class="user-img d-flex align-items-center">
                                            <?php if ($photo) { ?>
                                            <div class="avatar avatar-md"
                                                style="width: 43px; height: 43px; overflow: hidden; border-radius: 50%;">
                                                <img src="<?= $photoPath ?>"
                                                    style="width: 100%; height: 100%; object-fit: cover;" alt="Avatar">
                                            </div>
                                            <?php } else { ?>
                                            <div class="avatar avatar-md">
                                                <img src="<?= $defaultPhoto ?>" alt="User Photo">
                                            </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </a>

                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton"
                                    style="min-width: 11rem">
                                    <li>
                                        <h6 class="dropdown-header text-capitalize">Hello, <?= $name ?></h6>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="../pages/index.php"><i
                                                class="icon-mid bi bi-house me-2"></i>
                                            Home</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="profile.php"><i
                                                class="icon-mid bi bi-person me-2"></i> My
                                            Profile</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="change-password.php"><i
                                                class="icon-mid bi bi-person-lock me-2"></i>
                                            Change Password</a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider" />
                                    </li>
                                    <li class="logout-btn">
                                        <a href="#" class="dropdown-item logout-btn">
                                            <i class="icon-mid bi bi-box-arrow-left me-2"></i> Logout
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>
            </header>
            <div id="main-content">
                <div class="page-heading">
                    <div class="page-title">
                        <div class="row">
                            <div class="col-12 col-md-6 order-md-1 order-last">
                                <h3>Dashboard</h3>
                                <p class="text-subtitle text-muted">
                                    All Statistic Aplication.
                                </p>
                            </div>
                            <div class="col-12 col-md-6 order-md-2 order-first">
                                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item">
                                            <a href="#">Dashboard</a>
                                        </li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="page-content">
                    <div class="row">
                        <div class="col-12 col-lg-9">
                            <div class="row">
                                <div class="col-6 col-lg-3 col-md-6">
                                    <div class="card">
                                        <div class="card-body px-4 py-4-5">
                                            <div class="row">
                                                <div
                                                    class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start ">
                                                    <div class="stats-icon purple mb-2">
                                                        <i class="iconly-boldProfile"></i>
                                                    </div>
                                                </div>
                                                <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                                    <h6 class="text-muted font-semibold">Users</h6>
                                                    <h6 class="font-extrabold mb-0"><?= $users_count ?></h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 col-lg-3 col-md-6">
                                    <div class="card">
                                        <div class="card-body px-4 py-4-5">
                                            <div class="row">
                                                <div
                                                    class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start ">
                                                    <div class="stats-icon blue mb-2">
                                                        <i class="iconly-boldBookmark"></i>
                                                    </div>
                                                </div>
                                                <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                                    <h6 class="text-muted font-semibold">Books</h6>
                                                    <h6 class="font-extrabold mb-0"><?= $books_count ?></h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 col-lg-3 col-md-6">
                                    <div class="card">
                                        <div class="card-body px-4 py-4-5">
                                            <div class="row">
                                                <div
                                                    class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start ">
                                                    <div class="stats-icon green mb-2">
                                                        <i class="iconly-boldVideo"></i>
                                                    </div>
                                                </div>
                                                <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                                    <h6 class="text-muted font-semibold">Videos</h6>
                                                    <h6 class="font-extrabold mb-0"><?= $videos_count ?></h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 col-lg-3 col-md-6">
                                    <div class="card">
                                        <div class="card-body px-4 py-4-5">
                                            <div class="row">
                                                <div
                                                    class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start ">
                                                    <div class="stats-icon red mb-2">
                                                        <i class="iconly-boldSetting"></i>
                                                    </div>
                                                </div>
                                                <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                                    <h6 class="text-muted font-semibold">Role</h6>
                                                    <h6 class="font-extrabold mb-0"><?= $roles_count ?></h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-header">
                                    <h4>Data Books</h4>
                                </div>
                                <div class="card-body">
                                    <div id="chart-profile-visit" style="min-height: 315px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-3">
                            <div class="card">
                                <div class="card-body py-4 px-4">
                                    <div class="d-flex align-items-center">
                                        <?php if ($photo) { ?>
                                        <div class="avatar avatar-xl">
                                            <img src="<?= $photoPath ?>"
                                                style="width: 100%; height: 100%; object-fit: cover;" alt="Avatar">
                                        </div>
                                        <?php } else { ?>
                                        <div class="avatar avatar-xl">
                                            <img src="<?= $defaultPhoto ?>" alt="User Photo">
                                        </div>
                                        <?php } ?>
                                        <div class="ms-3 name">
                                            <h5 class="font-bold text-capitalize"><?= $name ?></h5>
                                            <h6 class="text-muted mb-0 text-capitalize"><?= $role ?></h6>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h4>Users by Role</h4>
                                </div>
                                <div class="card-body">
                                    <div id="roles-donut-chart" style="min-height: 300px;"></div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <footer>
                <div class="footer clearfix mb-0 text-muted">
                    <div class="float-start">
                        <p>2023 &copy; Mazer</p>
                    </div>
                    <div class="float-end">
                        <p>
                            Crafted with
                            <span class="text-danger"><i class="bi bi-heart-fill icon-mid"></i></span>
                            by <a href="https://saugi.me">Saugi</a>
                        </p>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- script -->
    <script src="../assets/static/js/components/dark.js"></script>
    <script src="../assets/extensions/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="../assets/compiled/js/app.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

    <script>
    <?php if (isset($_SESSION["sweetalert"])): ?>
    Swal.fire({
        icon: 'success',
        title: 'Login Success!',
        text: 'You have successfully logged in.',
        confirmButtonText: 'OK'
    });
    <?php unset($_SESSION["sweetalert"]); // hapus setelah ditampilkan ?>
    <?php endif; ?>
    </script>

    <!-- logout -->
    <script>
    document.body.addEventListener('click', function(e) {
        const element = e.target.closest('.logout-btn'); // cari elemen logout-btn terdekat
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

    <!-- ApexCharts CDN -->
    <script src="../assets/extensions/apexcharts/apexcharts.min.js"></script>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var optionsBooksMonth = {
            annotations: {
                position: "back"
            },
            dataLabels: {
                enabled: false
            },
            chart: {
                type: "area",
                height: 300,
            },
            stroke: {
                curve: "smooth",
                width: 3
            },
            fill: {
                type: "gradient",
                gradient: {
                    shadeIntensity: 0.5,
                    opacityFrom: 0.7,
                    opacityTo: 0.3,
                    stops: [0, 90, 100],
                },
            },
            series: [{
                name: "Books per Month",
                data: <?= $totals_json ?>,
            }],
            xaxis: {
                categories: <?= $categories_json ?>,
                title: {
                    text: "Month"
                }
            },
        };

        var chartBooksMonth = new ApexCharts(
            document.querySelector("#chart-profile-visit"),
            optionsBooksMonth
        );

        chartBooksMonth.render();
    });
    </script>

    <script>
    document.addEventListener("DOMContentLoaded", function() {

        var optionsDonutRoles = {
            chart: {
                type: 'radialBar',
                height: 300
            },
            labels: <?= $roleLabels_json ?>,
            series: <?= $roleTotals_json ?>,
            legend: {
                position: 'bottom'
            },
            dataLabels: {
                enabled: true
            }
        };


        var donutChart = new ApexCharts(
            document.querySelector("#roles-donut-chart"),
            optionsDonutRoles
        );

        donutChart.render();

    });
    </script>


</body>

</html>