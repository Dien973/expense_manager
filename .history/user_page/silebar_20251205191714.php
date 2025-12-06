
<header class="header">
    <div class="sidebar">
        <div class="logo_content">
            <div class="logo">
                <i class='bx bx-dollar-circle'></i>
                <div class="logo_name">Monee</div>
            </div>
            <i class="bx bx-menu" id="btn"></i>
        </div>
        <ul class="nav_list">
            <li>
                <a href="#">
                    <i class="bx bx-home-alt"></i>
                    <span class="links_name">Trang Chủ</span>
                </a>
                    <span class="tooltip">Trang Chủ</span>
            </li>
            <li>
                <a href="#">
                    <i class="bx bx-compass"></i>
                    <span class="links_name">Thu Nhập</span>
                </a>
                    <span class="tooltip">Thu Nhập</span>
            </li>
            <li>
                <a href="#">
                    <i class="bx bx-movie-play"></i>
                    <span class="links_name">Chi Tiêu</span>
                </a>
                    <span class="tooltip">Chi Tiêu</span>
            </li>
            <li>
                <a href="#">
                    <i class="bx bx-folder"></i>
                    <span class="links_name">Doanh Mục</span>
                </a>
                    <span class="tooltip">Doanh Mục</span>
            </li>
            <li>
                <a href="#">
                    <i class="bx bx-cart-alt"></i>
                    <span class="links_name">Ngân Sách</span>
                </a>
                    <span class="tooltip">Ngân Sách</span>
            </li>
            <li>
                <a href="#">
                    <i class="bx bx-heart"></i>
                    <span class="links_name">Báo Cáo</span>
                </a>
                    <span class="tooltip">Báo Cáo</span>
            </li>
            <li>
                <a href="#">
                    <i class="bx bx-cog"></i>
                    <span class="links_name">Tài Khoản</span>
                </a>
                    <span class="tooltip">Tài Khoản</span>
            </li>
        </ul>

        <div class="profile_content">
            <div class="profile">
                <div class="profile_details">
                    <img src="./img/profile.jpg" alt="">
                    <div class="name_job">
                        <div class="name">Cabeo</div>
                        <div class="job">Web Developer</div>
                    </div>
                </div>
                <i class="bx bx-log-out" id="log_out"></i>
            </div>
        </div>
    </div>
    <div class="home_content">
        <div class="text">Please Subcribes My Channel To Get More Video</div>
    </div>

    <script>
        let btn = document.querySelector("#btn");
        let sidebar = document.querySelector(".sidebar");
        let searchBtn = document.querySelector(".bx-search");

        btn.onclick = function(){
            sidebar.classList.toggle("active");
        }
        searchBtn.onclick = function(){
            sidebar.classList.toggle("active");
        }
    </script>
</header>