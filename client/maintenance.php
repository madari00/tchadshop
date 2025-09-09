<?php
// Démarrer la session et inclure le fichier d'initialisation
include 'init.php';

// Récupération du mode maintenance
$sql = "SELECT valeur FROM configuration WHERE parametre = 'maintenance_mode' LIMIT 1";
$res = $conn->query($sql);
$maintenance = ($res && $res->num_rows > 0) ? $res->fetch_assoc()['valeur'] : "off";

// Rediriger vers index.php si le mode maintenance n'est pas activé
if ($maintenance !== "on") {
    header("Location: index.php");
    exit();
}
$config = [];
$resultat_logo_site = $conn->query("SELECT * FROM configuration");
while ($logo_site_header = $resultat_logo_site->fetch_assoc()) {
    $config[$logo_site_header['parametre']] = $logo_site_header['valeur'];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚧 <?php echo $trans['title']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #d35400;
            --secondary-color: #3498db;
            --text-color: #555;
            --light-bg: #f8f9fa;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #f4f4f9 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            text-align: center;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            width: 100%;
            margin: 0 auto;
        }
        
        .box {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: var(--box-shadow);
            margin: 20px 0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .box:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }
        
        .logo-container {
            margin-bottom: 30px;
            animation: pulse 2s infinite;
        }
        
        .logo {
            max-width: 150px;
            height: auto;
            margin-bottom: 20px;
            filter: drop-shadow(0 5px 10px rgba(0,0,0,0.1));
        }
        
        h1 {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        p {
            font-size: 1.2rem;
            margin-bottom: 15px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }
        
        .progress-container {
            width: 100%;
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            margin: 30px 0;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            width: 60%;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: 4px;
            animation: progress 2s infinite ease-in-out;
        }
        
        .social-links {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }
        
        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--light-bg);
            color: var(--primary-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .social-links a:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-3px);
        }
        
        .countdown {
            margin-top: 25px;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .contact-info {
            margin-top: 20px;
            font-size: 1rem;
        }
        
        .admin-note {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
            text-align: left;
        }
        
        .admin-note h3 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .language-selector {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        
        .language-selector button {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .language-selector button:hover {
            background: var(--primary-color);
            color: white;
        }
        
        /* Animations */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            60% { transform: translateY(-10px); }
        }
        
        @keyframes progress {
            0% { width: 30%; }
            50% { width: 70%; }
            100% { width: 30%; }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .box {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            p {
                font-size: 1.1rem;
            }
            
            .icon {
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 15px;
            }
            
            .box {
                padding: 25px 15px;
            }
            
            h1 {
                font-size: 1.8rem;
            }
            
            p {
                font-size: 1rem;
            }
            
            .logo {
                max-width: 120px;
            }
            
            .icon {
                font-size: 2rem;
            }
            
            .social-links {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="box">
            <div class="logo-container">
                <img src="../tchadshop/<?= htmlspecialchars($config['logo']) ?>" alt="Logo" class="logo" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2Y0ZjRmOSIgc3Ryb2tlPSIjZDM1NDAwIiBzdHJva2Utd2lkdGg9IjIiLz48dGV4dCB4PSIxMDAiIHk9IjEwMCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjI0IiBmaWxsPSIjZDM1NDAwIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+TE9HTzwvdGV4dD48L3N2Zz4='">
            </div>
            
            <div class="icon">
                <i class="fas fa-tools"></i>
            </div>
            
            <h1>🚧 <?php echo $trans['title']; ?></h1>
            
            <p><?php echo $trans['message1']; ?></p>
            <p><?php echo $trans['message2']; ?></p>
            
            <div class="progress-container">
                <div class="progress-bar"></div>
            </div>
            
            <p><?php echo $trans['thank_you']; ?></p>
            
            <div class="countdown">
                <i class="far fa-clock"></i> <?php echo $trans['estimated_time']; ?>: <?php echo $lang == 'ar' ? 'بضع ساعات' : 'Quelques heures'; ?>
            </div>
            
            <div class="contact-info">
                <p><?php echo $trans['contact1']; ?> <a href="mailto:support@example.com">support@example.com</a></p>
            </div>
            
            <div class="social-links">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
            </div>
            
            <!-- Sélecteur de langue -->
            <div class="language-selector">
                <button onclick="changeLanguage('fr')">Français</button>
                <button onclick="changeLanguage('en')">English</button>
                <button onclick="changeLanguage('ar')">العربية</button>
            </div>
            
            
    </div>

    <script>
        // Animation simple pour rendre la page plus vivante
        document.addEventListener('DOMContentLoaded', function() {
            const messages = <?php echo json_encode($trans['progress_messages']); ?>;
            
            let current = 0;
            const element = document.createElement('p');
            element.style.marginTop = '15px';
            element.style.fontStyle = 'italic';
            element.style.color = '#777';
            document.querySelector('.box').appendChild(element);
            
            function updateMessage() {
                element.textContent = messages[current];
                current = (current + 1) % messages.length;
            }
            
            updateMessage();
            setInterval(updateMessage, 3000);
            
            // Simulation de compte à rebours
            const countdownElement = document.querySelector('.countdown');
            let hours = 2;
            let minutes = 0;
            
            const updateCountdown = () => {
                countdownElement.innerHTML = `<i class="far fa-clock"></i> <?php echo $trans['estimated_time']; ?>: ${hours}h ${minutes.toString().padStart(2, '0')}m`;
                
                minutes--;
                if (minutes < 0) {
                    minutes = 59;
                    hours--;
                    if (hours < 0) {
                        hours = 0;
                        minutes = 0;
                    }
                }
            };
            
            // Démarrer le compte à rebours (simulation)
            setInterval(updateCountdown, 60000);
        });
        
        // Fonction pour changer la langue
        function changeLanguage(lang) {
            // Créer un formulaire pour envoyer la requête de changement de langue
            const form = document.createElement('form');
            form.method = 'GET';
            form.action = window.location.pathname;
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'lang';
            input.value = lang;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>