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
if ($conn->connect_error) {
    die("Erreur de connexion à la base de données: " . $conn->connect_error);
}

// Chargement MANUEL de PHPMailer
require __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../lib/PHPMailer/src/SMTP.php';
require __DIR__ . '/../lib/PHPMailer/src/Exception.php';

// Utilisez le namespace complet
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Traitement du formulaire
$error = '';
$success = '';
$step = 1;
$remainingTime = 900; // 15 minutes par défaut

// --- LOGIQUE CORRIGÉE : Vérification du token avant toute autre action ---
// Si un token est présent dans l'URL, on l'évalue en priorité
if (isset($_GET['token'])) {
    $token = $conn->real_escape_string($_GET['token']);

    $sql_token = "SELECT id, reset_token_expire FROM clients WHERE reset_token = '$token'";
    $result_token = $conn->query($sql_token);
    
    if ($result_token && $result_token->num_rows > 0) {
        $user_with_token = $result_token->fetch_assoc();
        
        if (time() < $user_with_token['reset_token_expire']) {
            // Token valide et non expiré
            $_SESSION['reset_user_id'] = $user_with_token['id'];
            $step = 3;
            // On peut aussi stocker le temps restant si besoin
            $remainingTime = $user_with_token['reset_token_expire'] - time();
        } else {
            $error = "Le lien de réinitialisation a expiré. Veuillez recommencer le processus.";
            $step = 1;
        }
    } else {
        $error = "Lien de réinitialisation invalide. Veuillez demander un nouveau lien.";
        $step = 1;
    }
} else if (isset($_GET['step'])) {
    $step = intval($_GET['step']);
}


// Étape 1: Traitement du formulaire d'email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step1'])) {
    $email = $conn->real_escape_string($_POST['email']);
    
    $sql = "SELECT id, reset_token_expire FROM clients WHERE email = '$email'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['reset_email'] = $email;
        
        // Vérifier si un token existe et est encore valide
        if ($user['reset_token_expire'] && time() < $user['reset_token_expire']) {
            $remainingTime = $user['reset_token_expire'] - time();
            $success = "Un email de réinitialisation a déjà été envoyé à $email. Vérifiez votre boîte de réception ou attendez l'expiration du lien pour en demander un nouveau.";
            $step = 2;
        } else {
            $token = bin2hex(random_bytes(32));
            $token_expire = time() + 900;
            
            $update_token_sql = "UPDATE clients SET reset_token = '$token', reset_token_expire = $token_expire WHERE id = {$user['id']}";
            $conn->query($update_token_sql);

            $reset_link = "http://192.168.11.130/ammashop/client/reset_password.php?token=$token";
            
            try {
                $mail = new PHPMailer(true);
                
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'issakhadaoudabdelkerim95@gmail.com';
                $mail->Password = 'xcow wdmk qjur wnls';
                
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;
                
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
                
                $mail->Timeout = 30;
                
                $mail->setFrom('issakhadaoudabdelkerim95@gmail.com', 'TchadShop');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Nouvelle demande de réinitialisation';
                
                $mail->Body = "
                    <p>Bonjour,</p>
                    <p>Vous avez demandé un nouveau lien de réinitialisation pour votre mot de passe TchadShop.</p>
                    <p>Cliquez sur ce lien pour créer un nouveau mot de passe :</p>
                    <p><a href='$reset_link'>$reset_link</a></p>
                    <p>Ce lien expirera dans 15 minutes.</p>
                    <p>Cordialement,<br>L'équipe TchadShop</p>
                ";
                
                $mail->send();
                
                $success = "Un email de réinitialisation a été envoyé à $email!";
                $step = 2;
                
            } catch (Exception $e) {
                $error = "Erreur lors de l'envoi de l'email : " . $e->getMessage();
            }
        }
    } else {
        $error = "Aucun compte trouvé avec cet email.";
    }
}

// Étape 3: Traitement de la réinitialisation du mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step3'])) {
    if (isset($_SESSION['reset_user_id'])) {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password === $confirm_password) {
            if (strlen($password) >= 6) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $user_id = $_SESSION['reset_user_id'];
                
                $update_sql = "UPDATE clients SET password = '$hashed_password' WHERE id = $user_id";
                if ($conn->query($update_sql)) {
                    $success = "Votre mot de passe a été réinitialisé avec succès!";
                    
                    $clear_token_sql = "UPDATE clients SET reset_token = NULL, reset_token_expire = NULL WHERE id = $user_id";
                    $conn->query($clear_token_sql);
                    
                    unset($_SESSION['reset_user_id']);
                    unset($_SESSION['reset_email']);
                    
                    $step = 4;
                } else {
                    $error = "Erreur lors de la mise à jour du mot de passe: " . $conn->error;
                }
            } else {
                $error = "Le mot de passe doit contenir au moins 6 caractères.";
            }
        } else {
            $error = "Les mots de passe ne correspondent pas.";
        }
    } else {
        $error = "Session de réinitialisation expirée. Veuillez recommencer.";
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser votre mot de passe - TchadShop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ... Le style CSS reste le même que dans le code précédent ... */
        :root {
            --primary: #6a1b9a;
            --primary-light: #9c4dcc;
            --secondary: #26a69a;
            --dark: #333;
            --light: #f8f9fa;
            --gray: #e0e0e0;
            --error: #f44336;
            --success: #4caf50;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .reset-container {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: fadeIn 0.6s ease-out;
            position: relative;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .reset-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            padding: 30px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .reset-header::before {
            content: "";
            position: absolute;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            top: -50px;
            right: -50px;
        }
        
        .reset-header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
        }
        
        .reset-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 2;
        }
        
        .reset-icon {
            font-size: 80px;
            margin-bottom: 20px;
            color: rgba(255, 255, 255, 0.9);
            position: relative;
            z-index: 2;
        }
        
        .reset-body {
            padding: 30px;
            position: relative;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        
        .progress-steps::before {
            content: "";
            position: absolute;
            top: 15px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--gray);
            z-index: 1;
        }
        
        .progress-step {
            position: relative;
            z-index: 2;
            text-align: center;
            width: 33.33%;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--gray);
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .step-text {
            font-size: 0.85rem;
            color: #666;
        }
        
        .step-active .step-number {
            background: var(--primary);
            color: white;
            transform: scale(1.1);
        }
        
        .step-completed .step-number {
            background: var(--success);
            color: white;
        }
        
        .step-completed .step-number::after {
            content: "\f00c";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
        }
        
        .reset-content {
            margin-bottom: 25px;
        }
        
        .form-header {
            margin-bottom: 25px;
            text-align: center;
        }
        
        .form-header h2 {
            font-size: 1.8rem;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .form-header p {
            color: #666;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
            display: flex;
            align-items: center;
        }
        
        .form-group label i {
            margin-right: 10px;
            color: var(--primary);
        }
        
        .form-control {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 1px solid var(--gray);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }
        
        .form-control:focus {
            border-color: var(--primary-light);
            outline: none;
            box-shadow: 0 0 0 3px rgba(106, 27, 154, 0.2);
            background-color: white;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 43px;
            color: #777;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 43px;
            cursor: pointer;
            color: #777;
            z-index: 10;
            background: transparent;
            border: none;
            font-size: 16px;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.05rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            margin-top: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            text-decoration: none; /* Ajouté pour le lien */
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(106, 27, 154, 0.2);
        }
        
        .btn-secondary {
            background: var(--light);
            color: var(--dark);
            border: 1px solid var(--gray);
        }
        
        .btn-secondary:hover {
            background: #f1f1f1;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .error-message {
            background: var(--error);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            display: <?php echo $error ? 'block' : 'none'; ?>;
        }
        
        .success-message {
            background: var(--success);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            display: <?php echo $success ? 'block' : 'none'; ?>;
        }
        
        .timer {
            text-align: center;
            margin: 20px 0;
            font-size: 1.1rem;
            color: #666;
        }
        
        .timer span {
            font-weight: 600;
            color: var(--primary);
        }
        
        .resend-link {
            text-align: center;
            margin: 20px 0;
        }
        
        .resend-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .resend-link a:hover {
            text-decoration: underline;
        }
        
        .success-screen {
            text-align: center;
            padding: 40px 20px;
        }
        
        .success-screen i {
            font-size: 80px;
            color: var(--success);
            margin-bottom: 20px;
        }
        
        .success-screen h2 {
            font-size: 2rem;
            color: var(--dark);
            margin-bottom: 15px;
        }
        
        .success-screen p {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }
        
        .email-simulation {
            background: #e3f2fd;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            display: none;
        }
        
        .email-simulation h3 {
            color: var(--primary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .email-simulation h3 i {
            margin-right: 10px;
        }
        
        .email-content {
            background: white;
            border: 1px solid var(--gray);
            border-radius: 8px;
            padding: 20px;
            font-family: monospace;
            white-space: pre-wrap;
        }
        
        /* Responsive Design */
        @media (max-width: 600px) {
            .reset-container {
                max-width: 100%;
            }
            
            .reset-header {
                padding: 20px;
            }
            
            .reset-icon {
                font-size: 60px;
            }
            
            .reset-header h1 {
                font-size: 1.5rem;
            }
            
            .reset-body {
                padding: 20px;
            }
            
            .step-text {
                font-size: 0.75rem;
            }
            
            .form-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <div class="reset-icon">
                <i class="fas fa-key"></i>
            </div>
            <h1>Réinitialiser votre mot de passe</h1>
            <p>Sécurisez votre compte en quelques étapes</p>
        </div>
        
        <div class="reset-body">
            <div class="error-message" id="error-message" style="display: <?php echo $error ? 'block' : 'none'; ?>">
                <?php echo $error; ?>
            </div>
            
            <div class="success-message" id="success-message" style="display: <?php echo $success ? 'block' : 'none'; ?>">
                <?php echo $success; ?>
            </div>
            
            <div class="progress-steps">
                <div class="progress-step <?php echo $step >= 1 ? 'step-completed' : ''; ?> <?php echo $step == 1 ? 'step-active' : ''; ?>">
                    <div class="step-number">1</div>
                    <div class="step-text">Vérification</div>
                </div>
                <div class="progress-step <?php echo $step >= 2 ? 'step-completed' : ''; ?> <?php echo $step == 2 ? 'step-active' : ''; ?>">
                    <div class="step-number">2</div>
                    <div class="step-text">Email envoyé</div>
                </div>
                <div class="progress-step <?php echo $step >= 3 ? 'step-completed' : ''; ?> <?php echo $step == 3 || $step == 4 ? 'step-active' : ''; ?>">
                    <div class="step-number">3</div>
                    <div class="step-text">Nouveau mot de passe</div>
                </div>
            </div>
            
            <div class="reset-content">
                <?php if ($step == 1): ?>
                    <form method="post">
                        <div class="form-header">
                            <h2>Vérifiez votre identité</h2>
                            <p>Entrez votre adresse email pour commencer</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Adresse email</label>
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" id="email" name="email" class="form-control" placeholder="votre@email.com" required>
                        </div>
                        
                        <button type="submit" name="step1" class="btn btn-primary">
                            <i class="fas fa-arrow-right"></i> Continuer
                        </button>
                    </form>
                <?php elseif ($step == 2): ?>
                    <div class="form-header">
                        <i class="fas fa-envelope-open-text" style="font-size: 60px; color: var(--primary); margin-bottom: 20px;"></i>
                        <h2>Email envoyé!</h2>
                        <p>Nous avons envoyé un lien de réinitialisation à <strong><?php echo htmlspecialchars($_SESSION['reset_email'] ?? ''); ?></strong></p>
                    </div>
                    <div class="timer">
                        Le lien expirera dans: <span id="countdown">15:00</span>
                    </div>
                    <a href="reset_password.php?step=1" class="btn btn-secondary">
                        <i class="fas fa-sync-alt"></i> Renvoyer l'e-mail
                    </a>
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Retour à la connexion
                    </a>
                <?php elseif ($step == 3): ?>
                    <form method="post">
                        <div class="form-header">
                            <h2>Créez un nouveau mot de passe</h2>
                            <p>Votre mot de passe doit contenir au moins 6 caractères</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="password"><i class="fas fa-lock"></i> Nouveau mot de passe</label>
                            <i class="fas fa-key input-icon"></i>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Votre nouveau mot de passe" required>
                            <button type="button" class="password-toggle" id="toggle_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password"><i class="fas fa-lock"></i> Confirmer le mot de passe</label>
                            <i class="fas fa-key input-icon"></i>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirmez votre mot de passe" required>
                            <button type="button" class="password-toggle" id="toggle_confirm_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        
                        <button type="submit" name="step3" class="btn btn-primary">
                            <i class="fas fa-sync-alt"></i> Réinitialiser le mot de passe
                        </button>
                    </form>
                <?php elseif ($step == 4): ?>
                    <div class="success-screen">
                        <i class="fas fa-check-circle"></i>
                        <h2>Mot de passe réinitialisé!</h2>
                        <p>Votre mot de passe a été mis à jour avec succès.</p>
                        <a href="login.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Se connecter
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        // Password visibility toggle
        function setupPasswordToggle(toggleId, inputId) {
            const toggle = document.getElementById(toggleId);
            const input = document.getElementById(inputId);
            
            if (toggle && input) {
                toggle.addEventListener('click', function() {
                    const icon = this.querySelector('i');
                    
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            }
        }
        
        // Initialiser les toggles de mot de passe
        if (document.getElementById('toggle_password') && document.getElementById('password')) {
            setupPasswordToggle('toggle_password', 'password');
        }
        
        if (document.getElementById('toggle_confirm_password') && document.getElementById('confirm_password')) {
            setupPasswordToggle('toggle_confirm_password', 'confirm_password');
        }

        // Compte à rebours
        if (document.getElementById('countdown')) {
            let timeLeft = <?php echo $remainingTime; ?>;
            
            function updateTimer() {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                
                document.getElementById('countdown').textContent = 
                    `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                if (timeLeft > 0) {
                    timeLeft--;
                    setTimeout(updateTimer, 1000);
                } else {
                    document.getElementById('countdown').textContent = 'Expiré';
                }
            }
            
            updateTimer();
        }
    </script>
</body>
</html>