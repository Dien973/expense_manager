<?php

@include '../config.php';
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Trang Chủ</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/main.css">
   <link rel="stylesheet" href="../css/sidebar.css">
</head>
<body>
    <?php @include 'sidebar.php'; ?>
    <div id="popup" class="popup">
    <div class="popup-box">
        <p id="popup-message">Thông báo ở đây</p>
        <button id="popup-ok">OK</button>
    </div>
</div>
</body>
</html>