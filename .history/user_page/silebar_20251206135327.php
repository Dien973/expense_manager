
<header class="header">
    <div class="sidebar">
        <div class="logo-content">
            <div class="logo">
                <i class='bx bx-dollar-circle'></i>
                <div class="logo-name">Monee</div>
            </div>
            <i class="bx bx-menu" id="btn"></i>
        </div>
        <ul class="nav-list">
            <li>
                <a href="home.php">
                    <i class="bx bx-home-alt"></i>
                    <span class="links-name">Trang Chủ</span>
                </a>
                    <span class="tooltip">Trang Chủ</span>
            </li>
            <li>
                <a href="income.php">
                    <i class='bx bxs-badge-dollar' ></i>
                    <span class="links-name">Thu Nhập</span>
                </a>
                    <span class="tooltip">Thu Nhập</span>
            </li>
            <li>
                <a href="expense.php">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                    <span class="links-name">Chi Tiêu</span>
                </a>
                    <span class="tooltip">Chi Tiêu</span>
            </li>
            <li>
                <a href="category.php">
                    <i class='bx bxs-category-alt' ></i>
                    <span class="links-name">Doanh Mục</span>
                </a>
                    <span class="tooltip">Doanh Mục</span>
            </li>
            <li>
                <a href="budget.php">
                    <i class="fa-solid fa-landmark"></i>
                    <span class="links-name">Ngân Sách</span>
                </a>
                    <span class="tooltip">Ngân Sách</span>
            </li>
            <li>
                <a href="report.php">
                    <i class='bx bxs-report' ></i>
                    <span class="links-name">Báo Cáo</span>
                </a>
                    <span class="tooltip">Báo Cáo</span>
            </li>
            <li>
                <a href="account.php">
                    <i class='bx bxs-user' ></i>
                    <span class="links-name">Tài Khoản</span>
                </a>
                    <span class="tooltip">Tài Khoản</span>
            </li>
        </ul>

        <div class="logout-item">
            <a href="../account/logout.php">
                <div class="logout">
                    <div class="logout-name">Đăng Xuất</div>
                </div>
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
    </div>


    </div>

    <script>
        let btn = document.querySelector("#btn");
        let sidebar = document.querySelector(".sidebar");

        btn.onclick = function(){
            sidebar.classList.toggle("active");
        }
    </script>
</header>