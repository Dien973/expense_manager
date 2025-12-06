<?php
@include '../config.php';
session_start();

if(!isset($_SESSION['user_id'])){
    header('location:login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
/* ===================  DELETE ACCOUNT =================== */

if(isset($_POST['delete_account'])){
    
    // xóa bảng phụ
    $del1 = $conn->prepare("DELETE FROM users_detail WHERE uid=?");
    $del1->bind_param("i", $user_id);
    $del1->execute();

    // xóa bảng chính
    $del2 = $conn->prepare("DELETE FROM users WHERE uid=?");
    $del2->bind_param("i", $user_id);
    $del2->execute();

    session_destroy();
    
    header("Location: ../account/login.php");
    exit;
}


/* ===================  UPDATE =================== */

if(isset($_POST['update_profile'])){

    $uname = $_POST['user_name'];
    $phone = $_POST['phone'];
    $gender = $_POST['gender'];
    $birthday = $_POST['birthday'];

    /** update bảng users */
    $update1 = $conn->prepare("UPDATE users SET uname=? WHERE uid=?");
    $update1->bind_param("si", $uname, $user_id);
    $update1->execute();

    /** update bảng users_detail */
    // kiểm tra row tồn tại
    $check = $conn->prepare("SELECT uid FROM users_detail WHERE uid=?");
    $check->bind_param("i",$user_id);
    $check->execute();
    $check->store_result();

    if($check->num_rows == 0){
        $insert = $conn->prepare("INSERT INTO users_detail(uid,uphone,ugender,u_birthday,uimage) VALUES(?,?,?,?,?)");
        $img = "avatar_default.jpg";
        $insert->bind_param("issss",$user_id,$phone,$gender,$birthday,$img);
        $insert->execute();
    }

    // update
    $update2 = $conn->prepare("UPDATE users_detail SET uphone=?, ugender=?, u_birthday=? WHERE uid=?");
    $update2->bind_param("sssi", $phone, $gender, $birthday, $user_id);
    $update2->execute();


    /** xử lý upload ảnh */
    if($_FILES['avatar']['name'] != ""){
        $imageName = time()."_".$_FILES['avatar']['name'];
        $path = "../assets/".$imageName;
        move_uploaded_file($_FILES['avatar']['tmp_name'], $path);

        $upimg = $conn->prepare("UPDATE users_detail SET uimage=? WHERE uid=?");
        $upimg->bind_param("si",$imageName,$user_id);
        $upimg->execute();
    }
    $message = [];
    $message[] = 'Không tìm thấy người dùng!';
    header("Location: account.php");
    exit;
}

/* lấy user */
$sql = "SELECT * FROM users 
        LEFT JOIN users_detail ON users.uid = users_detail.uid 
        WHERE users.uid=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$dob = $user['u_birthday'] ?? $user['u_create_at'];

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Tài Khoản</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/main.css">
   <link rel="stylesheet" href="../css/sidebar.css">
</head>
<body>
    <?php @include 'sidebar.php'; ?>
    <?php
        if(isset($message)){
            foreach($message as $msg){
                echo '
                    <div class="message-overlay">
                        <div class="message-box">
                            <span>'.$msg.'</span>
                            <i class="fas fa-times" onclick="this.parentElement.parentElement.remove();"></i>
                        </div>
                    </div>
                ';
            }
            }
    ?>
    <div class="profile">
        <form action="" method="post" enctype="multipart/form-data">
            <h1>Hồ Sơ Cá Nhân</h1>
            <div class="avatar profile-items">
                <img src="../assets/<?= $user['uimage'] ?? 'avata_default.jpg'; ?>" width="150">
                <input type="file" name="avatar">
            </div>

            <div class="profile-items">
                <label>Tên</label>
                <input type="text" name="user_name" class="box" value="<?= $user['uname']; ?>">
            </div>

            <div class="profile-items">
                <label>Email</label>
                <input type="email" name="email" class="box disabled" value="<?= $user['uemail']; ?>" disabled>
                
            </div>

            <div class="profile-items">
                <label>Số điện thoại</label>
                <input type="text" name="phone" class="box" value="<?= $user['uphone']; ?>">
            </div>

            <div class="profile-items">
                <label>Giới tính</label>
                <select name="gender" class="box">
                    <option value="Khác" <?= ($user['ugender']=='Khác'?'selected':''); ?>>Khác</option>
                    <option value="Nam" <?= ($user['ugender']=='Nam'?'selected':''); ?>>Nam</option>
                    <option value="Nữ" <?= ($user['ugender']=='Nữ'?'selected':''); ?>>Nữ</option>
                </select>
            </div>

            <div class="profile-items">
                <label>Ngày sinh</label>
                <input type="date" name="birthday" class="box" value="<?= date('Y-m-d', strtotime($dob)); ?>">
            </div>

            <div class="profile-items">
                <label>Ngày tạo tài khoản</label>
                <input type="date" class="box disabled" value="<?= date('Y-m-d', strtotime($user['u_create_at'])); ?>" disabled>
            </div>

            <button type="submit" name="update_profile" class="btn">Cập nhật</button>
            <button type="button" id="openDelete" class="delete">Xóa tài khoản</button>
        </form>

        <div id="popupDelete" class="message-overlay" style="display:none;">
            <div class="message-box">
                <span>Bạn chắc chắn muốn xóa tài khoản chứ?</span>

                <div style="margin-top: 20px;">
                    <button id="confirmDelete" class="btn">Xác nhận</button>
                    <button id="cancelDelete" class="btn">Hủy</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById("openDelete").onclick = () => {
            document.getElementById("popupDelete").style.display = "flex";
        };
        document.getElementById("cancelDelete").onclick = () => {
            document.getElementById("popupDelete").style.display = "none";
        };

        document.getElementById("confirmDelete").onclick = () => {
            document.querySelector("button[name='delete_account']").click();
        };
    </script>

</body>
</html>


