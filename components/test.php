<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__.'/PHPMailer/src/PHPMailer.php';
require __DIR__.'/PHPMailer/src/SMTP.php';
require __DIR__.'/PHPMailer/src/Exception.php';

$status = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $to = $_POST['email'];
    $message = $_POST['message'];

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'plmunselfservicerequest@gmail.com'; // your Gmail
        $mail->Password   = 'gsbo yseb hzxg lyri';   // Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('plmunselfservicerequest@gmail.com', 'PLMun System');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = 'Message from PHP Form';
        $mail->Body    = nl2br($message);

        $mail->send();
        $status = "✅ Message sent successfully!";
    } catch (Exception $e) {
        $status = "❌ Error: {$mail->ErrorInfo}";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Send Email</title>
    <style>
        body {
            font-family: Arial;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            width: 350px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        h2 {
            text-align: center;
        }

        input, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        button {
            width: 100%;
            margin-top: 15px;
            padding: 10px;
            border: none;
            background: #4facfe;
            color: white;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #00c6ff;
        }

        .status {
            margin-top: 10px;
            text-align: center;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="card">
    <h2>📧 Send Email</h2>

    <form method="POST">
        <input type="email" name="email" placeholder="Receiver Gmail" required>
        <textarea name="message" rows="5" placeholder="Your message..." required></textarea>
        <button type="submit">Send</button>
    </form>

    <div class="status"><?php echo $status; ?></div>
</div>

</body>
</html>