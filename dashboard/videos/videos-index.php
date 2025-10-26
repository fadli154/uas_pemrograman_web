<?php

require '../../function.php';
require '../../check.php';

$name = htmlspecialchars($_SESSION['user']['name']);
$role = htmlspecialchars($_SESSION['user']['role']);

$videos = select('SELECT * FROM videos');
$categories = select('SELECT * FROM categories');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (insertVideo($_POST, $_FILES)) {
        unset($_SESSION["errors"]);
        $_SESSION["success"] = "Successfully added new video"; 
        header("Location: videos-index.php");
        exit;
    }
}

// Jika ada parameter delete_id di URL
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    deleteVideo($id); // panggil fungsi hapus
    header("Location: videos-index.php"); // redirect supaya URL bersih
    exit;
}

$photo = $_SESSION['user']['photo'] ?? null;

// path foto default
$defaultPhoto = "../../assets/compiled/jpg/1.jpg";

$photoPath = (!empty($photo)) ? "../../uploads/" . htmlspecialchars($photo) : $defaultPhoto;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Videos Index - Dashboard</title>
    <link rel="stylesheet" href="../../assets/extensions/choices.js/public/assets/styles/choices.css">
    <link rel="shortcut icon" href="../../assets/compiled/svg/favicon.svg" type="image/x-icon" />
    <link rel="stylesheet" href="../../assets/compiled/css/app.css" />
    <link rel="stylesheet" href="../../assets/compiled/css/app-dark.css" />

    <!-- datatables -->
    <link rel="stylesheet" href="../../assets/extensions/datatables.net-bs5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" crossorigin="" href="../../assets/compiled/css/table-datatable-jquery.css">
    <link rel="stylesheet" href="../../assets/extensions/iziToast/css/iziToast.min.css">
    <link rel="stylesheet" href="../../assets/extensions/quill/quill.snow.css">
    <link rel="stylesheet" href="../../assets/extensions/quill/quill.bubble.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
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

                        <li class="sidebar-item ">
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
                        <li class="sidebar-item ">
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
                                <h3>Data Videos</h3>
                                <p class="text-subtitle text-muted">
                                    Manage and view all registered videos.
                                </p>
                            </div>
                            <div class="col-12 col-md-6 order-md-2 order-first">
                                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                                    <ol class="breadcrumb mb-2">
                                        <li class="breadcrumb-item">
                                            <a href="../dashboard.php">Dashboard</a>
                                        </li>
                                        <li class="breadcrumb-item">
                                            <a href="#">Videos</a>
                                        </li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <section class="section position-relative">
                        <button type="button" class="btn btn-success rounded-top-pill position-absolute"
                            data-bs-toggle="modal" data-bs-target="#modalAddVideo" data-bs-toggle="tooltip"
                            data-bs-placement="top" data-bs-original-title="Add Video"
                            style="top:-32px; right: 0px; height: 40px;">
                            <i class="bi bi-plus-circle-dotted"></i>
                        </button>
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped" id="videoTable">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($videos as $video): ?>
                                            <tr>
                                                <td class="text-capitalize"><?= $video['title'] ?></td>
                                                <td>
                                                    <div class="btn-group mb-1">
                                                        <div class="dropdown">
                                                            <button class="btn btn-primary dropdown-toggle me-1"
                                                                type="button" id="dropdownMenuButton"
                                                                data-bs-toggle="dropdown" aria-haspopup="true"
                                                                aria-expanded="true">
                                                                Action
                                                            </button>
                                                            <div class="dropdown-menu"
                                                                aria-labelledby="dropdownMenuButton"
                                                                style="position: absolute; inset: 0px auto auto 0px; margin: 0px; transform: translate3d(0px, 40px, 0px);"
                                                                data-popper-placement="bottom-start">
                                                                <a href="video-detail.php?id=<?= $video['video_id'] ?>"
                                                                    class="dropdown-item position-relative btn-detail-video">
                                                                    <i class="bi bi-eye me-2 text-primary"></i>
                                                                    <span class="position-absolute text-primary"
                                                                        style="top: 9px">Detail</span>
                                                                </a>
                                                                <a href="video-edit.php?id=<?= $video['video_id'] ?>"
                                                                    class="dropdown-item position-relative btn-edit-video">
                                                                    <i
                                                                        class="bi bi-pencil-square me-2 text-success"></i>
                                                                    <span class="position-absolute text-success"
                                                                        style="top: 9px">Edit</span>
                                                                </a>
                                                                <a href="?delete_id=<?= $video['video_id']; ?>"
                                                                    class="dropdown-item position-relative delete-btn">
                                                                    <i class="bi bi-trash text-danger me-2"></i>
                                                                    <span class="position-absolute text-danger"
                                                                        style="top: 9px">Delete</span>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
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

        <!-- modal tambah data -->
        <div class="modal fade text-left" id="modalAddVideo" tabindex="-1" aria-labelledby="myModalLabel33"
            style="display: none;" aria-hidden="true" role="dialog" aria-modal="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header mx-2">
                        <h4 class="modal-title" id="myModalLabel33">Add Data Videos</h4>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="feather feather-x">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                            <div class="row m-2">

                                <!-- Video ID -->
                                <div class="col-12">
                                    <div class="form-group mandatory position-relative has-icon-left">
                                        <label for="video_id" class="form-label">Video ID</label>
                                        <input type="text" id="video_id" name="video_id"
                                            class="form-control form-control-lg <?= isset($_SESSION["errors"]["video_id"]) ? 'is-invalid' : '' ?>"
                                            placeholder="e.g VID001" value="<?= $_POST['video_id'] ?? '' ?>" required>
                                        <div class="form-control-icon" style="top: 38px">
                                            <i class="bi bi-hash"></i>
                                        </div>
                                        <?php if (isset($_SESSION["errors"]["video_id"])): ?>
                                        <div class="invalid-feedback"><?= $_SESSION["errors"]["video_id"]; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Title -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group mandatory position-relative has-icon-left">
                                        <label for="title" class="form-label">Title</label>
                                        <input type="text" id="title" name="title"
                                            class="form-control form-control-lg <?= isset($_SESSION["errors"]["title"]) ? 'is-invalid' : '' ?>"
                                            placeholder="e.g Tutorial HTML Dasar" value="<?= $_POST['title'] ?? '' ?>"
                                            required>
                                        <div class="form-control-icon" style="top: 38px">
                                            <i class="bi bi-camera-video"></i>
                                        </div>
                                        <?php if (isset($_SESSION["errors"]["title"])): ?>
                                        <div class="invalid-feedback"><?= $_SESSION["errors"]["title"]; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Duration -->
                                <div class="col-md-6 col-12">
                                    <div class="form-group mandatory position-relative has-icon-left">
                                        <label for="duration" class="form-label">Duration (mm:ss)</label>
                                        <input type="time" id="duration" name="duration"
                                            class="form-control form-control-lg <?= isset($_SESSION["errors"]["duration"]) ? 'is-invalid' : '' ?>"
                                            placeholder="e.g 12:34" value="<?= $_POST['duration'] ?? '' ?>" required>
                                        <div class="form-control-icon" style="top: 38px">
                                            <i class="bi bi-clock"></i>
                                        </div>
                                        <?php if (isset($_SESSION["errors"]["duration"])): ?>
                                        <div class="invalid-feedback"><?= $_SESSION["errors"]["duration"]; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- YouTube URL -->
                                <div class="col-12">
                                    <div class="form-group mandatory position-relative has-icon-left">
                                        <label for="youtube_url" class="form-label">YouTube URL</label>
                                        <input type="url" id="youtube_url" name="youtube_url"
                                            class="form-control form-control-lg <?= isset($_SESSION["errors"]["youtube_url"]) ? 'is-invalid' : '' ?>"
                                            placeholder="e.g https://www.youtube.com/watch?v=abcd1234"
                                            value="<?= $_POST['youtube_url'] ?? '' ?>" required>
                                        <div class="form-control-icon" style="top: 38px">
                                            <i class="bi bi-link-45deg"></i>
                                        </div>
                                        <?php if (isset($_SESSION["errors"]["youtube_url"])): ?>
                                        <div class="invalid-feedback"><?= $_SESSION["errors"]["youtube_url"]; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Category -->
                                <div class="col-12">
                                    <div class="form-group mandatory position-relative has-icon-left">
                                        <label for="categories" class="form-label">Categories video</label>
                                        <select id="categories" name="categories[]" multiple="multiple" required
                                            class="form-select multiple-remove choices <?= isset($_SESSION["errors"]["category_id"]) ? 'is-invalid' : '' ?>">
                                            <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category["category_id"] ?>"
                                                <?= (isset($_POST["categories"]) && in_array($category["category_id"], $_POST["categories"])) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($category["category_name"]) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (isset($_SESSION["errors"]["category_id"])): ?>
                                        <div class="invalid-feedback">
                                            <?= $_SESSION["errors"]["category_id"]; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Description -->
                                <div class="col-12">
                                    <div class="form-group mandatory position-relative has-icon-left">
                                        <label for="description" class="form-label">Description</label>

                                        <!-- Elemen tempat Quill muncul -->
                                        <div id="snow" class="ql-container ql-snow"></div>

                                        <!-- Hidden input untuk kirim isi editor -->
                                        <input type="hidden" name="description" id="description">

                                        <?php if (isset($_SESSION["errors"]["description"])): ?>
                                        <div class="invalid-feedback">
                                            <?= $_SESSION["errors"]["description"]; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Thumbnail Upload -->
                                <div class="col-12 mb-1">
                                    <fieldset>
                                        <label class="mb-1" for="thumbnail_url">Thumbnail</label>
                                        <div class="input-group">
                                            <input type="file" class="form-control" id="thumbnail_url"
                                                name="thumbnail_url" accept="image/*">
                                            <button class="btn btn-primary z-0" type="button"
                                                id="uploadThumbnail">Upload</button>
                                        </div>

                                        <!-- Preview -->
                                        <div class="mt-3 text-center position-relative" id="photoPreviewContainer"
                                            style="display: none;">
                                            <button type="button" class="position-absolute btn btn-danger btn-sm"
                                                id="closePreviewBtn"
                                                style="top: -12px; right: -12px; border-radius: 50%; width: 2px 2px;">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                            <img id="photoPreviewImg" src="#" alt="Preview foto"
                                                style="max-height: 120px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                                        </div>
                                    </fieldset>
                                </div>

                            </div>
                        </div>
                        <div class="modal-footer mx-2">
                            <button type="button" name="add-user" class="btn btn-danger" data-bs-toggle="tooltip"
                                data-bs-placement="top" data-bs-original-title="Close" data-bs-dismiss="modal">
                                <i class="bx bx-check d-block d-sm-none"></i>
                                <span class="d-none d-sm-block"><i class="bi bi-x-circle"></i></span>
                            </button>
                            <button type="submit" name="add-user" class="btn btn-primary" data-bs-toggle="tooltip"
                                data-bs-placement="top" data-bs-original-title="Insert">
                                <i class="bx bx-check d-block d-sm-none"></i>
                                <span class="d-none d-sm-block"><i class="bi bi-check-circle"></i></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- jquery -->
    <script src="../../assets/extensions/jquery/jquery.min.js"></script>

    <!-- script -->
    <script src="../../assets/static/js/components/dark.js"></script>
    <script src="../../assets/extensions/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="../../assets/compiled/js/app.js"></script>

    <!-- choices -->
    <script src="../../assets/extensions/choices.js/public/assets/scripts/choices.js"></script>

    <!-- datatables -->
    <script src="../../assets/extensions/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="../../assets/extensions/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    <script src="../../assets/static/js/pages/datatables.js"></script>

    <!-- sweetalert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

    <!-- format phone -->
    <script src="../../assets/static/js/phoneFormat.js"></script>

    <!-- parsley -->
    <script src="../../assets/extensions/parsleyjs/parsley.min.js"></script>
    <script src="../../assets/static/js/pages/parsley.js"></script>

    <!-- izitoast -->
    <script src="../../assets/extensions/iziToast/js/iziToast.min.js"></script>

    <!-- quil -->
    <script src="../../assets/extensions/quill/quill.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <!-- iziToast -->
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
        const element = e.target.closest('.delete-btn');
        if (!element) return;
        e.preventDefault();

        const deleteUrl = element.getAttribute('href'); // ambil href dengan ?delete_id=...

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
                window.location.href = deleteUrl; // jalankan delete langsung di videos-index.php
            }
        });
    });
    </script>


    <!-- datatables -->
    <script>
    $(document).ready(function() {
        $('#videoTable').DataTable({
            pageLength: 5,
            lengthMenu: [5, 10, 25, 50],
            language: {
                search: "Search videos:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ videos"
            },
            responsive: true, // otomatis responsif di semua device
        });
    });
    </script>

    <!-- choice -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        new Choices('#categories', {
            removeItemButton: true,
            placeholder: true,
            placeholderValue: 'Select one or more categories'
        });
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

    <!-- year -->
    <script>
    $(function() {
        $("#publication_year").datepicker({
            changeYear: true,
            changeMonth: false,
            showButtonPanel: true,
            dateFormat: 'yy',
            yearRange: "1100:2030",
            onClose: function(dateText, inst) {
                var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
                $(this).datepicker('setDate', new Date(year, 1));
            },
            beforeShow: function(input, inst) {
                $(".ui-datepicker-month").hide();
            }
        });
    });
    </script>

</body>

</html>