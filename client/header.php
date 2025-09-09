<?php
require_once 'init.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Récupération du mode maintenance
$sql = "SELECT valeur FROM configuration WHERE parametre = 'maintenance_mode' LIMIT 1";
$res = $conn->query($sql);
$maintenance = ($res && $res->num_rows > 0) ? $res->fetch_assoc()['valeur'] : "off";

// Vérification si l'utilisateur est administrateur
$isAdmin = isset($_SESSION['admin_id']);

// ⚡ Vérification mode maintenance
if ($maintenance === "on" && !$isAdmin) {
    // Rediriger vers la page de maintenance seulement si l'utilisateur n'est pas un admin
    header("Location: maintenance.php");
    exit();
}

$config = [];
$resultat_nome_site = $conn->query("SELECT * FROM configuration");
while ($logo_nome_header = $resultat_nome_site->fetch_assoc()) {
    $config[$logo_nome_header['parametre']] = $logo_nome_header['valeur'];
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TchadShop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Le CSS a été ajusté pour une meilleure adaptabilité */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f9f7ff 0%, #eef2ff 100%);
            color: #333;
            min-height: 100vh;
            padding-top: 110px;
        }

        /* Header styles */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(135deg, #6a1b9a 0%, #8e24aa 100%);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            padding: 0 18px;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            gap: 15px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            text-decoration: none;
            transition: transform 0.3s ease;
            flex-shrink: 0;
        }

        .logo:hover {
            transform: scale(1.03);
        }

        .logo-icon {
            font-size: 2rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Barre de recherche */
        .search-container {
            flex: 1;
            max-width: 600px;
            min-width: 250px;
            display: flex;
            gap: 10px;
            position: relative;
        }

        .search-box {
            flex: 1;
            display: flex;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .search-box:focus-within {
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.2);
        }

        .search-box input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 12px 20px;
            color: white;
            font-size: 0.9rem;
            outline: none;
            
        }

        .search-box input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .search-box button {
            background: transparent;
            border: none;
            color: white;
            padding: 0 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-left: 270px;
            
        }

        .search-box button:hover {
            transform: scale(1.1);
        }

        .voice-search-btn {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .voice-search-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.05);
        }

        .voice-search-btn.listening {
            animation: pulse 1s infinite;
            background: rgba(255, 0, 0, 0.3);
        }

        /* Nouveau conteneur pour le menu et les infos utilisateur */
        .nav-and-user-wrapper {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Navigation styles */
        .nav-menu {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            padding: 5px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateY(-2px);
        }

        .nav-link i {
            font-size: 1.2rem;
        }

        .nav-link .text {
            display: inline;
        }

        /* User info styles */
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-shrink: 0;
        }

        .user-greeting {
            margin-right: -4px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 50px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none; /* Ajout pour les liens */
        }

        .btn-login {
            background: #4CAF50;
            color: white;
        }

        .btn-logout {
            background: #f44336;
            color: white;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .btn:active {
            transform: translateY(-1px);
        }

        /* Mobile menu styles */
        .mobile-toggle {
            display: none;
            background: transparent;
            border: none;
            color: white;
            font-size: 1.8rem;
            cursor: pointer;
            padding: 10px;
        }

        /* Voice recognition panel */
        .voice-panel {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(106, 27, 154, 0.95);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            color: white;
            box-shadow: 0 10px 50px rgba(0, 0, 0, 0.3);
            z-index: 2000;
            display: none;
            max-width: 90%;
            width: 400px;
        }

        .voice-panel.active {
            display: block;
        }

        .voice-panel h3 {
            margin-bottom: 20px;
            font-size: 1.8rem;
        }

        .voice-animation {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .voice-animation .mic-icon {
            font-size: 4rem;
            color: white;
        }

        .voice-animation .wave {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 2px solid white;
            opacity: 0;
            animation: wave 1.5s infinite;
        }

        .voice-panel .result {
            margin-top: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .voice-panel .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .voice-panel .actions button {
            padding: 12px 25px;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .voice-panel .actions button:hover {
            transform: translateY(-3px);
        }

        .voice-panel .actions .stop-btn {
            background: #f44336;
            color: white;
        }

        .voice-panel .actions .close-btn {
            background: #9e9e9e;
            color: white;
        }

        @keyframes wave {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(1.5); opacity: 0; }
        }

        /* Styles pour le nouveau menu utilisateur */
        .user-profile {
            position: relative;
            display: inline-block;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .user-avatar:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.05);
        }

        .user-avatar .status-indicator {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            border: 2px solid #6a1b9a;
        }

        .user-avatar .status-indicator.online {
            background-color: #4CAF50;
        }

        .user-avatar .status-indicator.offline {
            background-color: #f44336;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            min-width: 180px;
            z-index: 1001;
            display: none;
            overflow: hidden;
            margin-top: 10px;
        }

        .dropdown-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .dropdown-menu a:hover {
            background: #f5f5f5;
            color: #6a1b9a;
        }

        .dropdown-menu a i {
            margin-right: 10px;
            color: #8e24aa;
            width: 20px;
            text-align: center;
        }

        .user-profile.active .dropdown-menu {
            display: block;
        }

        /* Language selector styles - MODIFIÉ */
        .language-selector {
            position: relative;
            display: inline-block;
        }

        .language-toggle {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            white-space: nowrap;
            background: none;
            border: none;
        }

        .language-toggle:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateY(-2px);
        }

        /* Modal pour la sélection de langue - NOUVEAU */
        .language-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .language-modal.active {
            display: flex;
            opacity: 1;
        }

        .modal-content1 {
            background-color: white;
            width: 90%;
            max-width: 400px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }

        .language-modal.active .modal-content1 {
            transform: scale(1);
        }

        .modal-header1 {
            background: linear-gradient(135deg, #6a1b9a 0%, #8e24aa 100%);
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }

        .modal-header1 h2 {
            font-size: 1.5rem;
            margin: 0;
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 20px;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .languages-list {
            padding: 20px;
        }

        .language-option {
            display: flex;
            align-items: center;
            padding: 15px;
            margin: 10px 0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid transparent;
            text-decoration: none;
            color: #333;
        }

        .language-option:hover {
            background-color: #f5f5f5;
            transform: translateX(5px);
        }

        .language-option.selected {
            border-color: #6a1b9a;
            background-color: #f9f5ff;
        }

        .flag {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .language-name {
            font-weight: 500;
            flex-grow: 1;
            text-align: left;
        }

        .language-check {
            color: #6a1b9a;
            font-size: 1.2rem;
        }

        /* For RTL languages */
        [dir="rtl"] .language-dropdown {
            right: auto;
            left: 0;
        }

        [dir="rtl"] .language-dropdown a {
            text-align: right;
        }

        /* Responsive styles */
        @media (max-width: 1200px) {
            .header-container {
                flex-direction: column;
                padding: 22px 0;
            }
            
            .search-container {
                order: 3;
                width: 100%;
                max-width: 100%;
                margin-top: 15px;
            }

            .nav-and-user-wrapper {
                order: 1;
                width: 100%;
                justify-content: space-between;
                margin-top: 15px;
            }

            .nav-menu {
                width: auto;
                justify-content: flex-start;
            }
            
            
        }

        @media (max-width: 992px) {
            .nav-menu {
                gap: 20px;
            }
            
            .nav-link .text {
                display: none;
            }
            
            .nav-link {
                padding: 12px;
                font-size: 1.4rem;
            }
            
            .user-greeting .text {
                display: none;
            }
            
            .search-container {
                order: 0;
                margin-top: 0;
            }

            .nav-and-user-wrapper {
                order: 2;
                margin-top: 0;
                width: auto;
            }

            .language-toggle .text {
                display: none;
            }
            
        }

        @media (max-width: 768px) {
            
            .nav-menu {
                position: fixed;
                top: 110px;
                left: 0;
                background: #6a1b9a;
                width: 100%;
                flex-direction: column;
                align-items: center;
                padding: 20px 0;
                gap: 15px;
                transform: translateY(-150%);
                transition: transform 0.4s ease;
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
            }
            
            .nav-menu.active {
                transform: translateY(0);
            }
            
            .nav-link {
                width: 90%;
                justify-content: center;
                padding: 15px;
                border-radius: 10px;
            }
            
            .nav-link .text {
                display: inline;
            }
            
            .mobile-toggle {
                display: block;
            }
            
            .user-greeting .text {
                display: inline;
            }
            
            .search-container {
                padding: 0 15px;
                order: 3;
                margin-top: 15px;
            }

            .nav-and-user-wrapper {
                order: 1;
                width: 100%;
                justify-content: space-between;
            }

            .language-selector {
                width: 100%;
            }
            
            .language-toggle {
                width: 100%;
                justify-content: center;
            }

            .language-toggle .text {
                display: inline;
            }
            
            
        }
        @media (min-width: 481px) and (max-width: 768px){
            .header{
                    margin-top: -15px;
                }
                .search-container {
                margin-top: -16px;
                }
                .nav-menu{
                margin-top: -8px;
            }

            .logo-text {
                margin-bottom: -80px;
                 margin-left: -90px;
            }
            .search-box button {
            background: transparent;
            border: none;
            color: white;
            padding: 0 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-left: 195px;
        }
        .search-box input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 12px 20px;
            color: white;
            font-size: 0.9rem;
            outline: none;
        }
            .logo-icon {
                margin-left: 40px;
            }
            .user-greeting .name {
                margin-bottom: -80px;
            }

            .btn span {
                display: none;
            }
            
            .btn {
                padding: 12px;
            }
            
            .voice-search-btn {
                width: 40px;
                height: 40px;
            }
        }
        @media (min-width: 769px) and (max-width: 1024px) and (orientation: landscape) {
            .header{
                    margin-top: -20px;
                }
                .search-container {
                margin-top: -16px;
                }
                .nav-menu{
                margin-top: -8px;
            }

            .logo-text {
                margin-bottom: 4px;
                
            }
            .search-box button {
            background: transparent;
            border: none;
            color: white;
            padding: 0 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-left: 585px;
        }
        .search-box input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 12px 20px;
            color: white;
            font-size: 0.9rem;
            outline: none;
        }
            .logo-icon {
                margin-left: 40px;
                margin-bottom: 6px;
            }
            .user-greeting .name {
                margin-bottom: -80px;
            }

            .btn span {
                display: none;
            }
            
            .btn {
                padding: 12px;
            }
            
            .voice-search-btn {
                width: 40px;
                height: 40px;
            }
        }
        @media (max-width: 480px)  {
            .nav-menu{
                margin-top: -8px;
            }

            .logo-text {
                margin-bottom: -80px;
                 margin-left: -90px;
            }
            .header{
            margin-top: -15px;
        }
         .search-container {
          margin-top: -16px;
        }
        .search-box button {
            background: transparent;
            border: none;
            color: white;
            padding: 0 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-left: 10px;
        }
        .search-box input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 12px 20px;
            color: white;
            font-size: 0.9rem;
            outline: none;
        }
            .logo-icon {
                margin-left: 40px;
            }
            .user-greeting .name {
                margin-bottom: -80px;
            }

            .btn span {
                display: none;
            }
            
            .btn {
                padding: 12px;
            }
            
            .voice-search-btn {
                width: 40px;
                height: 40px;
            }
        }
        
        /* Demo content */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .content-box {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            padding: 40px;
            margin-bottom: 30px;
        }
        
        h1 {
            color: #6a1b9a;
            margin-bottom: 20px;
            font-size: 2.5rem;
        }
        
        p {
            line-height: 1.8;
            margin-bottom: 15px;
            font-size: 1.1rem;
            color: #555;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 40px;
        }
        
        .feature-card {
            background: #f9f5ff;
            border-radius: 12px;
            padding: 25px;
            transition: transform 0.3s ease;
            border: 1px solid #eee;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(106, 27, 154, 0.1);
        }
        
        .feature-card i {
            font-size: 2.5rem;
            color: #8e24aa;
            margin-bottom: 15px;
        }
        
        .feature-card h3 {
            color: #6a1b9a;
            margin-bottom: 10px;
        }
        
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-left: 8px;
        }
        
        .online {
            background-color: #4CAF50;
            box-shadow: 0 0 8px #4CAF50;
        }
        
        .offline {
            background-color: #f44336;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <a href="index.php" class="logo">
                <i class="fas fa-shopping-bag logo-icon"></i>
                <span class="logo-text"><?= htmlspecialchars($config['site_name']) ?></span>
            </a>
            
            <div class="search-container">
            <div class="search-box">
                <form action="recherche.php" method="get" class="search-form">
                    <input type="text" id="searchInput" name="q" placeholder="<?php echo $trans['search_placeholder']; ?>">
                    <button id="searchButton" type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <button class="voice-search-btn" id="voiceButton">
                <i class="fas fa-microphone"></i>
            </button>
        </div>
            
            <div class="nav-and-user-wrapper">
                <button class="mobile-toggle" id="mobileToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <nav class="nav-menu" id="navMenu">
                    <a href="index.php" class="nav-link active">
                        <i class="fas fa-home"></i>
                        <span class="text"><?php echo $trans['home']; ?></span>
                    </a>
                    <a href="produits.php" class="nav-link">
                        <i class="fas fa-box-open"></i>
                        <span class="text"><?php echo $trans['products']; ?></span>
                    </a>
                    <a href="promotions.php" class="nav-link">
                        <i class="fas fa-gift" style="color: #ffffffff;"></i>
                        <span class="text"><?php echo $trans['promotions']; ?></span>
                    </a>
                    
                    <?php if ($isClient): ?>
                         <a href="mes_commandes.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="text"><?php echo $trans['my_orders']; ?></span>
                    </a>
                        
                        <a href="historique_client.php" class="nav-link">
                            <i class="fas fa-history"></i>
                            <span class="text"><?php echo $trans['history']; ?></span>
                        </a>
                        <a href="message_support.php" class="nav-link">
                            <i class="fas fa-envelope"></i>
                            <span class="text"><?php echo $trans['messages']; ?></span>
                        </a>
                    <?php endif; ?>
                    
                    <a href="contact.php" class="nav-link">
                        <i class="fas fa-phone-alt"></i>
                        <span class="text"><?php echo $trans['contact']; ?></span>
                    </a>
                    
                    <div class="language-selector">
                        <button class="nav-link language-toggle" id="languageToggle">
                            <i class="fas fa-globe"></i>
                            <span class="text"><?php echo $currentLanguageName; ?></span>
                        </button>
                    </div>
                    
                </nav>
                
                <div class="user-info">
                    <?php if ($isClient): ?>
                        <div class="user-profile">
                            <button class="user-avatar">
                                <i class="fas fa-user"></i>
                                <span class="status-indicator online"></span>
                            </button>
                            <div class="dropdown-menu">
                                <a href="mon_compte.php"><i class="fas fa-user-circle"></i><?php echo $trans['my_account']; ?></a>
                                <a href="logout.php"><i class="fas fa-sign-out-alt"></i><?php echo $trans['logout']; ?></a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-login">
                            <i class="fas fa-sign-in-alt"></i>
                            <span><?php echo $trans['login']; ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
       
    </header>

    <!-- Modal de sélection de langue - NOUVEAU -->
    <div class="language-modal" id="languageModal">
        <div class="modal-content1">
            <div class="modal-header1">
                <h2><?php echo $trans['choose_language']; ?></h2>
                <button class="close-modal" id="closeModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="languages-list">
                <a href="?lang=fr" class="language-option <?php echo $lang == 'fr' ? 'selected' : ''; ?>">
                    <div class="flag">🇫🇷</div>
                    <span class="language-name"><?php echo $trans['french']; ?></span>
                    <?php if ($lang == 'fr'): ?>
                        <i class="fas fa-check language-check"></i>
                    <?php endif; ?>
                </a>
                <a href="?lang=en" class="language-option <?php echo $lang == 'en' ? 'selected' : ''; ?>">
                    <div class="flag">🇬🇧</div>
                    <span class="language-name"><?php echo $trans['english']; ?></span>
                    <?php if ($lang == 'en'): ?>
                        <i class="fas fa-check language-check"></i>
                    <?php endif; ?>
                </a>
                <a href="?lang=ar" class="language-option <?php echo $lang == 'ar' ? 'selected' : ''; ?>">
                    <div class="flag">🇸🇦</div>
                    <span class="language-name"><?php echo $trans['arabic']; ?></span>
                    <?php if ($lang == 'ar'): ?>
                        <i class="fas fa-check language-check"></i>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>

    <div class="voice-panel" id="voicePanel">
    <h3><?php echo $trans['voice_search']; ?></h3>
    <div class="voice-animation">
        <i class="fas fa-microphone mic-icon"></i>
        <div class="wave"></div>
        <div class="wave"></div>
        <div class="wave"></div>
    </div>
    <p><?php echo $trans['speak_to_search']; ?></p>
    <div class="result" id="voiceResult">
        <?php echo $trans['voice_result']; ?>
    </div>
    <div class="actions">
        <button class="search-btn" id="voiceSearchButton" style="display:none;">
            <i class="fas fa-search"></i> <?php echo $trans['search']; ?>
        </button>
        <button class="stop-btn" id="stopButton">
            <i class="fas fa-stop"></i> <?php echo $trans['stop']; ?>
        </button>
        <button class="close-btn" id="closeButton">
            <i class="fas fa-times"></i> <?php echo $trans['close']; ?>
        </button>
    </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {

    // --- Sélection des éléments HTML ---
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');
    const voiceButton = document.getElementById('voiceButton');
    const voicePanel = document.getElementById('voicePanel');
    const voiceResult = document.getElementById('voiceResult');
    const stopButton = document.getElementById('stopButton');
    const closeButton = document.getElementById('closeButton');
    const mobileToggle = document.getElementById('mobileToggle');
    const navMenu = document.getElementById('navMenu');
    const voiceSearchButton = document.getElementById('voiceSearchButton');
    const userProfile = document.querySelector('.user-profile');
    const userAvatar = document.querySelector('.user-avatar');
    const languageToggle = document.getElementById('languageToggle');
    const languageModal = document.getElementById('languageModal');
    const closeModal = document.getElementById('closeModal');
    const navLinks = document.querySelectorAll('.nav-link');


    // --- Fonctions de base ---
    let recognition = null;
    let isListening = false;

    function performSearch(query) {
        if (query) {
            window.location.href = `recherche.php?q=${encodeURIComponent(query)}`;
        }
    }

    function initSpeechRecognition() {
        if (!('SpeechRecognition' in window || 'webkitSpeechRecognition' in window)) {
            console.warn('Speech Recognition non supporté par ce navigateur.');
            if (voiceButton) {
                voiceButton.style.display = 'none';
            }
            return null;
        }
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        const recognizer = new SpeechRecognition();
        recognizer.lang = 'fr-FR';
        recognizer.continuous = false;
        recognizer.interimResults = false;
        return recognizer;
    }

    function startListening() {
        recognition = initSpeechRecognition();
        if (!recognition) return;

        voicePanel.classList.add('active');
        voiceSearchButton.style.display = 'none';
        voiceResult.textContent = "<?php echo $trans['speak_to_search']; ?>";
        
        recognition.onstart = () => {
            isListening = true;
            voiceButton.classList.add('listening');
        };
        recognition.onend = () => {
            isListening = false;
            voiceButton.classList.remove('listening');
        };
        recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            searchInput.value = transcript;
            voiceResult.textContent = transcript;
            voiceSearchButton.style.display = 'inline-block';
        };
        recognition.onerror = (event) => {
            console.error("Erreur vocale :", event.error);
            voiceResult.textContent = "Erreur: " + event.error;
            voiceSearchButton.style.display = 'none';
        };
        try {
            recognition.start();
        } catch (e) {
            console.error("Erreur de démarrage du micro:", e);
            voiceResult.textContent = "Veuillez autoriser l'accès au microphone.";
            voicePanel.classList.add('active');
            isListening = false;
        }
    }

    function stopListening() {
        if (isListening && recognition) {
            recognition.stop();
        }
        voicePanel.classList.remove('active');
    }

    // --- Gestion des événements ---

    // Bouton de recherche
    if (searchButton) {
        searchButton.addEventListener('click', () => {
            performSearch(searchInput.value.trim());
        });
    }

    // Recherche avec la touche Entrée
    if (searchInput) {
        searchInput.addEventListener('keypress', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                performSearch(searchInput.value.trim().replace(/\.$/, ""));
            }
        });
    }

    // Bouton de reconnaissance vocale
    if (voiceButton) {
        voiceButton.addEventListener('click', () => {
            if (!isListening) {
                startListening();
            } else {
                stopListening();
            }
        });
    }

    if (stopButton) {
        stopButton.addEventListener('click', stopListening);
    }

    if (closeButton) {
        closeButton.addEventListener('click', stopListening);
    }
    
    if (voiceSearchButton) {
        voiceSearchButton.addEventListener('click', () => {
            const query = voiceResult.textContent.trim();
            if (query && query !== "<?php echo $trans['voice_result']; ?>" && query !== "<?php echo $trans['speak_to_search']; ?>") {
                performSearch(query);
            }
            stopListening();
        });
    }

    // --- Menu utilisateur (CORRIGÉ) ---
    if (userAvatar) {
        userAvatar.addEventListener('click', (e) => {
            e.stopPropagation(); // Évite que le clic ne se propage au document
            userProfile.classList.toggle('active');
        });
    }

    // --- Menu mobile ---
    if (mobileToggle) {
        mobileToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            const icon = mobileToggle.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        });
    }

    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
                const icon = mobileToggle.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    });

    // --- Modale de langue ---
    if (languageToggle) {
        languageToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            languageModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }

    if (closeModal) {
        closeModal.addEventListener('click', () => {
            languageModal.classList.remove('active');
            document.body.style.overflow = '';
        });
    }

    if (languageModal) {
        languageModal.addEventListener('click', (e) => {
            if (e.target === languageModal) {
                languageModal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }
    
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && languageModal.classList.contains('active')) {
            languageModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    // --- Fermer les menus si l'on clique en dehors ---
    document.addEventListener('click', (e) => {
        // Ferme le menu utilisateur
        if (userProfile && !userProfile.contains(e.target)) {
            userProfile.classList.remove('active');
        }
    });
});
</script>
</body>
</html>