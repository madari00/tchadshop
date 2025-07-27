<?php
session_start();

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['admin_id']);
$adminName = $_SESSION['admin_nom'] ?? 'Administrateur';

// Déconnexion si le paramètre est présent
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    // Détruire la session
    session_unset();
    session_destroy();
    
    // Redirection après un délai
    header("Refresh: 3; url=index.php");
    $logoutMessage = true;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Déconnexion</title>
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
        
        .logout-container {
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
        
        .logout-container::before {
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
        
        .logout-header {
            margin-bottom: 30px;
            position: relative;
        }
        
        .logout-header i {
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
        
        .logout-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .logout-message {
            font-size: 1.2rem;
            margin-bottom: 30px;
            background: rgba(0, 0, 0, 0.2);
            padding: 20px;
            border-radius: 10px;
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 50px;
            max-width: 300px;
            margin: 0 auto 30px;
        }
        
        .admin-info i {
            font-size: 1.5rem;
        }
        
        .admin-name {
            font-weight: 600;
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
            margin: 10px;
            min-width: 180px;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.3);
            background: #2980b9;
        }
        
        .btn-logout {
            background: linear-gradient(135deg, var(--error), #c0392b);
        }
        
        .btn-logout:hover {
            background: linear-gradient(135deg, #c0392b, var(--error));
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--success), #27ae60);
        }
        
        .btn-login:hover {
            background: linear-gradient(135deg, #27ae60, var(--success));
        }
        
        .countdown {
            font-size: 4rem;
            font-weight: 700;
            margin: 30px 0;
            color: var(--light);
            text-shadow: 0 0 10px rgba(255,255,255,0.5);
            animation: pulse 1.5s infinite;
        }
        
        .redirect-message {
            margin-top: 20px;
            font-size: 1rem;
            opacity: 0.8;
        }
        
        @keyframes fadeUp {
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
        
        .footer {
            position: absolute;
            bottom: -100px;
            width: 100%;
            text-align: center;
            font-size: 0.9rem;
            opacity: 0.7;
        }
        
        /* Responsive Design */
        @media (max-width: 600px) {
            .logout-container {
                padding: 30px 20px;
            }
            
            .logout-header h1 {
                font-size: 2rem;
            }
            
            .logout-header i {
                font-size: 4rem;
                width: 100px;
                height: 100px;
                line-height: 100px;
            }
            
            .btn {
                padding: 12px 20px;
                min-width: 160px;
                font-size: 1rem;
            }
            
            .countdown {
                font-size: 3rem;
            }
        }
        
        @media (max-width: 400px) {
            .logout-header h1 {
                font-size: 1.7rem;
            }
            
            .logout-message {
                font-size: 1rem;
            }
            
            .btn {
                display: block;
                width: 100%;
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-header">
            <i class='bx bx-log-out'></i>
            <h1>Déconnexion</h1>
            
            <?php if ($isLoggedIn) : ?>
                <div class="admin-info">
                    <i class='bx bx-user'></i>
                    <span class="admin-name"><?php echo htmlspecialchars($adminName); ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (isset($logoutMessage)) : ?>
            <div class="logout-message">
                <p>Vous avez été déconnecté avec succès.</p>
                <div class="countdown">3</div>
                <p class="redirect-message">Redirection vers la page de connexion...</p>
            </div>
        <?php else : ?>
            <div class="logout-message">
                <?php if ($isLoggedIn) : ?>
                    <p>Êtes-vous sûr de vouloir vous déconnecter de votre session administrateur ?</p>
                    <a href="?logout=1" class="btn btn-logout">Déconnexion <i class='bx bx-log-out'></i></a>
                    <a href="tableau_bord.php" class="btn">Retour au tableau de bord</a>
                <?php else : ?>
                    <p>Vous n'êtes actuellement pas connecté.</p>
                    <a href="index.php" class="btn btn-login">Se connecter <i class='bx bx-log-in'></i></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="footer">
        <p>TchadShop &copy; <?php echo date('Y'); ?></p>
    </div>
    
    <script>
        // Animation de compte à rebours
        <?php if (isset($logoutMessage)): ?>
            let count = 3;
            const countdownElement = document.querySelector('.countdown');
            
            const countdownInterval = setInterval(() => {
                count--;
                countdownElement.textContent = count;
                
                if (count <= 0) {
                    clearInterval(countdownInterval);
                }
            }, 1000);
        <?php endif; ?>
        
        // Animation d'apparition
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                document.querySelector('.logout-container').style.opacity = '1';
            }, 100);
        });
    </script>
</body>
</html>