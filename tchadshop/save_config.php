<?php
session_start();
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}


foreach ($_POST as $key => $value) {
    $stmt = $conn->prepare("UPDATE configuration SET valeur=? WHERE parametre=?");
    $stmt->bind_param("ss", $value, $key);
    $stmt->execute();
}

if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
    $filename = uniqid() . "_" . basename($_FILES['logo']['name']);
    $target = "" . $filename;
    move_uploaded_file($_FILES['logo']['tmp_name'], $target);
    $stmt = $conn->prepare("UPDATE configuration SET valeur=? WHERE parametre='logo'");
    $stmt->bind_param("s", $filename);
    $stmt->execute();
}

echo "✅ Modifications enregistrées avec succès.";
