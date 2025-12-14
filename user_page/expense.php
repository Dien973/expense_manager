<?php
@include '../config.php';
session_start();

if(!isset($_SESSION['user_id'])){
    header('location:../account/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// ================ XÓA TẤT CẢ GIAO DỊCH TRONG BẢNG ================ //
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_all'])) {
    $delete_month = $_POST['month'] ?? date('m');
    $delete_category = $_POST['category'] ?? '';

    $delete_where = ["uid = ?", "transaction_type = 'Chi tiêu'"];
    $delete_params = [$user_id];
    $delete_types = "i";

    if($delete_month) {
        $delete_where[] = "MONTH(transaction_date) = ?";
        $delete_params[] = $delete_month;
        $delete_types .= "i";
    }

    if($delete_category) {
        $delete_where[] = "category_id = ?";
        $delete_params[] = $delete_category;
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
        header("refresh:1;url=expense.php?month=" . $delete_month .($delete_category ? "&category=" . $delete_category : ""));
        exit();
    } else {
        $message = "Lỗi khi xóa giao dịch: " . $delete_stmt->error;
    }
}

// ======================= THÊM THU NHẬP ======================= //
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_income'])) {
    $amount = $_POST['transaction_amount'];
    $category_id = $_POST['category_id'];
    $note = $_POST['transaction_note'];
    $transaction_date = $_POST['transaction_date'];

    if($amount <= 0) {
        $message = "Số tiền phải lớn hơn 0!";
    } else {
        $stmt = $conn->prepare("INSERT into transactions (uid, category_id, transaction_amount, transaction_date, transaction_note, transaction_type) values (?, ?, ?, ?, ?, 'Chi tiêu')");
        if($stmt === false) {
            die('Lỗi SQL: ' . $conn->error);
        }

        $stmt->bind_param("iidss", $user_id, $category_id, $amount, $transaction_date, $note);

        if($stmt->execute()) {
            $message = 'Thêm chi tiêu thành công!';
            header("refresh:1;url=expense.php");
            exit();
        } else {
            $message = 'Lỗi khi thêm chi tiêu: ' . $stmt->error;
        }
    }
}

// ======================= XÓA CHI TIÊU ======================= //
if(isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE from transactions where transaction_id = ? and uid = ? and transaction_type = 'Chi tiêu'");

    if($stmt === false) {
        die('Lỗi SQL: ' . $conn->error);
    }

    $stmt->bind_param("ii", $delete_id, $user_id);

    if($stmt->execute()) {
        $message = 'Xóa chi tiêu thành công!';
        header("refresh:1;url=expense.php");
        exit();
    } else {
        $message = 'Lỗi khi xóa chi tiêu!';
    }
}

// ======================= LẤY DANH MỤC ======================= //
$categories_stmt = $conn->prepare("SELECT * from categories where (is_system = 1 or uid= ?)
                                        and category_type = 'Chi tiêu'
                                        order by is_system desc, category_name asc");
$categories_stmt->bind_param("i", $user_id);
$categories_stmt->execute();
$categories = $categories_stmt->get_result();

$filter_month = $_GET['month'] ?? date('m');
$filter_year = date('Y');
$filter_category = $_GET['category'] ?? '';

// ====== TÍNH TỔNG CHI THEO FILTER ============== //
$total_where = ["uid = ?", "transaction_type = 'Chi tiêu'"];
$total_params = [$user_id];
$total_types = "i";

if($filter_month){
    $total_where[] = "MONTH(transaction_date) = ?";
    $total_params[] = $filter_month;
    $total_types .= "i";
}

if($filter_category) {
    $total_where[] = "category_id = ?";
    $total_params[] = $filter_category;
    $total_types .= "i";
}

$total_where_clause = implode(" AND ", $total_where);
$total_query = "SELECT sum(transaction_amount) as total from transactions where $total_where_clause";

$total_stmt = $conn->prepare($total_query);
if($total_stmt === false) {
    die('Lỗi SQL tổng: ' . $conn->error);
}

if(count($total_params) > 0) {
    $total_stmt->bind_param($total_types, ...$total_params);
}

$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_income = $total_result->fetch_assoc()['total'] ?? 0;

// Lấy giao dịch thu nhập
$where_conditions = ["t.uid = ?", "transaction_type = 'Chi tiêu'"];
$params = [$user_id];
$param_types = "i";

//Theo tháng
$filter_month = $_GET['month'] ?? date('m');
if($filter_month){
    $where_conditions[] = "MONTH(transaction_date) = ?";
    $params[] = $filter_month;
    $param_types .= "i";
}

//Theo danh mục
$filter_category = $_GET['category'] ?? '';
if($filter_category){
    $where_conditions[] = "t.category_id = ?";
    $params[] = $filter_category;
    $param_types .= "i";
}

$where_clause = implode(" AND ", $where_conditions);
$query = "SELECT t.*, c.category_name, c.category_icon 
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
    <title>Quản Lý Chi Tiêu</title>

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
        <h1 class="title">Chi Tiêu</h1>

        <div class="status-summary">
            <div class="status-card status-card-expense">
                <h3>Tổng Chi Tháng <?php echo $filter_month; ?></h3>
                <div class="status-amount">-<?php echo number_format($total_income, 0, ',', '.'); ?> ₫</div>
            </div>
        </div>

        <div class="transaction-filters">
            <div class="filter-group">
                <label><i class='bx bx-calendar'></i> Tháng:</label>
                <select id="filterMonth">
                    <?php for($i = 1; $i <= 12; $i++): ?>
                        <option value="<?php echo sprintf('%02d', $i); ?>"
                                <?php echo $i == $filter_month ? 'selected' : ''; ?>>
                                Tháng <?php echo $i; ?>    
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="filter-group">
                <label><i class='bx bx-category'></i> Danh mục:</label>
                <select id="filterCategory">
                    <option value="">Tất cả</option>
                    <?php $categories->data_seek(0);
                        while($cat = $categories->fetch_assoc()): ?>
                            <option value="<?php echo $cat['category_id']; ?>"
                                <?php echo $cat['category_id'] == $filter_category ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <button class="filter-btn" onclick="applyFilters()">Xem</button>
        </div>
        
        <div class="transaction-btn">
            <button class="add-transaction-btn" onclick="openAddModal()">
                <i class='bx bx-plus-circle'></i> Thêm Chi Tiêu
            </button>

            <button class="delete-transaction-btn" onclick="confirmDeleteAll()">
                <i class='bx bxs-trash'></i> Xóa Tất Cả Giao Dịch
            </button>
        </div>

        <?php if($transactions->num_rows > 0): ?>
            <div class="transaction-table-content">
                <div class="table-responsive">
                    <table class="transaction-table">
                        <thead>
                            <tr>
                                <th width="50px"></th>
                                <th width="200px" style="text-align: left;">Danh mục</th>
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
                                        <div class="table-category">
                                            <div class="table-name"><?php echo htmlspecialchars($row['category_name']); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="table-category">
                                            <div class="table-note"><?php  echo htmlspecialchars($row['transaction_note']); ?></div>
                                        </div>
                                    </td>
                                    <td class="table-date">
                                         <?php echo date('d/m/Y', strtotime($row['transaction_date'])); ?>
                                    </td>
                                    <td class="table-amount amount-expense">
                                        -<?php echo number_format($row['transaction_amount'], 0, ',', '.'); ?> ₫ 
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

                        <div class="summary-box">
                            <div class="summary-label">
                                Tổng chi tháng <?php echo $filter_month; ?>
                                <?php if($filter_category): ?>
                                    <br><small>(theo danh mục)</small>
                                <?php endif; ?>
                            </div>

                            <div class="summary-value">
                                -<?php echo number_format($total_income, 0, ',', '.'); ?> ₫
                            </div>
                        </div>
                            
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
                    <p>Chưa có giao dịch chi tiêu nào.</p>
                </div>
        <?php endif; ?>
    </div> 

    <!--========================================THEM + CAP NHAT THU NHAP========================================-->
    <div class="modal-overlay transaction-modal" id="transactionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Thêm Chi Tiêu</h3>
                <button class="close-modal" type="button" onclick="closeModal()">&times;
                </button>
            </div>

            <form action="" id="transactionForm" method="POST">
                <input type="hidden" id="transactionId" name="transaction_id" value="">
                <input type="hidden" name="add_income" value="1">

                <div class="inpt">
                    <label class="inpt-name">
                        <i class='bx bx-money'></i>Số tiền
                    </label>

                    <input type="number" name="transaction_amount" id="amount" class="box" placeholder="0" min="1" required>
                </div>

                <div class="inpt">
                    <label class="inpt-name">
                        <i class='bx bx-category'></i>Danh mục
                    </label>

                    <select name="category_id" id="categoryId" class="box" required>
                        <option value="">Chọn danh mục</option>
                        <?php $categories->data_seek(0);
                            while($cat = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $cat['category_id']; ?>">
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                    <?php if($cat['is_system'] == 1): ?>
                                        (Hệ thống)
                                    <?php endif; ?>
                                </option>
                        <?php endwhile; ?>
                    </select>
                </div>

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
                        <i class='bx bx-save'></i> Lưu Chi Tiêu
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
                    <p>• Tổng số giao dịch: <strong><?php echo $transactions->num_rows; ?></strong></p>
                    <p>• Tổng số tiền: <strong>-<?php echo number_format($total_income, 0, ',', '.'); ?></strong></p>
                    <p>• Tháng: <strong><?php echo $filter_month; ?></strong></p>

                    <?php if($filter_category):
                        $categories->data_seek(0);
                        $cat_name = '';
                        while($cat = $categories->fetch_assoc()) {
                            if($cat['category_id'] == $filter_category) {
                                $cat_name = $cat['category_name'];
                                break;
                            }
                        }
                    ?>
                                <p>• Danh mục: <strong><?php echo htmlspecialchars($cat_name); ?></strong></p>
                    <?php endif; ?>
                </div>

                <p><i class='bx bx-info-circle'></i> Hành động này không thể hoàn tác. Tất cả giao dịch sẽ bị xóa vĩnh viễn.</p>

                <div class="delete-actions">
                    <form action="" method="POST" id="deleteAllForm">
                        <input type="hidden" name="delete_all" value="1">
                        <input type="hidden" name="month" value="<?php echo $filter_month; ?>">
                        <input type="hidden" name="category" value="<?php echo $filter_category; ?>">

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

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Thêm Chi Tiêu';
            document.getElementById('transactionForm').reset();
            document.getElementById('transactionId').value = '';
            document.getElementById('transactionDate').value = '<?php echo date('Y-m-d'); ?>';
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

                    document.getElementById('modalTitle').textContent = 'Sửa Chi Tiêu';
                    document.getElementById('transactionId').value = data.transaction_id;
                    document.getElementById('amount').value = data.transaction_amount;
                    document.getElementById('categoryId').value = data.category_id;
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
            const category = document.getElementById('filterCategory').value;

            let url = `expense.php?month=${month}`;
            if(category) url += `&category=${category}`;
            window.location.href = url;
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
                    window.location.href = `expense.php?delete_id=${deleteTransactionId}`;
                }, 500);
            }
        });
        
        // ===================== XÓA TẤT CẢ TRONG BẢNG ===================== //
        document.getElementById('deleteAllForm')?.addEventListener('submit', function(e) {
            e.preventDefault();

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