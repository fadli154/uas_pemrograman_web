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

function insertUser($data, $file)
{
    global $connection;
    $errors = [];

    // --- VALIDASI INPUT ---
    if (empty(trim($data["user_id"]))) {
        $errors["user_id"] = "User ID cannot be empty. (User ID tidak boleh kosong.)";
    }

    if (strlen(trim($data["name"])) < 3) {
        $errors["name"] = "Name must be at least 3 characters long. (Nama minimal 3 karakter.)";
    }

    if (!filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
        $errors["email"] = "Invalid email format. (Format email tidak valid.)";
    }

    if (empty(trim($data["phone"]))) {
        $errors["phone"] = "Phone number cannot be empty. (Nomor telepon tidak boleh kosong.)";
    } else {
        $cleanPhone = preg_replace('/\s+/', '', $data["phone"]);
        if (!preg_match('/^[0-9]{10,13}$/', $cleanPhone)) {
            $errors["phone"] = "Phone number must contain 10–13 digits only. (Nomor telepon harus berisi 10–13 digit angka.)";
        }
        $data["phone"] = $cleanPhone;
    }

    if (strlen(trim($data["password"])) < 6) {
        $errors["password"] = "Password must be at least 6 characters long. (Password minimal 6 karakter.)";
    }

    if (empty($data["status"])) {
        $errors["status"] = "Status must be selected. (Status harus dipilih.)";
    }

    if (empty($data["role_id"])) {
        $errors["role_id"] = "Role must be selected. (Role harus dipilih.)";
    }

    // --- CEK DUPLIKAT DATA (email, user_id, phone) ---
    $checkQuery = "SELECT user_id, email, phone FROM users WHERE user_id = ? OR email = ? OR phone = ?";
    $checkStmt = $connection->prepare($checkQuery);

    if ($checkStmt) {
        $checkStmt->bind_param("sss", $data["user_id"], $data["email"], $data["phone"]);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $checkStmt->bind_result($uid, $em, $ph);
            while ($checkStmt->fetch()) {
                if ($uid === $data["user_id"]) $errors["user_id"] = "User ID already exists. (User ID sudah terdaftar.)";
                if ($em === $data["email"]) $errors["email"] = "Email already exists. (Email sudah terdaftar.)";
                if ($ph === $data["phone"]) $errors["phone"] = "Phone number already exists. (Nomor telepon sudah terdaftar.)";
            }
        }
        $checkStmt->close();
    } else {
        $errors["db"] = "Database error: Failed to prepare duplicate check.";
    }

    // --- KALAU ADA ERROR APA PUN ---
    if (!empty($errors)) {
        $_SESSION["errors"] = $errors;
        $_SESSION["error"] = implode(" ", $errors);
        return false;
    }

    // --- UPLOAD FOTO ---
    $photoName = null;
    if (isset($file["photo"]) && $file["photo"]["error"] === UPLOAD_ERR_OK) {
        $targetDir = "../../uploads/";
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

        $photoName = time() . "_" . basename($file["photo"]["name"]);
        $targetFile = $targetDir . $photoName;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ["jpg", "jpeg", "png"];

        if (!in_array($fileType, $allowedTypes)) {
            $_SESSION["error"] = "Photo must be JPG, JPEG, or PNG format.";
            return false;
        }

        if ($file["photo"]["size"] > 3 * 1024 * 1024) {
            $_SESSION["error"] = "Photo size must not exceed 3MB. (Ukuran foto maksimal 3MB.)";
            return false;
        }

        if (!move_uploaded_file($file["photo"]["tmp_name"], $targetFile)) {
            $_SESSION["error"] = "Failed to upload photo. (Gagal upload foto.)";
            return false;
        }
    }

    // --- ENKRIPSI PASSWORD ---
    $hashedPassword = password_hash($data["password"], PASSWORD_DEFAULT);

    // --- INSERT DATA ---
    $query = "INSERT INTO users (user_id, name, email, phone, password, status, role_id, photo)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $connection->prepare($query);
    $stmt->bind_param(
        "ssssssis",
        $data["user_id"],
        $data["name"],
        $data["email"],
        $data["phone"],
        $hashedPassword,
        $data["status"],
        $data["role_id"],
        $photoName
    );

    if (!$stmt->execute()) {
        $_SESSION["error"] = "Failed to insert data. (" . $stmt->error . ")";
        return false;
    }

    $_SESSION["success"] = "Data has been successfully saved! (Data berhasil disimpan!)";
    return true;
}

// detail
 function detailUser($id){
    global $connection;
    $query = "SELECT * FROM users WHERE user_id = $id";
    $stmt = $connection->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    return $user;
 }


?>