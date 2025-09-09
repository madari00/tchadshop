<?php
session_start();
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];

    if (in_array($lang, ['fr', 'ar', 'en'])) {
    $_SESSION['lang'] = $lang;
    setcookie("lang", $lang, time() + (3600*24*30), "/");
    
    if (isset($_SESSION['client_id'])) {
        $stmt = $conn->prepare("UPDATE clients SET langue = ? WHERE id = ?");
        $stmt->bind_param("si", $lang, $_SESSION['client_id']);
        $stmt->execute();
        $stmt->close();
    }
}

}

// Retour à la page précédente
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
