<?php
session_start();
$searchContext = '';
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}
if (isset($_POST['save_language'])) {
    $newLang = $_POST['default_language'];
    $stmt = $conn->prepare("UPDATE configuration SET valeur=? WHERE parametre='default_language'");
    $stmt->bind_param("s", $newLang);
    $stmt->execute();
    echo "<script>alert('Langue mise à jour !'); location.reload();</script>";
}

// Charger la configuration
$config = [];
$result = $conn->query("SELECT * FROM configuration");
while ($row = $result->fetch_assoc()) {
    $config[$row['parametre']] = $row['valeur'];
}

// Gérer le changement de langue
$current_lang = isset($_GET['lang']) ? $_GET['lang'] : $config['default_language'];
$_SESSION['lang'] = $current_lang;

$translations = include 'traductions.php';


// Définir les textes en fonction de la langue
$t = $translations[$current_lang];
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['title_configuration']; ?></title>
    <style>
        :root {
            --primary: #236459;
            --primary-light: #2d7d6f;
            --secondary: #f8f9fa;
            --accent: #f0ad4e;
            --danger: #d9534f;
            --success: #5cb85c;
            --warning: #f0ad4e;
            --dark: #343a40;
            --light: #f8f9fa;
            --border: #dee2e6;
            --shadow: 0 4px 12px rgba(0,0,0,0.1);
            --radius: 8px;
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4efe9 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            
            direction: <?php echo ($current_lang == 'ar') ? 'ltr' : 'ltr'; ?>;
        }
        
        .container {
            max-width: 1000px;
            margin: 2px auto;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
       
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo h1 {
            font-size: 24px;
            font-weight: 600;
        }
        
        .logo-icon {
            background: white;
            color: var(--primary);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
        }
        
        .language-selector {
            display: flex;
            gap: 10px;
        }
        
        .lang-btn1 {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 30px;
            cursor: pointer;
            font-size: 14px;
            transition: var(--transition);
        }
        
        .lang-btn1:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .lang-btn1.active {
            background: white;
            color: var(--primary);
            font-weight: 600;
        }
        
        .admin-content {
            padding: 30px;
        }
        
        .settings-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border);
        }
        
        .settings-title h2 {
            font-size: 24px;
            color: var(--primary);
        }
        
        .settings-title i {
            background: var(--primary);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 30px;
            background: var(--secondary);
            border-radius: var(--radius);
            padding: 5px;
        }
        
        .tab-btn1 {
            flex: 1;
            padding: 15px;
            background: none;
            border: none;
            border-radius: var(--radius);
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
        }
        
        .tab-btn1:hover {
            background: rgba(35, 100, 89, 0.1);
        }
        
        .tab-btn1.active {
            background: var(--primary);
            color: white;
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.4s ease;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-container {
            background: var(--light);
            border-radius: var(--radius);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 14px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 16px;
            transition: var(--transition);
        }
        
        input:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(35, 100, 89, 0.2);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .checkbox-group input {
            width: 20px;
            height: 20px;
        }
        
        #logoPreview {
            max-width: 150px;
            height: auto;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            margin: 15px 0;
            padding: 5px;
            background: white;
            display: block;
        }
        
        .btn11 {
            padding: 14px 28px;
            border: none;
            border-radius: var(--radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn1-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn1-primary:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(35, 100, 89, 0.3);
        }
        
        .btn1-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn1-success {
            background: var(--success);
            color: white;
        }
        
        .btn1-secondary {
            background: var(--dark);
            color: white;
        }
        
        .language-info {
            background: #e8f4ff;
            border-left: 4px solid #1e88e5;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            font-size: 15px;
        }
        
      
        
        /* Responsive */
        @media (max-width: 768px) {
            .tabs {
                flex-direction: column;
            }
            
           
            .logo {
                justify-content: center;
            }
            
            .settings-title {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
<?php include("header.php"); ?>
<div class="home-content">
    <div class="container">

        
        <div class="admin-content">
            <div class="settings-title">
                <i>⚙️</i>
                <h2><?php echo $t['settings_title']; ?></h2>
            </div>
            
            <div class="tabs">
                <button class="tab-btn1 active" data-tab="general"><?php echo $t['general_settings']; ?></button>
                <!--<button class="tab-btn1" data-tab="security"></button>-->
                <button class="tab-btn1" data-tab="langue"><?php echo $t['language_settings']; ?></button>
                <button class="tab-btn1" data-tab="avance"><?php echo $t['advanced_settings']; ?></button>
            </div>
            
            <!-- 🔥 Onglet Général -->
            <div class="tab-content active" id="general">
                <div class="form-container">
                    <form id="generalForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="site_name"><?php echo $t['site_name']; ?></label>
                            <input type="text" name="site_name" value="<?= htmlspecialchars($config['site_name']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="email_support"><?php echo $t['email_support']; ?></label>
                            <input type="email" name="email_support" value="<?= htmlspecialchars($config['email_support']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="logo"><?php echo $t['logo']; ?></label>
                            <img src="<?= htmlspecialchars($config['logo']) ?>" id="logoPreview"><br>
                            <input type="file" name="logo">
                        </div>
                        
                        <button type="submit" class="btn11 btn1-primary">💾 <?php echo $t['save']; ?></button>
                    </form>
                </div>
            </div>
            
            <!-- 🔥 Onglet Sécurité - Gestion des administrateurs -->
<div class="tab-content" id="security">
    <div class="form-container">
        <h3>👮 Gestion des administrateurs</h3>

        <!-- Formulaire d'ajout -->
        <form method="POST" action="admin_action.php" style="margin-bottom: 30px;">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Nom</label>
                <input type="text" name="nom" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="mot_de_passe" required>
            </div>
            <button type="submit" class="btn11 btn1-success">➕ Ajouter un admin</button>
        </form>

        <!-- Liste des admins -->
        <table style="width:100%; border-collapse: collapse; font-size: 15px;">
            <thead>
                <tr style="background: var(--primary); color: white;">
                    <th style="padding: 10px;">Nom</th>
                    <th>Email</th>
                    <th>Adresse</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $admins = $conn->query("SELECT * FROM admin");
                while ($admin = $admins->fetch_assoc()) {
                    echo "<tr style='border-bottom: 1px solid var(--border);'>";
                    echo "<td style='padding: 8px;'>" . htmlspecialchars($admin['nom']) . "</td>";
                    echo "<td>" . htmlspecialchars($admin['email']) . "</td>";
                    echo "<td>" . htmlspecialchars($admin['adresse']) . "</td>";
                    echo "<td>
                            <form method='POST' action='admin_action.php' style='display:inline-block;'>
                                <input type='hidden' name='action' value='delete'>
                                <input type='hidden' name='id' value='{$admin['id']}'>
                                <button type='submit' class='btn11 btn1-danger' onclick='return confirm(\"Supprimer cet admin ?\")'>🗑️ Supprimer</button>
                            </form>
                            <form method='POST' action='admin_modifier.php' style='display:inline-block; margin-left: 5px;'>
                                <input type='hidden' name='id' value='{$admin['id']}'>
                                <button type='submit' class='btn11 btn1-secondary'>✏️ Modifier</button>
                            </form>
                          </td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

            
            <!-- 🔥 Onglet Langue -->
            <div class="tab-content" id="langue">
                <div class="form-container">
                    <form id="langueForm">
                        <div class="form-group">
                            <label for="default_language"><?php echo $t['default_language']; ?></label>
                            <select name="default_language">
                                <option value="fr" <?= $config['default_language']=='fr'?'selected':'' ?>><?php echo $t['french']; ?></option>
                                <option value="ar" <?= $config['default_language']=='ar'?'selected':'' ?>><?php echo $t['arabic']; ?></option>
                                <option value="en" <?= $config['default_language']=='en'?'selected':'' ?>><?php echo $t['english']; ?></option>
                            </select>
                        </div>
                        <button type="submit" class="btn11 btn1-success">🌐 <?php echo $t['save']; ?></button>
                    </form>
                    
                    <div class="language-info">
                        <p><?php echo $t['language_help']; ?></p>
                    </div>
                </div>
            </div>
            
            <!-- 🔥 Onglet Avancé -->
            <div class="tab-content" id="avance">
                <div class="form-container">
                    <form id="avanceForm">
                        <h3>⚙️ Paramètres avancés</h3>
                        <div>
                            <label>🚧 Mode maintenance</label><br>
                            <select name="maintenance_mode">
                                <option value="on" <?= ($config['maintenance_mode'] == 'on') ? 'selected' : '' ?>>Activé</option>
                                <option value="off" <?= ($config['maintenance_mode'] == 'off') ? 'selected' : '' ?>>Désactivé</option>
                            </select>
                        </div>
                        <br>
                        <div>
                            <label>📄 Produits par page (pagination)</label><br>
                            <input type="number" name="pagination_limit" value="<?= htmlspecialchars($config['pagination_limit'] ?? 10) ?>">
                        </div>
                        <br>
                        <div>
                            <label>🔑 Clé API (facultatif)</label><br>
                            <input type="text" name="api_key" value="<?= htmlspecialchars($config['api_key'] ?? '') ?>">
                        </div>

                        <button type="submit" class="btn11 btn1-secondary">⚙️ <?php echo $t['save']; ?></button>
                    </form>
                </div>
            </div>
        </div>
        
       
    </div>

    <script>
        // Tab switching functionality
        document.querySelectorAll('.tab-btn1').forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all buttons and content
                document.querySelectorAll('.tab-btn1').forEach(btn1 => btn1.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                
                // Add active class to clicked button
                button.classList.add('active');
                
                // Show corresponding content
                const targetId = button.getAttribute('data-tab');
                document.getElementById(targetId).classList.add('active');
            });
        });
        
        // Form submission handling
        function saveForm(formId) {
            const form = document.getElementById(formId);
            const formData = new FormData(form);
            
            fetch('save_config.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(data => {
                alert(data);
                // For logo preview update
                if(formId === 'generalForm') {
                    const fileInput = form.querySelector('input[type="file"]');
                    if(fileInput.files.length > 0) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('logoPreview').src = e.target.result;
                        }
                        reader.readAsDataURL(fileInput.files[0]);
                    }
                }
            })
            .catch(err => alert('Erreur : ' + err));
        }
        
        document.getElementById('generalForm').onsubmit = e => { e.preventDefault(); saveForm('generalForm'); }
        document.getElementById('securityForm').onsubmit = e => { e.preventDefault(); saveForm('securityForm'); }
        document.getElementById('langueForm').onsubmit = e => { e.preventDefault(); saveForm('langueForm'); }
        document.getElementById('avanceForm').onsubmit = e => { e.preventDefault(); saveForm('avanceForm'); }
        
        // Logo preview update
        document.querySelector('#generalForm input[type="file"]').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('logoPreview').src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
        
        // Set document direction based on language
        document.documentElement.dir = "<?php echo ($current_lang == 'ar') ? 'ltr' : 'ltr'; ?>";
    </script>
</body>
</html>