<?php
    @include '../config.php';
    session_start();

    $user_id = $_SESSION['user_id'];

    $dob = $user['birthday'] ?? $user['created_at'];  // nếu birthday null -> dùng ngày tạo


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
        <input type="text" name="user_name" value="<?= $user['user_name']; ?>">

        <label>Email</label>
        <input type="email" name="email" value="<?= $user['email']; ?>" readonly>
        
        <label>Số điện thoại</label>
        <input type="text" name="phone" value="<?= $user['phone']; ?>">

        <label>Giới tính</label>
        <select name="gender">
            <option value="male" <?= ($user['gender']=='male'?'selected':''); ?>>Nam</option>
            <option value="female" <?= ($user['gender']=='female'?'selected':''); ?>>Nữ</option>
            <option value="other" <?= ($user['gender']=='other'?'selected':''); ?>>Khác</option>
        </select>

        <label>Ngày sinh</label>
        <input type="date" name="birthday" value="<?= date('Y-m-d', strtotime($dob)); ?>">

        <label>Ngày tạo tài khoản</label>
        <input type="date" value="<?= date('Y-m-d', strtotime($user['created_at'])); ?>" disabled>

        <button type="submit">Cập nhật</button>
        <button class="delete">Xóa tài khoản</button>

    </div>
</body>
</html>


