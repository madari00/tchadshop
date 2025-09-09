<?php
// init.php
// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connexion à la base de données
require_once 'db_connect.php';

// GESTION DU CHANGEMENT DE LANGUE
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    // Valider que la langue est supportée
    $allowed_langs = ['fr', 'en', 'ar'];
    if (in_array($lang, $allowed_langs)) {
        $_SESSION['lang'] = $lang;
        // Sauvegarder aussi dans un cookie (1 an)
        setcookie('lang', $lang, time() + (365 * 24 * 60 * 60), '/');
        
        // Rediriger pour éviter la resoumission du formulaire
        $url = strtok($_SERVER["REQUEST_URI"], '?'); // Enlever les paramètres existants
        header("Location: $url");
        exit();
    }
}

// Variables pour vérifier si un client est connecté
$isClient = isset($_SESSION['client_id']);
// On récupère le nom du client s'il est connecté, sinon on utilise une chaîne vide
$clientName = $isClient ? htmlspecialchars($_SESSION['client_nom']) : '';

// Détection langue (après traitement possible du paramètre lang)
if (isset($_SESSION['lang'])) {
    $lang = $_SESSION['lang'];
} elseif (isset($_COOKIE['lang'])) {
    $lang = $_COOKIE['lang'];
    $_SESSION['lang'] = $lang; // synchroniser avec la session
} elseif (isset($_SESSION['client_id'])) {
    // Si client connecté -> récupérer depuis la DB
    $stmt = $conn->prepare("SELECT langue FROM clients WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['client_id']);
    $stmt->execute();
    $stmt->bind_result($langDb);
    $stmt->fetch();
    $stmt->close();
    $lang = $langDb ?: 'fr';
    $_SESSION['lang'] = $lang; // sauvegarde en session
} else {
    // Sinon on prend la langue par défaut du site
    $res = $conn->query("SELECT valeur FROM configuration WHERE parametre='default_language'");
    $row = $res->fetch_assoc();
    $lang = $row ? $row['valeur'] : 'fr';
    $_SESSION['lang'] = $lang; // sauvegarde en session
}

// Charger fichier traduction
$langFile = __DIR__ . "/lang/$lang.php";
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/lang/fr.php";
}

// Charger les traductions avec valeurs par défaut
$defaultTrans = [
    'choose_language' => 'Choose Language',
    'search_placeholder' => 'Search products...',
    'home' => 'Home',
    'products' => 'Products',
    'promotions' => 'Promotions',
    'my_orders' => 'My Orders',
    'my_account' => 'My Account',
    'history' => 'History',
    'messages' => 'Messages',
    'contact' => 'Contact',
    'language' => 'Language',
    'french' => 'French',
    'english' => 'English',
    'arabic' => 'Arabic',
    'login' => 'Login',
    'logout' => 'Logout',
    'voice_search' => 'Voice Search',
    'speak_to_search' => 'Speak to search...',
    'voice_result' => 'Listening...',
    'stop' => 'Stop',
    'close' => 'Close'
];

$trans = include($langFile);
// Fusionner avec les valeurs par défaut pour éviter les erreurs de clés manquantes
$trans = array_merge($defaultTrans, $trans);

// Définir la direction du texte selon la langue - correction pour l'arabe
$dir = ($lang == 'ar') ? 'ltr' : 'ltr';

// Déterminer le nom de la langue courante pour l'affichage
$currentLanguageName = '';
switch($lang) {
    case 'fr': $currentLanguageName = $trans['french']; break;
    case 'en': $currentLanguageName = $trans['english']; break;
    case 'ar': $currentLanguageName = $trans['arabic']; break;
    default: $currentLanguageName = $trans['french'];
}
?>