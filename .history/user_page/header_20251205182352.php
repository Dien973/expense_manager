<?php
if(isset($message)){
   foreach($message as $message){
      echo '
      <div class="message">
         <span>'.$message.'</span>
         <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
      </div>
      ';
   }
}
?>

<header class="header">

    <div class="flex">

        <a href="home.php" class="logo">Trang chủ</a>

        <li><a href="shirt_page.php">Trang Chủ</a></li>
        <li><a href="skirt_page.php">Thu Nhập </a></li>
        <li><a href="dress_page.php">Chi Tiêu</a></li>
        <li><a href="footwear_page.php">Danh Mục</a></li>
        <li><a href="accessories_page.php">Ngân Sách</a></li>
        <li><a href="order_page.php">Báo Cáo</a></li>
        <li><a href="search_page.php">Tìm kiếm</a><li>
        <li><a href="#" id="user-btn" class="fas fa-user">Tài Khoản </a>
            <ul>
                <li><a href="login.php">Đăng Nhập</a></li>
                <li><a href="signup.php">Đăng Xuất</a></li>
            </ul>
        </li>

        <div class="icons">
            <a href="account_page.php" id="user-btn" class="fas fa-user"></a>
        </div>

        

    </div>

</header>