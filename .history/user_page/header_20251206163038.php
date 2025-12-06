<?php

@include 'config.php';


session_start();

$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
   header('location:login.php');
};

if(isset($_POST['update_account'])){

   $update_u_id = $_POST['update_u_id'];
   $name = mysqli_real_escape_string($conn, $_POST['uname']);
   $email = mysqli_real_escape_string($conn, $_POST['email']);
   $lsname = mysqli_real_escape_string($conn, $_POST['lsname']);
   $frname = mysqli_real_escape_string($conn, $_POST['frname']);
   $gender = mysqli_real_escape_string($conn, $_POST['gender']);   
   $address = mysqli_real_escape_string($conn, $_POST['address']);
   $phone = mysqli_real_escape_string($conn, $_POST['phone']);
   $birthday =$_POST['birthday'];

   mysqli_query($conn, "UPDATE `users` SET uname = '$name', email = '$email' WHERE uid = '$update_u_id'") or die('Thất bại');
   //mysqli_query($conn, "UPDATE `users_detail` SET uid= '$update_u_id', lastname = '$lsname', firstname = '$frname', gender = '$gender', address = '$address', phone = '$phone', birthday = '$birthday' WHERE uid = '$update_u_id'") or die('Thất bại');
   //mysqli_query($conn, "INSERT INTO `users_detail` VALUES('$update_u_id','$lsname','$frname','$gender','$address','$phone','$birthday'") or die('Thất bại');
   $select_id_user = mysqli_query($conn, "SELECT uid FROM `users_detail` WHERE  uid = '$update_u_id'") or die('Thất bại');


   $image = $_FILES['image']['name'];
   $image_size = $_FILES['image']['size'];
   $image_tmp_name = $_FILES['image']['tmp_name'];
   $image_folter = 'uploaded_img/'.$image;
   $old_image_query = mysqli_query($conn, "SELECT image FROM users_detail WHERE uid = '$update_u_id'") or die('Thất bại');
if (mysqli_num_rows($old_image_query) > 0) {
    $old_image_data = mysqli_fetch_assoc($old_image_query);
    $old_image = $old_image_data['image'];
} else {
    $old_image = ''; // Hoặc xử lý khi không có ảnh cũ
}


   
    if(mysqli_num_rows($select_id_user) > 0){
        mysqli_query($conn, "UPDATE `users_detail`SET  lastname = '$lsname', firstname = '$frname', gender = '$gender', address = '$address', phone = '$phone', birthday = '$birthday'") or die('Thất bại');

        //$message[] = 'Tên sản phẩm đã tồn tại!';
    }else{
        $insert_users = mysqli_query($conn, "INSERT INTO `users_detail`(uid,lastname,firstname,gender,address,phone,birthday) VALUES('$update_u_id','$lsname','$frname','$gender','$address','$phone','$birthday')") or die('Thất bại');


        if (!empty($old_image) && file_exists('uploaded_img/'.$old_image)) {
    unlink('uploaded_img/'.$old_image); // Xóa tệp ảnh cũ nếu tồn tại
}

    }
   $message[] = 'cập nhật tài khoản thành công!';

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Tài khoản</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <!-- custom admin css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>
   
<?php @include 'header.php'; ?>

<section class="account">
    
    <?php

    //$select_users = mysqli_query($conn, "SELECT * FROM users JOIN users_detail ON users.uid = users_detail.uid") or die('query failed');
    $select_users = mysqli_query($conn, "SELECT * FROM users JOIN users_detail ON users.uid = users_detail.uid WHERE users.uid = $user_id") or die('query failed');
    if(mysqli_num_rows($select_users) > 0){
    while($fetch_users = mysqli_fetch_assoc($select_users)){
    ?>

    <form action="" method="post" enctype="multipart/form-data">
    <h3>Tài Khoản</h3>
        <input type="hidden" value="<?php echo $fetch_users['uid']; ?>" name="update_u_id">
        <input type="hidden" name="update_u_image">
        <input type="box" class="box" value="<?php echo $fetch_users['uname']; ?>" required placeholder="Tên người dùng" name="uname">
        <input type="box" class="box" value="<?php echo $fetch_users['email']; ?>" required placeholder="Email" name="email">
        <input type="text" class="box" value="<?php echo $fetch_users['lastname']; ?>" required placeholder="Họ" name="lsname">
        <input type="text" class="box" value="<?php echo $fetch_users['firstname']; ?>" required placeholder="Tên" name="frname">
        <input type="text" class="box" value="<?php echo $fetch_users['gender']; ?>" required placeholder="Giới tính" name="gender">
        <input type="text" class="box" value="<?php echo $fetch_users['address']; ?>" required placeholder="Địa chỉ" name="address">
        <input type="text" class="box" value="<?php echo $fetch_users['phone']; ?>" required placeholder="Số điện thoại" name="phone">
        <input type="date" class="box"  name="birthday">
        <input type="file" accept="image/jpg, image/jpeg, image/png" class="box" name="image">
        <input type="submit" value="Cập nhật" name="update_account" class="btn">
        <a href="home.php" class="option-btn">Trở về</a>
    </form>





   <?php
      }
   }else{
      echo '<p class="empty">Không có</p>';
   }
?>
</section>










<script src="js/admin_script.js"></script>

</body>
</html>