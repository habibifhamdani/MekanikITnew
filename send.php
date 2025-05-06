<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = htmlspecialchars(trim($_POST["nama"]));
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $pesan = htmlspecialchars(trim($_POST["pesan"]));

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth = true;
        $mail->Username = '8b2e230ab18e1f'; // From Mailtrap
        $mail->Password = '6bf3f1ad7f86eb'; // From Mailtrap
        $mail->Port = 2525;

        // Recipients
        $mail->setFrom($email, $nama);
        $mail->addAddress('admin@mekanikit.com');

        // Content
        $mail->isHTML(false);
        $mail->Subject = "Pesan baru dari $nama";
        $mail->Body    = "Nama: $nama\nEmail: $email\n\nPesan:\n$pesan";

        $mail->send();
        echo "Pesan berhasil dikirim (simulasi dengan Mailtrap)";
    } catch (Exception $e) {
        echo "Gagal mengirim pesan. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>
