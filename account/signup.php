<?php

@include '../config.php';
session_start();

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
       $message[] = 'Email người dùng đã tồn tại!';
    }else{
       if($pass != $cpass){
            $message[] = 'Mật khẩu không khớp!';
        }else{
            mysqli_begin_transaction($conn);

            try {
                mysqli_query($conn, "INSERT INTO `users`(uname, uemail, upwd) VALUES('$name', '$email', '$pass')") or die("Lỗi SQL: " . mysqli_error($conn));

                $user_id = mysqli_insert_id($conn);

                $uphone = "Chưa cập nhật";
                $ugender = "Khác";
                $uimage = "avata_default.jpg";

                $detail_query = "INSERT INTO `users_detail`(uid, uphone, ugender, uimage) 
                                VALUES('$user_id', '$uphone', '$ugender', '$uimage')";
                mysqli_query($conn, $detail_query) or die("Lỗi SQL (users_detail): " . mysqli_error($conn));

                mysqli_commit($conn);
                $_SESSION['success'] = 'Đăng kí thành công!';
                header('location:login.php');
                exit();
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $message[] = 'Lỗi đăng ký: ' . $e->getMessage();
            }
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
            <div class="message-overlay">
                <div class="message-box">
                    <span>'.$message.'</span>
                    <i class="fas fa-times" onclick="this.parentElement.parentElement.remove();"></i>
                </div>
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
                <i class="fa-solid fa-eye eye toggle-eye"></i>
            </div>
            <div class="inpt">
                <i class="fas fa-lock"></i>
                <input type="password" name="cpass" class="box pwd" placeholder="Xác nhận mật khẩu" required>
                <i class="fa-solid fa-eye eye toggle-eye"></i>
            </div>

            <input type="submit" class="btn" name="submit" value="Đăng Kí">
            <p>Bạn đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
        </div>
    </form>
    </section>

    <script src="../js/show_pwd.js"></script>
</body>
</html>