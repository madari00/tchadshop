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

// Traitement de la connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telephone = $conn->real_escape_string($_POST['login_telephone']);
    $password = $_POST['login_password'];
    
    $sql = "SELECT * FROM clients WHERE telephone = '$telephone' AND invite = 0";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Connexion réussie
            $_SESSION['client_id'] = $user['id'];
            $_SESSION['client_nom'] = $user['nom'];
            
            // Mettre à jour le statut vu si nécessaire
            if ($user['vu'] == 0) {
                $update_sql = "UPDATE clients SET vu = 1 WHERE id = " . $user['id'];
                $conn->query($update_sql);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Connexion réussie',
                'user' => [
                    'id' => $user['id'],
                    'nom' => $user['nom'],
                    'email' => $user['email'],
                    'telephone' => $user['telephone']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Mot de passe incorrect']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Aucun compte trouvé avec ce numéro de téléphone']);
    }
}
?>