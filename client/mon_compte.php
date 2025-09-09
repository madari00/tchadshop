<?php
require_once 'init.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure le fichier de traduction approprié


// Vérifier si la connexion existe déjà (peut-être déjà établie par header.php)
if (!isset($conn)) {
    $conn = new mysqli("localhost", "root", "", "tchadshop_db");
    if ($conn->connect_error) die($trans['connection_error'] . ": " . $conn->connect_error);
}

// Vérifier si client connecté
if (!isset($_SESSION['client_id'])) {
    echo "<div class='container'><div class='card'><p style='text-align:center; padding:20px;'>⚠️ " . $trans['must_be_logged_in'] . "</p><p style='text-align:center;'><a href='login.php' class='btn'>🔑 " . $trans['login'] . "</a></p></div></div>";
    exit;
}

$client_id = $_SESSION['client_id'];

// Récupérer infos client
$stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();
$stmt->close();

$message = "";

// Mise à jour infos personnelles
if (isset($_POST['update_info'])) {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $adresse = trim($_POST['adresse']);

    $stmt = $conn->prepare("UPDATE clients SET nom=?, email=?, telephone=?, adresse=? WHERE id=?");
    $stmt->bind_param("ssssi", $nom, $email, $telephone, $adresse, $client_id);
    if ($stmt->execute()) {
        $message = "<div class='alert success'>✅ " . $trans['info_updated_success'] . "</div>";
    } else {
        $message = "<div class='alert error'>❌ " . $trans['update_error'] . "</div>";
    }
    $stmt->close();
}

// Changer mot de passe (si pas invité)
if (isset($_POST['update_password']) && $client['invite'] == 0) {
    $pass1 = $_POST['password'];
    $pass2 = $_POST['password_confirm'];

    if (!empty($pass1) && $pass1 === $pass2) {
        $hash = password_hash($pass1, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE clients SET password=? WHERE id=?");
        $stmt->bind_param("si", $hash, $client_id);
        if ($stmt->execute()) {
            $message = "<div class='alert success'>✅ " . $trans['password_changed_success'] . "</div>";
        } else {
            $message = "<div class='alert error'>❌ " . $trans['password_change_error'] . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert warning'>⚠️ " . $trans['password_mismatch_or_empty'] . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mon Compte - TchadShop</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #8e24aa;
    --primary-light: #8e24aa;
    --secondary: #fefee3;
    --accent: #ff6b6b;
    --dark: #8e24aa;
    --light: #f8f9fa;
    --gray: #6c757d;
    --success: #28a745;
    --warning: #ffc107;
    --danger: #dc3545;
    --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    --transition: all 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #f5f7fa 0%, #e4e7f1 100%);
    color: #333;
    line-height: 1.6;
    min-height: 100vh;
  
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.header1 {
    text-align: center;
    margin-bottom: 40px;
    animation: fadeInDown 0.6s ease;
}

.header1 h1 {
    font-family: 'Montserrat', sans-serif;
    font-size: 2.5rem;
    color: var(--dark);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.header1 p {
    color: var(--gray);
    font-size: 1.1rem;
    max-width: 600px;
    margin: 0 auto;
}

.dashboard {
    display: grid;
    grid-template-columns: 1fr;
    gap: 30px;
}

@media (min-width: 992px) {
    .dashboard {
        grid-template-columns: 300px 1fr;
    }
}

.sidebar {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: var(--card-shadow);
    height: fit-content;
    animation: slideInLeft 0.5s ease;
}

.profile-card {
    text-align: center;
}

.profile-icon {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: white;
    font-size: 2.5rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.profile-card h2 {
    font-size: 1.5rem;
    margin-bottom: 5px;
    color: var(--dark);
}

.profile-card p {
    color: var(--gray);
    margin-bottom: 20px;
}

.account-info {
    margin-top: 30px;
}

.info-item {
    display: flex;
    margin-bottom: 15px;
    padding: 12px 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.info-item i {
    color: var(--primary);
    font-size: 1.2rem;
    min-width: 30px;
}

.info-item span {
    flex: 1;
    color: var(--gray);
}

.guest-badge {
    background: var(--warning);
    color: #000;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
    display: inline-block;
    margin-top: 10px;
}

.content {
    animation: fadeIn 0.8s ease;
}

.card {
    background: white;
    border-radius: 16px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: var(--card-shadow);
    transition: var(--transition);
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
}

.card h2 {
    font-size: 1.6rem;
    color: var(--dark);
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--primary-light);
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

@media (min-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr 1fr;
    }
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-group label i {
    color: var(--primary);
}

.form-control {
    width: 100%;
    padding: 14px 18px;
    border: 2px solid #e0e6ed;
    border-radius: 12px;
    font-size: 1rem;
    font-family: 'Poppins', sans-serif;
    transition: var(--transition);
    background: #f8fafc;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    background: white;
    box-shadow: 0 0 0 4px rgba(44, 110, 73, 0.15);
}

textarea.form-control {
    min-height: 120px;
    resize: vertical;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 28px;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    border: none;
    font-family: 'Poppins', sans-serif;
}

.btn-primary {
    background: linear-gradient(to right, var(--primary) 0%, var(--primary-light) 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(44, 110, 73, 0.3);
}

.btn-primary:hover {
    background: linear-gradient(to right, #23583a 0%, #3d7c58 100%);
    transform: translateY(-2px);
    box-shadow: 0 7px 20px rgba(44, 110, 73, 0.4);
}

.btn-secondary {
    background: white;
    color: var(--primary);
    border: 2px solid var(--primary);
}

.btn-secondary:hover {
    background: var(--primary-light);
    color: white;
}

.alert {
    padding: 16px;
    border-radius: 12px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: fadeIn 0.5s ease;
}

.alert i {
    font-size: 1.4rem;
}

.alert.success {
    background: rgba(40, 167, 69, 0.15);
    color: #155724;
    border-left: 4px solid var(--success);
}

.alert.error {
    background: rgba(220, 53, 69, 0.15);
    color: #721c24;
    border-left: 4px solid var(--danger);
}

.alert.warning {
    background: rgba(255, 193, 7, 0.15);
    color: #856404;
    border-left: 4px solid var(--warning);
}

.password-info {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    margin-top: 20px;
    border-left: 4px solid var(--primary);
}

.password-info h4 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    color: var(--dark);
}

.password-info ul {
    padding-left: 20px;
    color: var(--gray);
}

.password-info li {
    margin-bottom: 8px;
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .header1 h1 {
        font-size: 2rem;
    }
    
    .dashboard {
        grid-template-columns: 1fr;
    }
    
    .sidebar {
        margin-bottom: 30px;
    }
}

@media (max-width: 480px) {
    .header1 h1 {
        font-size: 1.8rem;
        margin-top: 100px;
    }
    
    .card {
        padding: 20px;
    }
    
    .btn {
        width: 100%;
        padding: 16px;
    }
}
</style>
</head>
<body>
    <?php include("header.php"); ?>
   <div class="container">
    <div class="header1">
        <h1><i class="fas fa-user-circle"></i> <?php echo $trans['my_account']; ?></h1>
        <p><?php echo $trans['manage_personal_info']; ?></p>
    </div>
    
    <?php if (!empty($message)) echo $message; ?>
    
    <div class="dashboard">
        <div class="sidebar">
            <div class="profile-card">
                <div class="profile-icon">
                    <i class="fas fa-user"></i>
                </div>
                <h2><?= htmlspecialchars($client['nom']) ?></h2>
                <p><?= htmlspecialchars($client['email']) ?></p>
                
                <?php if ($client['invite'] == 1): ?>
                    <span class="guest-badge"><?php echo $trans['guest_account']; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="account-info">
                <div class="info-item">
                    <i class="fas fa-id-card"></i>
                    <span><?php echo $trans['client_id']; ?>: #<?= $client_id ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-phone"></i>
                    <span><?= htmlspecialchars($client['telephone'] ?? $trans['not_provided']) ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?= htmlspecialchars($client['adresse'] ?? $trans['address_not_provided']) ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?php echo $trans['member_since']; ?>: <?= date('d/m/Y', strtotime($client['created_at'])) ?></span>
                </div>
            </div>
        </div>
        
        <div class="content">
            <div class="card">
                <h2><i class="fas fa-user-edit"></i> <?php echo $trans['personal_info']; ?></h2>
                <form method="post">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nom"><i class="fas fa-signature"></i> <?php echo $trans['full_name']; ?></label>
                            <input type="text" id="nom" name="nom" class="form-control" 
                                   value="<?= htmlspecialchars($client['nom']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> <?php echo $trans['email_address']; ?></label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?= htmlspecialchars($client['email']) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="telephone"><i class="fas fa-phone"></i> <?php echo $trans['phone_number']; ?></label>
                            <input type="text" id="telephone" name="telephone" class="form-control" 
                                   value="<?= htmlspecialchars($client['telephone']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="adresse"><i class="fas fa-map-marker-alt"></i> <?php echo $trans['address']; ?></label>
                            <textarea id="adresse" name="adresse" class="form-control"><?= htmlspecialchars($client['adresse'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" name="update_info" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $trans['update_info']; ?>
                    </button>
                </form>
            </div>
            
            <?php if ($client['invite'] == 0): ?>
            <div class="card">
                <h2><i class="fas fa-lock"></i> <?php echo $trans['account_security']; ?></h2>
                <form method="post">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="password"><i class="fas fa-key"></i> <?php echo $trans['new_password']; ?></label>
                            <input type="password" id="password" name="password" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="password_confirm"><i class="fas fa-key"></i> <?php echo $trans['confirm_password']; ?></label>
                            <input type="password" id="password_confirm" name="password_confirm" class="form-control">
                        </div>
                    </div>
                    
                    <div class="password-info">
                        <h4><i class="fas fa-info-circle"></i> <?php echo $trans['password_tips']; ?></h4>
                        <ul>
                            <li><?php echo $trans['password_tip1']; ?></li>
                            <li><?php echo $trans['password_tip2']; ?></li>
                            <li><?php echo $trans['password_tip3']; ?></li>
                        </ul>
                    </div>
                    
                    <button type="submit" name="update_password" class="btn btn-primary">
                        <i class="fas fa-sync-alt"></i> <?php echo $trans['change_password']; ?>
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="card">
                <h2><i class="fas fa-lock"></i> <?php echo $trans['account_security']; ?></h2>
                <div class="alert warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong><?php echo $trans['guest_warning_title']; ?></strong>
                        <p><?php echo $trans['guest_warning_message']; ?></p>
                    </div>
                </div>
                <a href="register.php" class="btn btn-secondary">
                    <i class="fas fa-user-plus"></i> <?php echo $trans['create_full_account']; ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation des champs de formulaire au focus
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'translateY(-3px)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'none';
        });
    });
    
    // Validation du mot de passe
    const passwordForm = document.querySelector('form[name="update_password"]');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const pass1 = document.getElementById('password').value;
            const pass2 = document.getElementById('password_confirm').value;
            
            if (pass1 !== pass2) {
                e.preventDefault();
                alert('<?php echo $trans["password_mismatch"]; ?>');
            }
        });
    }
});
</script>
<?php include("footer.php"); ?>
</body>
</html>