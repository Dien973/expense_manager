<?php
// save_category.php - VERSION ĐƠN GIẢN ĐỂ TEST
@include '../config.php';
session_start();

// BẬT DEBUG
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log để debug
$log = date('Y-m-d H:i:s') . " - POST: " . print_r($_POST, true) . "\n";
file_put_contents('debug.log', $log, FILE_APPEND);

if (!isset($_SESSION['user_id'])) {
    die('ERROR: Chưa đăng nhập!');
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'user';

// Lấy dữ liệu
$category_id = $_POST['category_id'] ?? 0;
$category_name = trim($_POST['category_name'] ?? '');
$category_note = trim($_POST['category_note'] ?? '');
$category_type = $_POST['category_type'] ?? '';
$category_icon = $_POST['category_icon'] ?? 'bx bx-category';
$is_system = isset($_POST['is_system']) && $_POST['is_system'] == '1' ? 1 : 0;

// Validate cơ bản
if (empty($category_name)) {
    die('ERROR: Tên danh mục không được để trống!');
}

if (empty($category_type) || !in_array($category_type, ['Thu nhập', 'Chi tiêu'])) {
    die('ERROR: Loại danh mục không hợp lệ!');
}

// Chỉ admin được tạo danh mục hệ thống
if ($is_system == 1 && $user_role != 'admin') {
    die('ERROR: Chỉ admin được tạo danh mục hệ thống!');
}

try {
    if ($category_id > 0) {
        // UPDATE - chỉ cho phép sửa danh mục của chính mình
        $stmt = $conn->prepare("UPDATE categories SET 
            category_name = ?, 
            category_note = ?, 
            category_icon = ?,
            is_system = ?
            WHERE category_id = ? AND uid = ?");
        
        if (!$stmt) {
            die('ERROR: Prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param("sssiii", $category_name, $category_note, $category_icon, 
                         $is_system, $category_id, $user_id);
    } else {
        // INSERT
        $stmt = $conn->prepare("INSERT INTO categories 
            (uid, category_name, category_note, category_type, category_icon, is_system) 
            VALUES (?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            die('ERROR: Prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param("issssi", $user_id, $category_name, $category_note, 
                         $category_type, $category_icon, $is_system);
    }
    
    if ($stmt->execute()) {
        if ($category_id == 0) {
            echo "SUCCESS: Đã thêm danh mục thành công!";
        } else {
            echo "SUCCESS: Đã cập nhật danh mục thành công!";
        }
    } else {
        // Kiểm tra lỗi duplicate
        if ($conn->errno == 1062) { // MySQL duplicate error code
            die('ERROR: Danh mục này đã tồn tại!');
        }
        die('ERROR: Database error: ' . $stmt->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    die('ERROR: Exception: ' . $e->getMessage());
}

$conn->close();
?>