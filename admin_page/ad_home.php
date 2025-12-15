<?php
@include '../config.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('location:../account/login.php');
    exit;
}

// ================================================================= //
$total_users = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE urole = 'user'");
$stmt->execute();
$result = $stmt->get_result();
if($row = $result->fetch_assoc()) {
    $total_users = $row['total'];
}

// ================================================================= //
$total_transactions = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM transactions");
$stmt->execute();
$result = $stmt->get_result();
if($row = $result->fetch_assoc()) {
    $total_transactions = $row['total'];
}

// ================================================================= //
$total_amount = 0;
$stmt = $conn->prepare("SELECT SUM(transaction_amount) as total FROM transactions");
$stmt->execute();
$result = $stmt->get_result();
if($row = $result->fetch_assoc()) {
    $total_amount = $row['total'] ?? 0;
}

// ================================================================= //
$today_activity = 0;
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM transactions WHERE DATE(transaction_created_at) = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
if($row = $result->fetch_assoc()) {
    $today_activity = $row['total'];
}

// ================================================================= //
function getCategoryAmountDistribution($conn, $type, $limit = 8) {
    $stmt = $conn->prepare("SELECT c.category_name, 
                           COALESCE(SUM(t.transaction_amount), 0) as total_amount,
                           COUNT(t.transaction_id) as count
                           FROM categories c
                           LEFT JOIN transactions t ON c.category_id = t.category_id 
                           AND t.transaction_type = ?
                           WHERE c.category_type = ?
                           GROUP BY c.category_id
                           ORDER BY total_amount DESC");
    $stmt->bind_param("ss", $type, $type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $all_categories = [];
    while($row = $result->fetch_assoc()) {
        $all_categories[] = $row;
    }
    
    // Chỉ lấy số lượng giới hạn và gộp phần còn lại
    $top_categories = array_slice($all_categories, 0, $limit);
    $other_amount = 0;
    $other_count = 0;
    
    if(count($all_categories) > $limit) {
        $other_categories = array_slice($all_categories, $limit);
        foreach($other_categories as $cat) {
            $other_amount += $cat['total_amount'];
            $other_count += $cat['count'];
        }
        
        if($other_amount > 0) {
            $top_categories[] = [
                'category_name' => 'Khác (' . (count($all_categories) - $limit) . ' danh mục)',
                'total_amount' => $other_amount,
                'count' => $other_count
            ];
        }
    }
    
    return $top_categories;
}

$income_category_data = getCategoryAmountDistribution($conn, 'Thu nhập', 8);
$expense_category_data = getCategoryAmountDistribution($conn, 'Chi tiêu', 8);

$total_income_amount = array_sum(array_column($income_category_data, 'total_amount'));
$total_expense_amount = array_sum(array_column($expense_category_data, 'total_amount'));

// ================================================================= //
$recent_activities = [];
$stmt = $conn->prepare("SELECT u.uname, t.*, c.category_name 
                       FROM transactions t 
                       JOIN users u ON t.uid = u.uid 
                       JOIN categories c ON t.category_id = c.category_id 
                       ORDER BY t.transaction_created_at DESC 
                       LIMIT 10");
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    $recent_activities[] = $row;
}

// ================================================================= //
$top_users = [];
$stmt = $conn->prepare("SELECT u.uid, u.uname, COUNT(t.transaction_id) as transaction_count, 
                               SUM(t.transaction_amount) as total_amount
                       FROM users u 
                       LEFT JOIN transactions t ON u.uid = t.uid 
                       WHERE u.urole = 'user'
                       GROUP BY u.uid 
                       ORDER BY transaction_count DESC 
                       LIMIT 5");
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    $top_users[] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Chủ Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../css/ad_home.css">
    <link rel="stylesheet" href="../css/sidebar.css">
</head>
<body>
    <?php @include 'admin_sidebar.php' ?>

    <div class="admin-main">
        <div class="admin-topbar">
            <div class="search-box">
                <i class='bx bx-search'></i>
                <input type="text" placeholder="Tìm kiếm người dùng, giao dịch...">
            </div>

            <div class="admin-profile">
                <i class='bx bxs-user-circle' style="font-size: 40px; color: #3498db;"></i>
                <span><?php echo $_SESSION['admin_name'] ?? 'Quản Trị Viên'; ?></span>
            </div>
        </div>

        <div class="admin-cards">
            <div class="admin-card">
                <div class="card-icon">
                    <i class='bx bxs-user'></i>
                </div>
                <div class="card-content">
                    <h3>Tổng Người Dùng</h3>
                    <div class="number"><?php echo number_format($total_users, 0, ',', '.'); ?></div>
                    <div class="trend up">Người dùng hệ thống</div>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-icon">
                    <i class='bx bxs-wallet'></i>
                </div>
                <div class="card-content">
                    <h3>Tổng Giao Dịch</h3>
                    <div class="number"><?php echo number_format($total_transactions, 0, ',', '.'); ?></div>
                    <div class="trend up">Giao dịch toàn hệ thống</div>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-icon">
                    <i class='bx bxs-dollar-circle'></i>
                </div>
                <div class="card-content">
                    <h3>Tổng Tiền Giao Dịch</h3>
                    <div class="number"><?php echo number_format($total_amount, 0, ',', '.'); ?> ₫</div>
                    <div class="trend down">Tổng thu/chi hệ thống</div>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-icon">
                    <i class='bx bxs-bar-chart-alt-2'></i>
                </div>
                <div class="card-content">
                    <h3>Hoạt Động Hôm Nay</h3>
                    <div class="number"><?php echo number_format($today_activity, 0, ',', '.'); ?></div>
                    <div class="trend up">Giao dịch mới hôm nay</div>
                </div>
            </div>
        </div>

        <div class="top-users">
            <h3><i class='bx bx-trophy'></i> Top Người Dùng Tích Cực</h3>

            <div class="users-list">
                <?php $rank = 1; foreach($top_users as $user): ?>
                <div class="user-item">
                    <div class="user-rank">#<?php echo $rank; $rank++; ?></div>
                    <i class='bx bxs-user-circle' style="font-size: 40px; color: #3498db;"></i>
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars($user['uname']); ?></h4>
                        <p><?php echo $user['transaction_count'] ?? 0; ?> giao dịch</p>
                    </div>
                    <div class="user-amount"><?php echo number_format($user['total_amount'] ?? 0, 0, ',', '.'); ?> ₫</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="tables-section">
            <div class="table-container">
                <div class="table-header">
                    <h3><i class='bx bx-time'></i> Giao Dịch Gần Đây</h3>
                </div>
                <div class="table-responesive">
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th>Thời Gian</th>
                                <th>Người Dùng</th>
                                <th>Loại</th>
                                <th>Danh Mục</th>
                                <th>Số Tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_activities as $activity): ?>
                            <tr>
                                <td><?php echo date('H:i d/m/Y', strtotime($activity['transaction_created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($activity['uname']); ?></td>
                                <td>
                                    <span class="badge <?php echo $activity['transaction_type'] == 'Thu nhập' ? 'success' : 'warning'; ?>">
                                        <?php echo $activity['transaction_type']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($activity['category_name']); ?></td>
                                <td style="color: <?php echo $activity['transaction_type'] == 'Thu nhập' ? '#27ae60' : '#e74c3c'; ?>; font-weight: bold;">
                                    <?php echo $activity['transaction_type'] == 'Thu nhập' ? '+' : '-'; ?>
                                    <?php echo number_format($activity['transaction_amount'], 0, ',', '.'); ?> ₫
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="admin-charts">
            <div class="chart-container">
                <div class="chart-header">
                    <h3><i class='bx bx-pie-chart-alt'></i> Phân Bổ Thu Nhập</h3>
                    <div class="chart-total">Theo Số Tiền</div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="categoryPieChartIncome"></canvas>                
                </div>
                <div class="chart-summary" id="incomeChartSummary">
                    <div class="total">Tổng: <?php echo number_format($total_income_amount, 0, ',', '.'); ?> ₫</div>
                    <div class="other-info">Hiển thị <?php echo count($income_category_data); ?> danh mục thu nhập</div>
                </div>
            </div>
            
            <div class="chart-container">
                <div class="chart-header">
                    <h3><i class='bx bx-pie-chart-alt'></i> Phân Bổ Chi Tiêu</h3>
                    <div class="chart-total">Theo Số Tiền</div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="categoryPieChartExpense"></canvas>
                </div>
                <div class="chart-summary" id="expenseChartSummary">
                    <div class="total">Tổng: <?php echo number_format($total_expense_amount, 0, ',', '.'); ?> ₫</div>
                    <div class="other-info">Hiển thị <?php echo count($expense_category_data); ?> danh mục chi tiêu</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const incomeAmountData = <?php echo json_encode(array_column($income_category_data, 'total_amount')); ?>;
        const incomeAmountLabels = <?php echo json_encode(array_column($income_category_data, 'category_name')); ?>;
        const totalIncomeAmount = <?php echo $total_income_amount; ?>;

        const expenseAmountData = <?php echo json_encode(array_column($expense_category_data, 'total_amount')); ?>;
        const expenseAmountLabels = <?php echo json_encode(array_column($expense_category_data, 'category_name')); ?>;
        const totalExpenseAmount = <?php echo $total_expense_amount; ?>;

        const chartColors = [
            "#6394ff", "#c1eb36", "#6456ff", "#4BC0C0", "#9966FF",
            "#8c40ff", "#63ff80", "#368f4b", "#FF6384", "#36A2EB",
            "#FFCE56", "#FF9F40"
        ];

        const categoryPieChartIncome = new Chart("categoryPieChartIncome", {
            type: "pie",
            data: {
                labels: incomeAmountLabels,
                datasets: [{
                    data: incomeAmountData,
                    backgroundColor: chartColors.slice(0, incomeAmountLabels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 20, 
                            font: {
                                size: 12
                            },
                            padding: 20
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                const value = context.raw;
                                const percentage = totalIncomeAmount > 0 ? ((value / totalIncomeAmount) * 100).toFixed(1) : 0;
                                
                                label += new Intl.NumberFormat('vi-VN', {
                                    style: 'currency',
                                    currency: 'VND'
                                }).format(value);
                                label += ` (${percentage}%)`;
                                return label;
                            }
                        }
                    }
                }
            }
        });

        const categoryPieChartExpense = new Chart("categoryPieChartExpense", {
            type: "pie",
            data: {
                labels: expenseAmountLabels,
                datasets: [{
                    data: expenseAmountData,
                    backgroundColor: chartColors.slice(0, expenseAmountLabels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 20, 
                            font: {
                                size: 12
                            },
                            padding: 20
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                const value = context.raw;
                                const percentage = totalExpenseAmount > 0 ? ((value / totalExpenseAmount) * 100).toFixed(1) : 0;
                                
                                label += new Intl.NumberFormat('vi-VN', {
                                    style: 'currency',
                                    currency: 'VND'
                                }).format(value);
                                label += ` (${percentage}%)`;
                                return label;
                            }
                        }
                    }
                }
            }
        });

        document.querySelector('.search-box input').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value.trim();
                if (searchTerm) {
                    window.location.href = `search.php?q=${encodeURIComponent(searchTerm)}`;
                }
            }
        });

        setInterval(function() {
            fetch('get_stats.php')
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        // Cập nhật các card
                        document.querySelectorAll('.admin-card .number')[0].textContent = 
                            new Intl.NumberFormat('vi-VN').format(data.total_users);
                        document.querySelectorAll('.admin-card .number')[1].textContent = 
                            new Intl.NumberFormat('vi-VN').format(data.total_transactions);
                        document.querySelectorAll('.admin-card .number')[2].textContent = 
                            new Intl.NumberFormat('vi-VN').format(data.total_amount) + ' ₫';
                        document.querySelectorAll('.admin-card .number')[3].textContent = 
                            new Intl.NumberFormat('vi-VN').format(data.today_activity);
                    }
                })
                .catch(error => console.error('Error:', error));
        }, 60000);
        
        window.addEventListener('resize', function() {
            categoryPieChartIncome.resize();
            categoryPieChartExpense.resize();
        });
    </script>
</body>
</html>