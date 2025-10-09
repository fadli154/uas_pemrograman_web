<?php

if(isset($_SESSION["log"])) {
    // User is logged in, allow access to dashboard 
} else {
    header("Location: ../pages/login.php");
}

?>