<?php
@include '../config.php';
session_start();


if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'user';

$category_id = (int)($_GET['id'] ?? 0);

if($category_id <= 0) {
    http_response_code(400);
    echo "ID danh mục không hợp lệ.";
    exit;
}

$stmt_query = "SELECT * FROM categories WHERE category_id = ?";
$stmt = $conn->prepare($stmt_query);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$category_result = $stmt->get_result();

if($row = $category_result->fetch_assoc()){
    if($row['is_system'] == 0 && $row['uid'] != $user_id) {
        http_response_code(403);
        echo "Bạn không có quyền truy cập danh mục này.";
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode($row);
} else {
    http_response_code(404);
    echo "Danh mục không tồn tại.";
    exit;
}
?>