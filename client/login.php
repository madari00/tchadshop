<?php
require_once 'init.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Déterminer la langue (par défaut: français)
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_SESSION['lang']) ? $_SESSION['lang'] : 'fr');
$_SESSION['lang'] = $lang;

// Inclure le fichier de langue approprié
include_once "lang/$lang.php";

// Vérifier si la connexion existe déjà (peut-être déjà établie par header.php)
if (!isset($conn)) {
    $conn = new mysqli("localhost", "root", "", "tchadshop_db");
    if ($conn->connect_error) die("Erreur de connexion : " . $conn->connect_error);
}

// Traitement des formulaires
$error = '';
$success = '';

// Traitement de l'inscription
if (isset($_POST['signup'])) {
    $nom = $conn->real_escape_string($_POST['nom']);
    $email = $conn->real_escape_string($_POST['email']);
    $telephone = $conn->real_escape_string($_POST['telephone']);
    $adresse = $conn->real_escape_string($_POST['adresse']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Vérifier si le téléphone existe déjà
    $check_sql = "SELECT id, invite FROM clients WHERE telephone = '$telephone'";
    $result = $conn->query($check_sql);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Si le compte est un compte invité, on met à jour les informations
        if ($user['invite'] == 1) {
            $update_sql = "UPDATE clients SET 
                            nom = '$nom', 
                            email = '$email', 
                            adresse = '$adresse', 
                            password = '$password', 
                            invite = 0 
                            WHERE id = " . $user['id'];
            
            if ($conn->query($update_sql)) {
                $success = $trans['account_created_success'];
            } else {
                $error = $trans['account_update_error'] . $conn->error;
            }
        } else {
            $error = $trans['phone_already_registered'];
        }
    } else {
        // Insérer le nouvel utilisateur
        $sql = "INSERT INTO clients (nom, email, telephone, adresse, password, invite, vu) 
                VALUES ('$nom', '$email', '$telephone', '$adresse', '$password', 0, 0)";
        
        if ($conn->query($sql)) {
            $success = $trans['account_created_success'];
        } else {
            $error = $trans['account_creation_error'] . $conn->error;
        }
    }
}

// Traitement de la connexion
if (isset($_POST['login'])) {
    $telephone = $conn->real_escape_string($_POST['login_telephone']);
    $password = $_POST['login_password'];
    
    $sql = "SELECT * FROM clients WHERE telephone = '$telephone' AND invite = 0";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Connexion réussie
            $_SESSION['client_id'] = $user['id'];
            $_SESSION['client_nom'] = $user['nom'];
            
            // Mettre à jour le statut vu si nécessaire
            if ($user['vu'] == 0) {
                $update_sql = "UPDATE clients SET vu = 1 WHERE id = " . $user['id'];
                $conn->query($update_sql);
            }
            
            // Redirection vers la page d'accueil
            header("Location: index.php");
            exit();
        } else {
            $error = $trans['incorrect_password'];
        }
    } else {
        $error = $trans['no_account_found'];
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $trans['join_tchadshop']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        
        .auth-container {
            width: 100%;
            max-width: 1000px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            min-height: 600px;
            animation: fadeIn 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .auth-illustration {
            flex: 1;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .auth-illustration::before {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            top: -100px;
            right: -100px;
        }
        
        .auth-illustration::after {
            content: "";
            position: absolute;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            bottom: -80px;
            left: -80px;
        }
        
        .illustration-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 400px;
        }
        
        .illustration-content h2 {
            font-size: 2.2rem;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .illustration-content p {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .illustration-img {
            font-size: 120px;
            margin-bottom: 30px;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .features {
            text-align: left;
            margin-top: 30px;
        }
        
        .features p {
            display: flex;
            align-items: center;
            margin: 10px 0;
            font-size: 0.95rem;
        }
        
        .features i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .auth-forms {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }
        
        .tabs {
            display: flex;
            border-bottom: 2px solid var(--gray);
            margin-bottom: 30px;
        }
        
        .tab {
            padding: 15px 30px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--dark);
            background: transparent;
            border: none;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .tab.active {
            color: var(--primary);
        }
        
        .tab.active::after {
            content: "";
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--primary);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: slideIn 0.4s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-header {
            margin-bottom: 25px;
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
        
        .info-text {
            background: #e3f2fd;
            padding: 12px;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-top: 10px;
            color: #0d47a1;
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
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin: 15px 0;
        }
        
        .checkbox-group input {
            margin-right: 10px;
            width: 18px;
            height: 18px;
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
        
        .form-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .form-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .form-footer a:hover {
            text-decoration: underline;
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
        
        /* Responsive Design */
        @media (max-width: 850px) {
            .auth-container {
                flex-direction: column;
                max-width: 600px;
            }
            
            .auth-illustration {
                padding: 30px;
            }
            
            .illustration-content h2 {
                font-size: 1.8rem;
            }
            
            .illustration-img {
                font-size: 80px;
            }
        }
        
        @media (max-width: 480px) {
            .auth-forms {
                padding: 25px;
            }
            
            .tab {
                padding: 12px 20px;
                font-size: 1rem;
            }
            
            .form-header h2 {
                font-size: 1.5rem;
            }
            
            .form-control {
                padding: 12px 15px 12px 40px;
            }
            
            .input-icon, .password-toggle {
                top: 38px;
            }
            
            .btn {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-illustration">
            <div class="illustration-content">
                <div class="illustration-img">
                    <i class="fas fa-user-lock"></i>
                </div>
                <h2><?php echo $trans['join_tchadshop']; ?></h2>
                <p><?php echo $trans['unique_shopping_experience']; ?></p>
                
                <div class="features">
                    <p><i class="fas fa-check-circle"></i> <?php echo $trans['real_time_tracking']; ?></p>
                    <p><i class="fas fa-check-circle"></i> <?php echo $trans['purchase_history']; ?></p>
                    <p><i class="fas fa-check-circle"></i> <?php echo $trans['personalized_recommendations']; ?></p>
                    <p><i class="fas fa-check-circle"></i> <?php echo $trans['fast_secure_delivery']; ?></p>
                    <p><i class="fas fa-check-circle"></i> <?php echo $trans['customer_support']; ?></p>
                </div>
            </div>
        </div>
        
        <div class="auth-forms">
            <div class="error-message" id="error-message">
                <?php echo $error; ?>
            </div>
            
            <div class="success-message" id="success-message">
                <?php echo $success; ?>
            </div>
            
            <div class="tabs">
                <button class="tab active" data-tab="login"><?php echo $trans['login_tab']; ?></button>
                <button class="tab" data-tab="signup"><?php echo $trans['signup_tab']; ?></button>
            </div>
            
            <!-- Connexion Form -->
            <div id="login" class="tab-content active">
                <div class="form-header">
                    <h2><?php echo $trans['welcome_back']; ?></h2>
                    <p><?php echo $trans['login_to_continue']; ?></p>
                </div>
                
                <form method="post">
                    <div class="form-group">
                        <label for="login_telephone"><i class="fas fa-phone"></i> <?php echo $trans['phone_label']; ?></label>
                        <i class="fas fa-mobile-alt input-icon"></i>
                        <input type="tel" id="login_telephone" name="login_telephone" class="form-control" placeholder="<?php echo $trans['phone_placeholder']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="login_password"><i class="fas fa-lock"></i> <?php echo $trans['password_label']; ?></label>
                        <i class="fas fa-key input-icon"></i>
                        <input type="password" id="login_password" name="login_password" class="form-control" placeholder="<?php echo $trans['password_placeholder']; ?>" required>
                        <button type="button" class="password-toggle" id="toggle_login_password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="remember">
                        <label for="remember"><?php echo $trans['remember_me']; ?></label>
                    </div>
                    
                    <button type="submit" name="login" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> <?php echo $trans['login_button']; ?>
                    </button>
                    
                    <div class="form-footer">
                        <p><?php echo $trans['forgot_password']; ?> <a href="reset_password.php" id="forgot-password"><?php echo $trans['reset_password']; ?></a></p>
                        <p><?php echo $trans['no_account']; ?> <a href="#" id="switch-to-signup"><?php echo $trans['create_account']; ?></a></p>
                    </div>
                </form>
            </div>
            
            <!-- Inscription Form -->
            <div id="signup" class="tab-content">
                <div class="form-header">
                    <h2><?php echo $trans['create_account_title']; ?></h2>
                    <p><?php echo $trans['join_steps']; ?></p>
                    <p class="info-text"><i class="fas fa-info-circle"></i> <?php echo $trans['guest_order_info']; ?></p>
                </div>
                
                <form method="post">
                    <div class="form-group">
                        <label for="nom"><i class="fas fa-user"></i> <?php echo $trans['fullname_label']; ?></label>
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="nom" name="nom" class="form-control" placeholder="<?php echo $trans['fullname_placeholder']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="telephone"><i class="fas fa-phone"></i> <?php echo $trans['phone_label']; ?></label>
                        <i class="fas fa-mobile-alt input-icon"></i>
                        <input type="tel" id="telephone" name="telephone" class="form-control" placeholder="<?php echo $trans['phone_placeholder']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> <?php echo $trans['email_label']; ?></label>
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="form-control" placeholder="<?php echo $trans['email_placeholder']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="adresse"><i class="fas fa-map-marker-alt"></i> <?php echo $trans['address_label']; ?></label>
                        <i class="fas fa-home input-icon"></i>
                        <input type="text" id="adresse" name="adresse" class="form-control" placeholder="<?php echo $trans['address_placeholder']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock"></i> <?php echo $trans['password_label']; ?></label>
                        <i class="fas fa-key input-icon"></i>
                        <input type="password" id="password" name="password" class="form-control" placeholder="<?php echo $trans['create_password_placeholder']; ?>" required>
                        <button type="button" class="password-toggle" id="toggle_password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password"><i class="fas fa-lock"></i> <?php echo $trans['confirm_password_label']; ?></label>
                        <i class="fas fa-key input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="<?php echo $trans['confirm_password_placeholder']; ?>" required>
                        <button type="button" class="password-toggle" id="toggle_confirm_password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="terms" required>
                        <label for="terms">
                            <?php echo $trans['accept_terms_1']; ?>
                            <a href="informations.php?section=confidentialite"><?php echo $trans['terms_of_use']; ?></a>
                            <?php echo $trans['accept_terms_2']; ?>
                            <a href="#"><?php echo $trans['privacy_policy']; ?></a>
                        </label>
                    </div>
                    
                    <button type="submit" name="signup" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> <?php echo $trans['signup_button']; ?>
                    </button>
                    
                    <div class="form-footer">
                        <p><?php echo $trans['already_have_account']; ?> <a href="#" id="switch-to-login"><?php echo $trans['login_here']; ?></a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Tab switching functionality
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs and contents
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab
                tab.classList.add('active');
                
                // Show corresponding content
                const tabId = tab.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Password visibility toggle - CORRIGÉ
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
        setupPasswordToggle('toggle_login_password', 'login_password');
        setupPasswordToggle('toggle_password', 'password');
        setupPasswordToggle('toggle_confirm_password', 'confirm_password');
        
        // Switch between login and signup
        document.getElementById('switch-to-login').addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('[data-tab="login"]').click();
        });
        
        document.getElementById('switch-to-signup').addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('[data-tab="signup"]').click();
        });
        
       
        
        // Form validation
        const signupForm = document.querySelector('#signup form');
        if (signupForm) {
            signupForm.addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                const confirm = document.getElementById('confirm_password').value;
                
                if (password !== confirm) {
                    e.preventDefault();
                    alert("<?php echo $trans['passwords_do_not_match']; ?>");
                }
            });
        }
        
        // Auto-focus on first input when tab changes
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const activeTab = this.getAttribute('data-tab');
                const firstInput = document.querySelector(`#${activeTab} .form-control`);
                if (firstInput) {
                    firstInput.focus();
                }
            });
        });
        
        // Info sur les comptes invités
        const telephoneInput = document.getElementById('telephone');
        if (telephoneInput) {
            telephoneInput.addEventListener('blur', function() {
                const phone = this.value;
                if (phone.length >= 8) {
                    // Vérifier si le numéro est associé à un compte invité
                    fetch('check_guest.php?phone=' + phone)
                        .then(response => response.json())
                        .then(data => {
                            if (data.is_guest) {
                                document.querySelector('.info-text').innerHTML = 
                                    '<i class="fas fa-info-circle"></i> Ce numéro est associé à un compte invité. ' + 
                                    'En complétant cette inscription, vous transformerez votre compte invité en compte complet.';
                            } else {
                                document.querySelector('.info-text').innerHTML = 
                                    '<i class="fas fa-info-circle"></i> Si vous avez déjà passé une commande en tant qu\'invité, ' + 
                                    'utilisez le même numéro de téléphone pour compléter votre inscription';
                            }
                        });
                }
            });
        }
    </script>
</body>
</html>