<?php
@include '../config.php';
session_start();

if(!isset($_SESSION['user_id'])){
    die('LỖI: Không được phép');
}

$user_id = $_SESSION['user_id'];
$transaction_id = $_POST['transaction_id'] ?? 0;
$amount = $_POST['amount'] ?? 0;
$category_id = $_POST['category_id'] ?? 0;
$description = $_POST['description'] ?? '';
$transaction_date = $_POST['transaction_date'] ?? '';

if($transaction_id <= 0 || $amount <= 0 || $category_id <= 0){
    die('LỖI: Dữ liệu không hợp lệ');
}

// Kiểm tra quyền sở hữu
$check = $conn->prepare("SELECT uid FROM transactions WHERE transaction_id = ?");
$check->bind_param("i", $transaction_id);
$check->execute();
$check->store_result();

if($check->num_rows == 0){
    die('LỖI: không tìm thấy giao dịch nào');
}

$check->bind_result($owner_id);
$check->fetch();

if($owner_id != $user_id){
    die('LỖI: Quyền truy cập bị từ chối');
}

// Update
$stmt = $conn->prepare("UPDATE transactions SET 
                       amount = ?, 
                       category_id = ?, 
                       description = ?, 
                       transaction_date = ? 
                       WHERE transaction_id = ?");
$stmt->bind_param("disss", $amount, $category_id, $description, $transaction_date, $transaction_id);

if($stmt->execute()){
    echo "Cập nhật thành công!";
} else {
    die('LỖI: ' . $stmt->error);
}
?>