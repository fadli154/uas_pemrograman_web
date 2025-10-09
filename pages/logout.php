<?php
session_start();

// Hapus semua data session kecuali notifikasi logout
session_unset();
$_SESSION["logout"] = true;

// session_destroy();s 
header("Location: ../pages/login.php");
exit;
?>