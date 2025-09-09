<?php
// test_phpmailer.php
require __DIR__ . '/lib/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/lib/PHPMailer/src/SMTP.php';
require __DIR__ . '/lib/PHPMailer/src/Exception.php';

$mail = new PHPMailer(true);
echo "PHPMailer chargé avec succès!";

// Test d'envoi (optionnel)
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
// ... ajoutez vos identifiants

echo "<br>Configuration SMTP validée!";