<?php
session_start();
session_unset(); // Xóa tất cả các session
session_destroy(); // Hủy session
header('location:login.php'); // Quay lại trang đăng nhập
exit();
?>