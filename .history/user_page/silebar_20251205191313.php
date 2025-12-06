
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
                <i class="bx bx-search"></i>
                <input type="text" placeholder="Search...">
                <span class="tooltip">Search</span>
                <!-- Lát mình sẽ CSS cho phần này, nên cứ comment ở đây đã nhé -->
            </li>
            <li>
                <a href="#">
                    <i class="bx bx-grid-alt"></i>
                    <span class="links_name">Dashboard</span>
                </a>
                    <span class="tooltip">Dashboard</span>
            </li>
            <li>
                <a href="#">
                    <i class="bx bx-home-alt"></i>
                    <span class="links_name">Home</span>
                </a>
                    <span class="tooltip">Home</span>
            </li>
            <li>
                <a href="#">
                    <i class="bx bx-compass"></i>
                    <span class="links_name">Explore</span>
                </a>
                    <span class="tooltip">Explore</span>
            </li>
            <li>
                <a href="#">
                    <i class="bx bx-movie-play"></i>
                    <span class="links_name">Library</span>
                </a>
                    <span class="tooltip">Library</span>
            </li>
            <li>
                <a href="#">
                    <i class="bx bx-folder"></i>
                    <span class="links_name">File Manager</span>
                </a>
                    <span class="tooltip">File Manager</span>
            </li>
            <li>
                <a href="#">
                    <i class="bx bx-cart-alt"></i>
                    <span class="links_name">Order</span>
                </a>
                    <span class="tooltip">Order</span>
            </li>
            <li>
                <a href="#">
                    <i class="bx bx-heart"></i>
                    <span class="links_name">Saved</span>
                </a>
                    <span class="tooltip">Saved</span>
            </li>
            <li>
                <a href="#">
                    <i class="bx bx-cog"></i>
                    <span class="links_name">Setting</span>
                </a>
                    <span class="tooltip">Setting</span>
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