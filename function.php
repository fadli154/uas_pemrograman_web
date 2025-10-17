<?php
session_start();

$connection = mysqli_connect("localhost", "root", "", "db_baind");

// =============== users =================

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
    $query = "SELECT * FROM users LEFT JOIN roles ON users.role_id = roles.role_id WHERE user_id = $id";
    $stmt = $connection->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    return $user;
 }

//  delete
function deleteUser($id) {
    global $connection;

    // Pastikan user login
    if (!isset($_SESSION["user"])) {
        $_SESSION["error"] = "You must be logged in to perform this action.";
        return false;
    }

    $currentUser = $_SESSION["user"];

    // Cegah user menghapus dirinya sendiri
    if ($id == $currentUser["user_id"]) {
        $_SESSION["error"] = "You cannot delete your own account.";
        return false;
    }

    // Hanya admin yang boleh hapus user lain
    if ($currentUser["role_id"] != '1') {
        $_SESSION["error"] = "You do not have permission to delete users.";
        return false;
    }

    // Cek apakah user ada
    $check = $connection->prepare("SELECT photo FROM users WHERE user_id = ?");
    $check->bind_param("s", $id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows == 0) {
        $_SESSION["error"] = "User not found.";
        return false;
    }

    // Ambil nama file foto
    $user = $result->fetch_assoc();
    $photo = $user["photo"];
    $check->close();

    // Hapus foto dari folder jika ada
    if (!empty($photo)) {
        $photoPath = "../../uploads/" . $photo;
        if (file_exists($photoPath)) {
            unlink($photoPath);
        }
    }

    // Hapus data user dari database
    $stmt = $connection->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $id);

    if ($stmt->execute()) {
        $_SESSION["success"] = "User has been successfully deleted!";
        return true;
    } else {
        $_SESSION["error"] = "Failed to delete user: " . $stmt->error;
        return false;
    }
}

// edit
function updateUser($id, $data, $file)
{
    global $connection;
    $errors = [];

    // --- VALIDASI DASAR ---
    if (empty(trim($data["user_id"]))) {
        $errors["user_id"] = "User ID cannot be empty. (User ID tidak boleh kosong.)";
    }

    if (strlen(trim($data["name"])) < 3) {
        $errors["name"] = "Name must be at least 3 characters long. (Nama minimal 3 karakter.)";
    }

    if (!filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
        $errors["email"] = "Invalid email format. (Format email tidak valid.)";
    }

    $cleanPhone = preg_replace('/\s+/', '', $data["phone"]);
    if (!preg_match('/^[0-9]{10,13}$/', $cleanPhone)) {
        $errors["phone"] = "Phone number must contain 10–13 digits only. (Nomor telepon harus berisi 10–13 digit angka.)";
    }
    $data["phone"] = $cleanPhone;

    if (empty($data["status"])) {
        $errors["status"] = "Status must be selected. (Status harus dipilih.)";
    }

    if (empty($data["role_id"])) {
        $errors["role_id"] = "Role must be selected. (Role harus dipilih.)";
    }

    // --- CEK DUPLIKAT user_id, email, phone (pada user lain) ---
    $checkQuery = "SELECT user_id FROM users WHERE (user_id = ? OR email = ? OR phone = ?) AND user_id != ?";
    $checkStmt = $connection->prepare($checkQuery);
    $checkStmt->bind_param("ssss", $data["user_id"], $data["email"], $data["phone"], $id);
    $checkStmt->execute();
    $checkStmt->store_result();
    if ($checkStmt->num_rows > 0) {
        $errors["duplicate"] = "User ID, email, or phone already used by another user. (User ID, email, atau nomor telepon sudah digunakan pengguna lain.)";
    }
    $checkStmt->close();

    if (!empty($errors)) {
        $_SESSION["errors"] = $errors;
        $_SESSION["error"] = implode(" ", $errors);
        return false;
    }

    // --- AMBIL FOTO LAMA ---
    $photoName = null;
    $getOldPhoto = $connection->prepare("SELECT photo FROM users WHERE user_id = ?");
    $getOldPhoto->bind_param("s", $id);
    $getOldPhoto->execute();
    $getOldPhoto->bind_result($oldPhoto);
    $getOldPhoto->fetch();
    $getOldPhoto->close();
    $photoName = $oldPhoto;

    // --- UPLOAD FOTO BARU (JIKA ADA) ---
    $newPhotoUploaded = false;
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

        // Hapus foto lama jika ada dan berbeda
        if (!empty($oldPhoto) && file_exists($targetDir . $oldPhoto)) {
            unlink($targetDir . $oldPhoto);
        }

        $newPhotoUploaded = true;
    }

    // --- UPDATE FIELD DINAMIS ---
    $updateFields = "user_id=?, name=?, email=?, phone=?, status=?, role_id=?";
    $params = [
        $data["user_id"],
        $data["name"],
        $data["email"],
        $data["phone"],
        $data["status"],
        $data["role_id"]
    ];
    $types = "sssssi";

    // Tambahkan foto jika ada upload baru
    if ($newPhotoUploaded) {
        $updateFields .= ", photo=?";
        $params[] = $photoName;
        $types .= "s";
    }

    // Tambahkan ID lama untuk WHERE
    $query = "UPDATE users SET $updateFields WHERE user_id=?";
    $params[] = $id;
    $types .= "s";

    $stmt = $connection->prepare($query);
    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
    $_SESSION["error"] = "Failed to update data. (" . $stmt->error . ")";
    return false;
}

// --- UPDATE SESSION JIKA USER YANG LOGIN MENGEDIT DIRINYA SENDIRI ---
if ($_SESSION['user']['user_id'] === $id) {
    $_SESSION['user']['user_id'] = $data['user_id'];
    $_SESSION['user']['name'] = $data['name'];
    $_SESSION['user']['email'] = $data['email'];
    $_SESSION['user']['photo'] = $photoName ?? $_SESSION['user']['photo'];
    $_SESSION['user']['role_id'] = $data['role_id'];

    $roleQuery = $connection->prepare("SELECT role_name FROM roles WHERE role_id = ?");
    $roleQuery->bind_param("i", $data['role_id']);
    $roleQuery->execute();
    $roleQuery->bind_result($roleName);
    $roleQuery->fetch();
    $roleQuery->close();
    $_SESSION['user']['role'] = $roleName ?? $_SESSION['user']['role'];
}

$_SESSION["success"] = "Data has been successfully updated! (Data berhasil diperbarui!)";
return true;

}

// =============== Roles =================

function insertRole($data, $file)
{
    global $connection;
    $errors = [];

    // --- VALIDASI INPUT ---
    if (strlen(trim($data["role_name"])) < 3) {
        $errors["role_name"] = "Name must be at least 3 characters long. (Nama minimal 3 karakter.)";
    }

    // --- CEK DUPLIKAT DATA (role_name) ---
    $checkQuery = "SELECT role_name FROM roles WHERE role_name = ?";
    $checkStmt = $connection->prepare($checkQuery);

    if ($checkStmt) {
        $checkStmt->bind_param("s", $data["role_name"]);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $checkStmt->bind_result($uid, $em, $ph);
            while ($checkStmt->fetch()) {
                if ($uid === $data["role_name"]) $errors["role_name"] = "Role name already exists. (role name sudah terdaftar.)";
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

    // --- INSERT DATA ---
    $query = "INSERT INTO roles (role_name)
              VALUES (?)";
    $stmt = $connection->prepare($query);
    $stmt->bind_param(
        "s",
        $data["role_name"],
    );

    if (!$stmt->execute()) {
        $_SESSION["error"] = "Failed to insert data. (" . $stmt->error . ")";
        return false;
    }

    $_SESSION["success"] = "Data has been successfully saved! (Data berhasil disimpan!)";
    return true;
}

// detail
 function detailRole($id){
    global $connection;
    $query = "SELECT * FROM roles WHERE role_id = $id";
    $stmt = $connection->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    $role = $result->fetch_assoc();
    return $role;
 }

//  delete
function deleteRole($id) {
    global $connection;

    // Pastikan user login
    if (!isset($_SESSION["user"])) {
        $_SESSION["error"] = "You must be logged in to perform this action.";
        return false;
    }

    // cek apakah role sedang digunakan
    $check = $connection->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $check->bind_result($count);
    $check->fetch();
    $check->close();

    if ($count > 0) {
        $_SESSION["error"] = "This role is currently in use by $count user.";
        return false;
    }

    $currentUser = $_SESSION["user"];

    // Cegah user menghapus dirinya sendiri
    if ($id == "1") {
        $_SESSION["error"] = "You cannot delete this role.";
        return false;
    }

    // Hanya admin yang boleh hapus user lain
    if ($currentUser["role_id"] != '1') {
        $_SESSION["error"] = "You do not have permission to delete roles.";
        return false;
    }

    // Hapus data user dari database
    $stmt = $connection->prepare("DELETE FROM roles WHERE role_id = ?");
    $stmt->bind_param("s", $id);

    if ($stmt->execute()) {
        $_SESSION["success"] = "role has been successfully deleted!";
        return true;
    } else {
        $_SESSION["error"] = "Failed to delete role: " . $stmt->error;
        return false;
    }
}

?>