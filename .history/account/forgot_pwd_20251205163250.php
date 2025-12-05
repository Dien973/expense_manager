<?php
session_start();
include '../config.php';

if(isset($_POST['submit'])){
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    $check = mysqli_query($conn, "SELECT * FROM users WHERE uemail='$email'");
    
    if(mysqli_num_rows($check) > 0){
        
        // Tạo token
        $token = bin2hex(random_bytes(32));

        // Lưu token vào DB
        mysqli_query($conn, "UPDATE users SET reset_token='$token' WHERE uemail='$email'");

        // Link reset
        $reset_link = "http://localhost/expense_manager/account/reset_pwd.php?token=".$token;

        // Gửi email
        require "send_mail.php";
        sendMail($email, $reset_link);

        $_SESSION['success'] = "Đã gửi email đặt lại mật khẩu!";
        header("Location: forgot_pwd.php");
        exit();

    } else {
        $_SESSION['error'] = "Email không tồn tại!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/main.css">
    <title>Quên mật khẩu</title>
</head>
<body>

<?php
if(isset($_SESSION['success'])){
    echo "<div class='message-overlay'><div class='message-box'><span>{$_SESSION['success']}</span><i class='fas fa-times' onclick='this.parentElement.parentElement.remove();'></i></div></div>";
    unset($_SESSION['success']);
}

if(isset($_SESSION['error'])){
    echo "<div class='message-overlay'><div class='message-box'><span>{$_SESSION['error']}</span><i class='fas fa-times' onclick='this.parentElement.parentElement.remove();'></i></div></div>";
    unset($_SESSION['error']);
}
?>

<section class="form-container">
<form action="" method="post">
    <h3>Quên Mật Khẩu</h3>
    
    <div class="inpt">
        <i class="fas fa-envelope"></i>
        <input type="email" name="email" class="box" placeholder="Nhập email đăng ký" required>
    </div>

    <input type="submit" name="submit" class="btn" value="Gửi yêu cầu">
    <p><a href="login.php">Quay lại đăng nhập</a></p>
</form>
</section>

</body>
</html>
