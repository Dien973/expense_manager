<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';
require '../phpmailer/src/Exception.php';

function sendMail($email, $reset_link){
    $mail = new PHPMailer(true);

    try {
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;

        // ⚠ ĐIỀN EMAIL & APP PASSWORD CỦA BẠN
        $mail->Username   = 'dientran7890@gmail.com';
        $mail->Password   = 'wggb dqxo gnyf hntp';

        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        
        // Người gửi
        $mail->setFrom('dientran7890@gmail.com', 'expense_manager');

        // Người nhận
        $mail->addAddress($email);

        // Nội dung email
        $mail->isHTML(true);
        $mail->Subject = "Yêu Cầu Đặt Lại Mật Khẩu Monee";
        $mail->Body    = "
            <h3>Bạn quên mật khẩu?</h3>
            <h4>Đừng lo lắng, thỉnh thoảng chúng ta cũng hay quên mật khẩu mà!</h4>
            <h4>Để thay đổi mật khẩu, hãy nhấn vào link bên dưới 👇</h4>
            <a href='$reset_link'>$reset_link</a>
        ";

        $mail->send();
    } catch (Exception $e) {
        echo "Lỗi gửi email: {$mail->ErrorInfo}";
    }
}
?>
