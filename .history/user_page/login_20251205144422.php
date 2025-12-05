<?php

@include '../config.php';
session_start();

if(isset($_POST['submit'])){

   $filter_email = filter_var($_POST['email'], FILTER_SANITIZE_STRING);
   $email = mysqli_real_escape_string($conn, $filter_email);
   $filter_pass = filter_var($_POST['pass'], FILTER_SANITIZE_STRING);
   $pass = mysqli_real_escape_string($conn, $filter_pass);

   $select_users = mysqli_query($conn, "SELECT * FROM `users` WHERE email = '$email' AND upwd = '$pass'") or die('Thất bại');


   if(mysqli_num_rows($select_users) > 0){
      
      $row = mysqli_fetch_assoc($select_users);

      if($row['urole'] == 'admin'){

         $_SESSION['admin_name'] = $row['uname'];
         $_SESSION['admin_email'] = $row['email'];
         $_SESSION['admin_id'] = $row['uid'];
         header('location:ad_home.php');

      }elseif($row['urole'] == 'user'){

         $_SESSION['user_name'] = $row['uname'];
         $_SESSION['user_email'] = $row['email'];
         $_SESSION['user_id'] = $row['uid'];
         header('location:home.php');

      }else{
         $message[] = 'Không tìm thấy người dùng!';
      }

   }else{
      $message[] = 'Sai email hoặc mật khẩu!';
   }

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Đăng nhập</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/main.css">

</head>
<body>

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
   
<section class="form-container">

   <form action="" method="post">
      <h3>Đăng Nhập</h3>

      <div class="input-boxes">
         <div class="inpt">
            <i class="fas fa-envelope"></i>
            <input type="email" name="email" class="box" placeholder="Email" required>
         </div>
         <div class="inpt">
            <i class="fas fa-lock"></i>
            <input type="password" name="pass" class="box" placeholder="Mật khẩu" required>
            <i class="fa-solid fa-eye" id="eye"></i>
         </div>
         <!--div>
            <input id="remember-login" type="checkbox" name="remember">
            <label for="remember-login">Ghi nhớ đăng nhập</label>
         </div-->
         <div class="forgot-password" class="text"><a href="#">Quên mật khẩu?</a></div>
         <input type="submit" class="btn" name="submit" value="Đăng Nhập">
         <p>Bạn chưa có tài khoản? <a href="signup.php">Đăng kí ngay</a></p>
      </div>
   </form>
</section>

   <script src="../js/show_pwd.js"></script>
</body>
</html>