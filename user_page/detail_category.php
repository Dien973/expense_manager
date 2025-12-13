<?php
@include '../config.php';
session_start();

if(!isset($_SESSION['user_id'])){
    header('location:../account/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$category_id = $_GET['id'] ?? 0;
$message = '';

if($category_id <= 0) {
    header('location:category.php');
    exit;
}

$category_query = "SELECT c.*, u.uname as owner_name 
                   FROM categories c 
                   LEFT JOIN users u ON c.uid = u.uid
                   WHERE c.category_id = ? AND (c.is_system = 1 OR c.uid = ?)";
$category_stmt = $conn->prepare($category_query);
$category_stmt->bind_param("ii", $category_id, $user_id);
$category_stmt->execute();
$category_result = $category_stmt->get_result();

if($category_result->num_rows == 0){
    $message = "Danh mục không tồn tại!";
    $category = null;
} else {
    $category = $category_result->fetch_assoc();
}

// ================ XÓA TẤT CẢ GIAO DỊCH TRONG BẢNG ================ //
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_all'])) {
    $delete_month = $_POST['month'] ?? date('m');

    $delete_where = ["uid = ?", "category_id = ?"];
    $delete_params = [$user_id, $category_id];
    $delete_types = "ii";

    if($delete_month) {
        $delete_where[] = "MONTH(transaction_date) = ?";
        $delete_params[] = $delete_month;
        $delete_types .= "i";
    }

    $count_where = implode(" AND ", $delete_where);
    $count_query = "SELECT count(*) as count from transactions where $count_where";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param($delete_types, ...$delete_params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $delete_count = $count_result->fetch_assoc()['count'];

    $delete_query = "DELETE from transactions where $count_where";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param($delete_types, ...$delete_params);

    if($delete_stmt->execute()) {
        $message = "Đã xóa thành công $delete_count giao dịch!";
        header("refresh:1;url=detail_category.php?month=" . $category_id . "&month=" . $delete_month);
        exit();
    } else {
        $message = "Lỗi khi xóa giao dịch: " . $delete_stmt->error;
    }
}

// ======================= THÊM THU NHẬP ======================= //
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_income'])) {
    $amount = $_POST['transaction_amount'];
    $transaction_type = $_POST['transaction_type'];
    $note = $_POST['transaction_note'];
    $transaction_date = $_POST['transaction_date'];

    if($amount <= 0) {
        $message = "Số tiền phải lớn hơn 0!";
    } else {
        $stmt = $conn->prepare("INSERT into transactions (uid, category_id, transaction_amount, transaction_date, transaction_note, transaction_type) values (?, ?, ?, ?, ?, ?)");
        if($stmt === false) {
            die('Lỗi SQL: ' . $conn->error);
        }

        $stmt->bind_param("iidsss", $user_id, $category_id, $amount, $transaction_date, $note, $transaction_type);

        if($stmt->execute()) {
            $message = 'Thêm giao dịch thành công!';
            header("refresh:1;url=detail_category.php?id=" . $category_id);
            exit();
        } else {
            $message = 'Lỗi khi thêm giao dịch: ' . $stmt->error;
        }
    }
}

// ======================= edit THU NHẬP ======================= //
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_transaction'])) {
    $transaction_id = $_POST['transaction_id'];
    $amount = $_POST['transaction_amount'];
    $transaction_type = $_POST['transaction_type'];
    $note = $_POST['transaction_note'];
    $transaction_date = $_POST['transaction_date'];

    if($amount <= 0) {
        $message = "Số tiền phải lớn hơn 0!";
    } else {
        $stmt = $conn->prepare("UPDATE transactions set transaction_amount = ?, transaction_date = ?, transaction_note = ?, transaction_type = ? where transaction_id = ? and uid = ? and category_id = ?");
        if($stmt === false) {
            die('Lỗi SQL: ' . $conn->error);
        }

        $stmt->bind_param("dsssiii", $amount,  $transaction_date, $note, $transaction_tye, $transaction_id, $user_id, $category_id);

        if($stmt->execute()) {
            $message = 'Cập nhật giao dịch thành công!';
            header("refresh:1;url=detail_category.php?id=" . $category_id);
            exit();
        } else {
            $message = 'Lỗi khi cập nhật giao dịch: ' . $stmt->error;
        }
    }
}

// ======================= XÓA THU NHẬP ======================= //
if(isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE from transactions where transaction_id = ? and uid = ? and category_id = ?");

    if($stmt === false) {
        die('Lỗi SQL: ' . $conn->error);
    }

    $stmt->bind_param("iii", $delete_id, $user_id, $category_id);

    if($stmt->execute()) {
        $message = 'Xóa giao dịch thành công!';
        header("refresh:1;url=detail_category.php?id=" . $category_id);
        exit();
    } else {
        $message = 'Lỗi khi xóa giao dịch!';
    }
}

// ======================= ======================= //
$month_query = "SELECT DISTINCT MONTH(transaction_date) as month 
                FROM transactions 
                WHERE uid = ? AND category_id = ? 
                ORDER BY month DESC";
$month_stmt = $conn->prepare($month_query);
$month_stmt ->bind_param("ii", $user_id, $category_id);
$month_stmt->execute();
$month_result = $month_stmt->get_result();

$filter_month = $_GET['month'] ?? date('m');
$filter_year = date('Y');

// ====== TÍNH TỔNG THU THEO FILTER ============== //
$total_where = ["uid = ?", "category_id = ?"];
$total_params = [$user_id, $category_id];
$total_types = "ii";

if($filter_month){
    $total_where[] = "MONTH(transaction_date) = ?";
    $total_params[] = $filter_month;
    $total_types .= "i";
}

$total_where_clause = implode(" AND ", $total_where);
$total_query = "SELECT sum(case when transaction_type = 'Thu nhập' then transaction_amount else 0 end) as total_income,
                        sum(case when transaction_type = 'Chi Tiêu' then transaction_amount else 0 end) as total_expense
                from transactions where $total_where_clause";

$total_stmt = $conn->prepare($total_query);
if($total_stmt === false) {
    die('Lỗi SQL tổng: ' . $conn->error);
}

if(count($total_params) > 0) {
    $total_stmt->bind_param($total_types, ...$total_params);
}

$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_income = $total_row['total_income'] ?? 0;
$total_expense = $total_row['total_expense'] ?? 0;

// Lấy giao dịch thu nhập
$where_conditions = ["t.uid = ?", "t.category_id = ?"];
$params = [$user_id, $category_id];
$param_types = "ii";

//Theo tháng
if($filter_month){
    $where_conditions[] = "MONTH(transaction_date) = ?";
    $params[] = $filter_month;
    $param_types .= "i";
}

$where_clause = implode(" AND ", $where_conditions);
$query = "SELECT t.*, c.category_name, c.category_icon, c.category_type
          FROM transactions t 
          JOIN categories c ON t.category_id = c.category_id 
          WHERE $where_clause 
          ORDER BY t.transaction_date DESC, t.transaction_created_at DESC";
  
$transactions_stmt = $conn->prepare($query);
if($transactions_stmt === false) {
    die('Lỗi SQL: ' . $conn->error . '<br>Query: ' . $query);
}

if(count($params) > 0) {
    $transactions_stmt->bind_param($param_types, ...$params);
}

$transactions_stmt->execute();
$transactions = $transactions_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Thu Nhập</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/transaction.css">

    
</head>
<body>
    <?php @include 'sidebar.php'; ?>

    <?php if($message): ?>
        <div class="message-overlay">
            <div class="message-box">
                <span><?php echo $message; ?></span>
                <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
            </div>
        </div>
    <?php endif; ?>

    <div class="transaction-container">
        <?php if(!$category): ?>
            <div class="error-container">
                <i class='bx bx-error-circle'></i>
                <h2><?php echo $message; ?></h2>
                <a href="category.php" class="back-button">
                    <i class='bx bx-arrow-back'></i> Quay lại
                </a>
            </div>
        <?php else: ?>
            <div class="category-header">
                <a href="category.php" class="back-button">
                    <i class='bx bx-arrow-back'></i> Quay lại
                </a>

                <div class="category-name">
                    <h1 class="title">DANH MỤC: <?php echo htmlspecialchars($category['category_name']); ?></h1>
                </div>

            </div>

        <div class="status-summary">
            <?php if($category['category_type']== 'Thu nhập' || $total_income > 0): ?>
                <div class="status-card status-card-income">
                    <h3>Tổng Thu Tháng <?php echo $filter_month; ?></h3>
                    <div class="status-amount">+<?php echo number_format($total_income, 0, ',', '.'); ?> ₫</div>
                </div>
            <?php endif; ?>

            <?php if($category['category_type']== 'Chi tiêu' || $total_expense > 0): ?>
                <div class="status-card status-card-expense">
                    <h3>Tổng Chi Tháng <?php echo $filter_month; ?></h3>
                    <div class="status-amount">-<?php echo number_format($total_expense, 0, ',', '.'); ?> ₫</div>
                </div>
            <?php endif; ?>

        </div>

        <div class="transaction-filters">
            <div class="filter-group">
                <label><i class='bx bx-calendar'></i> Tháng:</label>
                <select id="filterMonth">
                    <?php if($month_result->num_rows > 0): ?>
                        <?php  while($month_row = $month_result->fetch_assoc()): ?>
                            <option value="<?php echo sprintf('%02d', $month_row['month']); ?>" 
                                            <?php echo $month_row['month'] == $filter_month ? 'selected' : ''; ?>>
                                Tháng <?php echo date('m'); ?>            
                            </option>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <option value="<?php echo date('m'); ?>" selected>Tháng <?php echo date('m'); ?></option>
                    <?php endif; ?>
                </select>
            </div>

            <button class="filter-btn" onclick="applyFilters()">Xem</button>
        </div>
        
        <div class="transaction-btn">
            <button class="add-transaction-btn" onclick="openAddModal()">
                <i class='bx bx-plus-circle'></i> Thêm Giao Dịch
            </button>
        </div>

        <?php if($transactions->num_rows > 0): ?>
            <div class="transaction-table-content">
                <div class="table-responsive">
                    <table class="transaction-table">
                        <thead>
                            <tr>
                                <th width="50px"></th>
                                <th width="200px" style="text-align: left;">Thu/Chi</th>
                                <th style="text-align: left;">Mô tả</th>
                                <th width="120px" style="text-align: center;">Ngày</th>
                                <th width="200px" style="text-align: center;">Số tiền</th>
                                <th width="120px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $transactions->fetch_assoc()): ?>
                                <tr>
                                    <td class="table-icon">
                                        <i class='<?php echo $row['category_icon']; ?>'></i>
                                    </td>
                                    <td>
                                        <span class="table-type <?php echo $row['transaction_type'] == 'Thu nhập' ? 'type-income' : 'type-expense'; ?>">
                                            <?php echo $row['transaction_type']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-category">
                                            <div class="table-note"><?php  echo htmlspecialchars($row['transaction_note']); ?></div>
                                        </div>
                                    </td>
                                    <td class="table-date">
                                         <?php echo date('d/m/Y', strtotime($row['transaction_date'])); ?>
                                    </td>
                                    <td class="table-amount <?php echo $row['transaction_type'] == 'Thu nhập' ? 'amount-income' : 'amount-expense'; ?>">
                                        <?php echo $row['transaction_type'] == 'Thu nhập' ? '+' : '-'; ?>
                                        <?php echo number_format($row['transaction_amount'], 0, ',', '.'); ?> ₫ 
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <button class="table-btn table-edit"
                                                onclick="openEditModal(<?php echo $row['transaction_id']; ?>)">
                                                <i class='bx bx-edit'></i>
                                            </button>
                                            <button class="table-btn table-delete"
                                                onclick="openDeleteModal(<?php echo $row['transaction_id']; ?>)">
                                                <i class='bx bx-trash'></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?> 
                        </tbody>
                    </table>
                </div>

                <div class="table-pagination">
                    <div class="table-summary">
                        <div class="summary-box">
                            <div class="summary-label">Tổng số giao dịch</div>
                            <div class="summary-value">
                                <?php echo $transactions->num_rows; ?>
                            </div>
                        </div>

                        <?php if($category['category_type'] == 'Thu nhập' || $total_income > 0): ?>
                            <div class="summary-box">
                                <div class="summary-label">
                                    Tổng thu tháng <?php echo $filter_month; ?>
                                </div>

                                <div class="summary-value">
                                    +<?php echo number_format($total_income, 0, ',', '.'); ?> ₫
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if($category['category_type'] == 'Chi tiêu' || $total_expense > 0): ?>
                            <div class="summary-box">
                                <div class="summary-label">
                                    Tổng chi tháng <?php echo $filter_month; ?>
                                </div>

                                <div class="summary-value">
                                    -<?php echo number_format($total_expense, 0, ',', '.'); ?> ₫
                                </div>
                            </div>
                        <?php endif; ?>
                            
                        <?php if($transactions->num_rows > 0): ?>
                                <button class="btn-delete-all"
                                    type="button"
                                    onclick="confirmDeleteAll()">
                                    <i class='bx bx-trash-alt'></i> Xóa tất cả (<?php echo $transactions->num_rows; ?>)
                                </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="no-transaction-table">
                <i class='bx bx-money-withdraw'></i>
                <p>Chưa có giao dịch nào trong danh mục này.</p>
            </div>
        <?php endif; ?>
        <?php endif; ?>
    </div> 

    <!--========================================THEM + CAP NHAT THU NHAP========================================-->
    <div class="modal-overlay transaction-modal" id="transactionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Thêm Giao Dịch</h3>
                <button class="close-modal" type="button" onclick="closeModal()">&times;
                </button>
            </div>

            <form action="" id="transactionForm" method="POST">
                <input type="hidden" id="transactionId" name="transaction_id" value="">
                <input type="hidden" id="formAction" name="add_income" value="1">
                <input type="hidden" name="category_id" value="<?php echo $category_id; ?>">

                <div class="inpt">
                    <label class="inpt-name">
                        <i class='bx bx-money'></i>Số tiền
                    </label>

                    <input type="number" name="transaction_amount" id="amount" class="box" placeholder="0" min="1" required>
                </div>

                <?php if($category['category_type'] == 'Thu nhập'): ?>
                    <input type="hidden" name="transaction_type" value="Thu nhập">
                <?php elseif($category['category_type'] == 'Chi tiêu'): ?>
                    <input type="hidden" name="transaction_type" value="Chi tiêu">
                <?php else: ?>
                    <div class="inpt">
                        <label class="inpt-name">
                            <i class='bx bx-category'></i>Loại giao dịch
                        </label>

                        <select name="transaction_type" id="transactionType" class="box" required>
                            <option value="Thu nhập">Thu nhập</option>
                            <option value="Chi tiêu">Chi tiêu</option>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="inpt">
                    <label class="inpt-name">
                        <i class='bx bx-calendar'></i>Ngày giao dịch
                    </label>

                    <input type="date" name="transaction_date" id="transactionDate" class="box" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="inpt">
                    <label class="inpt-name">
                        <i class='bx bx-note'></i>Ghi chú
                    </label>

                    <textarea name="transaction_note" id="note" class="box" placeholder="Ghi chú (không bắt buộc)"></textarea>
                </div>

                <div class="save-category-btn">
                    <button class="save-btn" type="submit">
                        <i class='bx bx-save'></i> Lưu Giao Dịch
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!--========================================XOA TUNG GIAO DICH TRONG BANG========================================-->
    <div class="modal-overlay simple-delete-modal" id="simpleDeleteModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class='bx bx-trash'></i> Xóa Giao Dịch
                </h3>
                <button class="close-modal" type="button" onclick="closeSimpleDeleteModal()">&times;</button>
            </div>

            <div class="modal-body">
                <div class="delete-content">
                    <i class='bx bx-error'></i>
                    <p>
                        Bạn có chắc chắn muốn xóa giao dịch này?
                    </p>
                </div>

                <div class="modal-actions">
                    <button class="btn-cancel" type="button" onclick="closeSimpleDeleteModal()">
                        Hủy
                    </button>
                    <button class="btn-confirm" id="confirmDeleteBtn">
                        Xác nhận xóa
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!--========================================XOA TAT CA GIAO DICH TRONG BANG========================================-->
    <div class="modal-overlay delete-all-modal" id="deleteAllModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class='bx bx-error-circle'></i> Xác Nhận Xóa Tất Cả
                </h3>

                <button class="close-modal" type="button" onclick="closeDeleteAllModal()">&times;</button>
            </div>

            <div>
                <h4>Bạn có chắc chắn muốn xóa tất cả giao dịch này?</h4>

                <div class="delete-all-info">
                    <p><strong>Thông tin sẽ bị xóa:</strong></p>
                    <p>• Danh mục: <strong><?php echo htmlspecialchars($category['category_name']); ?></strong></p>
                    <p>• Loại giao dịch: <strong><?php echo $category['category_type']; ?></strong></p>
                    <p>• Tổng số giao dịch: <strong><?php echo $transactions->$num_rows; ?></strong></p>

                    <?php if($total_income > 0): ?>
                        <p><strong>• Tổng thu: </strong>+<?php echo number_format($total_income, 0, ',', '.'); ?></strong></p>
                    <?php endif; ?>

                    <?php if($total_expense > 0): ?>
                        <p><strong>• Tổng chi: </strong>+<?php echo number_format($total_expense, 0, ',', '.'); ?></strong></p>
                    <?php endif; ?>
                    
                    
                    <p>• Tháng: <strong><?php echo $filter_month; ?></strong></p>
                </div>

                <p><i class='bx bx-info-circle'></i> Hành động này không thể hoàn tác. Tất cả giao dịch sẽ bị xóa vĩnh viễn.</p>

                <div class="delete-actions">
                    <form action="" method="POST" id="deleteAllForm">
                        <input type="hidden" name="delete_all" value="1">
                        <input type="hidden" name="month" value="<?php echo $filter_month; ?>">

                        <button class="btn-confirm-delete" type="submit">
                            <i class='bx bx-check'></i> Xác Nhận Xóa
                        </button>

                        <button class="btn-cancel-delete" type="button" onclick="closeDeleteAllModal()">
                            <i class='bx bx-x'></i> Hủy Bỏ
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let deleteTransactionId = null;
        const categoryId = <?php echo $category_id; ?>;
        const categoryType = '<?php echo $category["category_type"]; ?>';

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Thêm Thu Nhập';
            document.getElementById('transactionForm').reset();
            document.getElementById('transactionId').value = '';
            document.getElementById('formAction').name = 'add_income';
            document.getElementById('formAction').value = '1';

            if(categoryType === 'Thu nhập' || categoryType === 'Chi tiêu') {
                document.getElementById('transactionDate').value = '<?php echo date('Y-m-d'); ?>';
            }

            document.getElementById('transactionModal').style.display = 'flex';
        }

        function openEditModal(transactionId) {
            fetch(`get_transaction.php?id=${transactionId}`)
                .then(response => {
                    if(!response.ok) {
                        throw new Error('Kết nối mạng không ổn định');
                    }
                    return response.json();
                })
                .then(data => {
                    if(data.error) {
                        alert('Lỗi: ' + data.error);
                        return;
                    }

                    document.getElementById('modalTitle').textContent = 'Sửa Giao Dịch';
                    document.getElementById('transactionId').value = data.transaction_id;
                    document.getElementById('formAction').name = 'edit_transaction';
                    document.getElementById('formAction').value = '1';
                    document.getElementById('amount').value = data.transaction_amount;

                    if(categoryType !== 'Thu nhập' && categoryType !== 'Chi tiêu') {
                        document.getElementById('transactionType').value = data.transaction_type;
                    }

                    document.getElementById('transactionDate').value = data.transaction_date;
                    document.getElementById('note').value = data.transaction_note || '';
                    document.getElementById('transactionModal').style.display = 'flex';
                })
                .catch(error => {
                    alert('Lỗi khi tải thông tin giao dịch: ' + error.message);
                    console.error(error);
                });
        }

        function closeModal() {
            document.getElementById('transactionModal').style.display = 'none';
        }

        function applyFilters() {
            const month = document.getElementById('filterMonth').value;
            window.location.href = `detail_category.php?id=${categoryId}&month=${month}`;
        }

        // =============== ĐỊNH DẠNG SỐ TIỀN KHI NHẬP ================ //
        document.getElementById('amount')?.addEventListener('input', function(e) {
            let value = this.value.replace(/[^\d]/g, '');

            if(value) {
                this.value = parseInt(value).toLocaleString('vi-VN');
            }
        });

        // =============== ĐỊNH DẠNG SỐ TIỀN KHI SUBMIT XONG ================ //
        document.getElementById('transactionForm')?.addEventListener('submit', function(e) {
            const amountInput = document.getElementById('amount');
            const rawValue = amountInput.value.replace(/[^\d]/g, '');
            amountInput.value = rawValue;
        });

        // =============================== //
        function confirmDeleteAll() {
            document.getElementById('deleteAllModal').style.display = 'flex';
        }

        function closeDeleteAllModal() {
            document.getElementById('deleteAllModal').style.display = 'none';
        }

        // ===================== XÓA TUNG GIAO DICH TRONG BẢNG ===================== //
        function openDeleteModal(transactionId) {
            deleteTransactionId = transactionId;
            document.getElementById('simpleDeleteModal').style.display = 'flex';
        }

        function closeSimpleDeleteModal() {
            document.getElementById('simpleDeleteModal').style.display = 'none';
            deleteTransactionId = null;
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (deleteTransactionId) {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="bx bx-loader bx-spin"></i> Đang xóa...';
                this.disabled = true;
                
                setTimeout(() => {
                    window.location.href = `detail_category.php?delete_id=${deleteTransactionId}`;
                }, 500);
            }
        });
        
        // ===================== XÓA TẤT CẢ TRONG BẢNG ===================== //
        document.getElementById('deleteAllForm')?.addEventListener('submit', function(e) {
            const confirmBtn = this.querySelector('.btn-confirm-delete');
            const originalText = confirmBtn.innerHTML;
            confirmBtn.innerHTML = '<i class="bx bx-loader bx-spin"></i> Đang xóa...';
            confirmBtn.disabled = true;

            fetch('', {
                method: 'POST', body: new FormData(this)
            })
            .then(response => {
                if(response.redirected) {
                    window.location.href = response.url;
                }
            })
            .catch(error => {
                alert('Lỗi khi xóa: ' +error.message);
                confirmBtn.innerHTML = originalText;
                confirmBtn.disabled = false;
            });
        });
    </script>
</body>
</html>