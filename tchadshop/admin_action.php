<?php
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'add') {
        $nom = $_POST['nom'];
        $email = $_POST['email'];
        $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_BCRYPT);

        $stmt = $conn->prepare("INSERT INTO admin (nom, email, mot_de_passe) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nom, $email, $mot_de_passe);
        $stmt->execute();
        header("Location: configuration.php#security");
        exit;
    }

    if ($action === 'delete' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM admin WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: configuration.php#security");
        exit;
    }
}
?>
