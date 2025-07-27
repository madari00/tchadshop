<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die(json_encode(['trouve' => false, 'error' => 'Erreur connexion DB']));
}

if (!isset($_GET['telephone']) || empty($_GET['telephone'])) {
    echo json_encode(['trouve' => false, 'error' => 'Téléphone manquant']);
    exit();
}

$telephone = $conn->real_escape_string($_GET['telephone']);
$result = $conn->query("SELECT id, nom FROM clients WHERE telephone LIKE '$telephone%' LIMIT 1");

if ($client = $result->fetch_assoc()) {
    echo json_encode([
        'trouve' => true,
        'id' => $client['id'],
        'nom' => $client['nom']
    ]);
} else {
    echo json_encode(['trouve' => false]);
}
?>
