<?php
@include '../config.php';
session_start();

if(!isset($_SESSION['user_id'])){
   header('location:../login.php');
   exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'user';
$message = '';

//Xoa doanh muc
if(isset($_GET['delete_id'])){
    $delete_id = $_GET['delete_id'];

    if(isset($_SESSION['last_deleted']) && $_SESSION['last_deleted'] == $delete_id){
        header('Location: category.php');
        exit;
    }
    // Kiểm tra nếu danh mục thuộc về người dùng và không phải là danh mục hệ thống
    $check_query = "SELECT is_system FROM categories WHERE category_id = ? AND uid = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $delete_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($row = $result->fetch_assoc()){
        if($row['is_system'] == 1){
            $message = 'Bạn không thể xóa danh mục hệ thống!';
        } else {
            $check_usage_query = "SELECT COUNT(*) as usage_count FROM transactions WHERE category_id = ?";
            $check_stmt = $conn->prepare($check_usage_query);
            $check_stmt->bind_param("i", $delete_id);
            $check_stmt->execute();
            $usage_result = $check_stmt->get_result();
            $usage_row = $usage_result->fetch_assoc();

            if($usage_row['usage_count'] > 0){
                $message = 'Bạn không thể xóa danh mục đang được sử dụng trong giao dịch!';
            } else {
                // Xóa danh mục
                $delete_query = "DELETE FROM categories WHERE category_id = ? and uid = ?";
                $delete_stmt = $conn->prepare($delete_query);
                $delete_stmt->bind_param("ii", $delete_id, $user_id);
                if($delete_stmt->execute()){
                    $message = 'Xóa danh mục thành công!';
                    $_SESSION['last_deleted'] = $delete_id;
                    header("refresh:1;url=category.php");
                } else {
                    $message = 'Lỗi khi xóa danh mục.';
                }
            }
        }
    } else {
        $message = 'Danh mục không tồn tại hoặc bạn không có quyền xóa.';
    }

    IF(isset($_SESSION['last_deleted'])){
        unset($_SESSION['last_deleted']);
    }
}

// Lấy danh mục thu nhập và chi tiêu của người dùng
function getCategoriesForUser($conn, $user_id, $type) {
    $query = "SELECT c.*, u.uname as owner_name 
                FROM categories c left join users u on c.uid = u.uid
                WHERE (c.is_system = 1 or c.uid = ?) AND category_type = ?
                order by c.is_system desc, c.category_name asc";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $user_id, $type);
    $stmt->execute();
    return $stmt->get_result();
}
$income_categories = getCategoriesForUser($conn, $user_id, 'Thu nhập');
$expense_categories = getCategoriesForUser($conn, $user_id, 'Chi tiêu');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Danh Mục</title>

    <!-- font awesome & boxicons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <!-- custom css -->
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/category.css">
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

    <div class="category-container">
        <h1 class="title">Danh Mục</h1>

        <div class="tabs">
            <button class="tab active" data-target="income-tab">Thu Nhập</button>
            <button class="tab" data-target="expense-tab">Chi Tiêu</button>
        </div>

        <div id="income-tab" class="tab-content active">
            <button class="add-category-btn" onclick="openAddModal('Thu nhập')">
                <i class='bx bx-plus-circle'></i>Thêm Danh Mục
            </button>

            <ul class="category-list">
                <?php if($income_categories->num_rows > 0): ?>
                    <?php while($row = $income_categories->fetch_assoc()): ?>
                        <li class="category-item <?php echo $row['is_system'] == 1 ? 'system-item' : 'personal-item'; ?>">
                            <div class="category-header">
                                <div class="category-icon">
                                    <i class='<?php echo $row['category_icon']; ?>'></i>
                                </div>
                                <div class="category-name">
                                    <?php echo htmlspecialchars($row['category_name']); ?>
                                </div>
                            </div>

                            <div class="category-bages">
                                <span class="type-badge income-badge">
                                    <?php echo $row['category_type']; ?>
                                </span>
                                <?php if($row['is_system'] == 1): ?>
                                    <span class="system-badge">
                                        <i class='bx bx-globe'></i>Hệ Thống
                                    </span>
                                <?php else: ?>
                                    <span class="personal-badge">
                                        <i class='bx bx-user'></i>Cá Nhân
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if(!empty($row['category_note'])): ?>
                                <div class="category-note">
                                    <i class='bx bx-note'></i>
                                    <?php echo htmlspecialchars($row['category_note']); ?>
                                </div>
                            <?php endif; ?>

                            <div class="category-actions">
                                <?php if($row['is_system'] == 0 && $row['uid'] == $user_id): ?>
                                    <button class="edit-btn" onclick="openEditModal(<?php echo $row['category_id']; ?>)">
                                        <i class='bx bx-edit'></i> Sửa
                                    </button> 
                                    <button class="delete-btn" onclick="openDeleteModal(<?php echo $row['category_id']; ?>, '<?php echo htmlspecialchars($row['category_name'], ENT_QUOTES); ?>')">
                                        <i class='bx bx-trash'></i> Xóa
                                    </button>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endwhile; ?>
                <?php endif; ?>
            </ul>
        </div>

        <div id="expense-tab" class="tab-content">
            <button class="add-category-btn" onclick="openAddModal('Chi tiêu')">
                <i class='bx bx-plus-circle'></i>Thêm Danh Mục
            </button>

            <ul class="category-list">
                <?php if($expense_categories->num_rows > 0): ?>
                    <?php while($row = $expense_categories->fetch_assoc()): ?>
                        <li class="category-item <?php echo $row['is_system'] == 1 ? 'system-item' : 'personal-item'; ?>">
                            <div class="category-header">
                                <div class="category-icon">
                                    <i class='<?php echo $row['category_icon']; ?>'></i>
                                </div>
                                <div class="category-name">
                                    <?php echo htmlspecialchars($row['category_name']); ?>
                                </div>
                            </div>

                            <div class="category-bages">
                                <span class="type-badge expense-badge">
                                    <?php echo $row['category_type']; ?>
                                </span>
                                <?php if($row['is_system'] == 1): ?>
                                    <span class="system-badge">
                                        <i class='bx bx-globe'></i>Hệ Thống
                                    </span>
                                <?php else: ?>
                                    <span class="personal-badge">
                                        <i class='bx bx-user'></i>Cá Nhân
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if(!empty($row['category_note'])): ?>
                                <div class="category-note">
                                    <i class='bx bx-note'></i>
                                    <?php echo htmlspecialchars($row['category_note']); ?>
                                </div>
                            <?php endif; ?>

                            <div class="category-actions">
                                <?php if($row['is_system'] == 0 && $row['uid'] == $user_id): ?>
                                    <button class="edit-btn" onclick="openEditModal(<?php echo $row['category_id']; ?>)">
                                        <i class='bx bx-edit'></i> Sửa
                                    </button> 
                                    <button class="delete-btn" onclick="openDeleteModal(<?php echo $row['category_id']; ?>, '<?php echo htmlspecialchars($row['category_name'], ENT_QUOTES); ?>')">
                                        <i class='bx bx-trash'></i> Xóa
                                    </button>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endwhile; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="delete-confirm-modal" id="deleteModal">
        <div class="delete-confirm-content">
            <div class="delete-confirm-header">
                <i class='bx bx-error'></i>
                <h3>Xác Nhận Xóa</h3>
            </div>
            
            <div class="delete-confirm-body">
                <p>Bạn có chắc chắn muốn xóa danh mục này?</p>
                <p><strong id="categoryToDeleteName"></strong></p>
            </div>
            
            <div class="delete-confirm-actions">
                <button class="btn-cancel-delete" onclick="closeDeleteModal()">
                    <i class='bx bx-x'></i> Hủy
                </button>
                <button class="btn-confirm-delete" id="confirmDeleteBtn">
                    <i class='bx bx-check'></i> Xác Nhận Xóa
                </button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="categoryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">
                    Thêm Danh Mục
                </h3>
                <button class="close-modal" type="button" onclick="closeModal()">&times;</button>
            </div>

            <form action="save_category.php" method="POST" id="categoryForm">
                <input type="hidden" name="category_id" id="categoryId" VALUE="">
                <input type="hidden" name="category_type" id="categoryType" VALUE="">
                <input type="hidden" name="category_icon" id="selectIcon" value="bx bx-category">
                <input type="hidden" name="is_system" value="0">
                
                <div class="inpt">
                    <label class="inpt-name">
                        <i class='bx bx-rename'></i>Tên danh mục
                    </label>
                    <input type="text" name="category_name" id="categoryName" class="box box-name" placeholder="Tên danh mục" required>
                </div>

                <div class="inpt">
                    <label class="inpt-name">
                        <i class='bx bx-note'></i>Ghi chú
                    </label>
                    <textarea name="category_note" id="categoryNote" class="box" placeholder="Ghi chú (không bắt buộc)" style="height: 80px; resize: vertical; font-size: 20px; padding-left: 5px;"></textarea>
                </div>

                <div class="select-icon">
                    <label class="iconSelect"><i class='bx bx-icons'></i>Chọn biểu tượng:</label>
                    <div class="icon-grid" id="iconGrid">
                        <!-- JS -->
                    </div>
                </div>

                <div class="save-category-btn">
                    <button class="save-btn" type="submit">
                        <i class='bx bx-save'></i> Lưu Danh Mục
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const iconList = [
            { class: "bx bx-universal-access" },
            { class: "bx bxs-baby-carriage" },
            { class: "bx bx-body" },
            { class: "bx bxs-dog" },
            { class: "bx bxs-right-top-arrow-circle" },
            { class: "bx bxs-right-down-arrow-circle" },
            { class: "bx bxl-meta" },
            { class: "bx bxl-tiktok" },
            { class: "bx bxl-instagram" },
            { class: "bx bxl-facebook" },
            { class: "bx bxl-youtube" },
            { class: "bx bxl-twitter" },
            { class: "bx bxs-wallet" },
            { class: "bx bxl-steam" },
            { class: "bx bx-money" },
            { class: "bx bxs-credit-card" },
            { class: "bx bxs-gift" },
            { class: "bx bxs-coin-stack" },
            { class: "bx bx-trending-up" },
            { class: "bx bx-dollar-circle" },
            { class: "bx bx-food-menu" },
            { class: "bx bx-car" },
            { class: "bx bx-home" },
            { class: "bx bx-game" },
            { class: "bx bx-shopping-bag" },
            { class: "bx bx-plus-medical" },
            { class: "bx bx-book" },
            { class: "bx bx-wifi" },
            { class: "bx bx-water" },
            { class: "bx bx-dish" },
            { class: "bx bx-coffee" },
            { class: "bx bx-movie" },
            { class: "bx bx-basketball" },
            { class: "bx bx-gas-pump" },
            { class: "bx bx-phone" },
            { class: "bx bx-heart" },
            { class: "bx bx-star" },
            { class: "bx bx-category" },
            { class: "bx bxs-playlist" },
        ];

        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                tab.classList.add('active');
                const target = tab.getAttribute('data-target');
                document.getElementById(target).classList.add('active');
            });
        });

        function openDeleteModal(categoryId, categoryName) {
            categoryToDeleteId = categoryId;
            document.getElementById('categoryToDeleteName').textContent = categoryName;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            categoryToDeleteId = null;
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (categoryToDeleteId) {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="bx bx-loader bx-spin"></i> Đang xóa...';
                this.disabled = true;
                
                setTimeout(() => {
                    window.location.href = `?delete_id=${categoryToDeleteId}`;
                }, 500);
            }
        });

        function openAddModal(type) {
            document.getElementById('modalTitle').textContent = `Thêm danh mục ${type}`;
            document.getElementById('categoryType').value = type;
            document.getElementById('categoryId').value = '';
            document.getElementById('categoryNote').value = '';
            document.getElementById('categoryName').value = '';

            loadIcons('bx bx-category');
            document.getElementById('categoryModal').style.display = 'flex';
        }

        function openEditModal(categoryId) {
            fetch(`get_category.php?id=${categoryId}`)
                .then(response => response.json())
                .then(data => {
                    console.log(data);

                    document.getElementById('modalTitle').textContent = 'Cập nhật danh mục';
                    document.getElementById('categoryType').value = data.category_type;
                    document.getElementById('categoryId').value = data.category_id;
                    document.getElementById('categoryName').value = data.category_name;
                    document.getElementById('categoryNote').value = data.category_note || '';

                    loadIcons(data.category_icon);
                    document.getElementById('categoryModal').style.display = 'flex';
                })
                .catch(error => {
                    alert('Lỗi khi tải danh mục');
                    console.error(error);
                });
        }

        function closeModal() {
            document.getElementById('categoryModal').style.display = 'none';
        }

        function loadIcons(selectIcon) {
            const iconGrid = document.getElementById('iconGrid');
            iconGrid.innerHTML = '';

            iconList.forEach(icon => {
                const iconDiv = document.createElement('div');
                iconDiv.className = `icon-option ${icon.class == selectIcon ? 'selected' : ''}`;
                iconDiv.setAttribute('data-icon', icon.class);
                iconDiv.innerHTML = `<i class='${icon.class}'></i>`;
            
                iconDiv.addEventListener('click', () => {
                    document.querySelectorAll('.icon-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    iconDiv.classList.add('selected');
                    document.getElementById('selectIcon').value = icon.class;
                });
                iconGrid.appendChild(iconDiv);
            });
            document.getElementById('selectIcon').value = selectIcon;
        }

        document.getElementById('categoryForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(this);

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bx bx-loader bx-spin"></i> Đang Lưu...';

            fetch('save_category.php', {
                method: 'POST',
                body: formData
            })
            .then(respone => {
                if (!respone.ok) {
                    throw new Error('Lỗi mạng, vui lòng thử lại.');
                }
                return respone.text();
            })
            .then(data => {
                console.log(data);

                if(data.includes('Thành công') || data.includes('SUCCESS')) {
                    showMessage('Luu danh mục thành công!', 'success');

                    setTimeout(() => {
                        closeModal();
                        window.location.reload();
                    }, 1000);
                } else {
                    showMessage(data, 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }                
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Lỗi kết nối: ' + error.message, 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });

        function showMessage(message, type) {

            const oldMessage = document.querySelector('.custom-message');
            if (oldMessage) {
                oldMessage.remove();
            }

            const messageOverlay = document.createElement('div');
            messageOverlay.className = `custom-message message-${type}` ;
            messageOverlay.innerHTML = `<span>${message}</span>
                                      <button onclick="this.parentElement.remove()">&times;</button>`;
        
            document.body.appendChild(messageOverlay);

            setTimeout(() => {
                if(messageOverlay.parentElement){
                    messageOverlay.remove();
                }
            }, 5000);
        }

        document.getElementById('categoryNote')?.addEventListener('keydown', function(e) {
            if(e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
            }
        });

    </script>
</body>
</html>