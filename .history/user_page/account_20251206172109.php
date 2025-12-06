<?php
@include '../config.php';
session_start();

if(!isset($_SESSION['user_id'])){
    header('location:login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
/* ===================  DELETE ACCOUNT =================== */
/
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
    
    header("Location: ../index.php");
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
        // tạo dòng mới
        $insert = $conn->prepare("INSERT INTO users_detail(uid,uphone,ugender,u_birthday,uimage) VALUES(?,?,?,?,?)");
        $img = "avatar_default.jpg"; // tuỳ em
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài Khoản</title>
</head>
<body>
    <div class="profile">
        <form action="" method="post" enctype="multipart/form-data">
            <h1>Hồ Sơ Cá Nhân</h1>
            <div class="avatar">
                <img src="../assets/<?= $user['uimage'] ?? 'avata_default.jpg'; ?>" width="150">
                <input type="file" name="avatar">
            </div>

            <label>Tên người dùng</label>
            <input type="text" name="user_name" value="<?= $user['uname']; ?>">

            <label>Email</label>
            <input type="email" name="email" value="<?= $user['uemail']; ?>" readonly>
            
            <label>Số điện thoại</label>
            <input type="text" name="phone" value="<?= $user['uphone']; ?>">

            <label>Giới tính</label>
            <select name="gender">
                <option value="Khác" <?= ($user['ugender']=='Khác'?'selected':''); ?>>Khác</option>
                <option value="Nam" <?= ($user['ugender']=='Nam'?'selected':''); ?>>Nam</option>
                <option value="Nữ" <?= ($user['ugender']=='Nữ'?'selected':''); ?>>Nữ</option>
            </select>

            <label>Ngày sinh</label>
            <input type="date" name="birthday" value="<?= date('Y-m-d', strtotime($dob)); ?>">

            <label>Ngày tạo tài khoản</label>
            <input type="date" value="<?= date('Y-m-d', strtotime($user['u_create_at'])); ?>" disabled>

            <button type="submit" name="update_profile">Cập nhật</button>
            <button type="submit" name="delete_account" class="delete" onclick="return confirm('Bạn chắc chắn muốn xóa tài khoản chứ?');">
                Xóa tài khoản
            </button>

        </form>

    </div>
</body>
</html>


