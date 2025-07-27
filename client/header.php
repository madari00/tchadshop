<?php
session_start();
$isClient = isset($_SESSION['client']);
$clientName = $isClient ? $_SESSION['client']['nom'] : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TchadShop - Header Moderne</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
            padding: 0 20px;
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
            font-size: 1rem;
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

        /* Navigation styles */
        .nav-menu {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            padding: 12px 18px;
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
            color: white;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.15);
            padding: 8px 15px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 8px;
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

        /* Responsive styles */
        @media (max-width: 1200px) {
            .header-container {
                flex-direction: column;
                padding: 15px 0;
            }
            
            .search-container {
                order: 3;
                width: 100%;
                max-width: 100%;
                margin-top: 15px;
            }
        }

        @media (max-width: 992px) {
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
            }
        }

        @media (max-width: 480px) {
            .logo-text {
                display: none;
            }
            
            .user-greeting .name {
                max-width: 100px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
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
        
        .footer {
            text-align: center;
            padding: 30px;
            color: #777;
            font-size: 1rem;
            margin-top: 50px;
            border-top: 1px solid #eee;
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
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <a href="index.php" class="logo">
                <i class="fas fa-shopping-bag logo-icon"></i>
                <span class="logo-text">TchadShop</span>
            </a>
            
            <div class="search-container">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Rechercher des produits...">
                    <button id="searchButton"><i class="fas fa-search"></i></button>
                </div>
                <button class="voice-search-btn" id="voiceButton">
                    <i class="fas fa-microphone"></i>
                </button>
            </div>
            
            <button class="mobile-toggle" id="mobileToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <nav class="nav-menu" id="navMenu">
                <a href="index.php" class="nav-link active">
                    <i class="fas fa-home"></i>
                    <span class="text">Accueil</span>
                </a>
                <a href="produits.php" class="nav-link">
                    <i class="fas fa-box-open"></i>
                    <span class="text">Produits</span>
                </a>
                <a href="mes_commandes.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="text">Mes commandes</span>
                </a>
                
                <?php if ($isClient): ?>
                    <a href="mon_compte.php" class="nav-link">
                        <i class="fas fa-user-circle"></i>
                        <span class="text">Mon compte</span>
                    </a>
                    <a href="historique_client.php" class="nav-link">
                        <i class="fas fa-history"></i>
                        <span class="text">Historique</span>
                    </a>
                    <a href="message_support.php" class="nav-link">
                        <i class="fas fa-envelope"></i>
                        <span class="text">Messages</span>
                    </a>
                <?php endif; ?>
                
                <a href="contact.php" class="nav-link">
                    <i class="fas fa-phone-alt"></i>
                    <span class="text">Contact</span>
                </a>
                <a href="?lang=ar" class="nav-link">
                    <i class="fas fa-globe"></i>
                    <span class="text">عربي</span>
                </a>
            </nav>
            
            <div class="user-info">
                <?php if ($isClient): ?>
                    <div class="user-greeting">
                        <i class="fas fa-user"></i>
                        <span class="text">Bonjour,</span>
                        <span class="name"><?php echo $clientName; ?></span>
                        <span class="status-indicator online"></span>
                    </div>
                    <a href="logout.php" class="btn btn-logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Déconnexion</span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Connexion</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Voice Recognition Panel -->
    <div class="voice-panel" id="voicePanel">
        <h3>Recherche vocale</h3>
        <div class="voice-animation">
            <i class="fas fa-microphone mic-icon"></i>
            <div class="wave"></div>
            <div class="wave"></div>
            <div class="wave"></div>
        </div>
        <p>Parlez maintenant pour rechercher des produits...</p>
        <div class="result" id="voiceResult">
            Votre demande sera affichée ici
        </div>
        <div class="actions">
            <button class="stop-btn" id="stopButton">
                <i class="fas fa-stop"></i> Arrêter
            </button>
            <button class="close-btn" id="closeButton">
                <i class="fas fa-times"></i> Fermer
            </button>
        </div>
    </div>

   
    <script>
        // Mobile menu toggle
        const mobileToggle = document.getElementById('mobileToggle');
        const navMenu = document.getElementById('navMenu');
        
        mobileToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            const icon = mobileToggle.querySelector('i');
            if (navMenu.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
        
        // Close menu when clicking on a link
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
                const icon = mobileToggle.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            });
        });
        
        // Voice recognition functionality
        const voiceButton = document.getElementById('voiceButton');
        const voicePanel = document.getElementById('voicePanel');
        const stopButton = document.getElementById('stopButton');
        const closeButton = document.getElementById('closeButton');
        const voiceResult = document.getElementById('voiceResult');
        const searchInput = document.getElementById('searchInput');
        
        let recognition;
        let isListening = false;
        
        // Initialize speech recognition
        function initSpeechRecognition() {
            recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
            recognition.continuous = true;
            recognition.lang = 'fr-FR';
            
            recognition.onstart = function() {
                isListening = true;
                voiceButton.classList.add('listening');
                voicePanel.classList.add('active');
                voiceResult.textContent = "Écoute en cours...";
            };
            
            recognition.onresult = function(event) {
                const transcript = event.results[event.results.length - 1][0].transcript;
                voiceResult.textContent = transcript;
                
                // Check for availability request
                if (transcript.toLowerCase().includes('disponibilité')) {
                    voiceResult.innerHTML = `Vérification de disponibilité pour: <strong>${transcript.replace('disponibilité', '').trim()}</strong>`;
                    setTimeout(() => {
                        voiceResult.innerHTML += `<br><br><span style="color: #4CAF50; font-weight: bold;">✔️ Disponible en stock</span>`;
                    }, 1500);
                } else {
                    searchInput.value = transcript;
                }
            };
            
            recognition.onerror = function(event) {
                console.error('Erreur de reconnaissance vocale:', event.error);
                voiceResult.textContent = "Erreur: " + event.error;
                stopListening();
            };
            
            recognition.onend = function() {
                if (isListening) {
                    recognition.start();
                }
            };
        }
        
        function startListening() {
            if (!recognition) {
                initSpeechRecognition();
            }
            
            try {
                recognition.start();
            } catch (error) {
                voiceResult.textContent = "Veuillez autoriser l'accès au microphone.";
                voicePanel.classList.add('active');
            }
        }
        
        function stopListening() {
            if (recognition) {
                recognition.stop();
            }
            isListening = false;
            voiceButton.classList.remove('listening');
        }
        
        voiceButton.addEventListener('click', () => {
            if (isListening) {
                stopListening();
            } else {
                startListening();
            }
        });
        
        stopButton.addEventListener('click', () => {
            stopListening();
        });
        
        closeButton.addEventListener('click', () => {
            voicePanel.classList.remove('active');
            stopListening();
        });
        
        // Search functionality
        const searchButton = document.getElementById('searchButton');
        
        searchButton.addEventListener('click', () => {
            if (searchInput.value.trim() !== '') {
                alert(`Recherche pour: ${searchInput.value}`);
                // Ici vous pourriez rediriger vers la page de résultats de recherche
                // window.location.href = `recherche.php?q=${encodeURIComponent(searchInput.value)}`;
            }
        });
        
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && searchInput.value.trim() !== '') {
                alert(`Recherche pour: ${searchInput.value}`);
                // window.location.href = `recherche.php?q=${encodeURIComponent(searchInput.value)}`;
            }
        });
    </script>
</body>
</html>