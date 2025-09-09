<?php
session_start();

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['admin_id'])) {
    $_SESSION['admin_id'] = $admin_id;
    header('Location: tableau_bord.php');
    exit();
}

$error = '';
$db_host = 'localhost';
$db_name = 'tchadshop_db';
$db_user = 'root';
$db_pass = "";

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'] ?? '';
    $mot = $_POST['mot'] ?? '';
    
    if (!empty($nom) && !empty($mot)) {
        try {
            // Connexion à la base de données
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Recherche de l'administrateur
            $stmt = $pdo->prepare("SELECT id, nom, mot_de_passe FROM admin WHERE nom = ?");
            $stmt->execute([$nom]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                // Vérification du mot de passe (version texte clair - à remplacer par password_verify() si hachage)
                if ($mot === $admin['mot_de_passe']) {
                    // Authentification réussie
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_nom'] = $admin['nom'];
                    header('Location: tableau_bord.php');
                    exit();
                } else {
                    $error = "Mot de passe incorrect";
                }
            } else {
                $error = "Nom d'utilisateur introuvable";
            }
        } catch (PDOException $e) {
            $error = "Erreur de base de données: " . $e->getMessage();
        }
    } else {
        $error = "Veuillez remplir tous les champs";
    }
}
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8" />
    <title>Connexion admin</title>
    <!-- Boxicons CDN Link -->
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <style>
        :root {
            --primary: #164b83;
            --secondary: #4b5252;
            --accent: #80bdff;
            --light: #879fc0;
            --dark: #393d3d;
            --white: #ffffff;
            --error: #ff6b6b;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--white);
            background-image: 
                radial-gradient(circle at 25% 30%, rgba(22, 75, 131, 0.1) 0%, transparent 55%),
                radial-gradient(circle at 75% 70%, rgba(128, 189, 255, 0.1) 0%, transparent 55%);
        }
        
        header {
            width: 100%;
            max-width: 800px;
            background-color: var(--primary);
            text-align: center;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            box-shadow: var(--shadow);
            margin-bottom: -10px;
            z-index: 2;
            position: relative;
        }
        
        header h1 {
            color: var(--white);
            font-weight: 700;
            font-size: clamp(1.5rem, 2.5vw, 2rem);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .login-container {
            width: 100%;
            max-width: 800px;
            background-color: var(--secondary);
            border-radius: 0 0 10px 10px;
            padding: 30px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--accent), #96c9ff);
            z-index: 2;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h2 {
            font-size: clamp(1.8rem, 3vw, 2.5rem);
            font-weight: 600;
            color: var(--accent);
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: var(--light);
            font-size: clamp(0.9rem, 1.5vw, 1.1rem);
        }
        
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group i {
            font-size: 1.2rem;
        }
        
        .form-input {
            padding: 14px 16px 14px 45px;
            border-radius: 8px;
            border: 2px solid #5e6868;
            background-color: #5e6868;
            color: var(--white);
            font-size: 1rem;
            transition: var(--transition);
            width: 100%;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 0.2rem rgba(128, 189, 255, 0.25);
            background-color: #6a7575;
        }
        
        .form-input::placeholder {
            color: #a0b0b0;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 42px;
            color: var(--light);
        }
        
        .btn {
            background: linear-gradient(135deg, var(--accent), #96c9ff);
            color: var(--primary);
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.25);
            background: linear-gradient(135deg, #96c9ff, var(--accent));
        }
        
        .btn:active {
            transform: translateY(1px);
        }
        
        .forgot-password {
            text-align: center;
            margin-top: 15px;
        }
        
        .forgot-password a {
            color: #96c9ff;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .forgot-password a:hover {
            color: var(--accent);
            text-decoration: underline;
        }
        
        .form-footer {
            margin-top: 30px;
            text-align: center;
            color: var(--light);
            font-size: 0.9rem;
            padding-top: 20px;
            border-top: 1px solid #5e6868;
        }
        
        .error-message {
            background-color: var(--error);
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
            animation: fadeIn 0.5s ease;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .login-container {
                padding: 25px 20px;
            }
            
            .login-header h2 {
                font-size: 1.8rem;
            }
            
            .form-input {
                padding: 12px 14px 12px 40px;
            }
            
            .input-icon {
                top: 38px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 15px;
            }
            
            header {
                padding: 15px;
                border-radius: 8px 8px 0 0;
            }
            
            .login-container {
                padding: 20px 15px;
                border-radius: 0 0 8px 8px;
            }
            
            .login-header h2 {
                font-size: 1.5rem;
            }
            
            .form-input {
                padding: 10px 12px 10px 38px;
                font-size: 0.95rem;
            }
            
            .input-icon {
                top: 36px;
                font-size: 1rem;
            }
            
            .btn {
                padding: 12px;
                font-size: 1rem;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(128, 189, 255, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(128, 189, 255, 0); }
            100% { box-shadow: 0 0 0 0 rgba(128, 189, 255, 0); }
        }
        
        .form-input:focus {
            animation: pulse 1.5s infinite;
        }
        
        .login-container {
            animation: fadeIn 0.8s ease;
        }
    </style>
</head>
<body>
    <header>
        <h1><i class='bx bx-shield-quarter'></i> AUTHENTIFICATION ADMIN</h1>
    </header>
    
    <div class="login-container">
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class='bx bx-error'></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="login-header">
            <h2>Connexion à l'espace admin</h2>
            <p>Veuillez saisir vos identifiants pour accéder au panneau d'administration</p>
        </div>
        
        <form method="post" action="" class="login-form">
            <div class="form-group">
                <label for="nom"><i class='bx bx-user'></i> Nom complet</label>
                <i class='bx bx-user input-icon'></i>
                <input type="text" id="nom" name="nom" class="form-input" placeholder="Entrez votre nom complet" required />
            </div>
            
            <div class="form-group">
                <label for="mot"><i class='bx bx-lock-alt'></i> Mot de passe</label>
                <i class='bx bx-lock-alt input-icon'></i>
                <input type="password" id="mot" name="mot" class="form-input" placeholder="Entrez votre mot de passe" required />
            </div>
            
            <button type="submit" class="btn">Se connecter <i class='bx bx-log-in'></i></button>
            
            <div class="forgot-password">
                <a href="mot_de_passe_oublie.php"><i class='bx bx-key'></i> Mot de passe oublié ?</a>
            </div>
        </form>
        
        <div class="form-footer">
            <p>© 2025 TchadShop.</p>
        </div>
    </div>

    <script>
        // Animation au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.login-container');
            setTimeout(() => {
                form.style.opacity = '1';
                form.style.transform = 'translateY(0)';
            }, 100);
            
            // Focus sur le premier champ
            document.getElementById('nom').focus();
        });
        
        // Effet de chargement lors de la soumission
        const loginForm = document.querySelector('.login-form');
        if(loginForm) {
            loginForm.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('.btn');
                submitBtn.innerHTML = 'Connexion en cours... <i class="bx bx-loader bx-spin"></i>';
                submitBtn.disabled = true;
            });
        }
    </script>
</body>
</html>