<?php

require '../../function.php';
require '../../check.php';

// Hapus data dulu kalau ada delete_id
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    deleteUser($id);
    header("Location: users-index.php");
    exit;
}

$name = htmlspecialchars($_SESSION['user']['name']);
$role = htmlspecialchars($_SESSION['user']['role']);

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: users-index.php");
    exit;
}

$id = $_GET['id'];
$user = detailUser($id);
$roles = select('SELECT * FROM roles');

$photo = $_SESSION['user']['photo'] ?? null;
$photoDetail = $user["photo"] ?? null;

// path foto default
$defaultPhoto = "../../assets/compiled/jpg/1.jpg";

$photoPath = (!empty($photo)) ? "../../uploads/" . htmlspecialchars($photo) : $defaultPhoto;
$photoPathDetail = (!empty($photoDetail)) ? "../../uploads/" . htmlspecialchars($photoDetail) : $defaultPhoto;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>User Detail | Dashboard</title>
    <link rel="stylesheet" href="../../assets/extensions/choices.js/public/assets/styles/choices.css">
    <link rel="shortcut icon" href="../../assets/compiled/svg/favicon.svg" type="image/x-icon" />
    <link rel="stylesheet" href="../../assets/compiled/css/app.css" />
    <link rel="stylesheet" href="../../assets/compiled/css/app-dark.css" />

    <!-- datatables -->
    <link rel="stylesheet" href="../../assets/extensions/datatables.net-bs5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" crossorigin="" href="../../assets/compiled/css/table-datatable-jquery.css">
    <link rel="stylesheet" href="../../assets/extensions/iziToast/css/iziToast.min.css">
</head>

<body>
    <script src="../../assets/static/js/initTheme.js"></script>
    <div id="app">
        <div id="sidebar">
            <div class="sidebar-wrapper active">
                <div class="sidebar-header position-relative">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="logo">
                            <a href="../../pages/index.php"><img src="../../assets/compiled/svg/logo.svg" alt="Logo"
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

                        <li class="sidebar-item">
                            <a href="../dashboard.php" class="sidebar-link">
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

                        <li class="sidebar-item active">
                            <a href="users-index.php" class="sidebar-link">
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
                            <a href="profile.php" class="sidebar-link">
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
                                        <a class="dropdown-item" href="../../pages/index.php"><i
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
                                <h3 class="text-capitalize">Detail data <?= $user["name"] ?></h3>
                                <p class="text-subtitle text-muted">
                                    View detail data user
                                </p>
                            </div>
                            <div class="col-12 col-md-6 order-md-2 order-first">
                                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                                    <ol class="breadcrumb mb-2">
                                        <li class="breadcrumb-item">
                                            <a href="../dashboard.php">Dashboard</a>
                                        </li>
                                        <li class="breadcrumb-item">
                                            <a href="users-index.php">Users</a>
                                        </li>
                                        <li class="breadcrumb-item">
                                            <a href="#">Users Detail</a>
                                        </li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <section class="section">
                        <div class="row">
                            <div class="col-12 col-lg-4">
                                <div class="card">
                                    <a href="javascript:history.back()"
                                        class="p-1 rounded-circle ms-2 mt-2 bg-secondary d-flex justify-content-center align-items-center position-relative"
                                        style="width: 25px; height: 25px;">
                                        <i class="bi bi-arrow-left text-white pb-2 position-absolute"
                                            style="top: 0px;"></i>
                                    </a>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-center align-items-center flex-column">
                                            <div class="avatar"
                                                style="width: 180px; height: 180px; overflow: hidden; border-radius: 50%;">
                                                <img src="<?= $photoPathDetail ?>"
                                                    style="width: 100%; height: 100%; object-fit: cover;" alt="Avatar">
                                            </div>

                                            <h3 class="mt-3"><?= $user["name"] ?></h3>
                                            <p class="text-small text-capitalize"><?= $user["role_name"] ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-8">
                                <div class="card">
                                    <div class="card-body">
                                        <form action="" method="" enctype="multipart/form-data">
                                            <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                                                <div class="row m-2">
                                                    <div class="col-12">
                                                        <div
                                                            class="form-group mandatory position-relative has-icon-left">
                                                            <label for="user_id" class="form-label">User ID</label>
                                                            <input type="number" id="user_id" name="user_id"
                                                                class="form-control form-control-lg <?= isset($_SESSION["errors"]["user_id"]) ? 'is-invalid' : '' ?>"
                                                                placeholder="e.g 241730042"
                                                                value="<?= $user["user_id"] ?>" readonly>
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
                                                    <div class="col-md-6 col-12">
                                                        <div
                                                            class="form-group mandatory position-relative has-icon-left">
                                                            <label for="name" class="form-label">Name</label>
                                                            <input type="text" id="name" name="name"
                                                                class="form-control form-control-lg <?= isset($_SESSION["errors"]["name"]) ? 'is-invalid' : '' ?>"
                                                                placeholder="e.g Fadli Hifziansyah"
                                                                value="<?= $user["name"] ?>" readonly>
                                                            <div class="form-control-icon" style="top: 38px">
                                                                <i class="bi bi-person"></i>
                                                            </div>
                                                            <?php if (isset($_SESSION["errors"]["name"])): ?>
                                                            <div class="invalid-feedback">
                                                                <?= $_SESSION["errors"]["name"]; ?>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 col-12">
                                                        <div
                                                            class="form-group mandatory position-relative has-icon-left">
                                                            <label for="email" class="form-label">Email</label>
                                                            <input type="email" id="email" name="email"
                                                                class="form-control form-control-lg <?= isset($_SESSION["errors"]["email"]) ? 'is-invalid' : '' ?>"
                                                                placeholder="e.g fadlihifziansyah153@gmail.com"
                                                                value="<?= $user["email"] ?>" readonly>
                                                            <div class="form-control-icon" style="top: 38px">
                                                                <i class="bi bi-envelope"></i>
                                                            </div>
                                                            <?php if (isset($_SESSION["errors"]["email"])): ?>
                                                            <div class="invalid-feedback">
                                                                <?= $_SESSION["errors"]["email"]; ?>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class=" col-12">
                                                        <div
                                                            class="form-group mandatory position-relative has-icon-left">
                                                            <label for="phone" class="form-label">Phone</label>
                                                            <input type="text" id="phone" name="phone"
                                                                class="form-control form-control-lg <?= isset($_SESSION["errors"]["phone"]) ? 'is-invalid' : '' ?>"
                                                                placeholder="e.g 0878 2738 2281"
                                                                value="<?= $user["phone"] ?>" readonly>
                                                            <div class="form-control-icon" style="top: 38px">
                                                                <i class="bi bi-telephone"></i>
                                                            </div>
                                                            <?php if (isset($_SESSION["errors"]["phone"])): ?>
                                                            <div class="invalid-feedback">
                                                                <?= $_SESSION["errors"]["phone"]; ?>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 col-12">
                                                        <div
                                                            class="form-group mandatory position-relative has-icon-left">
                                                            <label for="status" class="form-label">Status</label>
                                                            <select id="status"
                                                                class="choices form-control form-select <?= isset($_SESSION["errors"]["status"]) ? 'is-invalid' : '' ?>"
                                                                name="status" disabled>
                                                                <option value="">-- Select Status --</option>
                                                                <option value="active"
                                                                    <?= (($user["status"] ?? '') === 'active') ? 'selected' : '' ?>>
                                                                    Active
                                                                </option>
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
                                                    <div class="col-md-6 col-12">
                                                        <div
                                                            class="form-group mandatory position-relative has-icon-left">
                                                            <label for="role" class="form-label">Role</label>
                                                            <select id="role" name="role_id" disabled
                                                                class="choices form-control form-select <?= isset($_SESSION["errors"]["role_id"]) ? 'is-invalid' : '' ?>">
                                                                <option value="">-- Select Role --</option>
                                                                <?php foreach ($roles as $role): ?>
                                                                <option value="<?= $role["role_id"] ?>"
                                                                    class="text-capitalize "
                                                                    <?= (($user['role_id'] ?? '') == $role["role_id"]) ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($role["role_name"]) ?>
                                                                </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                            <?php if (isset($_SESSION["errors"]["role_id"])): ?>
                                                            <div class="invalid-feedback">
                                                                <?= $_SESSION["errors"]["role_id"]; ?>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer mx-2 mt-2">
                                                <a href="javascript:history.back()" type="button" name="add-user"
                                                    class="btn btn-secondary me-2 text-light" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" data-bs-original-title="Back to List">
                                                    <i class="bx bx-check d-block d-sm-none"></i>
                                                    <span class="d-none d-sm-block"><i
                                                            class="bi bi-arrow-90deg-left"></i>
                                                        back</span>
                                                </a>
                                                <a href="?delete_id=<?= $user['user_id']; ?>"
                                                    class="btn btn-danger me-2 text-white delete-btn"
                                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                                    data-bs-original-title="Delete User">
                                                    <i class="bi bi-trash text-white "></i>
                                                    <span class="text-white delete-btn" style="top: 9px">Delete</span>
                                                </a>
                                                <a href="user-edit.php?id=<?= $user['user_id']; ?>"
                                                    class="btn btn-success me-2 text-white" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" data-bs-original-title="Edit User">
                                                    <i class="bx bx-check d-block text-white d-sm-none"></i>
                                                    <span class="d-none d-sm-block"><i class="bi bi-pencil-square"></i>
                                                        Edit</span>
                                                </a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
            <footer <div class="footer clearfix mb-0 text-muted">
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

        <!-- jquery -->
        <script src="../../assets/extensions/jquery/jquery.min.js"></script>

        <!-- script -->
        <script src="../../assets/static/js/components/dark.js"></script>
        <script src="../../assets/extensions/perfect-scrollbar/perfect-scrollbar.min.js"></script>
        <script src="../../assets/compiled/js/app.js"></script>

        <!-- choices -->
        <script src="../../assets/extensions/choices.js/public/assets/scripts/choices.js"></script>

        <!-- sweetalert -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

        <!-- format phone -->
        <script src="../../assets/static/js/phoneFormat.js"></script>

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
                    window.location.href = "../../pages/logout.php";
                }
            });
        });
        </script>

        <!-- Delete -->
        <script>
        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('.delete-btn');
            if (!btn) return;
            e.preventDefault();

            // Cari elemen <a> terdekat
            const link = btn.closest('a');
            if (!link) return;

            const deleteUrl = link.getAttribute('href'); // Ambil URL delete

            Swal.fire({
                title: "Sure Wanna delete?",
                text: "Data user will be deleted permanently.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, Delete",
                cancelButtonText: "Reject"
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = deleteUrl;
                }
            });
        });
        </script>

        <!-- choice -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            new Choices('#role', {
                searchEnabled: true,
                itemSelectText: '',
            });
        });
        document.addEventListener('DOMContentLoaded', function() {
            new Choices('#status', {
                searchEnabled: true,
                itemSelectText: '',
            });
        });
        </script>

        <!-- preview img -->
        <script>
        const photoInput = document.getElementById('photo');
        const previewContainer = document.getElementById('photoPreviewContainer');
        const previewImg = document.getElementById('photoPreviewImg');
        const closeBtn = document.getElementById('closePreviewBtn');

        // tombol close preview
        closeBtn.addEventListener('click', function() {
            previewImg.src = '#';
            previewContainer.style.display = 'none';
            photoInput.value = ''; // kosongkan input file juga
        });

        // tombol reset form (jika ada)
        document.querySelector('button[type="reset"]')?.addEventListener('click', function() {
            previewImg.src = '#';
            previewContainer.style.display = 'none';
            photoInput.value = '';
        });
        </script>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })
        }, false);
        </script>

</body>

</html>