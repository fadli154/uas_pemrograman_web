<?php

require '../../function.php';
require '../../check.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $videoId = $_POST['video_id_old'];
    if (updateVideo($videoId, $_POST, $_FILES)) {
        unset($_SESSION["errors"]);
        $_SESSION["success"] = "Successfully updated video";
        header("Location: videos-index.php");
        exit;
    }
}

// Hapus data dulu kalau ada delete_id
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    deleteVideo($id);
    header("Location: videos-index.php");
    exit;
}

$name = htmlspecialchars($_SESSION['user']['name']);
$role = htmlspecialchars($_SESSION['user']['role']);

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: videos-index.php");
    exit;
}

$id = $_GET['id'];
$video = detailVideo($id);
$roles = select('SELECT * FROM roles');

$categories = select('SELECT * FROM categories');

// Ambil daftar ID kategori yang sudah dimiliki buku
$selectedCategories = array_column($video['categories'], 'category_id');

// Foto user
$photo = $_SESSION['user']['photo'] ?? null;
$photoDetail = $video["thumbnail_url"] ?? null;

// Path foto default
$defaultPhoto = "../../assets/compiled/jpg/video_placeholder.png";
$photoPath = !empty($photo) ? "../../uploads/" . htmlspecialchars($photo) : $defaultPhoto;
$photoPathEdit = !empty($photoDetail) ? "../../thumbnail/" . htmlspecialchars($photoDetail) : $defaultPhoto;


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Video Edit | Dashboard</title>
    <link rel="stylesheet" href="../../assets/extensions/choices.js/public/assets/styles/choices.css">
    <link rel="shortcut icon" href="../../assets/compiled/svg/favicon.svg" type="image/x-icon" />
    <link rel="stylesheet" href="../../assets/compiled/css/app.css" />
    <link rel="stylesheet" href="../../assets/compiled/css/app-dark.css" />
    <link rel="stylesheet" href="../../assets/extensions/iziToast/css/iziToast.min.css">
    <link rel="stylesheet" href="../../assets/extensions/quill/quill.snow.css">
    <link rel="stylesheet" href="../../assets/extensions/quill/quill.bubble.css">

    <!-- datatables -->
    <link rel="stylesheet" href="../../assets/extensions/datatables.net-bs5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" crossorigin="" href="../../assets/compiled/css/table-datatable-jquery.css">
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
                            <a href="../roles/roles-index.php" class="sidebar-link">
                                <i class="bi bi-person-exclamation"></i>
                                <span>Roles</span>
                            </a>
                        </li>

                        <li class="sidebar-item">
                            <a href="../users/users-index.php" class="sidebar-link">
                                <i class="bi bi-person-badge"></i>
                                <span>Users</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="../categories/categories-index.php" class="sidebar-link">
                                <i class="bi bi-book"></i>
                                <span>Categories</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="../books/books-index.php" class="sidebar-link">
                                <i class="bi bi-book-half"></i>
                                <span>Books</span>
                            </a>
                        </li>
                        <li class="sidebar-item active">
                            <a href="../videos/videos-index.php" class="sidebar-link">
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
                                <h3 class="text-capitalize">Edit data <?= $video["title"] ?></h3>
                                <p class="text-subtitle text-muted">
                                    View edit data video
                                </p>
                            </div>
                            <div class="col-12 col-md-6 order-md-2 order-first">
                                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                                    <ol class="breadcrumb mb-2">
                                        <li class="breadcrumb-item">
                                            <a href="../dashboard.php">Dashboard</a>
                                        </li>
                                        <li class="breadcrumb-item">
                                            <a href="video-index.php">Video</a>
                                        </li>
                                        <li class="breadcrumb-item">
                                            <a href="#">Video Edit</a>
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
                                            <!-- Avatar asli -->
                                            <div class="rounded-3" id="originalPhoto"
                                                style="width: 250px; height: 200px; overflow: hidden;">
                                                <img src="<?= $photoPathEdit ?>"
                                                    style="width: 100%; height: 100%; object-fit: cover;" alt="Avatar">
                                            </div>

                                            <!-- Preview container -->
                                            <div class="position-relative rounded-3"
                                                style="width: 250px; height: 250px; overflow: hidden; display: none;"
                                                id="photoPreviewContainer">
                                                <img src="" id="photoPreviewImg"
                                                    style="width: 100%; height: 100%; object-fit: cover;" alt="Avatar">

                                                <!-- Tombol close -->
                                                <button type="button" id="closePreviewBtn" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" data-bs-original-title="Close Preview"
                                                    style="position: absolute; top: 0px; right: 0px; background: rgba(0,0,0,0.5); color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; z-index: 10;">
                                                    ×
                                                </button>
                                            </div>

                                            <h3 class="mt-3"><?= $video["title"] ?></h3>
                                            <p class="text-small text-capitalize"><?= $video["duration"] ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-8">
                                <div class="card">
                                    <div class="card-body">
                                        <form action="" method="POST" enctype="multipart/form-data">
                                            <input type="text" name="video_id_old" hidden
                                                value="<?= $video["video_id"] ?>">
                                            <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                                                <div class="row m-2">
                                                    <div class="col-12">
                                                        <div
                                                            class="form-group mandatory position-relative has-icon-left">
                                                            <label for="video_id" class="form-label">Video ID</label>
                                                            <input type="text" id="video_id" name="video_id"
                                                                class="form-control form-control-lg <?= isset($_SESSION["errors"]["video_id"]) ? 'is-invalid' : '' ?>"
                                                                placeholder="e.g VIDEO001"
                                                                value="<?= $video["video_id"] ?>" required>
                                                            <div class="form-control-icon" style="top: 38px">
                                                                <i class="bi bi-hash"></i>
                                                            </div>
                                                            <?php if (isset($_SESSION["errors"]["video_id"])): ?>
                                                            <div class="invalid-feedback">
                                                                <?= $_SESSION["errors"]["video_id"]; ?>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 col-12">
                                                        <div
                                                            class="form-group mandatory position-relative has-icon-left">
                                                            <label for="title" class="form-label">Title</label>
                                                            <input type="text" id="title" name="title"
                                                                class="form-control form-control-lg <?= isset($_SESSION["errors"]["title"]) ? 'is-invalid' : '' ?>"
                                                                placeholder="e.g Dilan 1990"
                                                                value="<?= $video["title"] ?>" required>
                                                            <div class="form-control-icon" style="top: 38px">
                                                                <i class="bi bi-camera-video"></i>
                                                            </div>
                                                            <?php if (isset($_SESSION["errors"]["title"])): ?>
                                                            <div class="invalid-feedback">
                                                                <?= $_SESSION["errors"]["title"]; ?>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 col-12">
                                                        <div
                                                            class="form-group mandatory position-relative has-icon-left">
                                                            <label for="duration" class="form-label">Duration</label>
                                                            <input type="time" id="duration" name="duration"
                                                                class="form-control form-control-lg <?= isset($_SESSION["errors"]["duration"]) ? 'is-invalid' : '' ?>"
                                                                placeholder="e.g fadlihifziansyah153@gmail.com"
                                                                value="<?= $video["duration"] ?>" required>
                                                            <div class="form-control-icon" style="top: 38px">
                                                                <i class="bi bi-clock"></i>
                                                            </div>
                                                            <?php if (isset($_SESSION["errors"]["duration"])): ?>
                                                            <div class="invalid-feedback">
                                                                <?= $_SESSION["errors"]["duration"]; ?>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <div
                                                            class="form-group mandatory position-relative has-icon-left">
                                                            <label for="youtube_url" class="form-label">Youtube
                                                                url</label>
                                                            <input type="text" id="youtube_url" name="youtube_url"
                                                                class="form-control form-control-lg <?= isset($_SESSION["errors"]["youtube_url"]) ? 'is-invalid' : '' ?>"
                                                                placeholder="e.g fadlihifziansyah153@gmail.com"
                                                                value="<?= $video["youtube_url"] ?>" required>
                                                            <div class="form-control-icon" style="top: 38px">
                                                                <i class="bi bi-link-45deg"></i>
                                                            </div>
                                                            <?php if (isset($_SESSION["errors"]["youtube_url"])): ?>
                                                            <div class="invalid-feedback">
                                                                <?= $_SESSION["errors"]["youtube_url"]; ?>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <div
                                                            class="form-group mandatory position-relative has-icon-left">
                                                            <label for="description"
                                                                class="form-label">Description</label>

                                                            <!-- Elemen tempat Quill muncul -->
                                                            <div id="snow" class="ql-container ql-snow">
                                                                <?= $video["description"] ?>
                                                            </div>

                                                            <!-- Hidden input untuk kirim isi editor -->
                                                            <input type="hidden" name="description" id="description">

                                                            <?php if (isset($_SESSION["errors"]["description"])): ?>
                                                            <div class="invalid-feedback">
                                                                <?= $_SESSION["errors"]["description"]; ?>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <div
                                                            class="form-group mandatory position-relative has-icon-left">
                                                            <label for="categories" class="form-label">Categories
                                                                Video</label>
                                                            <select id="categories" name="categories[]"
                                                                multiple="multiple"
                                                                class="form-select multiple-remove choices" required>
                                                                <?php foreach ($categories as $category): ?>
                                                                <option value="<?= $category["category_id"] ?>"
                                                                    <?= in_array($category["category_id"], $selectedCategories) ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($category["category_name"]) ?>
                                                                </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 mb-1">
                                                        <fieldset>
                                                            <label class="mb-1" for="thumbnail_url">Thumbnail</label>
                                                            <div class="input-group">
                                                                <input type="file" class="form-control"
                                                                    id="thumbnail_url" name="thumbnail_url"
                                                                    accept="image/*">
                                                                <button class="btn btn-primary z-0" type="button"
                                                                    id="inputGroupFileAddon04">Upload</button>
                                                            </div>
                                                        </fieldset>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer mx-2 mt-2">
                                                <a href="javascript:history.back()" type="button" name="add-video"
                                                    class="btn btn-secondary me-2 text-light" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" data-bs-original-title="Back">
                                                    <i class="bx bx-check d-block d-sm-none"></i>
                                                    <span class="d-none d-sm-block"><i
                                                            class="bi bi-arrow-90deg-left"></i>
                                                        back</span>
                                                </a>
                                                <a href="?delete_id=<?= $video['video_id']; ?>"
                                                    class="btn btn-danger me-2 text-white delete-btn"
                                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                                    data-bs-original-title="Delete Video">
                                                    <i class="bi bi-trash text-white "></i>
                                                    <span class="text-white delete-btn" style="top: 9px">Delete</span>
                                                </a>
                                                <a href="video-detail.php?id=<?= $video['video_id']; ?>"
                                                    class="btn btn-info me-2 text-white" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" data-bs-original-title="Detail Video">
                                                    <i class="bx bx-check d-block text-white d-sm-none"></i>
                                                    <span class="d-none d-sm-block"><i class="bi bi-eye"></i>
                                                        Detail</span>
                                                </a>
                                                <button type="submit" class="btn btn-primary me-2 text-white"
                                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                                    data-bs-original-title="Edit Video">
                                                    <i class="bx bx-check d-block text-white d-sm-none"></i>
                                                    <span class="d-none d-sm-block"><i class="bi bi-check-circle"></i>
                                                        Edit</span>
                                                </button>
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

        <!-- izitoast -->
        <script src="../../assets/extensions/iziToast/js/iziToast.min.js"></script>

        <!-- choices -->
        <script src="../../assets/extensions/choices.js/public/assets/scripts/choices.js"></script>

        <!-- sweetalert -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

        <!-- format phone -->
        <script src="../../assets/static/js/phoneFormat.js"></script>

        <script src="../../assets/extensions/quill/quill.min.js"></script>
        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

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
                text: "Data video will be deleted permanently.",
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
            new Choices('#categories', {
                removeItemButton: true,
                placeholder: true,
            });
        });
        </script>

        <!-- Quill -->
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Inisialisasi Quill
            var quill = new Quill('#snow', {
                theme: 'snow',
                placeholder: 'write here...',
                modules: {
                    toolbar: [
                        [{
                            'header': [1, 2, false]
                        }],
                        ['bold', 'italic', 'underline'],
                        ['blockquote', 'code-block'],
                        [{
                            'list': 'ordered'
                        }, {
                            'list': 'bullet'
                        }]
                    ]
                }
            });

            // Ambil input hidden
            var inputDescription = document.getElementById('description');

            // Update isi input hidden setiap ada perubahan
            quill.on('text-change', function() {
                inputDescription.value = quill.root.innerHTML;
            });

            // Jika form diisi ulang (misalnya saat edit data), isi ulang editor
            if (inputDescription.value) {
                quill.root.innerHTML = inputDescription.value;
            }
        });
        </script>


        <!-- preview img -->
        <script>
        const photoInput = document.getElementById('thumbnail_url');
        const previewContainer = document.getElementById('photoPreviewContainer');
        const previewImg = document.getElementById('photoPreviewImg');
        const closeBtn = document.getElementById('closePreviewBtn');
        const originalPhoto = document.getElementById('originalPhoto');

        // tampilkan preview saat file dipilih
        photoInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewContainer.style.display = 'inline-block';
                    originalPhoto.style.display = 'none';
                };
                reader.readAsDataURL(file);
            } else {
                previewContainer.style.display = 'none';
                originalPhoto.style.display = 'inline-block';
            }
        });

        // tombol close preview
        closeBtn.addEventListener('click', function() {
            previewImg.src = '';
            previewContainer.style.display = 'none';
            originalPhoto.style.display = 'inline-block';
            photoInput.value = ''; // kosongkan input file juga
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