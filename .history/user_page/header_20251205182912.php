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
        <li><a href="income.php">Thu Nhập </a></li>
        <li><a href="expense.php">Chi Tiêu</a></li>
        <li><a href="category.php">Danh Mục</a></li>
        <!--li><a href="budget.php">Ngân Sách</a></li-->
        <li><a href="report.php">Báo Cáo</a></li>
        <li><a href="#" id="user-btn" class="fas fa-user"></a>
            <ul>
                <li><a href="login.php">Đăng Nhập</a></li>
                <li><a href="signup.php">Đăng Xuất</a></li>
            </ul>
        </li>

        <!--div class="icons">
            <a href="account_page.php" id="user-btn" class="fas fa-user"></a>
        </div-->

        

    </div>

</header>