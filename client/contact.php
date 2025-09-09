<?php
require_once 'init.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



// Vérifier si la connexion existe déjà (peut-être déjà établie par header.php)
if (!isset($conn)) {
    $conn = new mysqli("localhost", "root", "", "tchadshop_db");
    if ($conn->connect_error) die($trans['connection_error'] . ": " . $conn->connect_error);
}

$client_id = isset($_SESSION['client_id']) ? $_SESSION['client_id'] : null;

// Récupération des infos client si connecté
$client_info = [];
if ($client_id) {
    $stmt = $conn->prepare("SELECT nom, email, telephone FROM clients WHERE id = ?");
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $client_info = $result->fetch_assoc();
    }
    $stmt->close();
}

// Dossier pour stocker les audios
$upload_dir = __DIR__ . "/uploads/audio_contact/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$message_envoye = false;
$erreurs = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
    $telephone = trim($_POST['telephone']);
    $message = !empty($_POST['message']) ? trim($_POST['message']) : null;
    $audio_path = null;

    // Validation
    if (empty($nom)) {
        $erreurs['nom'] = $trans['please_enter_name'];
    }
    
    if (empty($telephone)) {
        $erreurs['telephone'] = $trans['please_enter_phone'];
    } elseif (!preg_match('/^[0-9+]{8,20}$/', $telephone)) {
        $erreurs['telephone'] = $trans['invalid_phone'];
    }
    
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs['email'] = $trans['invalid_email'];
    }
    
    if (empty($message) && empty($_FILES['audio']['name'])) {
        $erreurs['message'] = $trans['please_write_message_or_record_audio'];
    }

    // Upload audio si présent
    if (empty($erreurs)) {
        if (!empty($_FILES['audio']['name'])) {
            $ext = pathinfo($_FILES['audio']['name'], PATHINFO_EXTENSION);
            $filename = "contact_audio_" . time() . "_" . rand(1000, 9999) . "." . $ext;
            $target_file = $upload_dir . $filename;
            
            // Validation du fichier audio
            $allowed_extensions = ['mp3', 'wav', 'ogg', 'm4a'];
            $max_file_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array(strtolower($ext), $allowed_extensions)) {
                $erreurs['audio'] = $trans['unsupported_audio_format'];
            } elseif ($_FILES['audio']['size'] > $max_file_size) {
                $erreurs['audio'] = $trans['file_too_large'];
            } elseif (move_uploaded_file($_FILES['audio']['tmp_name'], $target_file)) {
                $audio_path = "uploads/audio_contact/" . $filename;
            } else {
                $erreurs['audio'] = $trans['audio_upload_error'];
            }
        }
    }

    // Sauvegarde dans la base de données
    if (empty($erreurs)) {
        $stmt = $conn->prepare("INSERT INTO contacts (client_id, nom, email, telephone, message, audio_path) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $client_id, $nom, $email, $telephone, $message, $audio_path);
        if ($stmt->execute()) {
            $message_envoye = true;
        } else {
            $erreurs['general'] = $trans['message_send_error'];
        }
        $stmt->close();
    }
}

// Récupérer le sujet depuis l'URL s'il existe
$sujet = isset($_GET['subject']) ? htmlspecialchars($_GET['subject']) : "";

?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - TchadShop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f9f7ff 0%, #eef2ff 100%);
            color: #333;
            min-height: 100vh;
            line-height: 1.6;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: linear-gradient(135deg, #6a1b9a 0%, #8e24aa 100%);
            color: white;
            padding: 15px 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .logo i {
            margin-right: 10px;
            font-size: 1.8rem;
        }
        
        .main-content {
            flex: 1;
            padding-top: 110px;
            padding-bottom: 30px;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-title {
            text-align: center;
            margin-bottom: 40px;
            color: #6a1b9a;
            position: relative;
            padding-bottom: 20px;
        }
        
        .page-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(to right, #6a1b9a, #4c956c);
            border-radius: 2px;
        }

        .contact-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .contact-info {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            padding: 30px;
            height: fit-content;
        }

        .contact-info h3 {
            color: #6a1b9a;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1.5rem;
        }

        .contact-info h3 i {
            background: linear-gradient(135deg, #6a1b9a 0%, #8e24aa 100%);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .contact-method {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            align-items: flex-start;
        }

        .contact-method i {
            font-size: 1.5rem;
            color: #6a1b9a;
            min-width: 30px;
        }

        .contact-method-content h4 {
            color: #6a1b9a;
            margin-bottom: 5px;
        }

        .contact-method-content p {
            color: #555;
        }

        .whatsapp-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: #25D366;
            color: white;
            padding: 12px 20px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-top: 20px;
            text-align: center;
        }

        .whatsapp-btn:hover {
            background: #128C7E;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(37, 211, 102, 0.3);
        }

        .contact-form {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #6a1b9a;
            box-shadow: 0 0 0 3px rgba(106, 27, 154, 0.2);
        }

        .form-group .error {
            color: #e74c3c;
            font-size: 0.9rem;
            margin-top: 5px;
            display: block;
        }

        .audio-section {
            background: #f9f5ff;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            border: 1px dashed #8e24aa;
        }

        .audio-section h4 {
            color: #6a1b9a;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .audio-controls {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .record-btn {
            flex: 1;
            background: #6a1b9a;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .record-btn:hover {
            background: #8e24aa;
        }

        .record-btn.recording {
            background: #e74c3c;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(231, 76, 60, 0); }
            100% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0); }
        }

        .audio-preview {
            margin-top: 15px;
            display: none;
        }

        .submit-btn {
            display: block;
            width: 100%;
            background: linear-gradient(135deg, #6a1b9a 0%, #8e24aa 100%);
            color: white;
            padding: 15px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            margin-top: 20px;
            font-size: 1.1rem;
        }
        
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(106, 27, 154, 0.3);
        }
        
        .submit-btn i {
            margin-right: 8px;
        }

        .confirmation {
            background: #d4edda;
            padding: 20px;
            border: 1px solid #c3e6cb;
            color: #155724;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .confirmation i {
            font-size: 3rem;
            color: #28a745;
        }

        .confirmation h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .error-message {
            background: #f8d7da;
            padding: 15px;
            border: 1px solid #f5c6cb;
            color: #721c24;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .map-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-top: 30px;
        }

        .map-card h3 {
            padding: 20px;
            background: #6a1b9a;
            color: white;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .map-container {
            height: 400px;
            width: 100%;
            background: #f8f9fa;
        }

        .delete-audio {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .delete-audio:hover {
            background: #c0392b;
        }

        .location-controls {
            padding: 15px 20px;
            background: #f8f5ff;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .location-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .location-btn {
            background: #4caf50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        
        .location-btn:hover {
            background: #388e3c;
        }
        
        .location-btn.my-location {
            background: #2196f3;
        }
        
        .location-btn.my-location:hover {
            background: #1976d2;
        }

        /* NOUVELLE SECTION DE RÉPONSE */
        .response-section {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            padding: 30px;
            margin-top: 40px;
            border-left: 5px solid #4c956c;
        }
        
        .response-section h3 {
            color: #4c956c;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
        }
        
        .response-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .response-option {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
            cursor: pointer;
        }
        
        .response-option:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border-color: #4c956c;
        }
        
        .response-option i {
            font-size: 2.5rem;
            color: #4c956c;
            margin-bottom: 15px;
        }
        
        .response-option h4 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .response-option p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .response-option.active {
            background: #e8f5e9;
            border-color: #4c956c;
        }
        
        .leaflet-container {
            background: #f8f9fa;
        }
        
        .leaflet-control-attribution {
            background: rgba(255, 255, 255, 0.8);
            padding: 2px 5px;
            font-size: 0.7rem;
        }
        
        .leaflet-popup-content {
            font-weight: 600;
            color: #6a1b9a;
        }
        
        .custom-marker {
            background: #6a1b9a;
            border: 2px solid white;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
        }
        
        .custom-marker i {
            color: white;
            font-size: 16px;
        }
        
        .user-marker {
            background: #2196f3;
            border: 2px solid white;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
        }
        
        .user-marker i {
            color: white;
            font-size: 16px;
        }
        
        .distance-info {
            padding: 10px 20px;
            background: #f8f5ff;
            border-bottom: 1px solid #e0e0e0;
            font-weight: 600;
            color: #6a1b9a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Section client connecté */
        .client-status {
            background: #e8f4fc;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 4px solid #2196f3;
        }
        
        .client-status i {
            font-size: 2rem;
            color: #2196f3;
        }
        
        .client-status-content h3 {
            color: #1976d2;
            margin-bottom: 8px;
        }
        
        .messagerie-link {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background: #4caf50;
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .messagerie-link:hover {
            background: #388e3c;
            transform: translateY(-2px);
        }
        
        .messagerie-link i {
            margin-right: 8px;
        }

        /* RESPONSIVE DESIGN */
        @media (max-width: 992px) {
            .contact-container {
                grid-template-columns: 1fr;
            }
            
            .contact-info, .contact-form {
                padding: 25px;
            }
            
            .page-title {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding-top: 90px;
            }
            
            .contact-info, .contact-form {
                padding: 20px;
            }
            
            .page-title {
                font-size: 1.6rem;
            }
            
            .audio-controls {
                flex-direction: column;
            }
            
            .map-container {
                height: 300px;
            }
            
            .location-controls {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 1.4rem;
            }
            
            .contact-method {
                flex-direction: column;
            }
            
            .map-container {
                height: 250px;
            }
            
            .response-options {
                grid-template-columns: 1fr;
            }
            
            .client-status {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
   <?php include 'header.php'; ?>

    <main class="main-content">
    <div class="container">
        <h1 class="page-title">
            <i class="fas fa-headset"></i> <?php echo $trans['contact_tchadshop']; ?>
        </h1>
        
        <?php if ($message_envoye): ?>
            <div class="confirmation">
                <i class="fas fa-check-circle"></i>
                <h3><?php echo $trans['message_sent_success']; ?></h3>
                <p><?php echo $trans['message_received']; ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($erreurs['general'])): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $erreurs['general']; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($client_id && !empty($client_info)): ?>
            <div class="client-status">
                <i class="fas fa-user-check"></i>
                <div class="client-status-content">
                    <h3><?php echo $trans['connected_as_client']; ?></h3>
                    <p><?php echo $trans['contact_info_prefilled']; ?></p>
                    <a href="message_support.php" class="messagerie-link">
                        <i class="fas fa-envelope"></i> <?php echo $trans['access_messaging']; ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="contact-container">
            <div class="contact-info">
                <h3><i class="fas fa-map-marker-alt"></i> <?php echo $trans['our_contact_info']; ?></h3>
                
                <div class="contact-method">
                    <i class="fas fa-map-marker-alt"></i>
                    <div class="contact-method-content">
                        <h4><?php echo $trans['address']; ?></h4>
                        <p>Rue du Commerce, N°123</p>
                        <p>N'Djaména, Tchad</p>
                    </div>
                </div>
                
                <div class="contact-method">
                    <i class="fas fa-phone-alt"></i>
                    <div class="contact-method-content">
                        <h4><?php echo $trans['phone']; ?></h4>
                        <p>+235 XX XX XX XX</p>
                        <p>+235 YY YY YY YY</p>
                        <p><?php echo $trans['business_hours']; ?></p>
                    </div>
                </div>
                
                <div class="contact-method">
                    <i class="fas fa-envelope"></i>
                    <div class="contact-method-content">
                        <h4><?php echo $trans['email']; ?></h4>
                        <p>contact@tchadshop.td</p>
                        <p>support@tchadshop.td</p>
                        <p><?php echo $trans['response_time']; ?></p>
                    </div>
                </div>
                
                <a href="https://wa.me/23560000000" class="whatsapp-btn" target="_blank">
                    <i class="fab fa-whatsapp"></i> <?php echo $trans['chat_whatsapp']; ?>
                </a>
            </div>
            
            <div class="contact-form">
                <h3><i class="fas fa-paper-plane"></i> <?php echo $trans['send_us_message']; ?></h3>
                
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="nom"><?php echo $trans['full_name']; ?></label>
                        <input type="text" id="nom" name="nom" placeholder="<?php echo $trans['your_name']; ?>" 
                               value="<?php 
                                   if (isset($_POST['nom'])) {
                                       echo htmlspecialchars($_POST['nom']);
                                   } elseif (!empty($client_info['nom'])) {
                                       echo htmlspecialchars($client_info['nom']);
                                   }
                               ?>">
                        <?php if (!empty($erreurs['nom'])): ?>
                            <span class="error"><i class="fas fa-exclamation-circle"></i> <?php echo $erreurs['nom']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="email"><?php echo $trans['email_address']; ?></label>
                        <input type="email" id="email" name="email" placeholder="<?php echo $trans['your_email']; ?>" 
                               value="<?php 
                                   if (isset($_POST['email'])) {
                                       echo htmlspecialchars($_POST['email']);
                                   } elseif (!empty($client_info['email'])) {
                                       echo htmlspecialchars($client_info['email']);
                                   }
                               ?>">
                        <?php if (!empty($erreurs['email'])): ?>
                            <span class="error"><i class="fas fa-exclamation-circle"></i> <?php echo $erreurs['email']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="telephone"><?php echo $trans['phone_number']; ?></label>
                        <input type="tel" id="telephone" name="telephone" placeholder="<?php echo $trans['your_phone']; ?>" 
                               value="<?php 
                                   if (isset($_POST['telephone'])) {
                                       echo htmlspecialchars($_POST['telephone']);
                                   } elseif (!empty($client_info['telephone'])) {
                                       echo htmlspecialchars($client_info['telephone']);
                                   }
                               ?>">
                        <?php if (!empty($erreurs['telephone'])): ?>
                            <span class="error"><i class="fas fa-exclamation-circle"></i> <?php echo $erreurs['telephone']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="message"><?php echo $trans['message']; ?></label>
                        <textarea id="message" name="message" rows="5" placeholder="<?php echo $trans['write_message_here']; ?>"><?php 
                            echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : $sujet; 
                        ?></textarea>
                        <?php if (!empty($erreurs['message'])): ?>
                            <span class="error"><i class="fas fa-exclamation-circle"></i> <?php echo $erreurs['message']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                   <div class="audio-section">
                        <h4><i class="fas fa-microphone"></i> <?php echo $trans['voice_message']; ?></h4>
                        <p><?php echo $trans['record_voice_message']; ?></p>
                        
                        <div class="audio-controls">
                            <button type="button" class="record-btn" id="recordBtn">
                                <i class="fas fa-microphone"></i> <?php echo $trans['record']; ?>
                            </button>
                            <input type="file" name="audio" id="audioFile" accept="audio/*" style="display: none;">
                            <label for="audioFile" class="record-btn">
                                <i class="fas fa-upload"></i> <?php echo $trans['upload']; ?>
                            </label>
                        </div>
                        
                        <div class="audio-preview" id="audioPreview">
                            <audio controls id="audioPlayer"></audio>
                            <p class="audio-info" id="audioInfo"></p>
                            <button type="button" class="delete-audio" id="deleteAudio">
                                <i class="fas fa-trash"></i> <?php echo $trans['delete_audio']; ?>
                            </button>
                        </div>
                        
                        <?php if (!empty($erreurs['audio'])): ?>
                            <span class="error"><i class="fas fa-exclamation-circle"></i> <?php echo $erreurs['audio']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> <?php echo $trans['send_message']; ?>
                    </button>
                </form>
            </div>
        </div>
        
        <!-- NOUVELLE SECTION DE RÉPONSE -->
        <div class="response-section">
            <h3><i class="fas fa-reply"></i> <?php echo $trans['how_we_respond']; ?></h3>
            <p><?php echo $trans['response_description']; ?></p>
            
            <div class="response-options">
                <div class="response-option">
                    <i class="fas fa-envelope"></i>
                    <h4><?php echo $trans['by_email']; ?></h4>
                    <p><?php echo $trans['email_response']; ?></p>
                </div>
                
                <div class="response-option">
                    <i class="fas fa-mobile-alt"></i>
                    <h4><?php echo $trans['by_sms_phone']; ?></h4>
                    <p><?php echo $trans['sms_response']; ?></p>
                </div>
                
                <div class="response-option">
                    <i class="fab fa-whatsapp"></i>
                    <h4><?php echo $trans['via_whatsapp']; ?></h4>
                    <p><?php echo $trans['whatsapp_response']; ?></p>
                </div>
                
                <div class="response-option">
                    <i class="fas fa-comment-dots"></i>
                    <h4><?php echo $trans['via_account']; ?></h4>
                    <p><?php echo $trans['account_response']; ?></p>
                </div>
            </div>
        </div>
        
        <div class="confirmation">
            <i class="fas fa-map-marker-alt"></i>
            <h3><?php echo $trans['add_location']; ?></h3>
            <p><?php echo $trans['location_description']; ?></p>
        </div>
        
        <div class="map-card">
            <h3><i class="fas fa-map-marked-alt"></i> <?php echo $trans['locate_relative']; ?></h3>
            
            <div class="distance-info">
                <i class="fas fa-road"></i>
                <span id="distanceText"><?php echo $trans['enter_location_prompt']; ?></span>
            </div>
            
            <div class="location-controls">
                <input type="text" id="locationInput" class="location-input" placeholder="<?php echo $trans['enter_address_placeholder']; ?>">
                <button id="searchLocation" class="location-btn">
                    <i class="fas fa-search"></i> <?php echo $trans['search']; ?>
                </button>
                <button id="myLocation" class="location-btn my-location">
                    <i class="fas fa-location-dot"></i> <?php echo $trans['my_location']; ?>
                </button>
            </div>
            
            <div class="map-container" id="map"></div>
        </div>
    </div>
</main>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Enregistrement audio et gestion de la suppression
    document.addEventListener('DOMContentLoaded', function() {
        const recordBtn = document.getElementById('recordBtn');
        const audioPreview = document.getElementById('audioPreview');
        const audioPlayer = document.getElementById('audioPlayer');
        const audioInfo = document.getElementById('audioInfo');
        const audioFileInput = document.getElementById('audioFile');
        const deleteAudioBtn = document.getElementById('deleteAudio');
        let mediaRecorder;
        let audioChunks = [];
        let audioBlob;
        
        // Fonction pour demander la permission du microphone
        async function requestMicrophonePermission() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                return stream;
            } catch (error) {
                console.error("Erreur d'accès au microphone:", error);
                alert("<?php echo $trans['microphone_access_error']; ?>");
                return null;
            }
        }
        
        // Initialisation de l'enregistrement audio
        recordBtn.addEventListener('click', async function() {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                // Arrêter l'enregistrement si déjà en cours
                mediaRecorder.stop();
                recordBtn.classList.remove('recording');
                recordBtn.innerHTML = '<i class="fas fa-microphone"></i> <?php echo $trans['record']; ?>';
                return;
            }
            
            // Demander la permission du microphone
            const stream = await requestMicrophonePermission();
            if (!stream) return;
            
            try {
                // Créer le MediaRecorder
                mediaRecorder = new MediaRecorder(stream);
                
                mediaRecorder.ondataavailable = function(e) {
                    audioChunks.push(e.data);
                };
                
                mediaRecorder.onstop = function() {
                    audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                    const audioUrl = URL.createObjectURL(audioBlob);
                    audioPlayer.src = audioUrl;
                    audioPreview.style.display = 'block';
                    audioInfo.textContent = "<?php echo $trans['audio_ready']; ?>";
                    
                    // Créer un fichier à partir du Blob
                    const audioFile = new File([audioBlob], "message_audio.webm", {
                        type: 'audio/webm'
                    });
                    
                    // Créer un DataTransfer pour simuler un input file
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(audioFile);
                    
                    // Assigner les fichiers à l'input
                    audioFileInput.files = dataTransfer.files;
                };
                
                // Démarrer l'enregistrement
                audioChunks = [];
                mediaRecorder.start();
                recordBtn.classList.add('recording');
                recordBtn.innerHTML = '<i class="fas fa-stop"></i> <?php echo $trans['record']; ?>';
                audioInfo.textContent = "<?php echo $trans['recording_in_progress']; ?>";
                audioPreview.style.display = 'block';
                
            } catch (error) {
                console.error("Erreur lors de l'enregistrement audio:", error);
                alert("<?php echo $trans['recording_error']; ?>");
            }
        });
        
        // Gestion du téléchargement de fichier
        audioFileInput.addEventListener('change', function(e) {
            if (this.files.length > 0) {
                const file = this.files[0];
                const fileUrl = URL.createObjectURL(file);
                audioPlayer.src = fileUrl;
                audioPreview.style.display = 'block';
                audioInfo.textContent = "<?php echo $trans['audio_file']; ?>".replace('%s', file.name);
            }
        });
        
        // Gestion de la suppression de l'audio
        deleteAudioBtn.addEventListener('click', function() {
            // Réinitialiser l'aperçu audio
            audioPlayer.src = "";
            audioPreview.style.display = 'none';
            audioInfo.textContent = "";
            
            // Réinitialiser l'input fichier
            audioFileInput.value = "";
            
            // Arrêter l'enregistrement s'il est en cours
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
                recordBtn.classList.remove('recording');
                recordBtn.innerHTML = '<i class="fas fa-microphone"></i> <?php echo $trans['record']; ?>';
            }
        });
        
        // Initialisation de la carte avec OpenStreetMap
        const tchadShopLocation = [12.1348, 15.0557]; // Coordonnées de TchadShop
        let map, userMarker;
        
        function initMap() {
            // Créer la carte
            map = L.map('map').setView(tchadShopLocation, 14);
            
            // Ajouter la couche OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributeurs'
            }).addTo(map);
            
            // Créer un marqueur personnalisé pour TchadShop
            const customIcon = L.divIcon({
                className: 'custom-marker',
                html: '<i class="fas fa-shopping-bag"></i>',
                iconSize: [32, 32],
                iconAnchor: [16, 32]
            });
            
            // Ajouter le marqueur de TchadShop
            const shopMarker = L.marker(tchadShopLocation, {icon: customIcon}).addTo(map);
            shopMarker.bindPopup("<b>TchadShop</b><br>Rue du Commerce, N°123").openPopup();
            
            // Ajouter un cercle pour indiquer la zone
            L.circle(tchadShopLocation, {
                color: '#6a1b9a',
                fillColor: '#8e24aa',
                fillOpacity: 0.2,
                radius: 500
            }).addTo(map);
            
            // Gestion de la recherche d'adresse
            document.getElementById('searchLocation').addEventListener('click', searchAddress);
            document.getElementById('locationInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') searchAddress();
            });
            
            // Gestion de la géolocalisation
            document.getElementById('myLocation').addEventListener('click', getMyLocation);
        }
        
        // Rechercher une adresse
        function searchAddress() {
            const address = document.getElementById('locationInput').value;
            if (!address) return;
            
            // Utiliser Nominatim pour géocoder l'adresse
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        const lat = parseFloat(data[0].lat);
                        const lon = parseFloat(data[0].lon);
                        addUserLocation(lat, lon, address);
                    } else {
                        alert("<?php echo $trans['address_not_found']; ?>");
                    }
                })
                .catch(error => {
                    console.error('Erreur de géocodage:', error);
                    alert("<?php echo $trans['address_not_found']; ?>");
                });
        }
        
        // Obtenir la position actuelle de l'utilisateur
        function getMyLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    position => {
                        const lat = position.coords.latitude;
                        const lon = position.coords.longitude;
                        addUserLocation(lat, lon, "<?php echo $trans['my_location']; ?>");
                    },
                    error => {
                        console.error('Erreur de géolocalisation:', error);
                        alert("<?php echo $trans['location_error']; ?>");
                    }
                );
            } else {
                alert("<?php echo $trans['geolocation_not_supported']; ?>");
            }
        }
        
        // Ajouter la localisation de l'utilisateur à la carte
        function addUserLocation(lat, lon, title) {
            // Supprimer le marqueur précédent s'il existe
            if (userMarker) map.removeLayer(userMarker);
            
            // Créer un marqueur pour la localisation de l'utilisateur
            const userIcon = L.divIcon({
                className: 'user-marker',
                html: '<i class="fas fa-user"></i>',
                iconSize: [32, 32],
                iconAnchor: [16, 32]
            });
            
            userMarker = L.marker([lat, lon], {icon: userIcon}).addTo(map);
            userMarker.bindPopup(`<b>${title}</b><br>Lat: ${lat.toFixed(5)}, Lon: ${lon.toFixed(5)}`).openPopup();
            
            // Centrer la carte sur les deux points
            const bounds = L.latLngBounds(tchadShopLocation, [lat, lon]);
            map.fitBounds(bounds, { padding: [50, 50] });
            
            // Calculer et afficher la distance
            const distance = calculateDistance(tchadShopLocation[0], tchadShopLocation[1], lat, lon);
            document.getElementById('distanceText').innerHTML = `
                <i class="fas fa-location-dot"></i> <?php echo $trans['distance_text']; ?>`.replace('%s', distance.toFixed(1));
        }
        
        // Calculer la distance entre deux points (formule Haversine)
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // Rayon de la Terre en km
            const dLat = deg2rad(lat2 - lat1);
            const dLon = deg2rad(lon2 - lon1);
            const a = 
                Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
                Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
            return R * c; // Distance en km
        }
        
        function deg2rad(deg) {
            return deg * (Math.PI/180);
        }
        
        // Initialiser la carte
        initMap();
        
        // Animation des options de réponse
        const responseOptions = document.querySelectorAll('.response-option');
        responseOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Supprimer la classe active de toutes les options
                responseOptions.forEach(opt => opt.classList.remove('active'));
                
                // Ajouter la classe active à l'option cliquée
                this.classList.add('active');
            });
        });
    });
</script>
    <?php include 'footer.php'; ?>
</body>
</html>