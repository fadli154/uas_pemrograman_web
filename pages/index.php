<?php
require '../function.php';
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <h1>home page</h1>
    <?php
    if(isset($_SESSION["log"])) {
        echo "<a href='dashboard/dashboard.php'>Dashboard</a>";
        echo "<a href='#' class='sidebar-link logout-btn'>
                                <i class='bi bi-door-open-fill logout-btn'></i>
                                <span class='logout-btn'>logout</span>
                            </a>";
    }else{
        echo "<a href='login.php'>Login</a>";
    }
    ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

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
</body>

</html>