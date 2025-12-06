<?php
@include '../config.php';

if (!isset($_SESSION['user_id'])) {
   header('location:login.php');
}

$user_id = $_SESSION['user_id'];

// XOÁ TÀI KHOẢN
if(isset($_POST['delete_account'])){
   mysqli_query($conn, "DELETE FROM users_detail WHERE uid = '$user_id' ");
   mysqli_query($conn, "DELETE FROM users WHERE uid = '$user_id' ");
   session_destroy();
   header('location:login.php');
   exit();
}


// UPDATE PROFILE
if(isset($_POST['update_account'])){

   $name = $_POST['uname'];
   $email = $_POST['uemail'];
   $gender = $_POST['ugender'];
   $phone = $_POST['uphone'];
   $birthday = $_POST['u_birthday'];

   // update bảng users
   mysqli_query($conn,"UPDATE users SET 
         uname='$name', 
         uemail='$email'
      WHERE uid='$user_id'
   ");

   // kiểm tra user_detail
   $check = mysqli_query($conn,"SELECT * FROM users_detail WHERE uid='$user_id' ");

   // xử lý avatar
   $image = $_FILES['uimage']['name'];
   $image_tmp = $_FILES['uimage']['tmp_name'];
   $folder = "assets/";

   if(mysqli_num_rows($check) > 0){

      $row = mysqli_fetch_assoc($check);
      $old_img = $row['uimage'];

      // nếu upload ảnh mới
      if(!empty($image)){
         move_uploaded_file($image_tmp,$folder.$image);

         // xoá ảnh cũ
         if(!empty($old_img) && file_exists($folder.$old_img)){
            unlink($folder.$old_img);
         }
         $uimg = $image;
      }else{
         $uimg = $old_img;
      }

      mysqli_query($conn,"
         UPDATE users_detail 
         SET 
            uphone='$phone',
            ugender='$gender',
            u_birthday='$birthday',
            uimage='$uimg'
         WHERE uid='$user_id'
      ");

   }else{
      // chưa có → INSERT
      move_uploaded_file($image_tmp,$folder.$image);

      mysqli_query($conn,"
         INSERT INTO users_detail(uid,uphone,ugender,u_birthday,uimage) 
         VALUES('$user_id','$phone','$gender','$birthday','$image')
      ");
   }


   $message = "Cập nhật thành công!";
}


// lấy info user
$info = mysqli_query($conn,"
     SELECT * FROM users 
     LEFT JOIN users_detail 
     ON users.uid = users_detail.uid
     WHERE users.uid='$user_id'
");

$user = mysqli_fetch_assoc($info);

?>

<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>Tài khoản</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'header.php' ?>

<section class="account">

<form action="" method="post" enctype="multipart/form-data">

<h2>Tài khoản</h2>

<input type="text" class="box" name="uname" value="<?=$user['uname']?>">

<input type="email" class="box" name="uemail" value="<?=$user['uemail']?>">

<input type="text" class="box" name="uphone" value="<?=$user['uphone']?>">

<input type="text" class="box" name="ugender" value="<?=$user['ugender']?>">

<input type="date" class="box" name="u_birthday" value="<?=$user['u_birthday']?>">

<img src="assets/<?=$user['uimage']?>" width="120"><br>

<input type="file" class="box" name="uimage">

<input type="submit" value="Cập nhật" name="update_account" class="btn">

<button name="delete_account" onclick="return confirm('Xóa tài khoản?')" class="delete-btn">Xóa tài khoản</button>

<a href="home.php" class="option-btn">Trở về</a>

</form>
</section>

</body>
</html>
