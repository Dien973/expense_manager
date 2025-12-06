<?php
    @include '../config.php';
    session_start();

    $user_id = $_SESSION['user_id'];

    if(!isset($user_id)){
    header('location:login.php');
    };

    // ---- TRUY VẤN LẤY USER ----
    $sql = "SELECT * FROM users 
            LEFT JOIN users_detail 
            ON users.uid = users_detail.uid 
            WHERE users.uid = ?";
    $stmt = $conn->prepare($sql);

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 0){
        die("Không tìm thấy người dùng!");
    }

$user = $result->fetch_assoc();
    $dob = $user['birthday'] ?? $user['u_create_at'];  // nếu birthday null -> dùng ngày tạo

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
        <h1>Hồ Sơ Cá Nhân</h1>
        <div class="avatar">
            <img src="<?= $user['avatar'] ?? './assets/avatar-default.png'; ?>" width="150">
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

        <button type="submit">Cập nhật</button>
        <button class="delete">Xóa tài khoản</button>

    </div>
</body>
</html>


