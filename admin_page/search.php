<?php
@include '../config.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('location:../account/login.php');
    exit;
}

// ======================================================================= //
$search_query = $_GET['q'] ?? '';
$search_type = $_GET['type'] ?? 'all';

if(empty($search_query)) {
    header('location:ad_home.php');
    exit;
}

$results = [
    'users' => [],
    'transactions' => [],
    'categories' => []
];

// ======================================================================= //
if($search_type == 'all' || $search_type == 'users') {
    $user_query = "SELECT u.*, ud.uphone, ud.uimage 
                   FROM users u 
                   LEFT JOIN users_detail ud ON u.uid = ud.uid 
                   WHERE u.urole = 'user' 
                   AND (u.uname LIKE ? OR u.uemail LIKE ? OR ud.uphone LIKE ?)
                   ORDER BY u.uname 
                   LIMIT 20";
    
    $search_term = "%{$search_query}%";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("sss", $search_term, $search_term, $search_term);
    $user_stmt->execute();
    $results['users'] = $user_stmt->get_result();
}

// ======================================================================= //
if($search_type == 'all' || $search_type == 'transactions') {
    $transaction_query = "SELECT t.*, u.uname, u.uemail, c.category_name, c.category_icon 
                         FROM transactions t 
                         JOIN users u ON t.uid = u.uid 
                         JOIN categories c ON t.category_id = c.category_id 
                         WHERE (u.uname LIKE ? OR u.uemail LIKE ? OR t.transaction_note LIKE ? 
                               OR c.category_name LIKE ? OR t.transaction_amount LIKE ?)
                         ORDER BY t.transaction_date DESC 
                         LIMIT 20";
    
    $search_term = "%{$search_query}%";
    $amount_search = is_numeric($search_query) ? $search_query . '%' : "%{$search_query}%";
    $transaction_stmt = $conn->prepare($transaction_query);
    $transaction_stmt->bind_param("sssss", $search_term, $search_term, $search_term, $search_term, $amount_search);
    $transaction_stmt->execute();
    $results['transactions'] = $transaction_stmt->get_result();
}

// ======================================================================= //
if($search_type == 'all' || $search_type == 'categories') {
    $category_query = "SELECT c.*, COUNT(t.transaction_id) as usage_count 
                      FROM categories c 
                      LEFT JOIN transactions t ON c.category_id = t.category_id 
                      WHERE c.category_name LIKE ? OR c.category_note LIKE ?
                      GROUP BY c.category_id 
                      ORDER BY c.category_name 
                      LIMIT 20";
    
    $search_term = "%{$search_query}%";
    $category_stmt = $conn->prepare($category_query);
    $category_stmt->bind_param("ss", $search_term, $search_term);
    $category_stmt->execute();
    $results['categories'] = $category_stmt->get_result();
}

// ======================================================================= //
$total_results = 0;
foreach($results as $type => $result) {
    if(is_object($result)) {
        $total_results += $result->num_rows;
    }
}

// ======================================================================= //
function highlightText($text, $search) {
        if(empty($search) || empty($text)) return htmlspecialchars($text);
        
        $search_terms = explode(' ', $search);
        $highlighted = htmlspecialchars($text);
        
        foreach($search_terms as $term) {
            if(strlen($term) > 2) {
                $pattern = '/' . preg_quote($term, '/') . '/i';
                $highlighted = preg_replace($pattern, '<span class="highlight">$0</span>', $highlighted);
            }
        }
        
        return $highlighted;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Tìm kiếm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../css/ad_home.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/search.css">
</head>
<body>
    <?php @include 'admin_sidebar.php'; ?>

    <div class="admin-main">
        <div class="admin-topbar">
            <div class="search-box">
                <a href="javascript:history.back()" class="back-btn">
                    <i class='bx bx-arrow-back'></i>
                </a>
                <span>Kết quả tìm kiếm</span>
            </div>
            <div class="admin-profile">
                <i class='bx bxs-user-circle'></i>
                <span><?php echo $_SESSION['admin_name'] ?? 'Admin'; ?></span>
            </div>
        </div>

        <div class="search-container">
            <div class="search-header">
                <h1 style="margin-bottom: 20px; color: #2c3e50;">
                    <i class='bx bx-search'></i> Tìm kiếm: "<?php echo htmlspecialchars($search_query); ?>"
                </h1>
                
                <form method="GET" action="search.php" class="search-box">
                    <input type="text" name="q" class="search-input" 
                           value="<?php echo htmlspecialchars($search_query); ?>" 
                           placeholder="Nhập từ khóa tìm kiếm..." required>
                    <button type="submit" class="search-btn">
                        <i class='bx bx-search'></i> Tìm kiếm
                    </button>
                </form>
                
                <div class="search-filters">
                    <a href="search.php?q=<?php echo urlencode($search_query); ?>&type=all" 
                       class="filter-btn <?php echo $search_type == 'all' ? 'active' : ''; ?>">
                        Tất cả (<?php echo $total_results; ?>)
                    </a>
                    <a href="search.php?q=<?php echo urlencode($search_query); ?>&type=users" 
                       class="filter-btn <?php echo $search_type == 'users' ? 'active' : ''; ?>">
                        Người dùng (<?php echo $results['users']->num_rows ?? 0; ?>)
                    </a>
                    <a href="search.php?q=<?php echo urlencode($search_query); ?>&type=transactions" 
                       class="filter-btn <?php echo $search_type == 'transactions' ? 'active' : ''; ?>">
                        Giao dịch (<?php echo $results['transactions']->num_rows ?? 0; ?>)
                    </a>
                    <a href="search.php?q=<?php echo urlencode($search_query); ?>&type=categories" 
                       class="filter-btn <?php echo $search_type == 'categories' ? 'active' : ''; ?>">
                        Danh mục (<?php echo $results['categories']->num_rows ?? 0; ?>)
                    </a>
                </div>
                
                <div class="results-summary">
                    Tìm thấy <strong><?php echo $total_results; ?></strong> kết quả cho từ khóa "<?php echo htmlspecialchars($search_query); ?>"
                </div>
            </div>
            
            <?php if($total_results == 0): ?>
                <div class="no-results">
                    <i class='bx bx-search-alt'></i>
                    <h3>Không tìm thấy kết quả nào</h3>
                    <p>Hãy thử với từ khóa khác hoặc tìm kiếm ít cụ thể hơn</p>
                </div>
            <?php else: ?>
                <!-- Kết quả Người dùng -->
                <?php if(($search_type == 'all' || $search_type == 'users') && $results['users']->num_rows > 0): ?>
                <div class="results-section">
                    <div class="section-header">
                        <i class='bx bxs-user'></i>
                        <h3>Người dùng</h3>
                        <span class="result-count"><?php echo $results['users']->num_rows; ?></span>
                    </div>
                    
                    <?php while($user = $results['users']->fetch_assoc()): ?>
                    <div class="result-item">
                        <div class="user-avatar">
                            <img src="../assets/<?php echo $user['uimage'] ?? 'avata_default.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($user['uname']); ?>">
                        </div>
                        <div class="result-info">
                            <div class="result-title">
                                <?php echo highlightText($user['uname'], $search_query); ?>
                                <span style="font-size: 12px; color: #666;">ID: <?php echo $user['uid']; ?></span>
                            </div>
                            <div class="result-details">
                                <i class='bx bx-envelope'></i> <?php echo highlightText($user['uemail'], $search_query); ?>
                                <?php if($user['uphone']): ?>
                                    | <i class='bx bx-phone'></i> <?php echo highlightText($user['uphone'], $search_query); ?>
                                <?php endif; ?>
                                | <i class='bx bx-calendar'></i> Tham gia: <?php echo date('d/m/Y', strtotime($user['u_create_at'])); ?>
                            </div>
                        </div>
                        <a href="user_detail.php?id=<?php echo $user['uid']; ?>" class="action-btn">
                            <i class='bx bx-show'></i> Xem chi tiết
                        </a>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php endif; ?>
                
                <!-- Kết quả Giao dịch -->
                <?php if(($search_type == 'all' || $search_type == 'transactions') && $results['transactions']->num_rows > 0): ?>
                <div class="results-section">
                    <div class="section-header">
                        <i class='bx bxs-wallet'></i>
                        <h3>Giao dịch</h3>
                        <span class="result-count"><?php echo $results['transactions']->num_rows; ?></span>
                    </div>
                    
                    <?php while($transaction = $results['transactions']->fetch_assoc()): ?>
                    <div class="result-item">
                        <div style="margin-right: 15px;">
                            <i class='<?php echo $transaction['category_icon']; ?>' 
                               style="font-size: 24px; color: #3498db;"></i>
                        </div>
                        <div class="result-info">
                            <div class="result-title">
                                <?php echo htmlspecialchars($transaction['category_name']); ?>
                                <span class="transaction-type type-<?php echo $transaction['transaction_type'] == 'Thu nhập' ? 'income' : 'expense'; ?>">
                                    <?php echo $transaction['transaction_type']; ?>
                                </span>
                                <span style="color: <?php echo $transaction['transaction_type'] == 'Thu nhập' ? '#27ae60' : '#e74c3c'; ?>; font-weight: 700;">
                                    <?php echo $transaction['transaction_type'] == 'Thu nhập' ? '+' : '-'; ?>
                                    <?php echo number_format($transaction['transaction_amount'], 0, ',', '.'); ?> ₫
                                </span>
                            </div>
                            <div class="result-details">
                                <i class='bx bx-user'></i> <?php echo highlightText($transaction['uname'], $search_query); ?>
                                (<?php echo highlightText($transaction['uemail'], $search_query); ?>)
                                | <i class='bx bx-calendar'></i> <?php echo date('d/m/Y', strtotime($transaction['transaction_date'])); ?>
                                <?php if($transaction['transaction_note']): ?>
                                    | <i class='bx bx-note'></i> <?php echo highlightText($transaction['transaction_note'], $search_query); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="font-size: 12px; color: #666; min-width: 150px; text-align: right;">
                            ID: <?php echo $transaction['transaction_id']; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php endif; ?>
                
                <!-- Kết quả Danh mục -->
                <?php if(($search_type == 'all' || $search_type == 'categories') && $results['categories']->num_rows > 0): ?>
                <div class="results-section">
                    <div class="section-header">
                        <i class='bx bxs-category'></i>
                        <h3>Danh mục</h3>
                        <span class="result-count"><?php echo $results['categories']->num_rows; ?></span>
                    </div>
                    
                    <?php while($category = $results['categories']->fetch_assoc()): ?>
                    <div class="result-item">
                        <div class="category-icon">
                            <i class='<?php echo $category['category_icon']; ?>'></i>
                        </div>
                        <div class="result-info">
                            <div class="result-title">
                                <?php echo highlightText($category['category_name'], $search_query); ?>
                                <span class="transaction-type type-<?php echo $category['category_type'] == 'Thu nhập' ? 'income' : 'expense'; ?>">
                                    <?php echo $category['category_type']; ?>
                                </span>
                                <?php if($category['is_system'] == 1): ?>
                                    <span class="system-badge">Hệ thống</span>
                                <?php endif; ?>
                            </div>
                            <div class="result-details">
                                <i class='bx bx-wallet'></i> <?php echo $category['usage_count']; ?> giao dịch
                                <?php if($category['category_note']): ?>
                                    | <i class='bx bx-note'></i> <?php echo highlightText($category['category_note'], $search_query); ?>
                                <?php endif; ?>
                                | <i class='bx bx-calendar'></i> Tạo: <?php echo date('d/m/Y', strtotime($category['created_at'])); ?>
                            </div>
                        </div>
                        <div style="font-size: 12px; color: #666; min-width: 100px; text-align: right;">
                            ID: <?php echo $category['category_id']; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-input');
            if(searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        });

        document.querySelector('.search-input')?.addEventListener('keyup', function(e) {
            if(e.key === 'Enter') {
                this.form.submit();
            }
        });

        function saveSearchHistory(query) {
            let history = JSON.parse(localStorage.getItem('search_history') || '[]');
            history = history.filter(item => item !== query);
            history.unshift(query);
            if(history.length > 10) history.pop();
            localStorage.setItem('search_history', JSON.stringify(history));
        }

        function loadSearchHistory() {
            const history = JSON.parse(localStorage.getItem('search_history') || '[]');
            return history;
        }
        
        const currentQuery = "<?php echo $search_query; ?>";
        if(currentQuery) {
            saveSearchHistory(currentQuery);
        }
    </script>
</body>
</html>