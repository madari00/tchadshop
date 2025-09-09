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

// Initialisation des erreurs
$errors = [];

// Récupération du téléphone selon le statut de connexion
if (isset($_SESSION['client_id'])) {
    // CLIENT CONNECTÉ : Récupérer le téléphone depuis la base
    $stmt = $conn->prepare("SELECT telephone FROM clients WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['client_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $telephone = $res->fetch_assoc()['telephone'];
    } else {
        $errors[] = "Session client invalide";
    }
} else {
    // CLIENT NON CONNECTÉ : Utiliser le téléphone du formulaire
    $telephone = trim($_POST['telephone'] ?? '');
    if (empty($telephone) || !preg_match('/^\d{6,}$/', $telephone)) {
        $errors[] = "Numéro de téléphone invalide";
    }
}

// Validation commune
$produit_id = (int)($_POST['produit_id'] ?? 0);
if ($produit_id <= 0) {
    $errors[] = "Produit invalide";
}

$quantite = (int)($_POST['quantite'] ?? 0);
if ($quantite <= 0) {
    $errors[] = "Quantité invalide";
}

$prix_unitaire = (float)($_POST['prix_unitaire'] ?? 0);
if ($prix_unitaire <= 0) {
    $errors[] = "Prix unitaire invalide";
}

$promotion = (int)($_POST['promotion'] ?? 0);
$date_livraison_prevue = $_POST['date_livraison_prevue'] ?? null;
$latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
$longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;

// Calcul du total
$total = $prix_unitaire * $quantite;

// Si erreurs, rediriger avec message
if (!empty($errors)) {
    $_SESSION['commande_errors'] = $errors;
    header("Location: produits.php");
    exit;
}

// Vérifier client existant (pour non connectés) ou récupérer ID (pour connectés)
if (isset($_SESSION['client_id'])) {
    $client_id = $_SESSION['client_id'];
} else {
    $sql = "SELECT id FROM clients WHERE telephone = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $telephone);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $client_id = $res->fetch_assoc()['id'];
    } else {
        $sql = "INSERT INTO clients (nom, telephone, invite) VALUES (?, ?, 1)";
        $stmt = $conn->prepare($sql);
        $nom = "Client $telephone";
        $stmt->bind_param("ss", $nom, $telephone);
        $stmt->execute();
        $client_id = $conn->insert_id;
    }
}

// Insérer commande avec date de livraison
$sql = "INSERT INTO commandes (client_id, total, latitude, longitude, date_livraison_prevue) 
        VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iddds", $client_id, $total, $latitude, $longitude, $date_livraison_prevue);
if (!$stmt->execute()) {
    $errors[] = "Erreur lors de la création de la commande: " . $stmt->error;
}

// Récupérer l'ID de la nouvelle commande
$commande_id = $conn->insert_id;

// Insérer détail avec information de promotion
$sql = "INSERT INTO details_commandes (commande_id, produit_id, quantite, prix_unitaire, promotion) 
        VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiidi", $commande_id, $produit_id, $quantite, $prix_unitaire, $promotion);
if (!$stmt->execute()) {
    $errors[] = "Erreur lors de l'ajout du détail: " . $stmt->error;
}

// Mettre à jour le stock du produit seulement si la commande est validée
if (empty($errors)) {
    $sql = "UPDATE produits SET stock = stock - ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $quantite, $produit_id);
    if (!$stmt->execute()) {
        $errors[] = "Erreur de mise à jour du stock: " . $stmt->error;
    }
}

// Gestion finale des erreurs ou redirection
if (!empty($errors)) {
    $_SESSION['commande_errors'] = $errors;
    header("Location: produits.php");
    exit;
} else {
    // Rediriger vers confirmation
    header("Location: confirmation_commande.php?id=$commande_id");
    exit;
}
?>