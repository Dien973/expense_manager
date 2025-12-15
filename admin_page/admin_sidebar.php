<div class="header">
    <div class="sidebar">
        <div class="logo-content">
            <div class="logo">
                <i class='bx bxs-dashboard'></i>
                <span>Monee Admin</span>
            </div>
            <i class="bx bx-menu" id="btn"></i>
        </div>
        <ul class="nav-list">
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'ad_home.php' ? 'active' : ''; ?>">
                <a href="ad_home.php"><i class='bx bx-home-alt'></i>
                    <span class="links-name"> Trang Chủ</span>
                </a>
                <span class="tooltip"> Trang Chủ</span>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' || basename($_SERVER['PHP_SELF']) == 'user_detail.php' ? 'active' : ''; ?>">
                <a href="users.php"><i class='bx bxs-user'></i>
                    <span class="links-name"> Người Dùng</span>
                </a>
                <span class="tooltip"> Người Dùng</span>

            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'active' : ''; ?>">
                <a href="transactions.php"><i class='bx bxs-wallet'></i>
                    <span class="links-name"> Giao Dịch</span>
                </a>
                <span class="tooltip"> Giao Dịch</span>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                <a href="categories.php"><i class='bx bxs-category-alt'></i>
                    <span class="links-name"> Danh Mục</span>
                </a>
                <span class="tooltip"> Danh Mục</span>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                <a href="reports.php"><i class='bx bxs-report'></i>
                    <span class="links-name"> Báo Cáo</span>
                </a>
                <span class="tooltip"> Báo Cáo</span>
            </li>
        </ul>
        
            <div class="logout-item">
                <a href="#" id="logoutBtn">
                    <div class="logout">
                        <div class="logout-name">Đăng Xuất</div>
                    </div>
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
    </div>
</div>
<div id="logoutPopup" class="message-overlay" style="display:none;">
        <div class="message-box">
            <i id="closeLogout" class="fa-solid fa-xmark"></i>
            <span>Bạn có chắc chắn muốn đăng xuất?</span>

            <div style="margin-top: 20px;">
                <button id="confirmLogout" class="btn" style="margin-right:10px;">OK</button>
                <button id="cancelLogout" class="btn">Hủy</button>
            </div>
        </div>
    </div>

    <script>
        let btn = document.querySelector("#btn");
        let sidebar = document.querySelector(".sidebar");

        btn.onclick = function(){
            sidebar.classList.toggle("active");
        }

        document.getElementById("logoutBtn").addEventListener("click", function(event) {
            event.preventDefault();
            document.getElementById("logoutPopup").style.display = "flex";
        });

        document.getElementById("closeLogout").onclick =
        document.getElementById("cancelLogout").onclick = function () {
            document.getElementById("logoutPopup").style.display = "none";
        };

        document.getElementById("confirmLogout").onclick = function() {
            window.location.href = "../account/logout.php";
        };

    </script>
</header>