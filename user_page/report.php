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

// ========================================================================== //
$chart_data = [];

if($filter_period == 'yearly') {
    for($i = 1; $i <= 12; $i++) {
        $query = "SELECT coalesce(sum(case when transaction_type = 'Thu nhập' then transaction_amount else 0 end), 0) as income,
                        coalesce(sum(case when transaction_type = 'Chi Tiêu' then transaction_amount else 0 end), 0) as expense
                from transactions 
                where uid = ? and year(transaction_date) = ? and month(transaction_date) = ?";

        $stmt = $conn->prepare($query);
        if($stmt) {
            $stmt->bind_param("iii", $user_id, $filter_year, $i);
            $stmt->execute();
            $result = $stmt->get_result();
            if($row = $result->fetch_assoc()) {
                $chart_data[] = [
                    'label' => "Tháng $i",
                    'income' => $row['income'],
                    'expense' => $row['expense']
                ];
            }
            $stmt->close();
        }
    }
} elseif ($filter_period == 'monthly') {
    $day_in_month = cal_days_in_month(CAL_GREGORIAN, $filter_month, $filter_year);

    $first_day = new DateTime("$filter_year-$filter_month-01");
    $last_day = new DateTime("$filter_year-$filter_month-$day_in_month");

    $first_week = (int)$first_day->format('W');
    $last_week = (int)$last_day->format('W');

    for($week_num = $first_week; $week_num <= $last_week; $week_num++) {
        $actual_week = $week_num > 53 ? $week_num - 52 : $week_num;

        $week_dates = getWeekDates($filter_year, $actual_week);

        $week_start = new DateTime($week_dates['start']);
        $week_end = new DateTime($week_dates['end']);

        $month_of_week_start = (int)$week_start->format('m');
        $month_of_week_end = (int)$week_end->format('m');

        if($month_of_week_start != $filter_month && $month_of_week_end != $filter_month) {
            continue;
        }

        $query = "SELECT coalesce(sum(case when transaction_type = 'Thu nhập' then transaction_amount else 0 end), 0) as income,
                coalesce(sum(case when transaction_type = 'Chi Tiêu' then transaction_amount else 0 end), 0) as expense
                from transactions 
                where uid = ? and transaction_date >= ? and transaction_date <= ?";
        $stmt = $conn->prepare($query);
        if($stmt) {
            $stmt->bind_param("iss", $user_id,$week_dates['start'], $week_dates['end']);
            $stmt->execute();
            $result = $stmt->get_result();
            if($row = $result->fetch_assoc()) {
                $chart_data[] = [
                    'label' => "Tuần $actual_week",
                    'income' => $row['income'],
                    'expense' => $row['expense']
                ];
            }
            $stmt->close();
        }
    }

    if(empty($chart_data)) {
        $week_in_month = 4;
        $day_per_week = ceil($day_in_month / $week_in_month);

        for($week = 1; $week <= $week_in_month; $week++) {
            $start_day = ($week - 1) * $day_per_week + 1;
            $end_day = min($week * $day_per_week, $day_in_month);

            $start_date = sprintf("%04d-%02d-%02d", $filter_year, $filter_month, $start_day);
            $end_date = sprintf("%04d-%02d-%02d", $filter_year, $filter_month, $end_day);

            $query = "SELECT coalesce(sum(case when transaction_type = 'Thu nhập' then transaction_amount else 0 end), 0) as income,
                coalesce(sum(case when transaction_type = 'Chi Tiêu' then transaction_amount else 0 end), 0) as expense
                from transactions 
                where uid = ? and transaction_date >= ? and transaction_date <= ?";

            $stmt = $conn->prepare($query);
            if($stmt) {
                $stmt->bind_param("iss", $user_id, $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                if($row = $result->fetch_assoc()) { 
                    $chart_data[] = [
                        'label' => "Tuần $week",
                        'income' => $row['income'],
                        'expense' => $row['expense']
                    ];
                }
                $stmt->close();
            }
        }
    }
} elseif ($filter_period == 'weekly') {
    $week_dates = getWeekDates($filter_year, $filter_week);
    $start_date = new DateTime($week_dates['start']);

    for($day = 0; $day < 7; $day++) {
        $current_date = clone $start_date;
        $current_date->modify("+$day days");
        $data_str = $current_date->format('Y-m-d');
        $data_label = $current_date->format('d/m');

        $query = "SELECT coalesce(sum(case when transaction_type = 'Thu nhập' then transaction_amount else 0 end), 0) as income,
                coalesce(sum(case when transaction_type = 'Chi Tiêu' then transaction_amount else 0 end), 0) as expense
                from transactions 
                where uid = ? and transaction_date = ?";
        $stmt = $conn->prepare($query);
        if($stmt) {
            $stmt->bind_param("is", $user_id, $data_str);
            $stmt->execute();
            $result = $stmt->get_result();
            if($row = $result->fetch_assoc()) {
                $chart_data[] = [
                    'label' => "$data_label",
                    'income' => $row['income'],
                    'expense' => $row['expense']
                ];
            } else {
                $chart_data[] = [
                    'label' => "$data_label",
                    'income' => 0,
                    'expense' => 0
                ];
            }
            $stmt->close();
        }
    }
}

// ========================================================================== //
$category_data = [];
$sql_categories = "SELECT c.category_name, 
                   COALESCE(SUM(t.transaction_amount), 0) as total
                   FROM categories c
                   LEFT JOIN transactions t ON c.category_id = t.category_id
                   WHERE t.uid = ? 
                   AND t.transaction_type = 'Chi tiêu'
                   AND YEAR(t.transaction_date) = ?
                   GROUP BY c.category_id
                   ORDER BY total DESC
                   LIMIT 8";

$stmt = $conn->prepare($sql_categories);
if ($stmt) {
    $stmt->bind_param("ii", $user_id, $filter_year);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $category_data[] = $row;
    }
    $stmt->close();
}

// ========================================================================== //
$income_category_data = [];
$sql_categories = "SELECT c.category_name, 
                   COALESCE(SUM(t.transaction_amount), 0) as total
                   FROM categories c
                   LEFT JOIN transactions t ON c.category_id = t.category_id
                   WHERE t.uid = ? 
                   AND t.transaction_type = 'Thu nhập'
                   AND YEAR(t.transaction_date) = ?
                   GROUP BY c.category_id
                   ORDER BY total DESC
                   LIMIT 8";

$stmt = $conn->prepare($sql_categories);
if ($stmt) {
    $stmt->bind_param("ii", $user_id, $filter_year);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $income_category_data[] = $row;
    }
    $stmt->close();
}
// ========================================================================== //
$top_categories = [];
$sql_top = "SELECT c.category_name, c.category_icon,
                   SUM(t.transaction_amount) as total,
                   COUNT(t.transaction_id) as count
            FROM categories c
            JOIN transactions t ON c.category_id = t.category_id
            WHERE t.uid = ? 
            AND t.transaction_type = 'Chi tiêu'
            AND YEAR(t.transaction_date) = ?
            GROUP BY c.category_id
            ORDER BY total DESC
            LIMIT 10";

$stmt = $conn->prepare($sql_top);
if ($stmt) {
    $stmt->bind_param("ii", $user_id, $filter_year);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $top_categories[] = $row;
    }
    $stmt->close();
}

// ========================================================================== //
$top_categories_income = [];
$sql_top = "SELECT c.category_name, c.category_icon,
                   SUM(t.transaction_amount) as total,
                   COUNT(t.transaction_id) as count
            FROM categories c
            JOIN transactions t ON c.category_id = t.category_id
            WHERE t.uid = ? 
            AND t.transaction_type = 'Thu nhập'
            AND YEAR(t.transaction_date) = ?
            GROUP BY c.category_id
            ORDER BY total DESC
            LIMIT 10";

$stmt = $conn->prepare($sql_top);
if ($stmt) {
    $stmt->bind_param("ii", $user_id, $filter_year);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $top_categories_income[] = $row;
    }
    $stmt->close();
}

// ========================================================================== //
$category_stats = [];
$sql_stats = "SELECT c.category_name, c.category_type, c.category_icon,
                     SUM(CASE WHEN t.transaction_type = 'Thu nhập' THEN t.transaction_amount ELSE 0 END) as total_income,
                     SUM(CASE WHEN t.transaction_type = 'Chi tiêu' THEN t.transaction_amount ELSE 0 END) as total_expense
              FROM categories c
              LEFT JOIN transactions t ON c.category_id = t.category_id AND t.uid = ?
              WHERE c.uid = ? OR c.is_system = 1
              GROUP BY c.category_id
              ORDER BY (total_income + total_expense) DESC";

$stmt = $conn->prepare($sql_stats);
if ($stmt) {
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $category_stats[] = $row;
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
    <title>Báo Cáo Thống Kê</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/report.css">    
</head>
<body>
    <?php @include 'sidebar.php' ?>

    <?php if($message): ?>
        <div class="message-overlay">
            <div class="message-box">
                <span><?php echo $message; ?></span>
                <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
            </div>
        </div>
    <?php endif; ?>

    <div class="main-container">
        <div class="report-container">
            <div class="report-header">
                <h1><i class='bx bxs-report'></i> BÁO CÁO THỐNG KÊ</h1>
        
                <div class="header-info">
                    <span class="header-text">
                        <?php if($filter_period == 'yearly') {
                                echo "Năm $filter_year";
                            } elseif ($filter_period == 'monthly') {
                                echo "Tháng $filter_month/$filter_year";
                            } else {
                                echo "Tuần $filter_week, Năm $filter_year";
                            }
                        ?>
                    </span>
                </div>
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
        <div class="table-section">
            <div class="table-container">
                <div class="table-header">
                    <h3><i class='bx bx-trophy'></i> Top Danh Mục Chi Tiêu Nhiều Nhất</h3>
                </div>
                <div class="table-responsive">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Danh mục</th>
                                <th>Tổng chi</th>
                                <th>Số giao dịch</th>
                                <th>Tỷ lệ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_all = array_sum(array_column($top_categories, 'total'));
                            $counter = 1;
                            foreach($top_categories as $cat):
                                $percentage = $total_all > 0 ? ($cat['total'] / $total_all * 100) : 0;
                            ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td>
                                    <div class="category-cell">
                                        <i class='<?= $cat['category_icon'] ?>'></i>
                                        <span><?= htmlspecialchars($cat['category_name']) ?></span>
                                    </div>
                                </td>
                                <td class="amount-expense">-<?= number_format($cat['total'], 0, ',', '.') ?> ₫</td>
                                <td><?= $cat['count'] ?? 0 ?></td>
                                <td>
                                    <div class="progress-bar">
                                        <div class="progress-fill expense" style="width: <?= $percentage ?>%"></div>
                                        <span><?= number_format($percentage, 1) ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="table-container">
                <div class="table-header">
                    <h3><i class='bx bx-trophy'></i> Top Danh Mục Thu Nhập Nhiều Nhất</h3>
                </div>
                <div class="table-responsive">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Danh mục</th>
                                <th>Tổng thu</th>
                                <th>Số giao dịch</th>
                                <th>Tỷ lệ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_all = array_sum(array_column($top_categories_income, 'total'));
                            $counter = 1;
                            foreach($top_categories_income as $cat):
                                $percentage = $total_all > 0 ? ($cat['total'] / $total_all * 100) : 0;
                            ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td>
                                    <div class="category-cell">
                                        <i class='<?= $cat['category_icon'] ?>'></i>
                                        <span><?= htmlspecialchars($cat['category_name']) ?></span>
                                    </div>
                                </td>
                                <td class="amount-income">+<?= number_format($cat['total'], 0, ',', '.') ?> ₫</td>
                                <td><?= $cat['count'] ?? 0 ?></td>
                                <td>
                                    <div class="progress-bar">
                                        <div class="progress-fill income" style="width: <?= $percentage ?>%"></div>
                                        <span><?= number_format($percentage, 1) ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="chart-section">
            <div class="chart-container">
                <div class="chart-header">
                    <h3><i class='bx bx-bar-chart-alt'></i> Thu Nhập & Chi Tiêu</h3>
                    <div class="chart-period">
                        <?php
                            $chartTitle = "";
                            if($filter_period == 'yearly') $chartTitle = "Theo Tháng";
                            elseif($filter_period == 'monthly') $chartTitle = "Theo Tuần";
                            else $chartTitle = "Theo Ngày";
                            echo $chartTitle;
                        ?>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="incomeExpenseChart" style="width:100%; height:300px"></canvas>
                </div>
            </div>
            <div class="chart-container">
                <div class="chart-header">
                    <h3><i class='bx bx-pie-chart-alt'></i> Phân Bổ Chi Tiêu</h3>
                    <div class="chart-period">Theo Danh Mục</div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="categoryPieChart" style="width:100%; height:500px"></canvas>                
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-header">
                    <h3><i class='bx bx-pie-chart-alt'></i> Phân Bổ Thu Nhập</h3>
                    <div class="chart-period">Theo Danh Mục</div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="categoryPieChartIncome" style="width:100%; height:500px"></canvas>                
                </div>
            </div>
        </div>
        <div class="export-section">
            <h3><i class='bx bx-export'></i> Xuất Báo Cáo</h3>
            <p>Xuất báo cáo dưới nhiều định dạng để lưu trữ hoặc chia sẻ.</p>
            <div class="export-buttons">
                <button class="export-btn" onclick="exportPDF()">
                    <i class='bx bxs-file-pdf'></i> Xuất PDF
                </button>
                <button class="export-btn excel" onclick="exportExcel()">
                    <i class='bx bxs-file-export'></i> Xuất Excel
                </button>
                <button class="export-btn" onclick="printReport()">
                    <i class='bx bx-printer'></i> In Báo Cáo
                </button>
            </div>
        </div>
    </div>

    <script>
        const incomeExpenseChart = new Chart("incomeExpenseChart", {
            type: "bar",
            data: {
                labels: [<?php 
                    $labels = [];
                    foreach($chart_data as $item) {
                        $labels[] = "'" . $item['label'] . "'";
                    }
                    echo implode(',', $labels);
                ?>],
                datasets: [{
                    label: "Thu nhập",
                    data: [<?php 
                        echo implode(',', array_column($chart_data, 'income'));
                    ?>],
                    backgroundColor: "rgba(46, 204, 113, 0.7)",
                    borderColor: "rgba(46, 204, 113, 1)",
                    borderWidth: 1
                }, {
                    label: "Chi tiêu",
                    data: [<?php 
                        echo implode(',', array_column($chart_data, 'expense'));
                    ?>],
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

        const categoryPieChart = new Chart("categoryPieChart", {
            type: "pie",
            data: {
                labels: [<?php 
                    $catLabels = [];
                    foreach($category_data as $cat) {
                        $catLabels[] = "'" . htmlspecialchars($cat['category_name']) . "'";
                    }
                    echo implode(',', $catLabels);
                ?>],
                datasets: [{
                    data: [<?php 
                        echo implode(',', array_column($category_data, 'total'));
                    ?>],
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                        '#FF9F40', '#FF6384', '#C9CBCF'
                    ],
                    borderWidth: 1
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
                        size: 20  
                    }
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
                                const total = <?= array_sum(array_column($category_data, 'total')) ?>;
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                
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
        
        const categoryPieChartIncome = new Chart("categoryPieChartIncome", {
            type: "pie",
            data: {
                labels: [<?php 
                    $catLabels = [];
                    foreach($income_category_data as $cat) {
                        $catLabels[] = "'" . htmlspecialchars($cat['category_name']) . "'";
                    }
                    echo implode(',', $catLabels);
                ?>],
                datasets: [{
                    data: [<?php 
                        echo implode(',', array_column($income_category_data, 'total'));
                    ?>],
                    backgroundColor: [
                        '#6394ffff', '#c1eb36ff', '#6456ffff', '#4BC0C0', '#9966FF',
                        '#8c40ffff', '#63ff80ff', '#368f4bff'
                    ],
                    borderWidth: 1
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
                        size: 20
                    }
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
                                const total = <?= array_sum(array_column($category_data, 'total')) ?>;
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                
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
            
            let url = `report.php?period=${period}&year=${year}&type=${type}`;
            
            if (period === 'monthly') {
                url += `&month=${month}`;
            } else if (period === 'weekly') {
                url += `&week=${week}`;
            }
            
            window.location.href = url;
        }

        function exportPDF() {
            alert('Chọn "Print" và trong máy in, chọn "Save as PDF" để xuất file PDF.');
            window.print();
        }

        function exportExcel() {
            const data = [
                ['BÁO CÁO TÀI CHÍNH'],
                ['Ngày xuất:', new Date().toLocaleDateString('vi-VN')],
                ['Tổng thu:', '<?= number_format($total_income, 0, ",", ".") ?> ₫'],
                ['Tổng chi:', '<?= number_format($total_expense, 0, ",", ".") ?> ₫'],
                ['Số dư:', '<?= number_format($balance, 0, ",", ".") ?> ₫'],
                ['Tổng giao dịch:', '<?= $total_transactions ?>'],
                [''],
                ['TOP DANH MỤC CHI TIÊU'],
                ['Danh mục', 'Tổng chi', 'Số giao dịch']
            ];
            
            <?php foreach($top_categories as $cat): ?>
                data.push([
                    '<?= htmlspecialchars($cat["category_name"]) ?>',
                    '<?= number_format($cat["total"], 0, ",", ".") ?>',
                    '<?= $cat["count"] ?? 0 ?>'
                ]);
            <?php endforeach; ?>

            <?php foreach($top_categories_income as $cat): ?>
                data.push([
                    '<?= htmlspecialchars($cat["category_name"]) ?>',
                    '<?= number_format($cat["total"], 0, ",", ".") ?>',
                    '<?= $cat["count"] ?? 0 ?>'
                ]);
            <?php endforeach; ?>
            
            const csvContent = data.map(row => 
                row.map(cell => `"${cell}"`).join(',')
            ).join('\n');
            
            const blob = new Blob(['\uFEFF' + csvContent], { 
                type: 'text/csv;charset=utf-8;' 
            });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `bao_cao_tai_chinh_<?= date('Y-m-d') ?>.csv`;
            link.click();
            
            alert('Đã xuất file Excel thành công!');
        }

        function printReport() {
            window.print();
        }
    </script>
</body>
</html>