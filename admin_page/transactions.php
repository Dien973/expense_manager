<?php 
@include '../config.php';
session_start();

if(!isset($_SESSION['admin_id'])) {
    header('location:../account/login.php');
    exit;
}

// ============================================================================= //
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// ============================================================================= //
if(isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    $delete_stmt = $conn->prepare("DELETE FROM transactions WHERE transaction_id = ?");
    $delete_stmt->bind_param("i", $delete_id);
    
    if($delete_stmt->execute()) {
        $_SESSION['success'] = 'Đã xóa giao dịch thành công!';
        header('Location: transactions.php');
        exit;
    }
}

// ============================================================================= //
$where = "WHERE 1=1";
$params = [];
$param_types = "";

if(!empty($search)) {
    $where .= " AND (u.uname LIKE ? OR t.transaction_note LIKE ? OR c.category_name LIKE ?)";
    $search_term = "%{$search}%";
    $params = [$search_term, $search_term, $search_term];
    $param_types = "sss";
}

if(!empty($type)) {
    $where .= " AND t.transaction_type = ?";
    $params[] = $type;
    $param_types .= "s";
}

if(!empty($start_date)) {
    $where .= " AND t.transaction_date >= ?";
    $params[] = $start_date;
    $param_types .= "s";
}

if(!empty($end_date)) {
    $where .= " AND t.transaction_date <= ?";
    $params[] = $end_date;
    $param_types .= "s";
}

$query = "SELECT t.*, u.uname, u.uemail, c.category_name, c.category_icon 
          FROM transactions t 
          JOIN users u ON t.uid = u.uid 
          JOIN categories c ON t.category_id = c.category_id 
          {$where} 
          ORDER BY t.transaction_date DESC, t.transaction_created_at DESC 
          LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$param_types .= "ii";

$stmt = $conn->prepare($query);
if($params) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$transactions = $stmt->get_result();

// ============================================================================= //
$count_query = "SELECT COUNT(*) as total FROM transactions t 
                JOIN users u ON t.uid = u.uid 
                JOIN categories c ON t.category_id = c.category_id 
                {$where}";
                
$count_stmt = $conn->prepare($count_query);

if($params) {
    $count_params = array_slice($params, 0, count($params) - 2);
    $count_types = substr($param_types, 0, -2);
    if($count_params) {
        $count_stmt->bind_param($count_types, ...$count_params);
    }
} else {
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM transactions");
}
$count_stmt->execute();
$total_transactions = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_transactions / $limit);

// ============================================================================= //
$stats_query = "SELECT 
                SUM(CASE WHEN transaction_type = 'Thu nhập' THEN transaction_amount ELSE 0 END) as total_income,
                SUM(CASE WHEN transaction_type = 'Chi tiêu' THEN transaction_amount ELSE 0 END) as total_expense,
                COUNT(*) as total_count
                FROM transactions";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Quản lý Giao dịch</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../css/ad_home.css">
    <link rel="stylesheet" href="../css/ad_users.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/ad_transaction.css">
</head>
<body>
    <?php @include 'admin_sidebar.php'; ?>

    <div class="admin-main">
        <div class="admin-topbar">
            <div class="search-box">
                <i class='bx bx-search'></i>
                <form method="GET" action="" style="display: inline;">
                    <input type="text" name="search" placeholder="Tìm kiếm giao dịch..." value="<?php echo htmlspecialchars($search); ?>">
                </form>
            </div>
            <div class="admin-profile">
                <i class='bx bxs-user-circle'></i>
                <span><?php echo $_SESSION['admin_name'] ?? 'Admin'; ?></span>
            </div>
        </div>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="notification notification-success" id="success-notification">
                <i class='bx bx-check-circle'></i>
                <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="notification notification-error" id="error-notification">
                <i class='bx bx-error-circle'></i>
                <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>

        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon income-icon">
                    <i class='bx bx-trending-up'></i>
                </div>
                <div class="stat-info">
                    <h3>Tổng Thu Nhập</h3>
                    <div class="stat-number income-number">
                        +<?php echo number_format($stats['total_income'] ?? 0, 0, ',', '.'); ?> ₫
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon expense-icon">
                    <i class='bx bx-trending-down'></i>
                </div>
                <div class="stat-info">
                    <h3>Tổng Chi Tiêu</h3>
                    <div class="stat-number expense-number">
                        -<?php echo number_format($stats['total_expense'] ?? 0, 0, ',', '.'); ?> ₫
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon total-icon">
                    <i class='bx bx-line-chart'></i>
                </div>
                <div class="stat-info">
                    <h3>Tổng Giao Dịch</h3>
                    <div class="stat-number total-number">
                        <?php echo number_format($stats['total_count'] ?? 0, 0, ',', '.'); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label><i class='bx bx-filter'></i> Loại giao dịch</label>
                    <select name="type">
                        <option value="">Tất cả</option>
                        <option value="Thu nhập" <?php echo $type == 'Thu nhập' ? 'selected' : ''; ?>>Thu nhập</option>
                        <option value="Chi tiêu" <?php echo $type == 'Chi tiêu' ? 'selected' : ''; ?>>Chi tiêu</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label><i class='bx bx-calendar'></i> Từ ngày</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                
                <div class="filter-group">
                    <label><i class='bx bx-calendar'></i> Đến ngày</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter">
                            <i class='bx bx-filter-alt'></i> Xem
                        </button>
                        <a href="transactions.php" class="btn-reset">Xóa lọc</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class='bx bxs-wallet'></i> Quản lý Giao dịch (<?php echo $total_transactions; ?> giao dịch)</h3>
            </div>
            
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Người dùng</th>
                            <th>Danh mục</th>
                            <th>Loại</th>
                            <th>Số tiền</th>
                            <th>Ghi chú</th>
                            <th>Ngày</th>
                            <th>Thời gian tạo</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($transaction = $transactions->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $transaction['transaction_id']; ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <i class='bx bxs-user-circle'></i>
                                    <div>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($transaction['uname']); ?></div>
                                        <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($transaction['uemail']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <i class='<?php echo $transaction['category_icon']; ?>'></i>
                                    <span><?php echo htmlspecialchars($transaction['category_name']); ?></span>
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
                            <td><?php echo date('d/m/Y', strtotime($transaction['transaction_date'])); ?></td>
                            <td><?php echo date('H:i d/m/Y', strtotime($transaction['transaction_created_at'])); ?></td>
                            <td>
                                <div class="transaction-actions">
                                    <button onclick="showDeleteModal(
                                        <?php echo $transaction['transaction_id']; ?>, 
                                        '<?php echo htmlspecialchars($transaction['uname']); ?>',
                                        '<?php echo htmlspecialchars($transaction['category_name']); ?>',
                                        '<?php echo $transaction['transaction_type']; ?>',
                                        <?php echo $transaction['transaction_amount']; ?>,
                                        '<?php echo htmlspecialchars($transaction['transaction_note'] ?? 'Không có'); ?>',
                                        '<?php echo date('d/m/Y', strtotime($transaction['transaction_date'])); ?>'
                                    )" class="btn-delete">
                                        <i class='bx bx-trash'></i> Xóa
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <?php if($transactions->num_rows == 0): ?>
                    <div class="no-data">
                        <i class='bx bx-wallet'></i>
                        <p>Không tìm thấy giao dịch nào</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if($total_pages > 1): ?>
            <div class="pagination">
                <?php if($page > 1): ?>
                    <a href="?page=<?php echo $page-1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $type ? '&type='.urlencode($type) : ''; ?><?php echo $start_date ? '&start_date='.urlencode($start_date) : ''; ?><?php echo $end_date ? '&end_date='.urlencode($end_date) : ''; ?>">
                        &laquo; Trước
                    </a>
                <?php endif; ?>
                
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if($i == $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $type ? '&type='.urlencode($type) : ''; ?><?php echo $start_date ? '&start_date='.urlencode($start_date) : ''; ?><?php echo $end_date ? '&end_date='.urlencode($end_date) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $type ? '&type='.urlencode($type) : ''; ?><?php echo $start_date ? '&start_date='.urlencode($start_date) : ''; ?><?php echo $end_date ? '&end_date='.urlencode($end_date) : ''; ?>">
                        Sau &raquo;
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">
                    <i class='bx bx-trash'></i> Xác nhận xóa giao dịch
                </div>
                <button class="modal-close" onclick="closeDeleteModal()">
                    <i class='bx bx-x'></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="modal-icon">
                    <i class='bx bx-error-circle' style="color: #e74c3c; font-size: 48px;"></i>
                </div>
                <p>Bạn có chắc chắn muốn xóa giao dịch này?</p>
                
                <div class="transaction-details" id="transactionDetails">
                    <!-- Transaction details will be inserted here by JavaScript -->
                </div>
                
                <p style="color: #e74c3c; font-size: 14px; margin-top: 15px;">
                    <i class='bx bx-info-circle'></i> Cảnh báo: Hành động này không thể hoàn tác!
                </p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-cancel" onclick="closeDeleteModal()">
                    Hủy bỏ
                </button>
                <button class="modal-btn modal-btn-confirm" onclick="confirmDelete()">
                    Xác nhận xóa
                </button>
            </div>
        </div>
    </div>

    <script>
        let deleteTransactionId = null;

        function showDeleteModal(transactionId, userName, categoryName, type, amount, note, date) {
            deleteTransactionId = transactionId;

            const formattedAmount = new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(amount);

            const detailsHTML = `
                <div class="detail-row">
                    <div class="detail-label">Người dùng:</div>
                    <div class="detail-value">${userName}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Danh mục:</div>
                    <div class="detail-value">${categoryName}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Loại:</div>
                    <div class="detail-value">
                        <span class="transaction-type type-${type === 'Thu nhập' ? 'income' : 'expense'}">
                            ${type}
                        </span>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Số tiền:</div>
                    <div class="detail-value detail-amount amount-${type === 'Thu nhập' ? 'income' : 'expense'}">
                        ${type === 'Thu nhập' ? '+' : '-'}${formattedAmount}
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Ghi chú:</div>
                    <div class="detail-value">${note}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Ngày:</div>
                    <div class="detail-value">${date}</div>
                </div>
            `;

            document.getElementById('transactionDetails').innerHTML = detailsHTML;
            document.getElementById('deleteModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
            deleteTransactionId = null;
            
            document.body.style.overflow = 'auto';
        }

        function confirmDelete() {
            if(deleteTransactionId) {
                window.location.href = 'transactions.php?delete_id=' + deleteTransactionId;
            }
        }

        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if(e.target === this) {
                closeDeleteModal();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach(notification => {
                setTimeout(() => {
                    notification.style.animation = 'slideInRight 0.3s ease-out reverse';
                    setTimeout(() => {
                        notification.style.display = 'none';
                    }, 300);
                }, 5000);
            });

            notifications.forEach(notification => {
                notification.addEventListener('click', function() {
                    this.style.animation = 'slideInRight 0.3s ease-out reverse';
                    setTimeout(() => {
                        this.style.display = 'none';
                    }, 300);
                });
            });

            document.addEventListener('keydown', function(e) {
                if(e.key === 'Escape') {
                    closeDeleteModal();
                }
            });

            const endDateInput = document.querySelector('input[name="end_date"]');
            const startDateInput = document.querySelector('input[name="start_date"]');
            
            if(endDateInput && !endDateInput.value) {
                const today = new Date().toISOString().split('T')[0];
                endDateInput.value = today;
            }

            if(startDateInput && !startDateInput.value) {
                const today = new Date();
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                startDateInput.value = firstDay;
            }
        });
    </script>
</body>
</html>