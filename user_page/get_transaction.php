<?php
@include '../config.php';
session_start();

if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['Lỗi' => 'Không được phép']);
    exit();
}

$user_id = $_SESSION['user_id'];
$transaction_id = (int)($_GET['id'] ?? 0);

if($transaction_id <= 0){
    http_response_code(400);
    echo json_encode(['Lỗi' => 'ID giao dịch không hợp lệ']);
    exit;
}

$stmt = $conn->prepare("SELECT t.*, c.category_name, c.category_type 
                       FROM transactions t 
                       JOIN categories c ON t.category_id = c.category_id 
                       WHERE t.transaction_id = ? AND t.uid = ?");
$stmt->bind_param("ii", $transaction_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if($row = $result->fetch_assoc()){
    header('Content-Type: application/json');
    echo json_encode($row);
} else {
    http_response_code(404);
    echo json_encode(['Lỗi' => 'Không tìm thấy giao dịch nào']);
}
?>