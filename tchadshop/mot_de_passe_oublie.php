<?php
session_start();
$message = '';
$error = '';

// Connexion à la base de données
$db_host = 'localhost';
$db_name = 'tchadshop_db';
$db_user = 'root';
$db_pass = '';

// Activer le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (!empty($email)) {
        try {
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Vérifier si l'email existe
            $stmt = $pdo->prepare("SELECT id, nom, email FROM admin WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                // Générer un token de réinitialisation (valide 15 minutes)
                $token = bin2hex(random_bytes(32));
                $expiry = date("Y-m-d H:i:s", time() + 900); // 15 minutes
                
                // Stocker le token dans la base
                $updateStmt = $pdo->prepare("UPDATE admin SET reset_token = ?, reset_expiry = ? WHERE id = ?");
                
                if ($updateStmt->execute([$token, $expiry, $admin['id']])) {
                    // Envoyer l'email (simulé ici)
                    $reset_link = "reinitialiser_password.php?token=$token";
                    
                    // En production, décommentez ce bloc pour envoyer un vrai email
                    /*
                    $to = $admin['email'];
                    $subject = "Réinitialisation de votre mot de passe - TchadShop";
                    $body = "Bonjour {$admin['nom']},\n\n";
                    $body .= "Vous avez demandé à réinitialiser votre mot de passe.\n";
                    $body .= "Cliquez sur ce lien pour réinitialiser votre mot de passe:\n";
                    $body .= "$reset_link\n\n";
                    $body .= "Ce lien expirera dans 15 minutes.\n";
                    $body .= "Si vous n'avez pas fait cette demande, ignorez cet email.\n";
                    $headers = "From: noreply@tchadshop.com\r\n";
                    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                    
                    if (mail($to, $subject, $body, $headers)) {
                        $message = "Un email de réinitialisation a été envoyé à $email.";
                    } else {
                        $error = "Erreur lors de l'envoi de l'email. Veuillez réessayer.";
                    }
                    */
                    
                    // Message de simulation pour le développement
                    $message = "Un email de réinitialisation a été envoyé à $email.<br>";
                    $message .= "<small>Lien de réinitialisation (simulation) : <a href='$reset_link'>$reset_link</a></small>";
                } else {
                    $error = "Erreur lors de la mise à jour de la base de données.";
                }
            } else {
                $error = "Aucun compte n'est associé à cette adresse email.";
            }
        } catch (PDOException $e) {
            $error = "Erreur de base de données: " . $e->getMessage();
        }
    } else {
        $error = "Veuillez entrer votre adresse email.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser votre mot de passe | TchadShop</title>
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #164b83;
            --secondary: #2c3e50;
            --accent: #3498db;
            --light: #ecf0f1;
            --success: #2ecc71;
            --error: #e74c3c;
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            --transition: all 0.4s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary), #1a5276);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--light);
            line-height: 1.6;
        }
        
        .reset-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 500px;
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transform: translateY(0);
            opacity: 1;
            animation: fadeUp 0.8s ease;
        }
        
        .reset-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transform: rotate(30deg);
            z-index: -1;
        }
        
        .reset-header {
            margin-bottom: 30px;
            position: relative;
        }
        
        .reset-header i {
            font-size: 5rem;
            color: var(--light);
            margin-bottom: 20px;
            display: inline-block;
            background: rgba(255, 255, 255, 0.1);
            width: 120px;
            height: 120px;
            line-height: 120px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        .reset-header h1 {
            font-size: 2.2rem;
            margin-bottom: 10px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .reset-message {
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        
        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--light);
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
            color: var(--light);
        }
        
        .form-input {
            width: 100%;
            padding: 14px 16px 14px 45px;
            border-radius: 10px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: var(--light);
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.3);
        }
        
        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .btn {
            display: inline-block;
            padding: 14px 28px;
            background: var(--accent);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            margin: 20px 0 10px;
            width: 100%;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.3);
            background: #2980b9;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: var(--light);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .back-link:hover {
            color: var(--accent);
            text-decoration: underline;
        }
        
        .message {
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            font-weight: 500;
            text-align: left;
        }
        
        .success-message {
            background: rgba(46, 204, 113, 0.2);
            border: 1px solid rgba(46, 204, 113, 0.5);
        }
        
        .error-message {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid rgba(231, 76, 60, 0.5);
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 0.9rem;
            opacity: 0.7;
        }
        
        @keyframes fadeUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 0.8;
            }
            50% {
                transform: scale(1.05);
                opacity: 1;
            }
            100% {
                transform: scale(1);
                opacity: 0.8;
            }
        }
        
        /* Responsive Design */
        @media (max-width: 600px) {
            .reset-container {
                padding: 30px 20px;
            }
            
            .reset-header h1 {
                font-size: 1.8rem;
            }
            
            .reset-header i {
                font-size: 4rem;
                width: 100px;
                height: 100px;
                line-height: 100px;
            }
            
            .btn {
                padding: 12px 20px;
                font-size: 1rem;
            }
            
            .reset-message {
                font-size: 1rem;
            }
        }
        
        @media (max-width: 400px) {
            .reset-header h1 {
                font-size: 1.6rem;
            }
            
            .reset-message {
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <i class='bx bx-lock-open-alt'></i>
            <h1>Mot de passe oublié</h1>
            <p class="reset-message">Entrez votre adresse email pour réinitialiser votre mot de passe</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="message success-message">
                <i class='bx bx-check-circle'></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="message error-message">
                <i class='bx bx-error-circle'></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Formulaire corrigé : action vide pour traiter sur la même page -->
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Adresse email</label>
                <div class="input-with-icon">
                    <i class='bx bx-envelope'></i>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="votre@email.com" 
                        required
                        autofocus
                    >
                </div>
            </div>
            
            <button type="submit" class="btn">
                <i class='bx bx-send'></i> Envoyer le lien de réinitialisation
            </button>
        </form>
        
        <a href="index.php" class="back-link">
            <i class='bx bx-arrow-back'></i> Retour à la page de connexion
        </a>
        
        <div class="footer">
            <p>TchadShop &copy; <?php echo date('Y'); ?></p>
        </div>
    </div>
    
    <script>
        // Focus sur le champ email
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });
    </script>
</body>
</html>