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

// Détruire toutes les variables de session
$_SESSION = array();

// Si vous voulez détruire complètement la session, effacez également le cookie de session.
// Note : cela détruira la session, pas seulement les données de la session.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalement, on détruit la session.
session_destroy();

// Redirection vers la page d'accueil ou la page de connexion avec un message
header("Location: index.php?logout=success");
exit;
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Déconnexion - TchadShop</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f9f7ff 0%, #eef2ff 100%);
            color: #333;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            padding: 40px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }
        h1 {
            color: #f44336; 
            margin-bottom: 20px;
        }
        p {
            font-size: 1.1rem;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Déconnexion en cours...</h1>
        <p>Vous allez être redirigé vers la page d'accueil.</p>
    </div>
</body>
</html>