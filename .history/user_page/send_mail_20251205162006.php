<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../phpmailer/PHPMailer.php';
require '../phpmailer/SMTP.php';
require '../phpmailer/Exception.php';

function sendMail($email, $reset_link){
    $mail = new PHPMailer(true);

    try {
        // SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;

        // ⚠ ĐIỀN EMAIL & APP PASSWORD CỦA BẠN
        $mail->Username   = 'YOUR_EMAIL@gmail.com';
        $mail->Password   = 'YOUR_APP_PASSWORD';

        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        
        // Người gửi
        $mail->setFrom('YOUR_EMAIL@gmail.com', 'Expense Manager');

        // Người nhận
        $mail->addAddress($email);

        // Nội dung email
        $mail->isHTML(true);
        $mail->Subject = "Yêu cầu đặt lại mật khẩu";
        $mail->Body    = "
            <h3>Nhấn vào link bên dưới để đặt lại mật khẩu:</h3>
            <a href='$reset_link'>$reset_link</a>
        ";

        $mail->send();
    } catch (Exception $e) {
        echo "Lỗi gửi email: {$mail->ErrorInfo}";
    }
}
?>
