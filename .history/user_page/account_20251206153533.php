<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài Khoản</title>
</head>
<body>
    <div class="account-page">
        <h1>Hồ Sơ Cá Nhân</h1>
        <img src="../images/user.png" alt="User Image" class="user-image">
        <p><?php echo $_SESSION['user_name']; ?></p>
        <p><?php echo $_SESSION['user_email']; ?></p>
        <p><?php echo $_SESSION['user_phone']; ?></p>
        <p></p>
        <a href="edit_account.php" class="edit-btn">Chỉnh Sửa Hồ Sơ</a>
    </div>
</body>
</html>

<div class="profile">

    <div class="avatar">
        <img src="<?= $user['avatar'] ?? './assets/avatar-default.png'; ?>" width="150">
        <input type="file" name="avatar">
    </div>

    <label>Tên người dùng</label>
    <input type="text" name="user_name" value="<?= $user['user_name']; ?>">

    <label>Số điện thoại</label>
    <input type="text" name="phone" value="<?= $user['phone']; ?>">

    <label>Email</label>
    <input type="email" name="email" value="<?= $user['email']; ?>" readonly>

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

</div>
