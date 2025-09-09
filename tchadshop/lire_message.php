<?php
session_start();
$searchContext = 'contact';
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Récupérer l'ID du contact depuis l'URL
$contact_id = $_GET['id'] ?? 0;

// Marquer le message comme lu s'il ne l'est pas déjà
if ($contact_id) {
    $conn->query("UPDATE contacts SET vu = 1 WHERE id = $contact_id");
}

// Récupérer les détails du message
$sql = "SELECT c.*, cl.nom AS client_nom, cl.email AS client_email, cl.invite
        FROM contacts c
        LEFT JOIN clients cl ON c.client_id = cl.id
        WHERE c.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $contact_id);
$stmt->execute();
$result = $stmt->get_result();
$message = $result->fetch_assoc();

if (!$message) {
    die("Message non trouvé");
}

// Formater le numéro de téléphone pour les liens
$phone = $message['telephone'] ?? '';
$clean_phone = preg_replace('/[^0-9+]/', '', $phone);
$whatsapp_link = "https://wa.me/$clean_phone";
$sms_link = "sms:$clean_phone";
$call_link = "tel:$clean_phone";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détail du message - Administration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #00796b;
            --primary-light: #48a999;
            --primary-dark: #004c40;
            --accent-color: #ff9800;
            --danger-color: #dc3545;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --light-gray: #f8f9fa;
            --dark-gray: #343a40;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f0f2f5;
            color: #333;
            line-height: 1.6;
        }
        
        .admin-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .page-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .page-title h1 {
            font-size: 1.8rem;
            color: var(--primary-dark);
            margin: 0;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
        }
        
        .back-btn:hover {
            background: var(--primary-dark);
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
        }
        
        .card-header h2 {
            font-size: 1.4rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .message-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-group {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .info-value {
            padding: 8px 12px;
            background: var(--light-gray);
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .message-content {
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-left: 4px solid var(--primary-color);
            border-radius: 0 5px 5px 0;
        }
        
        .audio-player {
            margin: 20px 0;
        }
        
        .audio-player audio {
            width: 100%;
            max-width: 400px;
        }
        
        .communication-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0;
        }
        
        .action-btn {
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .action-email {
            background-color: #4a6cf7;
            color: white;
        }
        
        .action-email:hover {
            background-color: #3a56d0;
        }
        
        .action-phone {
            background-color: #28a745;
            color: white;
        }
        
        .action-phone:hover {
            background-color: #218838;
        }
        
        .action-whatsapp {
            background-color: #25D366;
            color: white;
        }
        
        .action-whatsapp:hover {
            background-color: #128C7E;
        }
        
        .action-sms {
            background-color: #6610f2;
            color: white;
        }
        
        .action-sms:hover {
            background-color: #5300d8;
        }
        
        .response-form {
            margin-top: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-gray);
        }
        
        textarea {
            width: 100%;
            min-height: 150px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            resize: vertical;
        }
        
        .submit-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .submit-btn:hover {
            background: var(--primary-dark);
        }
        
        @media (max-width: 768px) {
            .admin-container {
                padding: 15px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .communication-options {
                flex-direction: column;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include "header.php"; ?>
    <div class="home-content">
    <div class="admin-container">
        <div class="header">
            <div class="page-title">
                <h1><i class="fas fa-envelope-open"></i> Détail du message</h1>
            </div>
            <a href="contact_visiteur.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Retour à la liste
            </a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-info-circle"></i> Informations du contact</h2>
            </div>
            <div class="card-body">
                <div class="message-info">
                    <div>
                        <div class="info-group">
                            <div class="info-label">Nom</div>
                            <div class="info-value"><?= htmlspecialchars($message['nom']) ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?= htmlspecialchars($message['email']) ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Téléphone</div>
                            <div class="info-value"><?= htmlspecialchars($message['telephone'] ?? 'Non fourni') ?></div>
                        </div>
                    </div>
                    <div>
                        <div class="info-group">
                            <div class="info-label">Sujet</div>
                            <div class="info-value"><?= htmlspecialchars($message['sujet']) ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Date d'envoi</div>
                            <div class="info-value"><?= date('d/m/Y H:i', strtotime($message['created_at'])) ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Statut</div>
                            <div class="info-value"><?= $message['vu'] ? 'Lu' : 'Non lu' ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="info-group">
                    <div class="info-label">Message</div>
                    <div class="message-content">
                        <?= nl2br(htmlspecialchars($message['message'])) ?>
                    </div>
                </div>
                
                <?php if (!empty($message['audio_path'])): ?>
                <div class="audio-player">
                    <div class="info-label">Message vocal</div>
                    <audio controls>
                        <source src="<?= htmlspecialchars($message['audio_path']) ?>" type="audio/mpeg">
                        Votre navigateur ne supporte pas l'élément audio.
                    </audio>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($message['telephone'])): ?>
                <div class="communication-options">
                    <a href="<?= $call_link ?>" class="action-btn action-phone">
                        <i class="fas fa-phone"></i> Appeler
                    </a>
                    
                    <a href="<?= $whatsapp_link ?>" target="_blank" class="action-btn action-whatsapp">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                    
                    <a href="<?= $sms_link ?>" class="action-btn action-sms">
                        <i class="fas fa-comment-alt"></i> Envoyer SMS
                    </a>
                    
                    <a href="mailto:<?= htmlspecialchars($message['email']) ?>?subject=Réponse à votre contact" class="action-btn action-email">
                        <i class="fas fa-envelope"></i> Répondre par email
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-reply"></i> Répondre au message</h2>
            </div>
            <div class="card-body">
                <form class="response-form" method="POST" action="envoyer_reponse.php">
                    <input type="hidden" name="contact_id" value="<?= $message['id'] ?>">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($message['email']) ?>">
                    
                    <div class="form-group">
                        <label for="reponse">Votre réponse</label>
                        <textarea id="reponse" name="reponse" placeholder="Écrivez votre réponse ici..." required></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Envoyer la réponse
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>