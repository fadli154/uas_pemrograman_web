<?php
session_start();

$connection = mysqli_connect("localhost", "root", "", "db_baind");

function select($query) {
    global $connection; // biar koneksi bisa diakses di dalam fungsi
    $result = mysqli_query($connection, $query);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows; // kembalikan hasilnya
}

// insert
function insertUsers($table, $data) {
    global $connection;
    $query = "INSERT INTO $table (name, email, password, )";
    $result = mysqli_query($connection, $query);
    $row = mysqli_fetch_assoc($result);
    return $row[""];
}

?>