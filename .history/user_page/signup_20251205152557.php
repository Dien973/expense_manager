<?php

@include '../config.php';

if(isset($_POST['submit'])){

   $filter_name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
   $name = mysqli_real_escape_string($conn, $filter_name);
   $filter_email = filter_var($_POST['email'], FILTER_SANITIZE_STRING);
   $email = mysqli_real_escape_string($conn, $filter_email);
   $filter_pass = filter_var($_POST['pass'], FILTER_SANITIZE_STRING);
   $pass = mysqli_real_escape_string($conn, $filter_pass);
   $filter_cpass = filter_var($_POST['cpass'], FILTER_SANITIZE_STRING);
   $cpass = mysqli_real_escape_string($conn, $filter_cpass);

   $select_users = mysqli_query($conn, "SELECT * FROM `users` WHERE uemail = '$email'") or die('Thất bại');

   if(mysqli_num_rows($select_users) > 0){
      $message[] = 'Người dùng đã tồn tại!';
   }else{
      if($pass != $cpass){
         $message[] = 'Mật khẩu không khớp!';
      }else{
         mysqli_query($conn, "INSERT INTO `users`(uname, uemail, upwd) VALUES('$name', '$email', '$pass')") or die("Lỗi SQL: " . mysqli_error($conn));
         $message[] = 'Đăng kí thành công!';
         header('location:login.php');
      }
   }

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">


   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <link rel="stylesheet" href="../css/main.css">

   <title>Đăng kí</title>
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
        <h3>ĐĂNG KÍ</h3>

        <div class="input-boxes">
            <div class="inpt">
                <i class="fa-solid fa-user"></i>
                <input type="text" name="name" class="box" placeholder="Tên tài khoản" required>
            </div>
            <div class="inpt">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" class="box" placeholder="Email" required>
            </div>
            <div class="inpt">
                <i class="fas fa-lock"></i>
                <input type="password" name="pass" class="box pwd" placeholder="Mật khẩu" required>
                <i class="fa-solid fa-eye eye"></i>
            </div>
            <div class="inpt">
                <i class="fas fa-lock"></i>
                <input type="password" name="cpass" class="box pwd" placeholder="Xác nhận mật khẩu" required>
                <i class="fa-solid fa-eye eye"></i>
            </div>

            <input type="submit" class="btn" name="submit" value="Đăng Kí">
            <p>Bạn đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
        </div>
    </form>
    </section>

    <script src="../js/show_pwd.js"></script>
</body>
</html>