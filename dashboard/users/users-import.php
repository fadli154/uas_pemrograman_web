<?php
require '../../function.php'; 
require __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$allowed = ['xlsx','xls','csv'];
$maxSize = 10 * 1024 * 1024;

if (!isset($_FILES['file'])) {
    $_SESSION['error'] = "Tidak ada file yang diupload.";
    header("Location: users-index.php");
    exit;
}

$file = $_FILES['file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowed)) {
    $_SESSION['error'] = "Format file tidak diperbolehkan.";
    header("Location: users-index.php");
    exit;
}

if ($file['size'] > $maxSize) {
    $_SESSION['error'] = "Ukuran file terlalu besar.";
    header("Location: users-index.php");
    exit;
}

$tmpPath = $file['tmp_name'];

$reader = IOFactory::createReaderForFile($tmpPath);
$spreadsheet = $reader->load($tmpPath);
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray(null, true, true, true);

$header = array_map('trim', $rows[1]);

$map = [];
foreach ($header as $col => $text) {
    $map[strtolower($text)] = $col;
}

$requiredFields = ['user_id','role_id','name','password','email'];
$missing = [];

foreach ($requiredFields as $f) {
    if (!isset($map[$f])) $missing[] = $f;
}

if (!empty($missing)) {
    $_SESSION['error'] = "Kolom wajib hilang: " . implode(', ', $missing);
    header("Location: users-index.php");
    exit;
}

$success = 0;
$errors = [];

$connection->begin_transaction();

try {
    $insertSql = "INSERT INTO users (user_id, role_id, name, password, status, phone, photo, email)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $connection->prepare($insertSql);
    if (!$stmt) throw new Exception("Prepare failed: " . $connection->error);

    for ($r = 2; $r <= count($rows); $r++) {
        $row = $rows[$r];

        $user_id = trim((string)($row[$map['user_id']] ?? ''));
        $role_id = trim((string)($row[$map['role_id']] ?? ''));
        $name    = trim((string)($row[$map['name']] ?? ''));
        $passwordPlain = trim((string)($row[$map['password']] ?? ''));
        $status  = trim((string)($row[$map['status']] ?? 'active'));
        $phone   = trim((string)($row[$map['phone']] ?? ''));
        $photo   = trim((string)($row[$map['photo']] ?? ''));
        $email   = trim((string)($row[$map['email']] ?? ''));

        if ($user_id === '' && $email === '') continue;

        $rowErrors = [];
        if ($user_id === '') $rowErrors[] = "user_id kosong";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $rowErrors[] = "email invalid";
        if ($name === '') $rowErrors[] = "name kosong";
        if ($passwordPlain === '') $passwordPlain = 'password123';
        if (!ctype_digit((string)$role_id)) $rowErrors[] = "role_id harus angka";

        $q = $connection->prepare("SELECT user_id FROM users WHERE user_id = ? OR email = ?");
        $q->bind_param("ss", $user_id, $email);
        $q->execute();
        $q->store_result();

        if ($q->num_rows > 0) $rowErrors[] = "user_id/email sudah ada";
        $q->close();

        if (!empty($rowErrors)) {
            $errors[$r] = $rowErrors;
            continue;
        }

        $passwordHash = password_hash($passwordPlain, PASSWORD_BCRYPT);

        $stmt->bind_param(
            "sissssss",
            $user_id,
            $role_id,
            $name,
            $passwordHash,
            $status,
            $phone,
            $photo,
            $email
        );

        if (!$stmt->execute()) {
            throw new Exception("Gagal insert row $r : " . $stmt->error);
        }

        $success++;
    }

    $connection->commit();
    $stmt->close();

} catch (Exception $e) {
    $connection->rollback();
    $_SESSION['error'] = "Error import: " . $e->getMessage();
    header("Location: users-index.php");
    exit;
}

// hasil sukses
if ($success > 0) {
    $_SESSION['success'] = "Berhasil import $success data!";
}
if (!empty($errors)) {
    $_SESSION['error'] = "Ada error pada beberapa baris.";
    $_SESSION['import_errors'] = $errors;
}

header("Location: users-index.php");
exit;