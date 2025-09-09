<?php
require_once 'init.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Vérifier si la connexion existe déjà (peut-être déjà établie par header.php)
if (!isset($conn)) {
    $conn = new mysqli("localhost", "root", "", "tchadshop_db");
    if ($conn->connect_error) die("Erreur de connexion : " . $conn->connect_error);
}

// Chargement MANUEL de PHPMailer
require __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../lib/PHPMailer/src/SMTP.php';
require __DIR__ . '/../lib/PHPMailer/src/Exception.php';

// Utilisez le namespace complet
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Récupérer les données POST
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$token = $data['token'] ?? '';

// Vérifier si l'email et le token sont présents
if (empty($email) || empty($token)) {
    echo json_encode([
        'success' => false,
        'error' => 'Paramètres manquants'
    ]);
    exit;
}

try {
    $mail = new PHPMailer(true);
    
    // Configuration SMTP (même que dans le fichier principal)
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'issakhadaoudabdelkerim95@gmail.com';
    $mail->Password = 'xcow wdmk qjur wnls';
    
    // Paramètres optimisés
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;
    
    // Options pour contourner les problèmes SSL
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];
    
    // Destinataire et contenu
    $mail->setFrom('issakhadaoudabdelkerim95@gmail.com', 'TchadShop');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Nouvelle demande de réinitialisation';
    
    $reset_link = "http://192.168.11.103/ammashop/reset_password.php?token=$token";
    
    $mail->Body = "
        <p>Bonjour,</p>
        <p>Vous avez demandé un nouveau lien de réinitialisation pour votre mot de passe TchadShop.</p>
        <p>Cliquez sur ce lien pour créer un nouveau mot de passe :</p>
        <p><a href='$reset_link'>$reset_link</a></p>
        <p>Ce lien expirera dans 15 minutes.</p>
        <p>Cordialement,<br>L'équipe TchadShop</p>
    ";
    
    $mail->send();
    
    echo json_encode([
        'success' => true,
        'message' => 'Un nouvel email de réinitialisation a été envoyé!'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage()
    ]);
}