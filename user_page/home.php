<?php

@include '../config.php';
session_start();

if(!isset($_SESSION['user_id'])){
    header('location:../account/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

$current_year = date('Y');
$current_month = date('m');
$current_week = date('W');

$filter_year = $_GET['year'] ?? $current_year;
$filter_month = $_GET['month'] ?? $current_month;
$filter_week = $_GET['week'] ?? $current_week;
$filter_type = $_GET['type'] ?? 'all';
$filter_period = $_GET['period'] ?? 'yearly';

$filter_year = intval($filter_year);
$filter_month = max(1, min(12, intval($filter_month)));
$filter_week = max(1, min(53, intval($filter_week)));

function getWeekDates($year, $week) {
    $date = new DateTime();
    $date->setISODate($year, $week);
    $start = $date->format('Y-m-d');
    $date->modify('+6 days');
    $end = $date->format('Y-m-d');
    return['start' => $start, 'end' => $end];
}

// ========================================================================== //
$total_income = 0;
$total_expense = 0;
$balance = 0;
$total_transactions = 0;

$where_clause ="uid = ?";
$params = [$user_id];
$param_types = "i";

if($filter_period == 'yearly') {
    $where_clause .= " AND year(transaction_date) = ?";
    $params[] = $filter_year;
    $param_types .= "i";
} elseif ($filter_period == 'monthly') {
    $where_clause .= " AND year(transaction_date) = ? and month(transaction_date) = ?";
    $params[] = $filter_year;
    $params[] = $filter_month;
    $param_types .= "ii";
} elseif ($filter_period == 'weekly') {
    $week_dates = getWeekDates($filter_year, $filter_week);
    $where_clause .= " AND transaction_date >= ? and transaction_date <= ?";
    $params[] = $week_dates['start'];
    $params[] = $week_dates['end'];
    $param_types .= "ss";
}

// ========================================================================== //
$recent_transactions = [];
$sql_recent = "SELECT t.*, c.category_name, c.category_icon
               FROM transactions t
               JOIN categories c ON t.category_id = c.category_id
               WHERE t.uid = ? 
               ORDER BY t.transaction_date DESC
               LIMIT 20";

$stmt = $conn->prepare($sql_recent);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $recent_transactions[] = $row;
    }
    $stmt->close();
}

// ========================================================================== //
$total_query = "SELECT sum(case when transaction_type = 'Thu nhập' then transaction_amount else 0 end) as total_income,
                        sum(case when transaction_type = 'Chi Tiêu' then transaction_amount else 0 end) as total_expense,
                        count(*) as total_amount
                from transactions where $where_clause";

$stmt = $conn->prepare($total_query);
if($stmt) {
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row  =$result->fetch_assoc()) {
        $total_income = $row['total_income'] ?? 0;
        $total_expense = $row['total_expense'] ?? 0;
        $total_transactions  =$row['total_amount'] ?? 0;
        $balance = $total_income - $total_expense;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Trang Chủ</title>

   <!-- font awesome cdn link  -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
   <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/main.css">
   <link rel="stylesheet" href="../css/sidebar.css">
   <link rel="stylesheet" href="../css/home.css">
</head>
<body>
    <?php @include 'sidebar.php'; ?>
    <div class="main-container">       
        <div class="report-container">
            <div class="report-header">
                <h1><i class='bx bx-dollar-circle'></i> MONEE</h1>
            </div>

            <div class="header-content">
                <div class="filter-section">
                    <div class="filter-group">
                        <label><i class='bx bx-calendar'></i> Phạm vi:</label>
                        <select id="periodSelect" onchange="changePeriod()">
                            <option value="yearly" <?= $filter_period == 'yearly' ? 'selected' : '' ?>>Theo năm</option>
                            <option value="monthly" <?= $filter_period == 'monthly' ? 'selected' : '' ?>>Theo tháng</option>
                            <option value="weekly" <?= $filter_period == 'weekly' ? 'selected' : '' ?>>Theo tuần</option>
                        </select>
                    </div>
                    <div class="filter-group" id="yearGroup">
                        <label><i class='bx bx-calendar'></i> Năm:</label>
                        <select id="yearSelect">
                            <?php for($y = 2020; $y <= $current_year; $y++): ?>
                                <option value="<?= $y ?>" <?= $y == $filter_year ? 'selected' : '' ?>>
                                    Năm <?= $y ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="filter-group" id="monthGroup" style="display: <?= $filter_period == 'monthly' ? 'flex' : 'none' ?>">
                        <label><i class='bx bx-calendar'></i> Tháng:</label>
                        <select id="monthSelect">
                            <?php for($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $m == $filter_month ? 'selected' : '' ?>>
                                    Tháng <?= $m ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="filter-group" id="weekGroup" style="display: <?= $filter_period == 'weekly' ? 'flex' : 'none' ?>">
                        <label><i class='bx bx-calendar'></i> Tuần:</label>
                        <select id="weekSelect">
                            <?php for($w = 1; $w <= 52; $w++):
                                $weekDates = getWeekDates($filter_year, $w);
                                $startDate = DateTime::createFromFormat('Y-m-d', $weekDates['start']);
                                $endDate = DateTime::createFromFormat('Y-m-d', $weekDates['end']);
                
                                $startFormatted = $startDate->format('d/m');
                                $endFormatted = $endDate->format('d/m');
                            ?>
                            <option value="<?= $w ?>" <?= $w == $filter_week ? 'selected' : '' ?>>
                                Tuần <?= $w ?> (<?= $startFormatted ?>-<?= $endFormatted ?>)
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class='bx bx-filter'></i> Loại:</label>
                        <select id="typeSelect">
                            <option value="all" <?= $filter_type == 'all' ? 'selected' : '' ?>>Tất cả</option>
                            <option value="income" <?= $filter_type == 'income' ? 'selected' : '' ?>>Thu nhập</option>
                            <option value="expense" <?= $filter_type == 'expense' ? 'selected' : '' ?>>Chi tiêu</option>
                        </select>
                    </div>
                
                    <button class="filter-btn" onclick="applyFilters()">
                        <i class='bx bx-refresh'></i> Áp dụng
                    </button>
                </div>
                <div class="summary-cards">
                    <div class="summary-card card-income">
                        <div class="card-icon">
                            <i class='bx bx-trending-up'></i>
                        </div>
                        <div class="card-content">
                            <h3>Tổng Thu</h3>
                            <div class="amount income">+<?= number_format($total_income, 0, ',', '.') ?> ₫</div>
                        </div>
                    </div>
                    <div class="summary-card card-expense">
                        <div class="card-icon">
                            <i class='bx bx-trending-down'></i>
                        </div>
                        <div class="card-content">
                            <h3>Tổng Chi</h3>
                            <div class="amount expense">-<?= number_format($total_expense, 0, ',', '.') ?> ₫</div>
                        </div>
                    </div>
                    <div class="summary-card card-balance">
                        <div class="card-icon">
                            <i class='bx bx-wallet'></i>
                        </div>
                        <div class="card-content">
                            <h3>Số Dư</h3>
                            <div class="amount balance" style="color: <?= $balance >= 0 ? '#27ae60' : '#e74c3c' ?>">
                                <?= number_format($balance, 0, ',', '.') ?> ₫
                            </div>
                        </div>
                    </div>
                    <div class="summary-card card-total">
                        <div class="card-icon">
                            <i class='bx bx-line-chart'></i>
                        </div>
                        <div class="card-content">
                            <h3>Tổng Giao Dịch</h3>
                            <div class="amount total"><?= number_format($total_transactions, 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tables-section">
            <div class="table-container">
                <div class="table-header">
                    <h3><i class='bx bx-list-ul'></i> Giao Dịch Gần Đây</h3>
                </div>
                <div class="table-responsive">
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Danh mục</th>
                                <th>Mô tả</th>
                                <th>Số tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_transactions as $trans): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($trans['transaction_date'])) ?></td>
                                <td>
                                    <div class="category-cell">
                                        <i class='<?= $trans['category_icon'] ?>'></i>
                                        <span><?= htmlspecialchars($trans['category_name']) ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($trans['transaction_note'] ?: 'Không có mô tả') ?></td>
                                <td class="<?= $trans['transaction_type'] == 'Thu nhập' ? 'amount-income' : 'amount-expense' ?>">
                                        <?= $trans['transaction_type'] == 'Thu nhập' ? '+' : '-' ?>
                                        <?= number_format($trans['transaction_amount'], 0, ',', '.') ?> ₫
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    
    </div>

    <script>
        function changePeriod() {
            const period = document.getElementById('periodSelect').value;
            document.getElementById('monthGroup').style.display = period === 'monthly' ? 'flex' : 'none';
            document.getElementById('weekGroup').style.display = period === 'weekly' ? 'flex' : 'none';
        }

        function applyFilters() {
            const period = document.getElementById('periodSelect').value;
            const year = document.getElementById('yearSelect').value;
            const month = document.getElementById('monthSelect').value;
            const week = document.getElementById('weekSelect').value;
            const type = document.getElementById('typeSelect').value;
            
            let url = `home.php?period=${period}&year=${year}&type=${type}`;
            
            if (period === 'monthly') {
                url += `&month=${month}`;
            } else if (period === 'weekly') {
                url += `&week=${week}`;
            }
            
            window.location.href = url;
        }
    </script>
</body>
</html>