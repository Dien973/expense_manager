<?php
@include '../config.php';
session_start();

if(!isset($_SESSION['admin_id'])) {
    header('location:../account/login.php');
    exit;
}

$_SESSION['previous_page'] = $_SERVER['HTTP_REFERER'] ?? 'users.php';

// ====================================================================== //
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// ====================================================================== //
if(isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];

    $check = $conn->prepare("SELECT urole FROM users WHERE uid = ?");
    $check->bind_param("i", $delete_id);
    $check->execute();
    $result = $check->get_result();

    if($row = $result->fetch_assoc()) {
        if($row['urole'] != 'admin') {
            $delete_transactions = $conn->prepare("DELETE FROM transactions WHERE uid = ?");
            $delete_transactions->bind_param("i", $delete_id);
            $delete_transactions->execute();

            $delete_details = $conn->prepare("DELETE FROM users_detail WHERE uid = ?");
            $delete_details->bind_param("i", $delete_id);
            $delete_details->execute();

            $delete_categories = $conn->prepare("DELETE FROM categories WHERE uid = ? AND is_system = 0");
            $delete_categories->bind_param("i", $delete_id);
            $delete_categories->execute();

            $delete_user = $conn->prepare("DELETE FROM users WHERE uid = ?");
            $delete_user->bind_param("i", $delete_id);
            
            if($delete_user->execute()) {
                $_SESSION['success'] = 'Đã xóa người dùng thành công!';
                header('Location: users.php');
                exit;
            }
        } else {
            $_SESSION['error'] = 'Không thể xóa tài khoản admin!';
        }
    }
}
// ====================================================================== //
$where = "WHERE urole = 'user'";
$params = [];
$param_types = "";

if(!empty($search)) {
    $where .= " AND (uname LIKE ? OR uemail LIKE ?)";
    $search_term = "%{$search}%";
    $params = [$search_term, $search_term];
    $param_types = "ss";
}

$query = "SELECT u.*, ud.uphone, ud.uimage 
          FROM users u 
          LEFT JOIN users_detail ud ON u.uid = ud.uid 
          {$where} 
          ORDER BY u.u_create_at DESC 
          LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$param_types .= "ii";

$stmt = $conn->prepare($query);
if($params) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();

// ====================================================================== //
$count_query = "SELECT COUNT(*) as total FROM users WHERE urole = 'user'";
if(!empty($search)) {
    $count_query = "SELECT COUNT(*) as total FROM users WHERE urole = 'user' AND (uname LIKE ? OR uemail LIKE ?)";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("ss", $search_term, $search_term);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
} else {
    $count_stmt = $conn->query($count_query);
    $count_result = $count_stmt;
}

$total_users = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Quản lý Người dùng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../css/ad_home.css">
    <link rel="stylesheet" href="../css/ad_users.css">
    <link rel="stylesheet" href="../css/sidebar.css">
</head>
<body>
    <?php @include 'admin_sidebar.php'; ?>

    <div class="admin-main">
        <div class="admin-topbar">
            <div class="search-box">
                <i class='bx bx-search'></i>
                <form method="GET" action="" style="display: inline;">
                    <input type="text" name="search" placeholder="Tìm kiếm người dùng..." value="<?php echo htmlspecialchars($search); ?>">
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

        <div class="card">
            <div class="card-header">
                <h3><i class='bx bxs-user'></i> Quản lý Người dùng (<?php echo $total_users; ?> người dùng)</h3>
            </div>
            
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ảnh</th>
                            <th>Tên</th>
                            <th>Email</th>
                            <th>SĐT</th>
                            <th>Ngày tạo</th>
                            <th>Giao dịch</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($user = $users->fetch_assoc()): 
                            // Đếm số giao dịch của người dùng
                            $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE uid = ?");
                            $count_stmt->bind_param("i", $user['uid']);
                            $count_stmt->execute();
                            $count_result = $count_stmt->get_result();
                            $transaction_count = $count_result->fetch_assoc()['count'];
                        ?>
                        <tr>
                            <td><?php echo $user['uid']; ?></td>
                            <td>
                                <img src="../assets/<?php echo $user['uimage'] ?? 'avata_default.jpg'; ?>" 
                                     width="40" height="40" style="border-radius: 50%; object-fit: cover;">
                            </td>
                            <td><?php echo htmlspecialchars($user['uname']); ?></td>
                            <td><?php echo htmlspecialchars($user['uemail']); ?></td>
                            <td><?php echo htmlspecialchars($user['uphone'] ?? 'Chưa cập nhật'); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($user['u_create_at'])); ?></td>
                            <td><?php echo $transaction_count; ?></td>
                            <td>
                                <div class="user-actions">
                                    <a href="user_detail.php?id=<?php echo $user['uid']; ?>" class="btn-view">
                                        <i class='bx bx-show'></i>
                                    </a>
                                    <button onclick="showDeleteModal(<?php echo $user['uid']; ?>, '<?php echo htmlspecialchars($user['uname']); ?>')" class="btn-delete">
                                        <i class='bx bx-trash'></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <?php if($users->num_rows == 0): ?>
                    <div class="no-data">
                        <i class='bx bx-user-x'></i>
                        <p>Không tìm thấy người dùng nào</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if($total_pages > 1): ?>
            <div class="pagination">
                <?php if($page > 1): ?>
                    <a href="?page=<?php echo $page-1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">
                        &laquo; Trước
                    </a>
                <?php endif; ?>
                
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if($i == $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">
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
                    <i class='bx bx-trash'></i> Xác nhận xóa
                </div>
                <button class="modal-close" onclick="closeDeleteModal()">
                    <i class='bx bx-x'></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="modal-icon">
                    <i class='bx bx-error-circle' style="color: #e74c3c; font-size: 48px;"></i>
                </div>
                <p id="deleteMessage">Bạn có chắc chắn muốn xóa người dùng <strong id="userName"></strong>?</p>
                <p style="color: #e74c3c; font-size: 14px; margin-top: 10px;">
                    <i class='bx bx-info-circle'></i> Cảnh báo: Toàn bộ giao dịch và danh mục cá nhân của người dùng này sẽ bị xóa vĩnh viễn. Hành động này không thể hoàn tác!
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
        let deleteUserId = null;
        
        function showDeleteModal(userId, userName) {
            deleteUserId = userId;
            document.getElementById('userName').textContent = userName;
            document.getElementById('deleteModal').classList.add('show');
            
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
            deleteUserId = null;
            
            document.body.style.overflow = 'auto';
        }

        function confirmDelete() {
            if(deleteUserId) {
                window.location.href = 'users.php?delete_id=' + deleteUserId;
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
        });
    </script>
</body>
</html>