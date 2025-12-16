<?php
@include '../config.php';
session_start();

if(!isset($_SESSION['admin_id'])) {
    header('location:../account/login.php');
    exit;
}

$message = '';
$success = '';
$edit_mode = false;
$edit_category = null;

// ============================================================================== //
if(isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM categories WHERE category_id = ? AND is_system = 1");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $edit_category = $result->fetch_assoc();
        $edit_mode = true;
    }
}

// ============================================================================== //
if(isset($_GET['cancel_edit'])) {
    header('Location: categories.php');
    exit;
}

// ============================================================================== //
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    $category_type = $_POST['category_type'];
    $category_icon = $_POST['category_icon'] ?? 'bx bx-category';
    $category_note = trim($_POST['category_note'] ?? '');
    
    if(empty($category_name)) {
        $_SESSION['error'] = 'Vui lòng nhập tên danh mục!';
    } else {
        // Kiểm tra danh mục đã tồn tại chưa (hệ thống)
        $check = $conn->prepare("SELECT * FROM categories WHERE category_name = ? AND category_type = ? AND is_system = 1");
        $check->bind_param("ss", $category_name, $category_type);
        $check->execute();
        $result = $check->get_result();
        
        if($result->num_rows > 0) {
            $_SESSION['error'] = 'Danh mục hệ thống này đã tồn tại!';
        } else {
            // Thêm danh mục hệ thống (uid = 1 cho admin)
            $stmt = $conn->prepare("INSERT INTO categories (uid, category_name, category_type, category_icon, category_note, is_system) VALUES (1, ?, ?, ?, ?, 1)");
            $stmt->bind_param("ssss", $category_name, $category_type, $category_icon, $category_note);
            
            if($stmt->execute()) {
                $_SESSION['success'] = 'Thêm danh mục hệ thống thành công!';
                header('Location: categories.php');
                exit;
            } else {
                $_SESSION['error'] = 'Lỗi: ' . $stmt->error;
            }
        }
    }
}

// ============================================================================== //
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_category'])) {
    $category_id = (int)$_POST['category_id'];
    $category_name = trim($_POST['category_name']);
    $category_type = $_POST['category_type'];
    $category_icon = $_POST['category_icon'] ?? 'bx bx-category';
    $category_note = trim($_POST['category_note'] ?? '');
    
    if(empty($category_name)) {
        $_SESSION['error'] = 'Vui lòng nhập tên danh mục!';
    } else {
        // Kiểm tra danh mục đã tồn tại chưa (trừ chính nó)
        $check = $conn->prepare("SELECT * FROM categories WHERE category_name = ? AND category_type = ? AND is_system = 1 AND category_id != ?");
        $check->bind_param("ssi", $category_name, $category_type, $category_id);
        $check->execute();
        $result = $check->get_result();
        
        if($result->num_rows > 0) {
            $_SESSION['error'] = 'Danh mục hệ thống này đã tồn tại!';
        } else {
            // Cập nhật danh mục
            $stmt = $conn->prepare("UPDATE categories SET category_name = ?, category_type = ?, category_icon = ?, category_note = ? WHERE category_id = ? AND is_system = 1");
            $stmt->bind_param("ssssi", $category_name, $category_type, $category_icon, $category_note, $category_id);
            
            if($stmt->execute()) {
                $_SESSION['success'] = 'Cập nhật danh mục hệ thống thành công!';
                header('Location: categories.php');
                exit;
            } else {
                $_SESSION['error'] = 'Lỗi: ' . $stmt->error;
            }
        }
    }
}

// ============================================================================== //
if(isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    // Chỉ cho phép xóa danh mục hệ thống và không có giao dịch nào sử dụng
    $check = $conn->prepare("SELECT c.*, COUNT(t.transaction_id) as usage_count 
                            FROM categories c 
                            LEFT JOIN transactions t ON c.category_id = t.category_id 
                            WHERE c.category_id = ? AND c.is_system = 1 
                            GROUP BY c.category_id");
    $check->bind_param("i", $delete_id);
    $check->execute();
    $result = $check->get_result();
    
    if($row = $result->fetch_assoc()) {
        if($row['usage_count'] > 0) {
            $_SESSION['error'] = 'Không thể xóa danh mục đang được sử dụng!';
        } else {
            $delete = $conn->prepare("DELETE FROM categories WHERE category_id = ? AND is_system = 1");
            $delete->bind_param("i", $delete_id);
            
            if($delete->execute()) {
                $_SESSION['success'] = 'Xóa danh mục hệ thống thành công!';
                header('Location: categories.php');
                exit;
            } else {
                $_SESSION['error'] = 'Lỗi khi xóa danh mục!';
            }
        }
    }
}

// ============================================================================== //
$system_categories = [];
$query = "SELECT c.*, COUNT(t.transaction_id) as usage_count 
          FROM categories c 
          LEFT JOIN transactions t ON c.category_id = t.category_id 
          WHERE c.is_system = 1 
          GROUP BY c.category_id 
          ORDER BY c.category_type, c.category_name";
$result = $conn->query($query);

if($result) {
    while($row = $result->fetch_assoc()) {
        $system_categories[] = $row;
    }
}

// ============================================================================== //
$income_categories = array_filter($system_categories, fn($cat) => $cat['category_type'] == 'Thu nhập');
$expense_categories = array_filter($system_categories, fn($cat) => $cat['category_type'] == 'Chi tiêu');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Quản lý Danh mục Hệ thống</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../css/ad_home.css">
    <link rel="stylesheet" href="../css/ad_users.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/ad_categories.css">
</head>
<body>
    <?php @include 'admin_sidebar.php'; ?>

    <div class="admin-main">
        <div class="admin-topbar">
            <div class="search-box">
                <i class='bx bx-search'></i>
                <input type="text" id="searchCategories" placeholder="Tìm kiếm danh mục...">
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
                <h3><i class='bx bxs-category'></i> Quản lý Danh mục Hệ thống</h3>
            </div>
            
            <div class="tabs">
                <button class="tab-btn <?php echo !$edit_mode ? 'active' : ''; ?>" onclick="switchTab('add')">
                    <?php echo $edit_mode ? 'Sửa Danh mục' : 'Thêm Danh mục'; ?>
                </button>
                <button class="tab-btn" onclick="switchTab('income')">Danh mục Thu nhập</button>
                <button class="tab-btn" onclick="switchTab('expense')">Danh mục Chi tiêu</button>
            </div>
            
            <div id="add" class="tab-content active">
                <div class="add-category-form">
                    <div class="form-header">
                        <div class="form-title">
                            <i class='bx <?php echo $edit_mode ? 'bx-edit' : 'bx-plus-circle'; ?>'></i>
                            <?php echo $edit_mode ? 'Chỉnh sửa Danh mục' : 'Thêm Danh mục Mới'; ?>
                            <?php if($edit_mode): ?>
                                <span class="edit-mode-badge">ĐANG CHỈNH SỬA</span>
                            <?php endif; ?>
                        </div>
                        <?php if($edit_mode): ?>
                            <a href="categories.php?cancel_edit=1" class="btn-cancel" style="text-decoration: none;">
                                <i class='bx bx-x'></i> Hủy
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST" action="">
                        <?php if($edit_mode): ?>
                            <input type="hidden" name="category_id" value="<?php echo $edit_category['category_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label><i class='bx bx-rename'></i> Tên danh mục *</label>
                            <input type="text" name="category_name" 
                                   placeholder="Nhập tên danh mục" 
                                   value="<?php echo $edit_mode ? htmlspecialchars($edit_category['category_name']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class='bx bx-category'></i> Loại danh mục *</label>
                            <select name="category_type" required>
                                <option value="">Chọn loại danh mục</option>
                                <option value="Thu nhập" <?php echo ($edit_mode && $edit_category['category_type'] == 'Thu nhập') ? 'selected' : ''; ?>>Thu nhập</option>
                                <option value="Chi tiêu" <?php echo ($edit_mode && $edit_category['category_type'] == 'Chi tiêu') ? 'selected' : ''; ?>>Chi tiêu</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class='bx bx-icons'></i> Biểu tượng</label>
                            <div class="icon-grid" id="iconGrid">
                            </div>
                            <input type="hidden" name="category_icon" id="selectedIcon" 
                                   value="<?php echo $edit_mode ? htmlspecialchars($edit_category['category_icon']) : 'bx bx-category'; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label><i class='bx bx-note'></i> Ghi chú</label>
                            <textarea name="category_note" placeholder="Ghi chú về danh mục..."><?php echo $edit_mode ? htmlspecialchars($edit_category['category_note']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <?php if($edit_mode): ?>
                                <button type="submit" name="edit_category" class="btn-edit">
                                    <i class='bx bx-save'></i> Lưu Thay Đổi
                                </button>
                            <?php else: ?>
                                <button type="submit" name="add_category" class="btn-submit">
                                    <i class='bx bx-plus-circle'></i> Thêm Danh mục Hệ thống
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <div id="income" class="tab-content">
                <h3 style="margin-bottom: 20px;">Danh mục Thu nhập Hệ thống</h3>
                
                <?php if(count($income_categories) > 0): ?>
                <div class="category-list">
                    <?php foreach($income_categories as $category): ?>
                    <div class="category-card">
                        <div class="category-icon">
                            <i class='<?php echo $category['category_icon']; ?>'></i>
                        </div>
                        <div class="category-info">
                            <div class="category-name"><?php echo htmlspecialchars($category['category_name']); ?></div>
                            <span class="category-type type-income"><?php echo $category['category_type']; ?></span>
                            <div class="category-usage">
                                <i class='bx bx-wallet'></i> <span class="usage-count"><?php echo $category['usage_count']; ?></span> giao dịch
                            </div>
                            <?php if(!empty($category['category_note'])): ?>
                            <div class="category-note" style="font-size: 12px; color: #666; margin-top: 5px;">
                                <?php echo htmlspecialchars($category['category_note']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="category-actions">
                            <a href="categories.php?edit_id=<?php echo $category['category_id']; ?>" class="btn-edit-sm">
                                <i class='bx bx-edit'></i>
                            </a>
                            <?php if($category['usage_count'] == 0): ?>
                            <button class="btn-delete" onclick="showDeleteModal(
                                <?php echo $category['category_id']; ?>, 
                                '<?php echo htmlspecialchars($category['category_name']); ?>',
                                '<?php echo $category['category_type']; ?>',
                                '<?php echo $category['category_icon']; ?>',
                                <?php echo $category['usage_count']; ?>,
                                '<?php echo htmlspecialchars($category['category_note'] ?? ''); ?>'
                            )">
                                <i class='bx bx-trash'></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="no-categories">
                    <i class='bx bx-category'></i>
                    <p>Chưa có danh mục thu nhập hệ thống nào</p>
                </div>
                <?php endif; ?>
            </div>
            
            <div id="expense" class="tab-content">
                <h3 style="margin-bottom: 20px;">Danh mục Chi tiêu Hệ thống</h3>
                
                <?php if(count($expense_categories) > 0): ?>
                <div class="category-list">
                    <?php foreach($expense_categories as $category): ?>
                    <div class="category-card">
                        <div class="category-icon">
                            <i class='<?php echo $category['category_icon']; ?>'></i>
                        </div>
                        <div class="category-info">
                            <div class="category-name"><?php echo htmlspecialchars($category['category_name']); ?></div>
                            <span class="category-type type-expense"><?php echo $category['category_type']; ?></span>
                            <div class="category-usage">
                                <i class='bx bx-wallet'></i> <span class="usage-count"><?php echo $category['usage_count']; ?></span> giao dịch
                            </div>
                            <?php if(!empty($category['category_note'])): ?>
                            <div class="category-note" style="font-size: 12px; color: #666; margin-top: 5px;">
                                <?php echo htmlspecialchars($category['category_note']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="category-actions">
                            <a href="categories.php?edit_id=<?php echo $category['category_id']; ?>" class="btn-edit-sm">
                                <i class='bx bx-edit'></i>
                            </a>
                            <?php if($category['usage_count'] == 0): ?>
                            <button class="btn-delete" onclick="showDeleteModal(
                                <?php echo $category['category_id']; ?>, 
                                '<?php echo htmlspecialchars($category['category_name']); ?>',
                                '<?php echo $category['category_type']; ?>',
                                '<?php echo $category['category_icon']; ?>',
                                <?php echo $category['usage_count']; ?>,
                                '<?php echo htmlspecialchars($category['category_note'] ?? ''); ?>'
                            )">
                                <i class='bx bx-trash'></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="no-categories">
                    <i class='bx bx-category'></i>
                    <p>Chưa có danh mục chi tiêu hệ thống nào</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">
                    <i class='bx bx-trash'></i> Xác nhận xóa danh mục
                </div>
                <button class="modal-close" onclick="closeDeleteModal()">
                    <i class='bx bx-x'></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="modal-icon">
                    <i class='bx bx-error-circle' style="color: #e74c3c; font-size: 48px;"></i>
                </div>
                <p>Bạn có chắc chắn muốn xóa danh mục hệ thống này?</p>
                
                <div class="category-details" id="categoryDetails">
                </div>
                
                <p style="color: #e74c3c; font-size: 14px; margin-top: 15px;">
                    <i class='bx bx-info-circle'></i> Cảnh báo: 
                    <span id="deleteWarning">
                        Hành động này không thể hoàn tác! Danh mục này sẽ không còn khả dụng cho người dùng mới.
                    </span>
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
        
        let deleteCategoryId = null;

        function initIconGrid() {
            const iconGrid = document.getElementById('iconGrid');
            iconGrid.innerHTML = '';
            
            const selectedIcon = document.getElementById('selectedIcon').value;
            
            iconList.forEach(icon => {
                const iconDiv = document.createElement('div');
                iconDiv.className = 'icon-option';
                iconDiv.setAttribute('data-icon', icon.class);
                iconDiv.innerHTML = `<i class='${icon.class}'></i>`;
                
                if(icon.class === selectedIcon) {
                    iconDiv.classList.add('selected');
                }
                
                iconDiv.addEventListener('click', () => {
                    document.querySelectorAll('.icon-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    iconDiv.classList.add('selected');
                    document.getElementById('selectedIcon').value = icon.class;
                });
                
                iconGrid.appendChild(iconDiv);
            });
            
            if(!document.querySelector('.icon-option.selected')) {
                const defaultIcon = document.querySelector('[data-icon="bx bx-category"]');
                if(defaultIcon) {
                    defaultIcon.classList.add('selected');
                    document.getElementById('selectedIcon').value = 'bx bx-category';
                }
            }
        }
        
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            document.getElementById(tabName).classList.add('active');
            
            event.target.classList.add('active');
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function showDeleteModal(categoryId, categoryName, categoryType, categoryIcon, usageCount, categoryNote) {
            deleteCategoryId = categoryId;
            
            const detailsHTML = `
                <div class="detail-row">
                    <div class="detail-label">Tên danh mục:</div>
                    <div class="detail-value"><strong>${categoryName}</strong></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Loại:</div>
                    <div class="detail-value">
                        <span class="category-type type-${categoryType === 'Thu nhập' ? 'income' : 'expense'}">
                            ${categoryType}
                        </span>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Biểu tượng:</div>
                    <div class="detail-value">
                        <i class='${categoryIcon}' style="font-size: 24px;"></i>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Số giao dịch:</div>
                    <div class="detail-value usage-count">${usageCount}</div>
                </div>
                ${categoryNote ? `
                <div class="detail-row">
                    <div class="detail-label">Ghi chú:</div>
                    <div class="detail-value">${categoryNote}</div>
                </div>
                ` : ''}
            `;
            
            let warningMessage = 'Hành động này không thể hoàn tác! Danh mục này sẽ không còn khả dụng cho người dùng mới.';
            if (parseInt(usageCount) > 0) {
                warningMessage = '<strong>Không thể xóa!</strong> Danh mục này đang được sử dụng bởi ' + usageCount + ' giao dịch.';
                document.querySelector('.modal-btn-confirm').style.display = 'none';
            } else {
                document.querySelector('.modal-btn-confirm').style.display = 'block';
            }
            
            document.getElementById('categoryDetails').innerHTML = detailsHTML;
            document.getElementById('deleteWarning').innerHTML = warningMessage;
            
            document.getElementById('deleteModal').classList.add('show');
            
            document.body.style.overflow = 'hidden';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
            deleteCategoryId = null;
            
            document.body.style.overflow = 'auto';
        }

        function confirmDelete() {
            if(deleteCategoryId) {
                window.location.href = `categories.php?delete_id=${deleteCategoryId}`;
            }
        }
        
        document.getElementById('searchCategories')?.addEventListener('input', function(e) {
            const searchTerm = this.value.toLowerCase();
            const categoryCards = document.querySelectorAll('.category-card');
            
            categoryCards.forEach(card => {
                const categoryName = card.querySelector('.category-name').textContent.toLowerCase();
                if(categoryName.includes(searchTerm)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if(e.target === this) {
                closeDeleteModal();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            initIconGrid();
            
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
            
            <?php if($edit_mode): ?>
                document.getElementById('add').classList.add('active');
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    if(btn.textContent.includes('Quay lại')) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>