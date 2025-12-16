<?php
@include '../config.php';
session_start();

if(!isset($_SESSION['admin_id'])){
    header('location:../account/login.php');
    exit;
}

$user_id = (int)$_GET['id'] ?? 0;

if($user_id <= 0) {
    header('location:users.php');
    exit;
}

// ========================================================================== //
$user_query = "SELECT u.*, ud.* 
               FROM users u 
               LEFT JOIN users_detail ud ON u.uid = ud.uid 
               WHERE u.uid = ? AND u.urole = 'user'";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if($user_result->num_rows == 0) {
    header('location:users.php');
    exit;
}

$user = $user_result->fetch_assoc();
// ========================================================================== //
$stats_query = "SELECT 
                SUM(CASE WHEN transaction_type = 'Thu nhập' THEN transaction_amount ELSE 0 END) as total_income,
                SUM(CASE WHEN transaction_type = 'Chi tiêu' THEN transaction_amount ELSE 0 END) as total_expense,
                COUNT(*) as total_transactions
                FROM transactions WHERE uid = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// ========================================================================== //
$recent_query = "SELECT t.*, c.category_name, c.category_icon 
                 FROM transactions t 
                 JOIN categories c ON t.category_id = c.category_id 
                 WHERE t.uid = ? 
                 ORDER BY t.transaction_date DESC 
                 LIMIT 10";
$recent_stmt = $conn->prepare($recent_query);
$recent_stmt->bind_param("i", $user_id);
$recent_stmt->execute();
$recent_transactions = $recent_stmt->get_result();

// ========================================================================== //
$categories_query = "SELECT * FROM categories WHERE uid = ? AND is_system = 0 ORDER BY category_type, category_name";
$categories_stmt = $conn->prepare($categories_query);
$categories_stmt->bind_param("i", $user_id);
$categories_stmt->execute();
$personal_categories = $categories_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Chi tiết Người dùng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../css/ad_home.css">
    <link rel="stylesheet" href="../css/ad_users.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/ad_user_detail.css">
</head>
<body>
    <?php @include 'admin_sidebar.php'; ?>
    
    <div class="admin-main">
        <div class="admin-topbar">
            <div class="search-box">
                <a href="users.php" class="back-btn">
                    <i class='bx bx-arrow-back'></i> Quay lại
                </a>
            </div>
            <div class="admin-profile">
                <i class='bx bxs-user-circle'></i>
                <span><?php echo $_SESSION['admin_name'] ?? 'Admin'; ?></span>
            </div>
        </div>
        
        <div class="user-profile">
            <div class="profile-header">
                <div class="profile-avatar">
                    <img src="../assets/<?php echo $user['uimage'] ?? 'avata_default.jpg'; ?>" alt="<?php echo htmlspecialchars($user['uname']); ?>">
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user['uname']); ?> <span class="user-id">ID: <?php echo $user['uid']; ?></span></h1>
                    <div class="email">
                        <i class='bx bx-envelope'></i> <?php echo htmlspecialchars($user['uemail']); ?>
                    </div>
                    <div class="user-details">
                        <p><i class='bx bx-phone'></i> <?php echo $user['uphone'] ?? 'Chưa cập nhật'; ?></p>
                        <p><i class='bx bx-user-circle'></i> <?php echo $user['ugender'] ?? 'Khác'; ?></p>
                        <p><i class='bx bx-cake'></i> <?php echo $user['u_birthday'] ? date('d/m/Y', strtotime($user['u_birthday'])) : 'Chưa cập nhật'; ?></p>
                        <p><i class='bx bx-calendar'></i> Tham gia: <?php echo date('d/m/Y', strtotime($user['u_create_at'])); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="profile-stats">
                <div class="stat-box">
                    <div class="stat-label">Tổng Thu Nhập</div>
                    <div class="stat-value income-stat">
                        +<?php echo number_format($stats['total_income'] ?? 0, 0, ',', '.'); ?> ₫
                    </div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-label">Tổng Chi Tiêu</div>
                    <div class="stat-value expense-stat">
                        -<?php echo number_format($stats['total_expense'] ?? 0, 0, ',', '.'); ?> ₫
                    </div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-label">Số Dư</div>
                    <div class="stat-value balance-stat">
                        <?php echo number_format(($stats['total_income'] ?? 0) - ($stats['total_expense'] ?? 0), 0, ',', '.'); ?> ₫
                    </div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-label">Tổng Giao Dịch</div>
                    <div class="stat-value transaction-stat">
                        <?php echo $stats['total_transactions'] ?? 0; ?>
                    </div>
                </div>
            </div>
            
            <div class="tabs">
                <button class="tab active" onclick="switchTab('transactions')">Giao Dịch Gần Đây</button>
                <button class="tab" onclick="switchTab('categories')">Danh Mục Cá Nhân</button>
                <button class="tab" onclick="switchTab('info')">Thông Tin Chi Tiết</button>
            </div>
            
            <div id="transactions" class="tab-content active">
                <h3>10 Giao Dịch Gần Đây</h3>
                <?php if($recent_transactions->num_rows > 0): ?>
                <table class="admin-table" style="margin-top: 20px;">
                    <thead>
                        <tr>
                            <th>Ngày</th>
                            <th>Danh mục</th>
                            <th>Loại</th>
                            <th>Số tiền</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($transaction = $recent_transactions->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($transaction['transaction_date'])); ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <i class='<?php echo $transaction['category_icon']; ?>'></i>
                                    <?php echo htmlspecialchars($transaction['category_name']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="transaction-type type-<?php echo $transaction['transaction_type'] == 'Thu nhập' ? 'income' : 'expense'; ?>">
                                    <?php echo $transaction['transaction_type']; ?>
                                </span>
                            </td>
                            <td style="font-weight: 700; color: <?php echo $transaction['transaction_type'] == 'Thu nhập' ? '#27ae60' : '#e74c3c'; ?>;">
                                <?php echo $transaction['transaction_type'] == 'Thu nhập' ? '+' : '-'; ?>
                                <?php echo number_format($transaction['transaction_amount'], 0, ',', '.'); ?> ₫
                            </td>
                            <td><?php echo htmlspecialchars($transaction['transaction_note'] ?? 'Không có'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="text-align: center; color: #7f8c8d; padding: 20px;">
                    <i class='bx bx-wallet' style="font-size: 48px; display: block; margin-bottom: 10px;"></i>
                    Người dùng chưa có giao dịch nào
                </p>
                <?php endif; ?>
            </div>
            
            <div id="categories" class="tab-content">
                <h3>Danh Mục Cá Nhân</h3>
                <?php if($personal_categories->num_rows > 0): ?>
                <table class="admin-table" style="margin-top: 20px;">
                    <thead>
                        <tr>
                            <th>Tên danh mục</th>
                            <th>Loại</th>
                            <th>Biểu tượng</th>
                            <th>Ghi chú</th>
                            <th>Ngày tạo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($category = $personal_categories->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                            <td>
                                <span class="transaction-type type-<?php echo $category['category_type'] == 'Thu nhập' ? 'income' : 'expense'; ?>">
                                    <?php echo $category['category_type']; ?>
                                </span>
                            </td>
                            <td><i class='<?php echo $category['category_icon']; ?>'></i></td>
                            <td><?php echo htmlspecialchars($category['category_note'] ?? 'Không có'); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($category['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="text-align: center; color: #7f8c8d; padding: 20px;">
                    <i class='bx bx-category' style="font-size: 48px; display: block; margin-bottom: 10px;"></i>
                    Người dùng chưa tạo danh mục cá nhân nào
                </p>
                <?php endif; ?>
            </div>
            
            <div id="info" class="tab-content">
                <h3>Thông Tin Hệ Thống</h3>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px;">
                    <p><strong>ID Người dùng:</strong> <?php echo $user['uid']; ?></p>
                    <p><strong>Vai trò:</strong> <?php echo $user['urole']; ?></p>
                    <p><strong>Ngày tạo tài khoản:</strong> <?php echo date('d/m/Y H:i:s', strtotime($user['u_create_at'])); ?></p>
                    <p><strong>Số giao dịch:</strong> <?php echo $stats['total_transactions'] ?? 0; ?></p>
                    <p><strong>Tổng số dư:</strong> <?php echo number_format(($stats['total_income'] ?? 0) - ($stats['total_expense'] ?? 0), 0, ',', '.'); ?> ₫</p>
                    <p><strong>Trung bình giao dịch/tháng:</strong> 
                        <?php 
                            $months_diff = (time() - strtotime($user['u_create_at'])) / (30 * 24 * 60 * 60);
                            $avg = $months_diff > 0 ? ($stats['total_transactions'] ?? 0) / $months_diff : 0;
                            echo number_format($avg, 1);
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.getElementById(tabName).classList.add('active');
            
            event.target.classList.add('active');
        }
    </script>
</body>
</html>