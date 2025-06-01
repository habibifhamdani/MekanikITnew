<?php
// 1. Enable Full Error Reporting AT THE VERY TOP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// --- IMPORTANT: CREDENTIALS SECTION ---
// Ensure these are correct and the RECAPTCHA_SECRET_KEY is your actual secret key.
// For live servers, consider moving these out of the script (e.g., to an included config file that's not in git).
define('GMAIL_USER', 'contact@mekanikit.com'); // Your SMTP username
define('GMAIL_PASSWORD', ')QswZ6{t#X{[');    // Password for the SMTP username
define('RECIPIENT_EMAIL', 'admin@mekanikit.com'); // Main recipient
define('SENDER_NAME', 'MekanikIT Contact Form');     // Sender name
define('RECAPTCHA_SECRET_KEY', '6LeOElIrAAAAAJIqAlbHxCUVL8pJSGS9HehkG5Nc'); // YOUR ACTUAL SECRET KEY
// --- IMPORTANT: End of credentials section ---

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $baseRedirectPage = 'index.html';

    // Get and sanitize form data
    $nama = isset($_POST["nama"]) ? htmlspecialchars(trim($_POST["nama"])) : '';
    $email_pengirim = isset($_POST["email"]) ? filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL) : '';
    $pesan = isset($_POST["pesan"]) ? htmlspecialchars(trim($_POST["pesan"])) : '';
    $recaptchaResponse = isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : '';

    $errorReturnData = [
        'nama_val' => $nama,
        'email_val' => $email_pengirim,
        'pesan_val' => $pesan
    ];

    // 1. CAPTCHA Verification
    if (!empty($recaptchaResponse)) {
        $verifyURL = 'https://www.google.com/recaptcha/api/siteverify';
        $postData = http_build_query([
            'secret'   => RECAPTCHA_SECRET_KEY,
            'response' => $recaptchaResponse,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ]);
        $options = ['http' => ['header' => "Content-type: application/x-www-form-urlencoded\r\n", 'method' => 'POST', 'content' => $postData]];
        $context  = stream_context_create($options);
        $verify = file_get_contents($verifyURL, false, $context);
        $captcha_success = json_decode($verify);

        if (!$verify || $captcha_success === null || !isset($captcha_success->success) || $captcha_success->success == false) {
            $error_codes_message = "Verification request failed or response malformed.";
            if (isset($captcha_success->{'error-codes'}) && is_array($captcha_success->{'error-codes'})) {
                $error_codes_message = "Error codes: " . implode(', ', $captcha_success->{'error-codes'});
            }
            error_log("reCAPTCHA Verification Failed. " . $error_codes_message . " Raw Response: " . $verify);
            
            $user_message = "Verifikasi CAPTCHA gagal. Tolong ulangi lagi!";
            if (isset($captcha_success->{'error-codes'})) {
                if (in_array('timeout-or-duplicate', $captcha_success->{'error-codes'})) {
                    $user_message = "CAPTCHA challenge expired. Tolong refresh dan ulangi lagi!.";
                } else if (in_array('invalid-input-response', $captcha_success->{'error-codes'})) {
                    $user_message = "CAPTCHA response was invalid. Tolong ulangi lagi!";
                }
            }
            $errorReturnData['status'] = 'error';
            $errorReturnData['message'] = $user_message;
            header("Location: " . $baseRedirectPage . "?" . http_build_query($errorReturnData) . "#contact");
            exit;
        }
    } else {
        $errorReturnData['status'] = 'error';
        $errorReturnData['message'] = "Tolong isi CAPTCHA!";
        header("Location: " . $baseRedirectPage . "?" . http_build_query($errorReturnData) . "#contact");
        exit;
    }

    // 2. Other Form Data Validation
    if (empty($nama) || empty($email_pengirim) || empty($pesan)) {
        $errorReturnData['status'] = 'error';
        $errorReturnData['message'] = "All fields are required.";
        header("Location: " . $baseRedirectPage . "?" . http_build_query($errorReturnData) . "#contact");
        exit;
    }

    if (!filter_var($email_pengirim, FILTER_VALIDATE_EMAIL)) {
        $errorReturnData['status'] = 'error';
        $errorReturnData['message'] = "Invalid email format.";
        header("Location: " . $baseRedirectPage . "?" . http_build_query($errorReturnData) . "#contact");
        exit;
    }

    // 3. Send Email
    $mail = new PHPMailer(true); // Enable exceptions

    try {
        // Server settings for mail.mekanikit.com
        $mail->isSMTP();
        $mail->Host       = 'mail.mekanikit.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = GMAIL_USER;       // 'contact@mekanikit.com'
        $mail->Password   = GMAIL_PASSWORD;   // Password for 'contact@mekanikit.com'
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use SMTPS if port is 465
        $mail->Port       = 465;                     // Use 465 if SMTPSecure is SMTPS
        
        // $mail->SMTPDebug = 2; // UNCOMMENT THIS LINE FOR DETAILED SMTP DEBUGGING IF NEEDED
                                // (and temporarily comment out header() redirects below)

        // Recipients
        $mail->setFrom(GMAIL_USER, SENDER_NAME);
        $mail->addAddress(RECIPIENT_EMAIL);
        $mail->addReplyTo(RECIPIENT_EMAIL, 'Admin MekanikIT');

        // Add Carbon Copy (CC) to the form submitter
        if (!empty($email_pengirim)) { // Already validated by filter_var
            $mail->addCC($email_pengirim, $nama);
        }

        // Content
        $mail->isHTML(false); 
        $mail->Subject = "Pesan Kontak Baru dari Website MekanikIT: $nama";
        $mail->Body    = "Pesan berikut telah dikirim melalui formulir kontak Website MekanikIT.\n\n" .
                         "------------------------------------------------------\n" .
                         "Detail Pengirim:\n" .
                         "------------------------------------------------------\n" .
                         "Nama           : $nama\n" .
                         "Email Pengirim : $email_pengirim\n" .
                         "------------------------------------------------------\n" .
                         "Isi Pesan:\n" .
                         "------------------------------------------------------\n" .
                         "$pesan\n\n" .
                         "------------------------------------------------------\n";
        $mail->send();

        header("Location: " . $baseRedirectPage . "?status=success&message=" . urlencode("Pesan berhasil dikirim!") . "#contact");
        exit;

    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo); // Logs the detailed error to the server's error log
        $errorReturnData['status'] = 'error';
        $errorReturnData['message'] = "Pesan gagal dikirim. Silakan coba lagi nanti."; // Generic message for user
        header("Location: " . $baseRedirectPage . "?" . http_build_query($errorReturnData) . "#contact");
        exit;
    }
} else {
    // If not a POST request, redirect to the contact form
    header("Location: index.html#contact");
    exit;
}
?>