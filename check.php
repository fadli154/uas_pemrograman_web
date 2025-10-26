<?php

if(isset($_SESSION["log"])) {
    // User is logged in, allow access to dashboard 
    if($_SESSION["user"]["role"] == 'admin' || $_SESSION["user"]["role"] == 'officer') {
    } else {
        header("Location: ../pages/index.php");
    }
} else {
    header("Location: ../pages/login.php");
}

?>