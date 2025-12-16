<?php
@include '../config.php';
session_start();

if(!isset($_SESSION['admin_id'])){
    header('location:../account/login.php');
    exit;
}

// ========================================================================== //
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$report_type = $_GET['report_type'] ?? 'daily';

// ========================================================================== //
if($start_date > $end_date) {
    $temp = $start_date;
    $start_date = $end_date;
    $end_date = $temp;
}

// ========================================================================== //
$overview_query = "SELECT 
                    COUNT(DISTINCT u.uid) as total_users,
                    COUNT(t.transaction_id) as total_transactions,
                    SUM(CASE WHEN t.transaction_type = 'Thu nhập' THEN t.transaction_amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN t.transaction_type = 'Chi tiêu' THEN t.transaction_amount ELSE 0 END) as total_expense,
                    AVG(CASE WHEN t.transaction_type = 'Thu nhập' THEN t.transaction_amount ELSE 0 END) as avg_income,
                    AVG(CASE WHEN t.transaction_type = 'Chi tiêu' THEN t.transaction_amount ELSE 0 END) as avg_expense
                   FROM users u
                   LEFT JOIN transactions t ON u.uid = t.uid 
                   WHERE u.urole = 'user' 
                   AND (t.transaction_date BETWEEN ? AND ? OR t.transaction_id IS NULL)";

$overview_stmt = $conn->prepare($overview_query);
$overview_stmt->bind_param("ss", $start_date, $end_date);
$overview_stmt->execute();
$overview = $overview_stmt->get_result()->fetch_assoc();

// ========================================================================== //
$daily_stats = [];
if($report_type == 'daily') {
    $daily_query = "SELECT 
                    DATE(t.transaction_date) as date,
                    COUNT(t.transaction_id) as transaction_count,
                    SUM(CASE WHEN t.transaction_type = 'Thu nhập' THEN t.transaction_amount ELSE 0 END) as daily_income,
                    SUM(CASE WHEN t.transaction_type = 'Chi tiêu' THEN t.transaction_amount ELSE 0 END) as daily_expense
                   FROM transactions t
                   JOIN users u ON t.uid = u.uid
                   WHERE u.urole = 'user'
                   AND t.transaction_date BETWEEN ? AND ?
                   GROUP BY DATE(t.transaction_date)
                   ORDER BY date DESC";
    
    $daily_stmt = $conn->prepare($daily_query);
    $daily_stmt->bind_param("ss", $start_date, $end_date);
    $daily_stmt->execute();
    $daily_result = $daily_stmt->get_result();
    
    while($row = $daily_result->fetch_assoc()) {
        $daily_stats[] = $row;
    }
}

// ========================================================================== //
$top_users_query = "SELECT 
                    u.uid, u.uname, u.uemail,
                    COUNT(t.transaction_id) as transaction_count,
                    SUM(CASE WHEN t.transaction_type = 'Thu nhập' THEN t.transaction_amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN t.transaction_type = 'Chi tiêu' THEN t.transaction_amount ELSE 0 END) as total_expense
                   FROM users u
                   LEFT JOIN transactions t ON u.uid = t.uid
                   WHERE u.urole = 'user'
                   AND (t.transaction_date BETWEEN ? AND ? OR t.transaction_id IS NULL)
                   GROUP BY u.uid
                   ORDER BY transaction_count DESC
                   LIMIT 10";
                   
$top_users_stmt = $conn->prepare($top_users_query);
$top_users_stmt->bind_param("ss", $start_date, $end_date);
$top_users_stmt->execute();
$top_users = $top_users_stmt->get_result();

// ========================================================================== //
$top_categories_query = "SELECT 
                        c.category_name, c.category_type,
                        COUNT(t.transaction_id) as usage_count,
                        SUM(t.transaction_amount) as total_amount
                       FROM categories c
                       JOIN transactions t ON c.category_id = t.category_id
                       JOIN users u ON t.uid = u.uid
                       WHERE u.urole = 'user'
                       AND t.transaction_date BETWEEN ? AND ?
                       GROUP BY c.category_id
                       ORDER BY usage_count DESC
                       LIMIT 10";
                       
$top_categories_stmt = $conn->prepare($top_categories_query);
$top_categories_stmt->bind_param("ss", $start_date, $end_date);
$top_categories_stmt->execute();
$top_categories = $top_categories_stmt->get_result();

// ========================================================================== //
$registration_stats = [];
$reg_query = "SELECT 
              DATE(u_create_at) as date,
              COUNT(*) as new_users
             FROM users 
             WHERE urole = 'user'
             AND u_create_at BETWEEN ? AND ?
             GROUP BY DATE(u_create_at)
             ORDER BY date";
             
$reg_stmt = $conn->prepare($reg_query);
$reg_stmt->bind_param("ss", $start_date, $end_date);
$reg_stmt->execute();
$reg_result = $reg_stmt->get_result();

while($row = $reg_result->fetch_assoc()) {
    $registration_stats[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Báo Cáo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <link rel="stylesheet" href="../css/ad_home.css">
    <link rel="stylesheet" href="../css/ad_users.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/ad_reports.css">
</head>
<body>
    <?php @include 'admin_sidebar.php'; ?>

    <div class="admin-main">
        <div class="admin-topbar">
            <div class="search-box">
                <i class='bx bx-calendar'></i>
                <span>Báo cáo từ <?php echo date('d/m/Y', strtotime($start_date)); ?> đến <?php echo date('d/m/Y', strtotime($end_date)); ?></span>
            </div>
            <div class="admin-profile">
                <i class='bx bxs-user-circle'></i>
                <span><?php echo $_SESSION['admin_name'] ?? 'Admin'; ?></span>
            </div>
        </div>
        
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label><i class='bx bx-calendar'></i> Từ ngày</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>" required>
                </div>
                
                <div class="filter-group">
                    <label><i class='bx bx-calendar'></i> Đến ngày</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>" required>
                </div>
                
                <div class="filter-group">
                    <label><i class='bx bx-filter'></i> Loại báo cáo</label>
                    <select name="report_type">
                        <option value="daily" <?php echo $report_type == 'daily' ? 'selected' : ''; ?>>Theo ngày</option>
                        <option value="weekly" <?php echo $report_type == 'weekly' ? 'selected' : ''; ?>>Theo tuần</option>
                        <option value="monthly" <?php echo $report_type == 'monthly' ? 'selected' : ''; ?>>Theo tháng</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <div class="filter-actions">
                        <button type="submit" class="btn-generate">
                            <i class='bx bx-refresh'></i> Xem
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="overview-cards">
            <div class="overview-card">
                <h3><i class='bx bxs-user'></i> Tổng Người dùng Hoạt động</h3>
                <div class="overview-value user-value"><?php echo number_format($overview['total_users'] ?? 0, 0, ',', '.'); ?></div>
                <div class="overview-trend">Trong khoảng thời gian đã chọn</div>
            </div>
            
            <div class="overview-card">
                <h3><i class='bx bxs-wallet'></i> Tổng Giao dịch</h3>
                <div class="overview-value transaction-value"><?php echo number_format($overview['total_transactions'] ?? 0, 0, ',', '.'); ?></div>
            </div>
            
            <div class="overview-card">
                <h3><i class='bx bx-trending-up'></i> Tổng Thu nhập</h3>
                <div class="overview-value income-value">+<?php echo number_format($overview['total_income'] ?? 0, 0, ',', '.'); ?> ₫</div>
            </div>
            
            <div class="overview-card">
                <h3><i class='bx bx-trending-down'></i> Tổng Chi tiêu</h3>
                <div class="overview-value expense-value">-<?php echo number_format($overview['total_expense'] ?? 0, 0, ',', '.'); ?> ₫</div>
            </div>
        </div>
        
        <div class="chart-section">
            <div class="chart-card">
                <h3><i class='bx bx-line-chart'></i> Thống kê Giao dịch Theo Ngày</h3>
                <canvas id="dailyChart"></canvas>
            </div>
            
            <div class="chart-card">
                <h3><i class='bx bx-bar-chart-alt'></i> Đăng ký Người dùng Mới</h3>
                <canvas id="registrationChart"></canvas>
            </div>
        </div>
        
        <div class="report-tables">
            <div class="report-table">
                <h3><i class='bx bx-trophy'></i> Top 10 Người dùng Tích cực</h3>
                <div class="table-scroll">
                    <table class="compact-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tên</th>
                                <th>Email</th>
                                <th>Số GD</th>
                                <th>Tổng thu</th>
                                <th>Tổng chi</th>
                                <th>Số dư</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rank = 1; while($user = $top_users->fetch_assoc()): 
                                $balance = ($user['total_income'] ?? 0) - ($user['total_expense'] ?? 0);
                            ?>
                            <tr>
                                <td>
                                    <div class="user-rank"><?php echo $rank; ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($user['uname']); ?></td>
                                <td style="font-size: 12px;"><?php echo htmlspecialchars($user['uemail']); ?></td>
                                <td><?php echo $user['transaction_count'] ?? 0; ?></td>
                                <td style="color: #27ae60; font-weight: 600;">+<?php echo number_format($user['total_income'] ?? 0, 0, ',', '.'); ?> ₫</td>
                                <td style="color: #e74c3c; font-weight: 600;">-<?php echo number_format($user['total_expense'] ?? 0, 0, ',', '.'); ?> ₫</td>
                                <td style="color: <?php echo $balance >= 0 ? '#27ae60' : '#e74c3c'; ?>; font-weight: 700;">
                                    <?php echo number_format($balance, 0, ',', '.'); ?> ₫
                                </td>
                            </tr>
                            <?php $rank++; endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="report-table">
                <h3><i class='bx bx-category'></i> Top 10 Danh mục Phổ biến</h3>
                <div class="table-scroll">
                    <table class="compact-table">
                        <thead>
                            <tr>
                                <th>Danh mục</th>
                                <th>Loại</th>
                                <th>Số lần dùng</th>
                                <th>Tổng tiền</th>
                                <th>Trung bình</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($category = $top_categories->fetch_assoc()): 
                                $avg = $category['usage_count'] > 0 ? $category['total_amount'] / $category['usage_count'] : 0;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                <td>
                                    <span class="type-badge badge-<?php echo $category['category_type'] == 'Thu nhập' ? 'income' : 'expense'; ?>">
                                        <?php echo $category['category_type']; ?>
                                    </span>
                                </td>
                                <td><?php echo $category['usage_count']; ?></td>
                                <td style="color: <?php echo $category['category_type'] == 'Thu nhập' ? '#27ae60' : '#e74c3c'; ?>; font-weight: 600;">
                                    <?php echo $category['category_type'] == 'Thu nhập' ? '+' : '-'; ?>
                                    <?php echo number_format($category['total_amount'], 0, ',', '.'); ?> ₫
                                </td>
                                <td><?php echo number_format($avg, 0, ',', '.'); ?> ₫</td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <?php if($report_type == 'daily' && count($daily_stats) > 0): ?>
        <div class="report-table" style="margin-top: 25px;">
            <h3><i class='bx bx-calendar'></i> Chi tiết Giao dịch Theo Ngày</h3>
            <div class="date-range">Từ <?php echo date('d/m/Y', strtotime($start_date)); ?> đến <?php echo date('d/m/Y', strtotime($end_date)); ?></div>
            <div class="table-scroll">
                <table class="compact-table" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th>Ngày</th>
                            <th>Số giao dịch</th>
                            <th>Tổng thu</th>
                            <th>Tổng chi</th>
                            <th>Chênh lệch</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($daily_stats as $day): 
                            $difference = $day['daily_income'] - $day['daily_expense'];
                        ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($day['date'])); ?></td>
                            <td><?php echo $day['transaction_count']; ?></td>
                            <td style="color: #27ae60; font-weight: 600;">+<?php echo number_format($day['daily_income'], 0, ',', '.'); ?> ₫</td>
                            <td style="color: #e74c3c; font-weight: 600;">-<?php echo number_format($day['daily_expense'], 0, ',', '.'); ?> ₫</td>
                            <td style="color: <?php echo $difference >= 0 ? '#27ae60' : '#e74c3c'; ?>; font-weight: 700;">
                                <?php echo number_format($difference, 0, ',', '.'); ?> ₫
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        const dailyDates = <?php echo json_encode(array_column($daily_stats, 'date')); ?>;
        const dailyIncomes = <?php echo json_encode(array_column($daily_stats, 'daily_income')); ?>;
        const dailyExpenses = <?php echo json_encode(array_column($daily_stats, 'daily_expense')); ?>;
        
        const regDates = <?php echo json_encode(array_column($registration_stats, 'date')); ?>;
        const newUsers = <?php echo json_encode(array_column($registration_stats, 'new_users')); ?>;
        
        const dailyChart = new Chart("dailyChart", {
            type: "bar",
            data: {
                labels: dailyDates.map(date => new Date(date).toLocaleDateString('vi-VN')),
                datasets: [{
                    label: "Thu nhập",
                    data: dailyIncomes,
                    backgroundColor: "rgba(46, 204, 113, 0.7)",
                    borderColor: "rgba(46, 204, 113, 1)",
                    borderWidth: 1
                }, {
                    label: "Chi tiêu",
                    data: dailyExpenses,
                    backgroundColor: "rgba(231, 76, 60, 0.7)",
                    borderColor: "rgba(231, 76, 60, 1)",
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += new Intl.NumberFormat('vi-VN', {
                                    style: 'currency',
                                    currency: 'VND'
                                }).format(context.raw);
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('vi-VN') + ' ₫';
                            }
                        }
                    }
                }
            }
        });
        
        const registrationChart = new Chart("registrationChart", {
            type: "line",
            data: {
                labels: regDates.map(date => new Date(date).toLocaleDateString('vi-VN')),
                datasets: [{
                    label: "Người dùng mới",
                    data: newUsers,
                    borderColor: "#3498db",
                    backgroundColor: "rgba(52, 152, 219, 0.1)",
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
        
        setInterval(function() {
            if(!document.querySelector('input[type="date"]:focus') && !document.querySelector('select:focus')) {
                location.reload();
            }
        }, 300000);
    </script>
</body>
</html>