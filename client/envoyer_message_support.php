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
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['client_id'])) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
    exit;
}

// Récupérer les données du formulaire
$client_id = $_POST['client_id'];
$sujet = $_POST['sujet'] ?? 'Support audio';
$message = $_POST['message'] ?? '';
$commande_id = $_POST['commande_id'] ?? 0;

// Vérifier si un fichier audio a été envoyé
$audio_path = null;
if (isset($_FILES['audio']) && $_FILES['audio']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/audio_support/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $filename = uniqid('audio_') . '.webm';
    $audio_path = $uploadDir . $filename;
    
    if (move_uploaded_file($_FILES['audio']['tmp_name'], $audio_path)) {
        // Succès de l'upload
    } else {
        $audio_path = null;
    }
}

// Si aucun audio n'a été uploadé, vérifier si un fichier a été sélectionné
if (!$audio_path && isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/audio_support/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $filename = uniqid('audio_') . '_' . basename($_FILES['audio_file']['name']);
    $audio_path = $uploadDir . $filename;
    
    if (move_uploaded_file($_FILES['audio_file']['tmp_name'], $audio_path)) {
        // Succès de l'upload
    } else {
        $audio_path = null;
    }
}

// Vérifier qu'au moins un audio a été fourni
if (!$audio_path) {
    echo json_encode(['success' => false, 'error' => 'Aucun enregistrement audio fourni']);
    exit;
}

// Ajouter la référence de commande au message
if ($commande_id) {
    $message = ($message ? $message . "\n\n" : "") . "[Commande #$commande_id]";
}

// Insérer le message dans la base de données
$sql = "INSERT INTO messages_clients 
        (client_id, sujet, message, audio_path, created_at) 
        VALUES (?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("isss", $client_id, $sujet, $message, $audio_path);
    $success = $stmt->execute();
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}

$stmt->close();
$conn->close();
exit;
?>