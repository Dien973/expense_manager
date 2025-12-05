<?php
session_start();
include '../config.php';

if(!isset($_GET['token'])){
    die("Token không hợp lệ!");
}

$token = $_GET['token'];

$check = mysqli_query($conn, "SELECT * FROM users WHERE reset_token='$token'");

if(mysqli_num_rows($check) == 0){
    die("Token sai hoặc hết hạn!");
}

if(isset($_POST['submit'])){
    $pass = mysqli_real_escape_string($conn, $_POST['pass']);
    $cpass = mysqli_real_escape_string($conn, $_POST['cpass']);

    if($pass !== $cpass){
        $error = "Mật khẩu không khớp!";
    } else {
        mysqli_query($conn,
        "UPDATE users SET upwd='$pass', reset_token=NULL WHERE reset_token='$token'");

        $_SESSION['success'] = "Đặt lại mật khẩu thành công!";
        header("Location: login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/main.css">
    <title>Đặt lại mật khẩu</title>
</head>
<body>

<section class="form-container">
<form method="post">
    <h3>Đặt lại mật khẩu</h3>

    <?php if(isset($error)){ echo "<p style='color:red'>$error</p>"; } ?>

    <div class="inpt">
        <i class="fas fa-lock"></i>
        <input type="password" name="pass" class="box" placeholder="Mật khẩu mới" required>
    </div>

    <div class="inpt">
        <i class="fas fa-lock"></i>
        <input type="password" name="cpass" class="box" placeholder="Nhập lại mật khẩu" required>
    </div>

    <input type="submit" name="submit" class="btn" value="Cập nhật mật khẩu">
</form>
</section>

</body>
</html>
