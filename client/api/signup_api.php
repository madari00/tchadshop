<?php
require_once 'init.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Vérifier si la connexion existe déjà
if (!isset($conn)) {
    $conn = new mysqli("localhost", "root", "", "tchadshop_db");
    if ($conn->connect_error) die(json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']));
}

// Traitement de l'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $conn->real_escape_string($_POST['nom']);
    $email = $conn->real_escape_string($_POST['email']);
    $telephone = $conn->real_escape_string($_POST['telephone']);
    $adresse = $conn->real_escape_string($_POST['adresse']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Vérifier si le téléphone existe déjà
    $check_sql = "SELECT id, invite FROM clients WHERE telephone = '$telephone'";
    $result = $conn->query($check_sql);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Si le compte est un compte invité, on met à jour les informations
        if ($user['invite'] == 1) {
            $update_sql = "UPDATE clients SET 
                            nom = '$nom', 
                            email = '$email', 
                            adresse = '$adresse', 
                            password = '$password', 
                            invite = 0 
                            WHERE id = " . $user['id'];
            
            if ($conn->query($update_sql)) {
                echo json_encode(['success' => true, 'message' => 'Compte créé avec succès']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du compte: ' . $conn->error]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Ce numéro de téléphone est déjà enregistré']);
        }
    } else {
        // Insérer le nouvel utilisateur
        $sql = "INSERT INTO clients (nom, email, telephone, adresse, password, invite, vu) 
                VALUES ('$nom', '$email', '$telephone', '$adresse', '$password', 0, 0)";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Compte créé avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la création du compte: ' . $conn->error]);
        }
    }
}
?>