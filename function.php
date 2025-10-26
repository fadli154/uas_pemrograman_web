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

// profile

// =============== Profile =================

// edit
function updateProfile($id, $data, $file)
{
    global $connection;
    $errors = [];

    $currentUser = $_SESSION["user"];

    // --- CEK AKSES ---
    if ($currentUser["role_id"] != $currentUser["role_id"]) {
        $_SESSION["error"] = "You do not have permission to edit this user.";
        return false;
    }

    // --- VALIDASI DASAR ---
    if (empty(trim($data["user_id"]))) {
        $errors["user_id"] = "User ID cannot be empty.";
    }

    if (strlen(trim($data["name"])) < 3) {
        $errors["name"] = "Name must be at least 3 characters.";
    }

    if (!filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
        $errors["email"] = "Invalid email format.";
    }

    $cleanPhone = preg_replace('/\s+/', '', $data["phone"]);
    if (!preg_match('/^[0-9]{10,13}$/', $cleanPhone)) {
        $errors["phone"] = "Phone number must contain 10–13 digits only.";
    }
    $data["phone"] = $cleanPhone;

    if (empty($data["status"])) {
        $errors["status"] = "Status must be selected.";
    }

    if (empty($data["role_id"])) {
        $errors["role_id"] = "Role must be selected.";
    }

    // --- CEK DUPLIKAT user_id, email, phone (kecuali user sendiri) ---
    $checkQuery = "SELECT user_id, email, phone FROM users WHERE (user_id = ? OR email = ? OR phone = ?) AND user_id != ?";
    $checkStmt = $connection->prepare($checkQuery);
    $checkStmt->bind_param("ssss", $data["user_id"], $data["email"], $data["phone"], $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if ($row["user_id"] === $data["user_id"]) {
            $errors["user_id"] = "User ID already used.";
        }
        if ($row["email"] === $data["email"]) {
            $errors["email"] = "Email already used.";
        }
        if ($row["phone"] === $data["phone"]) {
            $errors["phone"] = "Phone number already used.";
        }
    }

    $checkStmt->close();

    if (!empty($errors)) {
        $_SESSION["errors"] = $errors;
        $_SESSION["error"] = implode(" ", $errors);
        return false;
    }

    // --- AMBIL FOTO DAN PASSWORD LAMA ---
    $photoName = null;
    $hashedPassword = null;
    $getOld = $connection->prepare("SELECT photo, password FROM users WHERE user_id = ?");
    $getOld->bind_param("s", $id);
    $getOld->execute();
    $getOld->bind_result($oldPhoto, $oldHashedPassword);
    $getOld->fetch();
    $getOld->close();
    $photoName = $oldPhoto;
    $hashedPassword = $oldHashedPassword;

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
            $_SESSION["error"] = "Photo must be JPG, JPEG, or PNG.";
            return false;
        }

        if ($file["photo"]["size"] > 3 * 1024 * 1024) {
            $_SESSION["error"] = "Photo max size is 3MB.";
            return false;
        }

        if (!move_uploaded_file($file["photo"]["tmp_name"], $targetFile)) {
            $_SESSION["error"] = "Failed to upload photo.";
            return false;
        }

        if (!empty($oldPhoto) && file_exists($targetDir . $oldPhoto)) {
            unlink($targetDir . $oldPhoto);
        }

        $newPhotoUploaded = true;
    }

    // --- UPDATE PASSWORD (OPSIONAL) ---
    $newPassword = null;
    if (!empty($data["old_password"]) || !empty($data["new_password"])) {
        if (empty($data["old_password"]) || empty($data["new_password"])) {
            $_SESSION["error"] = "Both old and new passwords must be filled.";
            return false;
        }

        if (!password_verify($data["old_password"], $hashedPassword)) {
            $_SESSION["error"] = "Old password is incorrect.";
            return false;
        }

        if (strlen($data["new_password"]) < 6) {
            $_SESSION["error"] = "New password must be at least 6 characters.";
            return false;
        }

        $newPassword = password_hash($data["new_password"], PASSWORD_BCRYPT);
    }

    // --- SIAPKAN QUERY UPDATE ---
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

    if ($newPhotoUploaded) {
        $updateFields .= ", photo=?";
        $params[] = $photoName;
        $types .= "s";
    }

    if (!empty($newPassword)) {
        $updateFields .= ", password=?";
        $params[] = $newPassword;
        $types .= "s";
    }

    $query = "UPDATE users SET $updateFields WHERE user_id=?";
    $params[] = $id;
    $types .= "s";

    $stmt = $connection->prepare($query);
    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        $_SESSION["error"] = "Failed to update data. (" . $stmt->error . ")";
        return false;
    }

    // --- UPDATE SESSION JIKA USER EDIT DIRINYA SENDIRI ---
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

    $_SESSION["success"] = "Data profile updated successfully.";
    return true;
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

    $currentUser = $_SESSION["user"];

    // Hanya admin yang boleh edit data admin lain
    if ($currentUser["role_id"] != '1') {
        $_SESSION["error"] = "You do not have permission to delete users.";
        return false;
    }
    
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
    $checkQuery = "SELECT user_id, email, phone FROM users WHERE (user_id = ? OR email = ? OR phone = ?) AND user_id != ?";
    $checkStmt = $connection->prepare($checkQuery);
    $checkStmt->bind_param("ssss", $data["user_id"], $data["email"], $data["phone"], $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if ($row["user_id"] === $data["user_id"]) {
            $errors["user_id"] = "User ID is already used by another user.";
        }
        if ($row["email"] === $data["email"]) {
            $errors["email"] = "Email is already used by another user.";
        }
        if ($row["phone"] === $data["phone"]) {
            $errors["phone"] = "Phone number is already used by another user.";
        }
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

// edit

function updateRole($id, $data)
{
    global $connection;
    $errors = [];

    if($id == "1"){
        $_SESSION["error"] = "You cannot update this role.";
        return false;
    }

    // --- VALIDASI DASAR ---
    $roleName = trim($data["role_name"]);
    if (strlen($roleName) < 3) {
        $errors["role_name"] = "Role Name must be at least 3 characters long. (Nama minimal 3 karakter.)";
    }

    // --- CEK DUPLIKAT ROLE NAME ---
    $checkQuery = "SELECT role_id FROM roles WHERE role_name = ? AND role_id != ?";
    $checkStmt = $connection->prepare($checkQuery);
    $checkStmt->bind_param("si", $roleName, $id);
    $checkStmt->execute();
    $checkStmt->store_result();
    if ($checkStmt->num_rows > 0) {
        $errors["duplicate"] = "Role name already exists. (Nama role sudah digunakan.)";
    }
    $checkStmt->close();

    if (!empty($errors)) {
        $_SESSION["errors"] = $errors;
        $_SESSION["error"] = implode(" ", $errors);
        return false;
    }

    // --- UPDATE ROLE ---
    $query = "UPDATE roles SET role_name = ? WHERE role_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("si", $roleName, $id);

    if (!$stmt->execute()) {
        $_SESSION["error"] = "Failed to update role. (" . $stmt->error . ")";
        return false;
    }

    if ($_SESSION['user']['role_id'] == $id) {
        $_SESSION['user']['role'] = $roleName;
    }

    $_SESSION["success"] = "Role successfully updated. (Role berhasil diperbarui.)";
    return true;
}

// =============== Categories =================

function insertCategory($data, $file)
{
    global $connection;
    $errors = [];

    // --- VALIDASI INPUT ---
    if (strlen(trim($data["category_name"])) < 3) {
        $errors["category_name"] = "Name must be at least 3 characters long. (Nama minimal 3 karakter.)";
    }
    if (strlen(trim($data["category_id"])) < 7) {
        $errors["category_id"] = "ID must be at least 7 characters long. (ID minimal 7 karakter.)";
    }

    // --- CEK DUPLIKAT DATA ---
    $checkQuery = "SELECT category_name, category_id FROM categories WHERE category_name = ? OR category_id = ?";
    $checkStmt = $connection->prepare($checkQuery);

    if ($checkStmt) {
        $checkStmt->bind_param("ss", $data["category_name"], $data["category_id"]);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $checkStmt->bind_result($category_name_db, $category_id_db);
            while ($checkStmt->fetch()) {
                if ($category_name_db === $data["category_name"]) {
                    $errors["category_name"] = "Category name already exists. (category name sudah terdaftar.)";
                }
                if ($category_id_db === $data["category_id"]) {
                    $errors["category_id"] = "Category ID already exists. (category ID sudah terdaftar.)";
                }
            }
        }
        $checkStmt->close();
    } else {
        $errors["db"] = "Database error: Failed to prepare duplicate check.";
    }

    // --- KALAU ADA ERROR ---
    if (!empty($errors)) {
        $_SESSION["errors"] = $errors;
        $_SESSION["error"] = implode(" ", $errors);
        return false;
    }

    // --- INSERT DATA ---
    $query = "INSERT INTO categories (category_name, category_id, created_by, updated_by) VALUES (?, ?, ?, ?)";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("ssss", $data["category_name"], $data["category_id"], $_SESSION["user"]["user_id"], $_SESSION["user"]["user_id"]);

    if (!$stmt->execute()) {
        $_SESSION["error"] = "Failed to insert data. (" . $stmt->error . ")";
        return false;
    }

    $_SESSION["success"] = "Data has been successfully saved! (Data berhasil disimpan!)";
    return true;
}

// detail
function detailCategory($id) {
    global $connection;
    $query = "SELECT * FROM categories WHERE category_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();
    return $category;
}   

//  delete
function deleteCategory($id) {
    global $connection;

    // Pastikan user login
    if (!isset($_SESSION["user"])) {
        $_SESSION["error"] = "You must be logged in to perform this action.";
        return false;
    }

    // cek apakah category sedang digunakan
    $check = $connection->prepare("SELECT COUNT(*) FROM categories_books WHERE category_id = ?");
    $check->bind_param("s", $id);
    $check->execute();
    $check->bind_result($count);
    $check->fetch();
    $check->close();

    if ($count > 0) {
        $_SESSION["error"] = "This category is currently in use by $count book.";
        return false;
    }

    $currentUser = $_SESSION["user"];

    var_dump($currentUser);

    // Hanya admin yang boleh hapus category
    if ($currentUser["role_id"] != '1' && $currentUser["role_id"] != '2') {
        $_SESSION["error"] = "You do not have permission to delete categories.";
        return false;
    }

    // Hapus data category dari database
    $stmt = $connection->prepare("DELETE FROM categories WHERE category_id = ?");
    $stmt->bind_param("s", $id);

    if ($stmt->execute()) {
        $_SESSION["success"] = "category has been successfully deleted!";
        return true;
    } else {
        $_SESSION["error"] = "Failed to delete category: " . $stmt->error;
        return false;
    }
}


// edit

function updateCategory($id, $data)
{
    global $connection;
    $errors = [];

    // --- CEK KATEGORI YANG TIDAK BOLEH DIUBAH ---
    if ($id == "1") {
        $_SESSION["error"] = "You cannot update this category.";
        return false;
    }

    // --- VALIDASI INPUT ---
    $categoryId = trim($data["category_id"]);
    $categoryName = trim($data["category_name"]);

    if (strlen($categoryId) < 7) {
        $errors["category_id"] = "Category ID must be at least 7 characters long. (ID minimal 7 karakter.)";
    }
    if (strlen($categoryName) < 3) {
        $errors["category_name"] = "Category Name must be at least 3 characters long. (Nama minimal 3 karakter.)";
    }

    // --- CEK DUPLIKAT ---
    $checkQuery = "SELECT category_id, category_name FROM categories 
                   WHERE (category_name = ? OR category_id = ?) AND category_id != ?";
    $checkStmt = $connection->prepare($checkQuery);
    $checkStmt->bind_param("sss", $categoryName, $categoryId, $id);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $checkStmt->bind_result($db_category_id, $db_category_name);
        while ($checkStmt->fetch()) {
            if ($db_category_name === $categoryName) {
                $errors["category_name"] = "Category name already exists. (Nama kategori sudah terdaftar.)";
            }
            if ($db_category_id === $categoryId) {
                $errors["category_id"] = "Category ID already exists. (ID kategori sudah terdaftar.)";
            }
        }
    }
    $checkStmt->close();

    // --- KALAU ADA ERROR ---
    if (!empty($errors)) {
        $_SESSION["errors"] = $errors;
        $_SESSION["error"] = implode(" ", $errors);
        return false;
    }

    // --- UPDATE DATA ---
    $query = "UPDATE categories SET category_id = ?, category_name = ?, updated_by = ? WHERE category_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("ssss", $categoryId, $categoryName, $_SESSION["user"]["user_id"], $id);

    if (!$stmt->execute()) {
        $_SESSION["error"] = "Failed to update category. (" . $stmt->error . ")";
        return false;
    }

    $_SESSION["success"] = "Category successfully updated. (Category berhasil diperbarui.)";
    return true;
}

// =============== Books =================


function insertBook($data, $file)
{
    global $connection;
    $errors = [];

    // --- VALIDASI DASAR ---
    if (empty(trim($data["book_id"]))) $errors["book_id"] = "Book ID tidak boleh kosong.";
    if (empty(trim($data["isbn"]))) $errors["isbn"] = "ISBN tidak boleh kosong.";
    if (empty(trim($data["title"]))) $errors["title"] = "Judul buku tidak boleh kosong.";
    if (empty(trim($data["author"]))) $errors["author"] = "Penulis tidak boleh kosong.";
    if (empty(trim($data["publisher"]))) $errors["publisher"] = "Penerbit tidak boleh kosong.";
    if (empty($data["publication_year"])) $errors["publication_year"] = "Tahun terbit tidak boleh kosong.";
    if (empty($data["categories"]) || !is_array($data["categories"])) $errors["categories"] = "Minimal satu kategori harus dipilih.";

    // --- CEK DUPLIKAT BOOK_ID ATAU ISBN ---
    $checkQuery = "SELECT book_id, isbn FROM books WHERE book_id = ? OR isbn = ?";
    $checkStmt = $connection->prepare($checkQuery);
    if ($checkStmt) {
        $checkStmt->bind_param("ss", $data["book_id"], $data["isbn"]);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $checkStmt->bind_result($bid, $bisbn);
            while ($checkStmt->fetch()) {
                if ($bid === $data["book_id"]) $errors["book_id"] = "Book ID sudah terdaftar.";
                if ($bisbn === $data["isbn"]) $errors["isbn"] = "ISBN sudah terdaftar.";
            }
        }

        $checkStmt->close();
    } else {
        $errors["db"] = "Database error saat cek duplikat.";
    }

    // --- HANDLE ERROR VALIDASI ---
    if (!empty($errors)) {
        $_SESSION["errors"] = $errors;
        $_SESSION["error"] = implode(" ", $errors);
        return false;
    }

    // --- UPLOAD COVER ---
    $coverName = null;
    if (isset($file["book_cover"]) && $file["book_cover"]["error"] === UPLOAD_ERR_OK) {
        $targetDir = "../../books_cover/";
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

        $coverName = time() . "_" . basename($file["book_cover"]["name"]);
        $targetFile = $targetDir . $coverName;
        $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowed = ["jpg", "jpeg", "png"];

        if (!in_array($ext, $allowed)) {
            $_SESSION["error"] = "Cover harus JPG, JPEG, atau PNG.";
            return false;
        }

        if ($file["book_cover"]["size"] > 3 * 1024 * 1024) {
            $_SESSION["error"] = "Ukuran cover maksimal 3MB.";
            return false;
        }

        if (!move_uploaded_file($file["book_cover"]["tmp_name"], $targetFile)) {
            $_SESSION["error"] = "Gagal upload cover buku.";
            return false;
        }
    }

    // --- INSERT KE TABLE BOOKS ---
    $query = "INSERT INTO books 
              (book_id, isbn, title, author, publisher, synopsis, publication_year, book_cover, created_by, updated_by)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $connection->prepare($query);
    if (!$stmt) {
        $_SESSION["error"] = "Gagal menyiapkan query insert buku: " . $connection->error;
        return false;
    }

    $stmt->bind_param(
        "ssssssisss",
        $data["book_id"],
        $data["isbn"],
        $data["title"],
        $data["author"],
        $data["publisher"],
        $data["synopsis"],
        $data["publication_year"],
        $coverName,
        $_SESSION["user"]["user_id"],
        $_SESSION["user"]["user_id"]
    );

    if (!$stmt->execute()) {
        $_SESSION["error"] = "Gagal menyimpan buku (" . $stmt->error . ")";
        return false;
    }
    $stmt->close();

    // --- INSERT KE TABLE CATEGORIES_BOOKS ---
    $catQuery = "INSERT INTO categories_books (category_book_id, category_id, book_id) VALUES (?, ?, ?)";
    $catStmt = $connection->prepare($catQuery);
    if (!$catStmt) {
        $_SESSION["error"] = "Gagal menyiapkan query kategori: " . $connection->error;
        return false;
    }

    foreach ($data["categories"] as $categoryId) {
        $categoryBookId = uniqid("CTGRBOOK");
        $catStmt->bind_param("sss", $categoryBookId, $categoryId, $data["book_id"]);
        $catStmt->execute();
    }

    $catStmt->close();

    $_SESSION["success"] = "Data buku dan kategori berhasil disimpan.";
    return true;
}

//  delete
function deleteBook($id) {
    global $connection;

    // Pastikan user login
    if (!isset($_SESSION["user"])) {
        $_SESSION["error"] = "You must be logged in to perform this action.";
        return false;
    }

    $currentUser = $_SESSION["user"];

    if ($currentUser["role_id"] != '1' && $currentUser["role_id"] != '2') {
        $_SESSION["error"] = "You do not have permission to delete books.";
        return false;
    }

    // Cek apakah buku ada
    $check = $connection->prepare("SELECT book_cover FROM books WHERE book_id = ?");
    $check->bind_param("s", $id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows == 0) {
        $_SESSION["error"] = "Book not found.";
        return false;
    }

    $book = $result->fetch_assoc();
    $book_cover = $book["book_cover"];
    $check->close();

    // Hapus relasi buku dari tabel categories_books
    $delRelation = $connection->prepare("DELETE FROM categories_books WHERE book_id = ?");
    $delRelation->bind_param("s", $id);
    $delRelation->execute();
    $delRelation->close();

    // Hapus file cover jika ada
    if (!empty($book_cover)) {
        $bookCoverPath = "../../books_cover/" . $book_cover;
        if (file_exists($bookCoverPath)) {
            unlink($bookCoverPath);
        }
    }

    // Hapus buku dari tabel books
    $stmt = $connection->prepare("DELETE FROM books WHERE book_id = ?");
    $stmt->bind_param("s", $id);

    if ($stmt->execute()) {
        $_SESSION["success"] = "Book has been successfully deleted!";
        return true;
    } else {
        $_SESSION["error"] = "Failed to delete book: " . $stmt->error;
        return false;
    }
}

// detail
function detailBook($id) {
    global $connection;

    // Ambil detail buku utama
    $query = "SELECT * FROM books WHERE book_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    $stmt->close();

    if (!$book) return null; // jika buku tidak ditemukan

    // Ambil daftar kategori buku
    $catQuery = "SELECT c.category_id, c.category_name 
                 FROM categories_books cb
                 JOIN categories c ON cb.category_id = c.category_id
                 WHERE cb.book_id = ?";
    $catStmt = $connection->prepare($catQuery);
    $catStmt->bind_param("s", $id);
    $catStmt->execute();
    $catResult = $catStmt->get_result();

    $book["categories"] = [];
    while ($row = $catResult->fetch_assoc()) {
        $book["categories"][] = $row;
    }
    $catStmt->close();

    return $book;
}

// update
function updateBook($oldBookId, $data, $file)
{
    global $connection;
    $errors = [];

    // --- VALIDASI DASAR ---
    if (empty(trim($data["book_id"]))) $errors["book_id"] = "Book ID tidak boleh kosong.";
    if (empty(trim($data["isbn"]))) $errors["isbn"] = "ISBN tidak boleh kosong.";
    if (empty(trim($data["title"]))) $errors["title"] = "Judul buku tidak boleh kosong.";
    if (empty(trim($data["author"]))) $errors["author"] = "Penulis tidak boleh kosong.";
    if (empty(trim($data["publisher"]))) $errors["publisher"] = "Penerbit tidak boleh kosong.";
    if (empty($data["publication_year"])) $errors["publication_year"] = "Tahun terbit tidak boleh kosong.";
    if (empty($data["categories"]) || !is_array($data["categories"])) $errors["categories"] = "Minimal satu kategori harus dipilih.";

    if (!empty($errors)) {
        $_SESSION["errors"] = $errors;
        $_SESSION["error"] = implode(" ", $errors);
        return false;
    }

    // --- CEK DATA LAMA ---
    $oldStmt = $connection->prepare("SELECT * FROM books WHERE book_id = ?");
    if (!$oldStmt) {
        $_SESSION["error"] = "Gagal menyiapkan query cek data lama: " . $connection->error;
        return false;
    }
    $oldStmt->bind_param("s", $oldBookId);
    $oldStmt->execute();
    $oldResult = $oldStmt->get_result();
    $oldBook = $oldResult->fetch_assoc();
    $oldStmt->close();

    if (!$oldBook) {
        $_SESSION["error"] = "Buku tidak ditemukan.";
        return false;
    }

    // --- CEK DUPLIKAT BOOK_ID ---
    if ($data["book_id"] !== $oldBookId) {
        $check = $connection->prepare("SELECT book_id FROM books WHERE book_id = ?");
        if (!$check) {
            $_SESSION["error"] = "Gagal cek duplikat book_id: " . $connection->error;
            return false;
        }
        $check->bind_param("s", $data["book_id"]);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $_SESSION["error"] = "Book ID sudah terdaftar.";
            return false;
        }
        $check->close();
    }

    // --- CEK DUPLIKAT ISBN ---
    $check = $connection->prepare("SELECT isbn FROM books WHERE isbn = ? AND book_id != ?");
    if (!$check) {
        $_SESSION["error"] = "Gagal cek duplikat ISBN: " . $connection->error;
        return false;
    }
    $check->bind_param("ss", $data["isbn"], $oldBookId);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $_SESSION["error"] = "ISBN sudah terdaftar.";
        return false;
    }
    $check->close();

    // --- UPLOAD COVER ---
    $coverName = $oldBook["book_cover"];
    if (isset($file["book_cover"]) && $file["book_cover"]["error"] === UPLOAD_ERR_OK) {
        $targetDir = "../../books_cover/";
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

        $newName = time() . "_" . basename($file["book_cover"]["name"]);
        $targetFile = $targetDir . $newName;
        $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowed = ["jpg", "jpeg", "png"];

        if (!in_array($ext, $allowed)) {
            $_SESSION["error"] = "Cover harus JPG, JPEG, atau PNG.";
            return false;
        }

        if ($file["book_cover"]["size"] > 3 * 1024 * 1024) {
            $_SESSION["error"] = "Ukuran cover maksimal 3MB.";
            return false;
        }

        if (!move_uploaded_file($file["book_cover"]["tmp_name"], $targetFile)) {
            $_SESSION["error"] = "Gagal upload cover buku.";
            return false;
        }

        if (!empty($oldBook["book_cover"])) {
            $oldCoverPath = $targetDir . $oldBook["book_cover"];
            if (file_exists($oldCoverPath)) unlink($oldCoverPath);
        }

        $coverName = $newName;
    }

    // --- UPDATE DATA BUKU ---
    $updateQuery = "UPDATE books SET 
                    book_id = ?, isbn = ?, title = ?, author = ?, publisher = ?, 
                    synopsis = ?, publication_year = ?, book_cover = ?, updated_by = ? 
                    WHERE book_id = ?";
    $stmt = $connection->prepare($updateQuery);
    if (!$stmt) {
        $_SESSION["error"] = "Gagal menyiapkan query update buku: " . $connection->error;
        return false;
    }

    $userId = $_SESSION["user"]["user_id"];
    $stmt->bind_param(
        "ssssssisss",
        $data["book_id"],
        $data["isbn"],
        $data["title"],
        $data["author"],
        $data["publisher"],
        $data["synopsis"],
        $data["publication_year"],
        $coverName,
        $userId,
        $oldBookId
    );

    if (!$stmt->execute()) {
        $_SESSION["error"] = "Gagal update buku: " . $stmt->error;
        return false;
    }
    $stmt->close();

    // --- UPDATE RELASI KATEGORI ---
    $delCat = $connection->prepare("DELETE FROM categories_books WHERE book_id = ?");
    if (!$delCat) {
        $_SESSION["error"] = "Gagal hapus kategori lama: " . $connection->error;
        return false;
    }
    $delCat->bind_param("s", $oldBookId);
    $delCat->execute();
    $delCat->close();

    $catQuery = "INSERT INTO categories_books (category_book_id, category_id, book_id) VALUES (?, ?, ?)";
    $catStmt = $connection->prepare($catQuery);
    if (!$catStmt) {
        $_SESSION["error"] = "Gagal menyiapkan query kategori: " . $connection->error;
        return false;
    }

    foreach ($data["categories"] as $categoryId) {
    // Cek dulu apakah kombinasi kategori dan buku sudah ada
    $checkCat = $connection->prepare("SELECT * FROM categories_books WHERE category_id = ? AND book_id = ?");
    $checkCat->bind_param("ss", $categoryId, $data["book_id"]);
    $checkCat->execute();
    $exists = $checkCat->get_result()->num_rows > 0;
    $checkCat->close();

    if (!$exists) {
        $categoryBookId = uniqid("CTGRBOOK");
        $catStmt = $connection->prepare("INSERT INTO categories_books (category_book_id, category_id, book_id) VALUES (?, ?, ?)");
        $catStmt->bind_param("sss", $categoryBookId, $categoryId, $data["book_id"]);
        $catStmt->execute();
        $catStmt->close();
    }
}

    $_SESSION["success"] = "Data buku berhasil diperbarui.";
    return true;
}

// =============== Videos =================

function insertVideo($data, $file)
{
    global $connection;
    $errors = [];

    // --- VALIDASI DASAR ---
    if (empty(trim($data["video_id"]))) $errors["video_id"] = "Video ID tidak boleh kosong.";
    if (empty(trim($data["title"]))) $errors["title"] = "Judul video tidak boleh kosong.";
    if (empty(trim($data["youtube_url"]))) $errors["youtube_url"] = "URL YouTube tidak boleh kosong.";
    if (empty($data["categories"]) || !is_array($data["categories"])) $errors["categories"] = "Minimal satu kategori harus dipilih.";

    // --- CEK DUPLIKAT VIDEO_ID ---
    $checkQuery = "SELECT video_id FROM videos WHERE video_id = ?";
    $checkStmt = $connection->prepare($checkQuery);
    if ($checkStmt) {
        $checkStmt->bind_param("s", $data["video_id"]);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $errors["video_id"] = "Video ID sudah terdaftar.";
        }

        $checkStmt->close();
    } else {
        $errors["db"] = "Database error saat cek duplikat.";
    }

    // --- HANDLE ERROR VALIDASI ---
    if (!empty($errors)) {
        $_SESSION["errors"] = $errors;
        $_SESSION["error"] = implode(" ", $errors);
        return false;
    }

    // --- UPLOAD THUMBNAIL_URL ---
    $thumbnail_url = null;
    if (isset($file["thumbnail_url"]) && $file["thumbnail_url"]["error"] === UPLOAD_ERR_OK) {
        $targetDir = "../../thumbnail/";
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

        $thumbnail_url = time() . "_" . basename($file["thumbnail_url"]["name"]);
        $targetFile = $targetDir . $thumbnail_url;
        $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowed = ["jpg", "jpeg", "png"];

        if (!in_array($ext, $allowed)) {
            $_SESSION["error"] = "Thumbnail_url harus JPG, JPEG, atau PNG.";
            return false;
        }

        if ($file["thumbnail_url"]["size"] > 3 * 1024 * 1024) {
            $_SESSION["error"] = "Ukuran thumbnail_url maksimal 3MB.";
            return false;
        }

        if (!move_uploaded_file($file["thumbnail_url"]["tmp_name"], $targetFile)) {
            $_SESSION["error"] = "Gagal upload thumbnail_url video.";
            return false;
        }
    }

    // --- INSERT KE TABLE VIDEOS ---
    $query = "INSERT INTO videos 
              (video_id, title, description, youtube_url, thumbnail_url, duration, created_by)
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $connection->prepare($query);
    if (!$stmt) {
        $_SESSION["error"] = "Gagal menyiapkan query insert video: " . $connection->error;
        return false;
    }

    $stmt->bind_param(
        "sssssss",
        $data["video_id"],
        $data["title"],
        $data["description"],
        $data["youtube_url"],
        $thumbnail_url,
        $data["duration"],
        $_SESSION["user"]["user_id"]
    );

    if (!$stmt->execute()) {
        $_SESSION["error"] = "Gagal menyimpan video (" . $stmt->error . ")";
        return false;
    }
    $stmt->close();

    // --- INSERT KE TABLE CATEGORIES_VIDEOS ---
    $catQuery = "INSERT INTO categories_videos (category_video_id, category_id, video_id) VALUES (?, ?, ?)";
    $catStmt = $connection->prepare($catQuery);
    if (!$catStmt) {
        $_SESSION["error"] = "Gagal menyiapkan query kategori: " . $connection->error;
        return false;
    }

    foreach ($data["categories"] as $categoryId) {
        $categoryVideoId = uniqid("CTGRVIDEO");
        $catStmt->bind_param("sss", $categoryVideoId, $categoryId, $data["video_id"]);
        $catStmt->execute();
    }

    $catStmt->close();

    $_SESSION["success"] = "Data video berhasil disimpan.";
    return true;
}

//  delete
function deleteVideo($id) {
    global $connection;

    // Pastikan user login
    if (!isset($_SESSION["user"])) {
        $_SESSION["error"] = "You must be logged in to perform this action.";
        return false;
    }

    $currentUser = $_SESSION["user"];

    if ($currentUser["role_id"] != '1' && $currentUser["role_id"] != '2') {
        $_SESSION["error"] = "You do not have permission to delete videos.";
        return false;
    }

    // Cek apakah buku ada
    $check = $connection->prepare("SELECT thumbnail_url FROM videos WHERE video_id = ?");
    $check->bind_param("s", $id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows == 0) {
        $_SESSION["error"] = "Video not found.";
        return false;
    }

    $video = $result->fetch_assoc();
    $thumbnail_url = $video["thumbnail_url"];
    $check->close();

    // Hapus relasi buku dari tabel categories_videos
    $delRelation = $connection->prepare("DELETE FROM categories_videos WHERE video_id = ?");
    $delRelation->bind_param("s", $id);
    $delRelation->execute();
    $delRelation->close();

    // Hapus file cover jika ada
    if (!empty($thumbnail_url)) {
        $videoCoverPath = "../../thumbnail/" . $thumbnail_url;
        if (file_exists($videoCoverPath)) {
            unlink($videoCoverPath);
        }
    }

    // Hapus video dari tabel videos
    $stmt = $connection->prepare("DELETE FROM videos WHERE video_id = ?");
    $stmt->bind_param("s", $id);

    if ($stmt->execute()) {
        $_SESSION["success"] = "Video has been successfully deleted!";
        return true;
    } else {
        $_SESSION["error"] = "Failed to delete video: " . $stmt->error;
        return false;
    }
}

// detail
function detailVideo($id) {
    global $connection;

    // Ambil detail video utama
    $query = "SELECT * FROM videos WHERE video_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $video = $result->fetch_assoc();
    $stmt->close();

    if (!$video) return null; // jika video tidak ditemukan

    // Ambil daftar kategori video
    $catQuery = "SELECT c.category_id, c.category_name 
                 FROM categories_videos cb
                 JOIN categories c ON cb.category_id = c.category_id
                 WHERE cb.video_id = ?";
    $catStmt = $connection->prepare($catQuery);
    $catStmt->bind_param("s", $id);
    $catStmt->execute();
    $catResult = $catStmt->get_result();

    $video["categories"] = [];
    while ($row = $catResult->fetch_assoc()) {
        $video["categories"][] = $row;
    }
    $catStmt->close();

    return $video;
}

// update
function updateVideo($oldVideoId, $data, $file)
{
    global $connection;
    $errors = [];

    // --- VALIDASI DASAR ---
    if (empty(trim($data["video_id"]))) $errors["video_id"] = "Video ID tidak boleh kosong.";
    if (empty(trim($data["title"]))) $errors["title"] = "Judul video tidak boleh kosong.";
    if (empty(trim($data["youtube_url"]))) $errors["youtube_url"] = "URL YouTube tidak boleh kosong.";
    if (empty($data["categories"]) || !is_array($data["categories"])) $errors["categories"] = "Minimal satu kategori harus dipilih.";

    if (!empty($errors)) {
        $_SESSION["errors"] = $errors;
        $_SESSION["error"] = implode(" ", $errors);
        return false;
    }

    // --- CEK DATA LAMA ---
    $oldStmt = $connection->prepare("SELECT * FROM videos WHERE video_id = ?");
    if (!$oldStmt) {
        $_SESSION["error"] = "Gagal menyiapkan query cek data lama: " . $connection->error;
        return false;
    }
    $oldStmt->bind_param("s", $oldVideoId);
    $oldStmt->execute();
    $oldResult = $oldStmt->get_result();
    $oldVideo = $oldResult->fetch_assoc();
    $oldStmt->close();

    if (!$oldVideo) {
        $_SESSION["error"] = "Video tidak ditemukan.";
        return false;
    }

    // --- CEK DUPLIKAT VIDEO_ID ---
    if ($data["video_id"] !== $oldVideoId) {
        $check = $connection->prepare("SELECT video_id FROM videos WHERE video_id = ?");
        if (!$check) {
            $_SESSION["error"] = "Gagal cek duplikat video_id: " . $connection->error;
            return false;
        }
        $check->bind_param("s", $data["video_id"]);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $_SESSION["error"] = "Video ID sudah terdaftar.";
            return false;
        }
        $check->close();
    }

    // --- UPLOAD THUMBNAIL ---
    $thumbnailUrl = $oldVideo["thumbnail_url"];
    if (isset($file["thumbnail_url"]) && $file["thumbnail_url"]["error"] === UPLOAD_ERR_OK) {
        $targetDir = "../../thumbnail/";
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

        $newName = time() . "_" . basename($file["thumbnail_url"]["name"]);
        $targetFile = $targetDir . $newName;
        $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowed = ["jpg", "jpeg", "png"];

        if (!in_array($ext, $allowed)) {
            $_SESSION["error"] = "Thumbnail url harus JPG, JPEG, atau PNG.";
            return false;
        }

        if ($file["thumbnail_url"]["size"] > 3 * 1024 * 1024) {
            $_SESSION["error"] = "Ukuran thumbnail url maksimal 3MB.";
            return false;
        }

        if (!move_uploaded_file($file["thumbnail_url"]["tmp_name"], $targetFile)) {
            $_SESSION["error"] = "Gagal upload thumbnail_url video.";
            return false;
        }

        // Hapus thumbnail_url lama jika ada
        if (!empty($oldVideo["thumbnail_url"])) {
            $oldThumbnailPath = $targetDir . $oldVideo["thumbnail_url"];
            if (file_exists($oldThumbnailPath)) unlink($oldThumbnailPath);
        }

        $thumbnailUrl = $newName;
    }

    // --- UPDATE DATA VIDEO ---
    $updateQuery = "UPDATE videos SET 
                    video_id = ?, title = ?, description = ?, youtube_url = ?, 
                    thumbnail_url = ?, duration = ?
                    WHERE video_id = ?";
    $stmt = $connection->prepare($updateQuery);
    if (!$stmt) {
        $_SESSION["error"] = "Gagal menyiapkan query update video: " . $connection->error;
        return false;
    }

    $stmt->bind_param(
        "sssssss",
        $data["video_id"],
        $data["title"],
        $data["description"],
        $data["youtube_url"],
        $thumbnailUrl,
        $data["duration"],
        $oldVideoId
    );

    if (!$stmt->execute()) {
        $_SESSION["error"] = "Gagal update video: " . $stmt->error;
        return false;
    }
    $stmt->close();

    // --- UPDATE RELASI KATEGORI ---
    // Hapus kategori lama
    $delCat = $connection->prepare("DELETE FROM categories_videos WHERE video_id = ?");
    if (!$delCat) {
        $_SESSION["error"] = "Gagal hapus kategori lama: " . $connection->error;
        return false;
    }
    $delCat->bind_param("s", $data["video_id"]);
    $delCat->execute();
    $delCat->close();

    // Insert kategori baru
    $catQuery = "INSERT INTO categories_videos (category_video_id, category_id, video_id) VALUES (?, ?, ?)";
    $catStmt = $connection->prepare($catQuery);
    if (!$catStmt) {
        $_SESSION["error"] = "Gagal menyiapkan query kategori: " . $connection->error;
        return false;
    }

    foreach ($data["categories"] as $categoryId) {
        $categoryVideoId = uniqid("CTGRVIDEO");
        $catStmt->bind_param("sss", $categoryVideoId, $categoryId, $data["video_id"]);
        $catStmt->execute();
    }

    $catStmt->close();

    $_SESSION["success"] = "Data video berhasil diperbarui.";
    return true;
}


?>