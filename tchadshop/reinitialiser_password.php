<?php
session_start();
$message = '';
$error = '';

// Connexion à la base de données
$db_host = 'localhost';
$db_name = 'tchadshop_db';
$db_user = 'root';
$db_pass = '';

// Vérifier si le token est présent dans l'URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: mot_de_passe_oublie.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    password_hash($password, PASSWORD_DEFAULT);
    if (empty($password) || empty($confirm_password)) {
        $error = "Veuillez remplir tous les champs.";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        try {
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Vérifier le token et son expiration
            $stmt = $pdo->prepare("SELECT id, reset_expiry FROM admin WHERE reset_token = ?");
            $stmt->execute([$token]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                // Vérifier si le token n'a pas expiré
                $current_time = date("Y-m-d H:i:s");
                if ($current_time > $admin['reset_expiry']) {
                    $error = "Le lien de réinitialisation a expiré. Veuillez faire une nouvelle demande.";
                } else {
                    // Mettre à jour le mot de passe
                    // NOTE: En production, utilisez password_hash()
                    $stmt = $pdo->prepare("UPDATE admin SET mot_de_passe = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?");
                    $stmt->execute([$password, $admin['id']]);
                    
                    $message = "Votre mot de passe a été réinitialisé avec succès.";
                    
                    // Rediriger vers la page de connexion après 3 secondes
                    header("Refresh: 3; url=index.php");
                }
            } else {
                $error = "Lien de réinitialisation invalide.";
            }
        } catch (PDOException $e) {
            $error = "Erreur de base de données: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser votre mot de passe</title>
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
            transform: translateY(20px);
            opacity: 0;
            animation: fadeUp 0.8s forwards 0.3s;
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
        }
        
        .reset-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .reset-message {
            font-size: 1.2rem;
            margin-bottom: 30px;
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
            cursor: pointer;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
            color: var(--light);
            cursor: pointer;
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
            margin: 10px 0;
            width: 100%;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.3);
            background: #2980b9;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
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
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Responsive Design */
        @media (max-width: 600px) {
            .reset-container {
                padding: 30px 20px;
            }
            
            .reset-header h1 {
                font-size: 2rem;
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
        }
        
        @media (max-width: 400px) {
            .reset-header h1 {
                font-size: 1.7rem;
            }
            
            .reset-message {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <i class='bx bx-lock'></i>
            <h1>Réinitialiser votre mot de passe</h1>
            <p class="reset-message">Entrez votre nouveau mot de passe</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="message success-message">
                <i class='bx bx-check-circle'></i> <?php echo $message; ?>
                <p>Vous serez redirigé vers la page de connexion dans 3 secondes...</p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="message error-message">
                <i class='bx bx-error-circle'></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($message)): ?>
        <form method="POST" action="">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <div class="form-group">
                <label for="password">Nouveau mot de passe</label>
                <div class="input-with-icon">
                    <i class='bx bx-key'></i>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="Entrez votre nouveau mot de passe" 
                        required
                        minlength="6"
                    >
                    <span class="password-toggle" id="togglePassword">
                        <i class='bx bx-hide'></i>
                    </span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe</label>
                <div class="input-with-icon">
                    <i class='bx bx-key'></i>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-input" 
                        placeholder="Confirmez votre nouveau mot de passe" 
                        required
                        minlength="6"
                    >
                    <span class="password-toggle" id="toggleConfirmPassword">
                        <i class='bx bx-hide'></i>
                    </span>
                </div>
            </div>
            
            <button type="submit" class="btn">
                <i class='bx bx-reset'></i> Réinitialiser le mot de passe
            </button>
        </form>
        
        <a href="index.php" class="back-link">
            <i class='bx bx-arrow-back'></i> Retour à la page de connexion
        </a>
        <?php endif; ?>
        
        <div class="footer">
            <p>TchadShop &copy; <?php echo date('Y'); ?></p>
        </div>
    </div>
    
    <script>
        // Animation d'apparition
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                document.querySelector('.reset-container').style.opacity = '1';
            }, 100);
            
            // Fonctionnalité pour afficher/masquer le mot de passe
            const togglePassword = document.getElementById('togglePassword');
            const password = document.getElementById('password');
            
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const confirmPassword = document.getElementById('confirm_password');
            
            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.querySelector('i').classList.toggle('bx-hide');
                this.querySelector('i').classList.toggle('bx-show');
            });
            
            toggleConfirmPassword.addEventListener('click', function() {
                const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPassword.setAttribute('type', type);
                this.querySelector('i').classList.toggle('bx-hide');
                this.querySelector('i').classList.toggle('bx-show');
            });
        });
    </script>
</body>
</html>