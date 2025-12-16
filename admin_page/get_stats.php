<?php
@include '../config.php';
session_start();

if(!isset($_SESSION['admin_id'])){
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$stats = [];

// ================================================================================= //
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE urole = 'user'");
$stmt->execute();
$result = $stmt->get_result();
$stats['total_users'] = $result->fetch_assoc()['total'] ?? 0;

// ================================================================================= //
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM transactions");
$stmt->execute();
$result = $stmt->get_result();
$stats['total_transactions'] = $result->fetch_assoc()['total'] ?? 0;

// ================================================================================= //
$stmt = $conn->prepare("SELECT SUM(transaction_amount) as total FROM transactions");
$stmt->execute();
$result = $stmt->get_result();
$stats['total_amount'] = $result->fetch_assoc()['total'] ?? 0;

// ================================================================================= //
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM transactions WHERE DATE(transaction_created_at) = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
$stats['today_activity'] = $result->fetch_assoc()['total'] ?? 0;

header('Content-Type: application/json');
echo json_encode(['success' => true, ...$stats]);
?>