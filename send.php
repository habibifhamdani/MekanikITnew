<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// --- IMPORTANT: Replace with your actual sending email and App Password ---
define('GMAIL_USER', 'habibifhamdani@gmail.com'); // Your Gmail address
define('GMAIL_PASSWORD', 'namq lnrs hskt pvpm'); // Your Gmail App Password (16 characters)
define('RECIPIENT_EMAIL', 'habibifhamdani@gmail.com'); // Where the email should go
define('SENDER_NAME', 'MekanikIT Contact Form');     // Name that appears as sender
// --- IMPORTANT: End of credentials section ---


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = htmlspecialchars(trim($_POST["nama"]));
    $email_pengirim = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $pesan = htmlspecialchars(trim($_POST["pesan"]));
    $baseRedirectPage = 'index.html'; // Your main page

    if (empty($nama) || empty($email_pengirim) || empty($pesan)) {
        // CORRECTED URL STRUCTURE: Query params FIRST, then #anchor
        header("Location: " . $baseRedirectPage . "?status=error&message=" . urlencode("All fields are required.") . "#contact");
        exit;
    }

    if (!filter_var($email_pengirim, FILTER_VALIDATE_EMAIL)) {
        // CORRECTED URL STRUCTURE: Query params FIRST, then #anchor
        header("Location: " . $baseRedirectPage . "?status=error&message=" . urlencode("Invalid email format.") . "#contact");
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        // Server settings for Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = GMAIL_USER;       // Use defined constant
        $mail->Password   = GMAIL_PASSWORD;   // Use defined constant
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom(GMAIL_USER, SENDER_NAME);     // Sender is your Gmail account
        $mail->addAddress(RECIPIENT_EMAIL);          // Recipient
        $mail->addReplyTo($email_pengirim, $nama);     // So replies go to the form submitter

        // Content
        $mail->isHTML(false); // Plain text email
        $mail->Subject = "Pesan baru dari Website MekanikIT oleh: $nama";
        $mail->Body    = "Anda menerima pesan baru dari formulir kontak Website MekanikIT:\n\n" .
                         "Nama: $nama\n" .
                         "Email Pengirim: $email_pengirim\n\n" .
                         "Pesan:\n$pesan";

        $mail->send();
        // CORRECTED URL STRUCTURE: Query params FIRST, then #anchor
        header("Location: " . $baseRedirectPage . "?status=success&message=" . urlencode("Pesan berhasil dikirim!") . "#contact");
        exit;

    } catch (Exception $e) {
        // Log detailed error to server logs for debugging
        error_log("Mailer Error: " . $mail->ErrorInfo); 
        header("Location: " . $baseRedirectPage . "?status=error&message=" . urlencode("Tidak berhasil mengirim pesan, silakan dicoba pada lain waktu.") . "#contact");
        exit;
    }
} else {
    // If accessed directly, redirect to the main page's contact section
    header("Location: index.html#contact"); // This one is fine as it has no query params
    exit;
}
?>