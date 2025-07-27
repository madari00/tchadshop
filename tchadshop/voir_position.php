<?php
session_start();
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID commande invalide !");
}

// Récupérer la commande avec latitude & longitude
$sql = "SELECT latitude, longitude FROM commandes WHERE id = $id";
$res = $conn->query($sql);
if (!$res || $res->num_rows == 0) {
    die("Commande non trouvée !");
}

$commande = $res->fetch_assoc();
$lat = $commande['latitude'];
$lng = $commande['longitude'];

if (!$lat || !$lng) {
    die("Pas de position GPS disponible pour cette commande.");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Voir la position de la commande #<?= $id ?></title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        #map { height: 500px; width: 100%; }
        body { font-family: Arial, sans-serif; margin: 20px; }
    </style>
</head>
<body>
    <h2>Position de la commande #<?= $id ?></h2>
    <div id="map"></div>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        // Initialisation de la carte
        var map = L.map('map').setView([<?= $lat ?>, <?= $lng ?>], 15);

        // Ajouter les tuiles OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Ajouter un marqueur à la position
        L.marker([<?= $lat ?>, <?= $lng ?>]).addTo(map)
            .bindPopup('Position de la commande #<?= $id ?>')
            .openPopup();
    </script>

    <br>
    <a href="toutes_commandes.php">⬅ Retour aux commandes</a>
</body>
</html>
