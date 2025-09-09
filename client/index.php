<?php
require_once 'init.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('header.php');
// Vérifier si la connexion existe déjà (peut-être déjà établie par header.php)
if (!isset($conn)) {
    $conn = new mysqli("localhost", "root", "", "tchadshop_db");
    if ($conn->connect_error) die("Erreur de connexion : " . $conn->connect_error);
}

// Déterminer la locale JavaScript appropriée
$js_locale = 'fr-FR'; // par défaut
if ($lang == 'en') {
    $js_locale = 'en-US';
} elseif ($lang == 'ar') {
    $js_locale = 'ar-SA';
}

$isConnected = isset($_SESSION['client_id']);
$telephone_connecte = '';

// Traitement de la soumission d'un nouvel avis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_avis'])) {
    if ($isConnected) {
        $client_id = $_SESSION['client_id'];
        $note = intval($_POST['note']);
        $commentaire = $conn->real_escape_string(trim($_POST['commentaire']));
        
        // Récupérer le nom du client
        $res = $conn->query("SELECT nom, prenom FROM clients WHERE id = $client_id LIMIT 1");
        $client = $res->fetch_assoc();
        $nom_client = $conn->real_escape_string($client['prenom'] . ' ' . $client['nom']);
        
        // Insérer l'avis dans la base de données
        $sql = "INSERT INTO avis_clients (client_id, nom_client, note, commentaire, approuve) 
                VALUES ($client_id, '$nom_client', $note, '$commentaire', 1)";
        
        if ($conn->query($sql)) {
            $success_message = "Merci pour votre avis ! Il sera publié après modération.";
        } else {
            $error_message = "Une erreur s'est produite. Veuillez réessayer.";
        }
    } else {
        $error_message = "Veuillez vous connecter pour laisser un avis.";
    }
}

// Récupération des avis approuvés
$sql_avis = "SELECT * FROM avis_clients WHERE approuve = 1 ORDER BY date_creation DESC LIMIT 3";
$result_avis = $conn->query($sql_avis);
$avis = [];
if ($result_avis) {
    while ($row = $result_avis->fetch_assoc()) {
        $avis[] = $row;
    }
}
// Fonction pour récupérer les images d'un produit
function getProduitImages($produit_id, $conn) {
    $sql = "SELECT image FROM images_produit WHERE produit_id = $produit_id ORDER BY id ASC";
    $result = $conn->query($sql);
    $images = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $images[] = $row['image'];
        }
    }
    // Image par défaut si aucune image trouvée
    if (empty($images)) {
        $images[] = "https://via.placeholder.com/400x300?text=TchadShop";
    }
    return $images;
}

// Récupération des produits en promotion pour le slider
$aujourdhui = date('Y-m-d');
$sql_promotions = "SELECT * FROM produits 
                  WHERE promotion > 0 
                  AND date_debut_promo <= '$aujourdhui' 
                  AND date_fin_promo >= '$aujourdhui' 
                  ORDER BY created_at DESC 
                  LIMIT 5";
$result_promotions = $conn->query($sql_promotions);
$promotions = [];
if ($result_promotions) {
    while ($row = $result_promotions->fetch_assoc()) {
        $row['images'] = getProduitImages($row['id'], $conn);
        $promotions[] = $row;
    }
}

// Si pas de promotions, on prend les derniers produits disponibles
if (empty($promotions)) {
    $sql_promotions = "SELECT * FROM produits 
                      WHERE statut = 'disponible' 
                      ORDER BY created_at DESC 
                      LIMIT 5";
    $result_promotions = $conn->query($sql_promotions);
    if ($result_promotions) {
        while ($row = $result_promotions->fetch_assoc()) {
            $row['images'] = getProduitImages($row['id'], $conn);
            $promotions[] = $row;
        }
    }
}

// Récupération des derniers produits disponibles
$sql_produits = "SELECT * FROM produits 
                WHERE statut = 'disponible' 
                ORDER BY created_at DESC 
                LIMIT 4";
$result_produits = $conn->query($sql_produits);
$produits = [];
if ($result_produits) {
    while ($row = $result_produits->fetch_assoc()) {
        $row['images'] = getProduitImages($row['id'], $conn);
        $produits[] = $row;
    }
}

// Fonction pour formater le prix
function formatPrix($prix) {
    return number_format($prix, 0, ',', ' ') . ' FCFA';
}

// Fonction pour calculer le prix promotionnel
function calculerPrixPromotion($prix, $promotion) {
    if ($promotion > 0) {
        return $prix * (1 - $promotion/100);
    }
    return $prix;
}

// Fonction pour formater une date en français
function formaterDate($date,$trans) {
    $mois = [
        'January' => $trans['january'],
        'February' => $trans['february'],
        'March' => $trans['march'],
        'April' => $trans['april'],
        'May' => $trans['may'],
        'June' => $trans['june'],
        'July' => $trans['july'],
        'August' => $trans['august'],
        'September' => $trans['september'],
        'October' => $trans['october'],
        'November' => $trans['november'],
        'December' => $trans['december']
    ];
    
    $dateObj = new DateTime($date);
    $moisNom = $mois[$dateObj->format('F')];
    return $dateObj->format('j') . ' ' . $moisNom . ' ' . $dateObj->format('Y');
}

// Variables pour la commande
$telephone_connecte = '';
if ($isConnected) {
    $client_id = $_SESSION['client_id'];
    $res = $conn->query("SELECT telephone FROM clients WHERE id = $client_id LIMIT 1");
    $telephone_connecte = $res->fetch_assoc()['telephone'] ?? '';

}
// Calculer la date de fin de la prochaine promotion à expirer
$sql_prochaine_fin = "SELECT MIN(date_fin_promo) AS prochaine_fin_promo 
                      FROM produits 
                      WHERE promotion > 0 
                      AND date_debut_promo <= '$aujourdhui' 
                      AND date_fin_promo >= '$aujourdhui'";
$result_fin = $conn->query($sql_prochaine_fin);
$row_fin = $result_fin->fetch_assoc();
$prochaine_fin_promo = $row_fin['prochaine_fin_promo'] ?? null;

$showBanner = false;
if ($prochaine_fin_promo) {
    $aujourdhui = date('Y-m-d');
    if ($prochaine_fin_promo >= $aujourdhui) {
        $showBanner = true;
        // Ajouter l'heure de fin de journée
        $prochaine_fin_promo .= ' 23:59:59';
    }
}

echo "<script>const prochaineFinPromo = " . ($showBanner ? "new Date('$prochaine_fin_promo')" : "null") . ";</script>";
$sql_tous_produits = "SELECT * FROM produits 
                     WHERE statut = 'disponible' 
                     ORDER BY created_at DESC";
$result_tous_produits = $conn->query($sql_tous_produits);
$tousProduits = [];
if ($result_tous_produits) {
    while ($row = $result_tous_produits->fetch_assoc()) {
        $row['images'] = getProduitImages($row['id'], $conn);
        $tousProduits[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $trans['home_title']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
         /* Ajout de styles pour la nouvelle section */
        .tous-produits-section {
            padding: 60px 0;
            background: #f0f2f5;
            margin-bottom: 80px;
            border-radius: 16px;
        }
        
        .section-anchor {
            display: block;
            position: relative;
            top: -120px;
            visibility: hidden;
        }
        
        .jump-link {
            display: block;
            text-align: center;
            margin: 30px 0;
        }
        
        .jump-link a {
            display: inline-block;
            background: linear-gradient(135deg, #6a1b9a 0%, #8e24aa 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(106, 27, 154, 0.3);
        }
        
        .jump-link a:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(106, 27, 154, 0.4);
        }
        
        .jump-link a i {
            margin-left: 8px;
        }
        /* Styles pour la section d'avis */
        /* Correction pour les étoiles cliquables */
        .rating-input {
            display: none;
        }
        
        .rating-label {
            cursor: pointer;
            font-size: 2.5rem;
            color: #ddd;
            transition: color 0.2s ease;
        }
        
        .rating-label:hover,
        .rating-label:hover ~ .rating-label {
            color: #ffcc00;
        }
        
        .rating-input:checked ~ .rating-label {
            color: #ffcc00;
        }
        
        .rating-input:checked + .rating-label {
            color: #ffcc00;
        }
        
        .rating-container {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin: 15px 0;
            flex-direction: row-reverse; /* Pour la sélection correcte */
        }
        .avis-section {
            padding: 80px 0;
            background: #f9f7ff;
            margin-bottom: 80px;
            border-radius: 16px;
        }
        
        .avis-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .avis-form {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 50px;
            max-width: 800px;
            margin: 0 auto 50px;
        }
        
        .avis-form h3 {
            font-size: 1.8rem;
            color: #6a1b9a;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        
        .rating-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }
        
        .rating-star {
            font-size: 2.5rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        
        .rating-star.active, .rating-star:hover {
            color: #ffcc00;
        }
        
        textarea.form-control {
            width: 100%;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            min-height: 150px;
            resize: vertical;
        }
        
        .btn-submit-avis {
            background: linear-gradient(135deg, #6a1b9a 0%, #8e24aa 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: block;
            margin: 20px auto 0;
            transition: all 0.3s ease;
        }
        
        .btn-submit-avis:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(106, 27, 154, 0.3);
        }
        
        .message {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Ajustements pour les avis existants */
        .testimonial-card .rating {
            margin-top: 10px;
        }
        
        .testimonial-date {
            color: #888;
            font-size: 0.85rem;
            margin-top: 5px;
        }
        
        .no-avis {
            text-align: center;
            padding: 30px;
            color: #666;
            font-size: 1.1rem;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f9f7ff 0%, #eef2ff 100%);
            color: #333;
            min-height: 100vh;
            padding-top: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 120px auto;
            padding: 0 20px;
          
        }
        .content-section1{
            margin-top: -90px;
        }

        .promo-info {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            background: rgba(255, 255, 255, 0.1);
            padding: 12px 20px;
            border-radius: 10px;
            backdrop-filter: blur(5px);
        }
        
        .promo-percent {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .promo-dates {
            color: #ffcc70;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        /* Animation pour le compte à rebours */
        .countdown-flash {
            animation: flash 1s infinite;
        }
        
        @keyframes flash {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        /* Masquer les informations de promotion sur mobile */
        @media (max-width: 768px) {
            .promo-info, .price-comparison {
                display: none;
            }
        }

        /* Styles pour le contenu de la page d'accueil */
        /* PROMO SLIDER */
        .promo-slider {
            position: relative;
            margin: 20px auto;
            max-width: 1400px;
            height: 450px;
            overflow: hidden;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .slides-container {
            display: flex;
            height: 100%;
            transition: transform 0.8s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }

        .slide {
            min-width: 100%;
            position: relative;
            overflow: hidden;
        }

        .slide-content {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            padding: 0 10%;
            background: linear-gradient(90deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.3) 100%);
            z-index: 2;
        }

        .slide-text {
            max-width: 600px;
            color: white;
            animation: fadeInUp 0.8s ease-out;
        }

        .slide-text h2 {
            font-size: 3.5rem;
            margin-bottom: 20px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .slide-text p {
            font-size: 1.5rem;
            margin-bottom: 30px;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .slide-btn {
            display: inline-block;
            background: white;
            color: #6a1b9a;
            padding: 15px 35px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            text-decoration: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .slide-btn:hover {
            background: #f0f0f0;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .slide-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 1;
        }

        .slider-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.2);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }

        .slider-nav:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-50%) scale(1.1);
        }

        .slider-prev {
            left: 20px;
        }

        .slider-next {
            right: 20px;
        }

        .slider-dots {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 10px;
            z-index: 10;
        }

        .slider-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .slider-dot.active {
            background: white;
            transform: scale(1.2);
        }

        /* COUNTDOWN */
        .countdown {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .countdown-item {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 10px;
            min-width: 70px;
            text-align: center;
            backdrop-filter: blur(5px);
        }

        .countdown-number {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .countdown-label {
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        /* CONTENT SECTION */
        .content-section {
            padding: 60px 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-title h2 {
            font-size: 2.5rem;
            color: #96009bff;
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }
        .section-title1 h2 {
            font-size: 2.5rem;
            color: #ffffffff;
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }
        .section-title1 h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, #dfcb17ff, #4c8a95ff);
            border-radius: 2px;
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, #6a1b9a, #4c956c);
            border-radius: 2px;
        }

        .section-title p {
            color: #000000ff;
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto;
        }

        /* PRODUCTS */
        .produits-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 80px;
            padding: 0 15px;
        }

        .produit {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }

        .produit:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(106, 27, 154, 0.2);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .stock-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: #4CAF50;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            z-index: 2;
        }
        
        .promo-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            z-index: 2;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .carousel {
            position: relative;
            height: 250px;
            overflow: hidden;
        }

        .carousel img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
            transition: opacity 0.5s ease;
            position: absolute;
            top: 0;
            left: 0;
        }

        .carousel img.active { 
            display: block; 
        }

        .carousel .arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0,0,0,0.4);
            color: white;
            border: none;
            font-size: 18px;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 50%;
            z-index: 10;
            transition: all 0.3s ease;
            opacity: 0;
        }

        .carousel:hover .arrow {
            opacity: 1;
        }

        .carousel .arrow:hover {
            background: rgba(0,0,0,0.7);
            transform: translateY(-50%) scale(1.1);
        }

        .carousel .prev { 
            left: 15px; 
        }

        .carousel .next { 
            right: 15px; 
        }

        .carousel-dots {
            position: absolute;
            bottom: 10px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            z-index: 5;
        }

        .carousel-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            margin: 0 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .carousel-dot.active {
            background: #fff;
            transform: scale(1.2);
        }

        .product-info {
            padding: 20px;
            text-align: center;
            background: white;
        }

        .product-info h3 {
            font-size: 1.4rem;
            color: #6a1b9a;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .product-price {
            font-size: 1.3rem;
            color: #8e24aa;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .original-price {
            text-decoration: line-through;
            color: #888;
            font-size: 1rem;
        }

        .promo-price {
            color: #e74c3c;
            font-size: 1.4rem;
        }

        .product-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
            font-size: 0.95rem;
            height: 60px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }

        .btn-commander {
            display: block;
            background: linear-gradient(135deg, #6a1b9a 0%, #8e24aa 100%);
            color: white;
            padding: 12px 20px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1.05rem;
            width: 100%;
        }

        .btn-commander:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(106, 27, 154, 0.3);
        }

        /* NOUVELLES SECTIONS AJOUTÉES */
        .why-us-section {
            padding: 80px 0;
            color: white;
            text-align: center;
            margin-bottom: 80px;
            border-radius: 16px;
            box-shadow: 0 15px 40px rgba(106, 27, 154, 0.3);
            background: linear-gradient(135deg, #6a1b9a 0%, #8e24aa 100%);
        }

        .why-us-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .why-us-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .why-us-card {
            background: rgba(132, 21, 136, 1);
            border-radius: 16px;
            padding: 30px;
            transition: transform 0.3s ease, background 0.3s ease;
            backdrop-filter: blur(5px);
        }

        .why-us-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 0, 255, 0.44);
            color: black;
        }

        .why-us-icon {
            font-size: 3.5rem;
            margin-bottom: 20px;
            color: #ffcc70;
        }

        .why-us-title {
            font-size: 1.5rem;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .why-us-description {
            font-size: 1rem;
            line-height: 1.6;
            opacity: 0.9;
            color: #f0f0f0;
        }

        .testimonials-section {
            padding: 80px 0;
            background: #f9f7ff;
            margin-bottom: 80px;
            border-radius: 16px;
        }

        .testimonials-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .testimonial-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
            position: relative;
        }

        .testimonial-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(106, 27, 154, 0.15);
        }

        .testimonial-card::before {
            content: '"';
            position: absolute;
            top: 20px;
            left: 25px;
            font-size: 5rem;
            color: #6a1b9a;
            opacity: 0.1;
            font-family: Georgia, serif;
        }

        .testimonial-content {
            font-size: 1.1rem;
            line-height: 1.7;
            margin-bottom: 25px;
            position: relative;
            z-index: 2;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .author-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #eef2ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #6a1b9a;
        }

        .author-info h4 {
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        .author-info p {
            color: #666;
            font-size: 0.9rem;
        }

        .rating {
            display: flex;
            gap: 5px;
            margin-top: 5px;
        }

        .rating i {
            color: #ffcc00;
        }

        /* ANIMATIONS */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Styles pour les modales */
        .modal-overlay, .modal-content {
            position: fixed;
            display: none;
            z-index: 1001;
        }
        .modal-overlay {
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
        }
        .modal-content {
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            width: 90%; max-width: 400px;
            max-height: 90vh; 
            overflow-y: auto;
        }

        .modal-content img { max-width: 100%; margin-bottom: 10px; }

        @keyframes modalOpen {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .modal-header h3 {
            font-size: 1.8rem;
            color: #6a1b9a;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .modal-header p {
            color: #666;
            margin-top: 10px;
        }

        .modal-body {
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: #8e24aa;
            outline: none;
            box-shadow: 0 0 0 3px rgba(138, 43, 226, 0.2);
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn-modal {
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6a1b9a 0%, #8e24aa 100%);
            color: white;
        }

        .btn-secondary {
            background: #f1f1f1;
            color: #555;
        }

        .btn-modal:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .whatsapp-btn {
            display: inline-block;
            background: #25D366;
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            font-weight: 600;
            text-decoration: none;
            margin: 15px 0;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .whatsapp-btn:hover {
            background: #128C7E;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(37, 211, 102, 0.3);
        }

        .modal-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
        }

        .quantity-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f1f1f1;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .quantity-btn:hover {
            background: #e1e1e1;
        }

        .quantity-input {
            width: 60px;
            text-align: center;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 1.1rem;
        }
        
        /* Styles pour le champ de date */
        .delivery-date {
            margin: 15px 0;
            padding: 10px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }
        
        .delivery-date label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        
        .delivery-date input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            background: #f9f9f9;
        }

        /* RESPONSIVE pour le contenu */
        @media (max-width: 1200px) {
            .slide-text h2 {
                font-size: 3rem;
            }
            
            .slide-text p {
                font-size: 1.3rem;
            }
        }
        @media (min-width: 481px) and (max-width: 768px){
                .content-section1 {
                margin-top: -130px;    
            }
            .produits-container{
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                padding: 10px;
            }
            .promo-slider {
                margin: 170px auto;
            }
        }
        @media (min-width: 769px) and (max-width: 1024px){
                .content-section1 {
                margin-top: -130px;
            }
            .promo-slider {
                margin: 155px auto;
            }
            .produits-container{
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                padding: 10px;
            }
        }
        @media (max-width: 992px) {
            .promo-slider {
                height: 400px;
            }
            .container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }
            .slide-text h2 {
                font-size: 2.5rem;
            }
            
            .slider-nav {
                width: 50px;
                height: 50px;
            }
            
        }

        @media (max-width: 768px) {
            .promo-slider {
                height: 350px;
            }
            
            .slide-content {
                padding: 0 5%;
            }
            
            .slide-text h2 {
                font-size: 2rem;
                margin-bottom: 15px;
            }
            
            .slide-text p {
                font-size: 1.1rem;
                margin-bottom: 20px;
            }
            
            .slide-btn {
                padding: 12px 25px;
                font-size: 1rem;
            }
            
            .countdown {
                gap: 10px;
            }
            
            .countdown-item {
                min-width: 60px;
                padding: 8px;
            }
            
            .countdown-number {
                font-size: 1.5rem;
            }
            
            .modal-actions {
                flex-direction: column;
                gap: 10px;
            }

            .btn-modal {
                width: 100%;
            }
            
            /* Responsive pour les nouvelles sections */
            .why-us-section, .testimonials-section {
                padding: 50px 0;
            }
            
            .why-us-grid, .testimonials-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        @media (max-width: 576px) {
            .promo-slider {
                height: 300px;
                border-radius: 8px;
            }
            
            .slide-text h2 {
                font-size: 1.7rem;
            }
            
            .slider-nav {
                width: 40px;
                height: 40px;
                font-size: 0.8rem;
            }
            
            .countdown {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .countdown-item {
                min-width: 50px;
                padding: 6px;
            }
            
            .countdown-number {
                font-size: 1.3rem;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            .content-section1 {
                margin-top: -130px;
            }
            .container {
                margin: 170px auto;
            }
            .produits-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
                padding: 10px;
            }
            
            .page-title h2 {
                font-size: 1.8rem;
            }
            
            .page-title {
                margin: 20px 0;
                padding: 15px;
            }
            
            .produit {
                border-radius: 12px;
            }
            
            .carousel {
                height: 150px;
            }
            
            .product-info {
                padding: 12px;
            }
            
            .product-info h3 {
                font-size: 1rem;
            }
            
            .product-price {
                font-size: 1rem;
            }
            
            .btn-commander {
                padding: 8px 12px;
                font-size: 0.85rem;
            }
            
            .stock-badge, .promo-badge {
                font-size: 0.7rem;
                padding: 3px 8px;
            }
        }
            /* Cache l'image de la modale 3 sur les petits écrans */
            #step3 .modal-image {
              display: none;
            }
            
            /* Ajuste le comportement des boutons sur mobile */
            #step3 .modal-actions {
              display: flex;
              flex-direction: row; /* Force l'affichage côte à côte */
              justify-content: space-between;
              gap: 10px;
            }

            #step3 .modal-actions .btn-modal {
              flex-grow: 1; /* Permet aux boutons de prendre la même largeur */
              width: auto;
            }
            
        }
    </style>
</head>
<body>
<<<<<<< HEAD
=======
<div class="content">
  <h2>🛍 Bienvenue sur TchadShop</h2>
  <p>Commandez facilement depuis chez vous!</p>
>>>>>>> ded8c060c21e08021168a2d7785d6bf3c25338c9

    <!-- SLIDER DE PROMOTIONS DYNAMIQUE -->
    <div class="container">
        <div class="promo-slider">
            <div class="slides-container">
                <?php foreach ($promotions as $index => $promo): ?>
                    <?php 
                    $prix_promo = calculerPrixPromotion($promo['prix'], $promo['promotion']);
                    $date_debut = formaterDate($promo['date_debut_promo'], $trans);
                    $date_fin = formaterDate($promo['date_fin_promo'], $trans);
                    ?>
                    <div class="slide" data-promo-id="<?= $promo['id'] ?>" data-fin="<?= $promo['date_fin_promo'] ?>">
                        <img src="<?= htmlspecialchars($promo['images'][0]) ?>" class="slide-image" alt="<?= htmlspecialchars($promo['nom']) ?>">
                        
                        <div class="slide-content">
                            <div class="slide-text">
                                <h2><?= htmlspecialchars($promo['nom']) ?></h2>
                                <p><?= htmlspecialchars(substr($promo['description'], 0, 100)) . '...' ?></p>
                                
                                <!-- Affichage dynamique de la promotion - MASQUÉ SUR MOBILE -->
                                <?php if ($promo['promotion'] > 0): ?>
                                    <div class="promo-info">
                                        <div class="promo-percent">
                                            <i class="fas fa-tag"></i> -<?= $promo['promotion'] ?>%
                                        </div>
                                        <div class="promo-dates">
                                            <i class="fas fa-calendar-alt"></i> 
                                            <?php echo sprintf($trans['promo_from_to'], $date_debut, $date_fin); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="price-comparison">
                                        <span style="text-decoration: line-through; color: #ccc; margin-right: 15px;">
                                            <?= formatPrix($promo['prix']) ?>
                                        </span>
                                        <span style="color: #fff; font-weight: 700; font-size: 1.5rem;">
                                            <?= formatPrix($prix_promo) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <a href="produits.php?id=<?= $promo['id'] ?>" class="slide-btn">
                                    <?php echo $trans['view_product']; ?> <i class="fas fa-arrow-right"></i>
                                </a>
                                
                                <!-- Compte à rebours dynamique basé sur la date de fin -->
                                <?php if ($promo['promotion'] > 0): ?>
                                    <div class="countdown">
                                        <div class="countdown-item">
                                            <div class="countdown-number" id="days-<?= $promo['id'] ?>">00</div>
                                            <div class="countdown-label"><?php echo $trans['days']; ?></div>
                                        </div>
                                        <div class="countdown-item">
                                            <div class="countdown-number" id="hours-<?= $promo['id'] ?>">00</div>
                                            <div class="countdown-label"><?php echo $trans['hours']; ?></div>
                                        </div>
                                        <div class="countdown-item">
                                            <div class="countdown-number" id="minutes-<?= $promo['id'] ?>">00</div>
                                            <div class="countdown-label"><?php echo $trans['minutes']; ?></div>
                                        </div>
                                        <div class="countdown-item">
                                            <div class="countdown-number" id="seconds-<?= $promo['id'] ?>">00</div>
                                            <div class="countdown-label"><?php echo $trans['seconds']; ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="slider-nav slider-prev">
                <i class="fas fa-chevron-left"></i>
            </div>
            <div class="slider-nav slider-next">
                <i class="fas fa-chevron-right"></i>
            </div>
            
            <div class="slider-dots">
                <?php for ($i = 0; $i < count($promotions); $i++): ?>
                    <div class="slider-dot <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>"></div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- CONTENU PRINCIPAL -->
    <div class="content-section1">
        <div class="container1">
            <div class="section-title">
                <h2><?php echo $trans['welcome']; ?></h2>
                <p><?php echo $trans['discover_products']; ?></p>
            </div>
            
            <div class="produits-container">
                <?php foreach ($produits as $index => $produit): ?>
                    <div class="produit">
                        <?php if ($produit['stock'] > 10): ?>
                            <div class="stock-badge"><?php echo $trans['in_stock']; ?></div>
                        <?php elseif ($produit['stock'] > 0): ?>
                            <div class="stock-badge" style="background: #ff9800;"><?php echo $trans['limited_stock']; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($produit['promotion'] > 0): ?>
                            <div class="promo-badge">-<?= $produit['promotion'] ?>%</div>
                        <?php endif; ?>
                        
                        <div class="carousel" id="carousel-<?= $index ?>">
                            <?php foreach ($produit['images'] as $i => $img): ?>
                                <img src="<?= htmlspecialchars($img) ?>" class="<?= $i === 0 ? 'active' : '' ?>">
                            <?php endforeach; ?>
                            <button class="arrow prev" onclick="prevSlide(<?= $index ?>)">&#10094;</button>
                            <button class="arrow next" onclick="nextSlide(<?= $index ?>)">&#10095;</button>
                            <div class="carousel-dots" id="dots-<?= $index ?>">
                                <?php foreach ($produit['images'] as $i => $img): ?>
                                    <div class="carousel-dot <?= $i === 0 ? 'active' : '' ?>" onclick="showSlide(<?= $index ?>, <?= $i ?>)"></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="product-info">
                            <h3><?= htmlspecialchars($produit['nom']) ?></h3>
                            <div class="product-price">
                                <?php if ($produit['promotion'] > 0): 
                                    $prix_promo = calculerPrixPromotion($produit['prix'], $produit['promotion']);
                                ?>
                                    <span class="original-price"><?= formatPrix($produit['prix']) ?></span>
                                    <span class="promo-price"><?= formatPrix($prix_promo) ?></span>
                                <?php else: ?>
                                    <span><?= formatPrix($produit['prix']) ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="product-description"><?= htmlspecialchars($produit['description']) ?></p>
                            <button class="btn-commander" onclick="ouvrirCommande(
                                <?= $produit['id'] ?>,
                                '<?= htmlspecialchars($produit['nom']) ?>',
                                '<?= htmlspecialchars($produit['images'][0]) ?>',
                                '<?= $prix_promo ?? $produit['prix'] ?>',
                                `<?= htmlspecialchars($produit['description']) ?>`,
                                '<?= $produit['prix'] ?>',
                                <?= ($produit['promotion'] > 0) ? 'true' : 'false' ?>
                            )">
                                <i class="fas fa-shopping-cart"></i> <?php echo $trans['order']; ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="jump-link">
                <a href="#tous-les-produits">
                    <i class="fas fa-arrow-down"></i> <?php echo $trans['see_all_products']; ?>
                </a>
            </div>
        </div>
    </div>

    
    <!-- NOUVELLE SECTION: Tous les produits -->
    <div class="tous-produits-section">
        <span class="section-anchor" id="tous-les-produits"></span>
        <div class="container">
            <div class="section-title">
                <h2><?php echo $trans['all_products']; ?></h2>
                <p><?php echo $trans['discover_catalog']; ?></p>
            </div>
            
            <div class="produits-container">
                <?php 
                // Utiliser un compteur global qui commence après les produits populaires
                $globalIndex = count($produits); // Commence après les indices des produits populaires
                foreach ($tousProduits as $produit): 
                    $prix_promo = $produit['promotion'] > 0 
                        ? calculerPrixPromotion($produit['prix'], $produit['promotion']) 
                        : $produit['prix'];
                ?>
                    <div class="produit">
                        <?php if ($produit['stock'] > 10): ?>
                            <div class="stock-badge"><?php echo $trans['in_stock']; ?></div>
                        <?php elseif ($produit['stock'] > 0): ?>
                            <div class="stock-badge" style="background: #ff9800;"><?php echo $trans['limited_stock']; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($produit['promotion'] > 0): ?>
                            <div class="promo-badge">-<?= $produit['promotion'] ?>%</div>
                        <?php endif; ?>
                        
                        <div class="carousel" id="carousel-<?= $globalIndex ?>">
                            <?php foreach ($produit['images'] as $i => $img): ?>
                                <img src="<?= htmlspecialchars($img) ?>" class="<?= $i === 0 ? 'active' : '' ?>">
                            <?php endforeach; ?>
                            <button class="arrow prev" onclick="prevSlide(<?= $globalIndex ?>)">&#10094;</button>
                            <button class="arrow next" onclick="nextSlide(<?= $globalIndex ?>)">&#10095;</button>
                            <div class="carousel-dots" id="dots-<?= $globalIndex ?>">
                                <?php foreach ($produit['images'] as $i => $img): ?>
                                    <div class="carousel-dot <?= $i === 0 ? 'active' : '' ?>" onclick="showSlide(<?= $globalIndex ?>, <?= $i ?>)"></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="product-info">
                            <h3><?= htmlspecialchars($produit['nom']) ?></h3>
                            <div class="product-price">
                                <?php if ($produit['promotion'] > 0): ?>
                                    <span class="original-price"><?= formatPrix($produit['prix']) ?></span>
                                    <span class="promo-price"><?= formatPrix($prix_promo) ?></span>
                                <?php else: ?>
                                    <span><?= formatPrix($produit['prix']) ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="product-description"><?= htmlspecialchars($produit['description']) ?></p>
                            <button class="btn-commander" onclick="ouvrirCommande(
                                <?= $produit['id'] ?>,
                                '<?= htmlspecialchars($produit['nom']) ?>',
                                '<?= htmlspecialchars($produit['images'][0]) ?>',
                                '<?= $prix_promo ?>',
                                `<?= htmlspecialchars($produit['description']) ?>`,
                                '<?= $produit['prix'] ?>',
                                <?= ($produit['promotion'] > 0) ? 'true' : 'false' ?>
                            )">
                                <i class="fas fa-shopping-cart"></i> <?php echo $trans['order']; ?>
                            </button>
                        </div>
                    </div>
                    <?php $globalIndex++; ?>
                <?php endforeach; ?>
            </div>
            
            <!-- Lien pour revenir en haut -->
            <div class="jump-link">
                <a href="#">
                    <i class="fas fa-arrow-up"></i> <?php echo $trans['back_to_top']; ?>
                </a>
            </div>
        </div>
    </div>
    <!-- NOUVELLE SECTION: Pourquoi utiliser TchadShop -->
    <div class="why-us-section">
        <div class="why-us-container">
            <div class="section-title1" style="color: white;">
                <h2><?php echo $trans['why_choose']; ?></h2>
                <p class="why-us-description"><?php echo $trans['why_choose_sub']; ?></p>
            </div>
            
            <div class="why-us-grid">
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <h3 class="why-us-title"><?php echo $trans['fast_delivery']; ?></h3>
                    <p class="why-us-description"><?php echo $trans['fast_delivery_desc']; ?></p>
                </div>
                
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="why-us-title"><?php echo $trans['secure_payment']; ?></h3>
                    <p class="why-us-description"><?php echo $trans['secure_payment_desc']; ?></p>
                </div>
                
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3 class="why-us-title"><?php echo $trans['support']; ?></h3>
                    <p class="why-us-description"><?php echo $trans['support_desc']; ?></p>
                </div>
                
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <h3 class="why-us-title"><?php echo $trans['competitive_prices']; ?></h3>
                    <p class="why-us-description"><?php echo $trans['competitive_prices_desc']; ?></p>
                </div>
            </div>
        </div>
    </div>

  <!-- Section d'avis clients -->
    <div class="avis-section">
        <div class="avis-container">
            <div class="section-title">
                <h2><?php echo $trans['leave_review']; ?></h2>
                <p><?php echo $trans['leave_review_sub']; ?></p>
            </div>
            
            <?php if ($isConnected): ?>
            <div class="avis-form">
                <h3><?php echo $trans['share_experience']; ?></h3>
                
                <?php if (isset($success_message)): ?>
                    <div class="message success"><?= $success_message ?></div>
                <?php elseif (isset($error_message)): ?>
                    <div class="message error"><?= $error_message ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label><?php echo $trans['rate_experience']; ?></label>
                        <div class="rating-container">
                            <!-- Système d'étoiles cliquables -->
                            <input type="radio" id="star5" name="note" value="5" class="rating-input">
                            <label for="star5" class="rating-label">★</label>
                            
                            <input type="radio" id="star4" name="note" value="4" class="rating-input">
                            <label for="star4" class="rating-label">★</label>
                            
                            <input type="radio" id="star3" name="note" value="3" class="rating-input">
                            <label for="star3" class="rating-label">★</label>
                            
                            <input type="radio" id="star2" name="note" value="2" class="rating-input">
                            <label for="star2" class="rating-label">★</label>
                            
                            <input type="radio" id="star1" name="note" value="1" class="rating-input">
                            <label for="star1" class="rating-label">★</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="commentaire"><?php echo $trans['your_review']; ?></label>
                        <textarea class="form-control" id="commentaire" name="commentaire" 
                                  placeholder="<?php echo $trans['review_placeholder']; ?>" required></textarea>
                    </div>
                    
                    <button type="submit" name="submit_avis" class="btn-submit-avis">
                        <i class="fas fa-paper-plane"></i> <?php echo $trans['publish_review']; ?>
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="avis-form" style="text-align: center;">
                <h3><?php echo $trans['login_to_review']; ?></h3>
                <p><?php echo $trans['login_to_review_desc']; ?></p>
                <a href="login.php" class="btn-submit-avis">
                    <i class="fas fa-sign-in-alt"></i> <?php echo $trans['login']; ?>
                </a>
            </div>
            <?php endif; ?>
            
            <div class="section-title" style="margin-top: 60px;">
                <h2><?php echo $trans['customers_reviews']; ?></h2>
                <p><?php echo $trans['customers_reviews_sub']; ?></p>
            </div>
            
            <div class="testimonials-grid">
                <?php if (!empty($avis)): ?>
                    <?php foreach ($avis as $avis_item): ?>
                        <div class="testimonial-card">
                            <p class="testimonial-content">"<?= htmlspecialchars($avis_item['commentaire']) ?>"</p>
                            <div class="testimonial-author">
                                <div class="author-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="author-info">
                                    <h4><?= htmlspecialchars($avis_item['nom_client']) ?></h4>
                                    <p><?php echo $trans['customer_since']; ?> <?= date('Y', strtotime($avis_item['date_creation'])) ?></p>
                                    <div class="rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= $avis_item['note'] ? 'active' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="testimonial-date">
                                        <?= date('d/m/Y', strtotime($avis_item['date_creation'])) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-avis" style="grid-column: 1 / -1;">
                        <i class="fas fa-comments" style="font-size: 3rem; color: #6a1b9a; margin-bottom: 20px;"></i>
                        <h3><?php echo $trans['no_reviews']; ?></h3>
                        <p><?php echo $trans['no_reviews_desc']; ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- MODALES DE COMMANDE -->
    <div class="modal-overlay" id="overlay"></div>

    <div class="modal-content" id="step1">
        <div class="modal-header">
            <h3><i class="fas fa-phone-alt"></i> <?php echo $trans['enter_phone']; ?></h3>
            <p><?php echo $trans['enter_phone_desc']; ?></p>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <input type="tel" class="form-control" id="numeroClient" placeholder="<?php echo $trans['phone_example']; ?>">
            </div>
        </div>
        <div class="modal-actions">
            <button class="btn-modal btn-primary" onclick="nextStep1()">
                <i class="fas fa-arrow-right"></i> <?php echo $trans['next']; ?>
            </button>
            <button class="btn-modal btn-secondary" onclick="fermerModals()">
                <i class="fas fa-times"></i> <?php echo $trans['cancel']; ?>
            </button>
        </div>
    </div>

    <div class="modal-content" id="step2">
        <div class="modal-header">
            <h3><i class="fas fa-map-marker-alt"></i> <?php echo $trans['allow_location']; ?></h3>
            <p><?php echo $trans['allow_location_desc']; ?></p>
        </div>
        <div class="modal-body">
            <p><?php echo $trans['allow_location_text']; ?></p>
        </div>
        <div class="modal-actions">
            <button class="btn-modal btn-primary" onclick="autoriserLocalisation()">
                <i class="fas fa-check"></i> <?php echo $trans['allow']; ?>
            </button>
            <button class="btn-modal btn-secondary" onclick="ignorerLocalisation()">
                <i class="fas fa-ban"></i> <?php echo $trans['ignore']; ?>
            </button>
        </div>
    </div>

    <div class="modal-content" id="step3">
        <div class="modal-header">
            <h3><i class="fas fa-shopping-cart"></i> <?php echo $trans['finalize_order']; ?></h3>
            <p><?php echo $trans['finalize_order_desc']; ?></p>
        </div>
        <div class="modal-body">
            <img src="" id="modalImage" class="modal-image">
            
            <h3 id="produitNom" style="margin-bottom: 10px;"></h3>
            <p id="produitPrix" style="color: #8e24aa; font-weight: 700; font-size: 1.3rem; margin-bottom: 15px;"></p>
            <p id="produitDescription" style="color: #666; margin-bottom: 15px;"></p>
            
            <div class="quantity-control">
                <button class="quantity-btn" onclick="updateQuantity(-1)">-</button>
                <input type="number" class="quantity-input" id="quantite" value="1" min="1">
                <button class="quantity-btn" onclick="updateQuantity(1)">+</button>
            </div>
            
            <div class="delivery-date">
                <label for="dateLivraison"><i class="fas fa-calendar-alt"></i> <?php echo $trans['delivery_date']; ?></label>
                <input type="date" id="dateLivraison" name="dateLivraison">
            </div>
            
            <a id="btnWhatsapp" class="whatsapp-btn" target="_blank">
                <i class="fab fa-whatsapp"></i> <?php echo $trans['order_whatsapp']; ?>
            </a>
        </div>
        <div class="modal-actions">
            <button class="btn-modal btn-primary" onclick="validerCommande()">
                <i class="fas fa-check-circle"></i> <?php echo $trans['validate']; ?>
            </button>
            <button class="btn-modal btn-secondary" onclick="fermerModals()">
                <i class="fas fa-times"></i> <?php echo $trans['cancel']; ?>
            </button>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        // Variables globales pour la commande
        const isConnected = <?= $isConnected ? 'true' : 'false' ?>;
        let telephone = '<?= $telephone_connecte ?>';
        let produitId = null, produitNom = '', produitImage = '', produitPrixFinal = '', produitDescription = '';
        let produitPrixOriginal = null, produitPromotion = false;
        let latitude = null, longitude = null;
        
        // Gestion des carrousels
        let carousels = [];
        
        document.querySelectorAll('.carousel').forEach((c, i) => {
            const imgs = c.querySelectorAll('img');
            carousels[i] = { 
                images: imgs, 
                currentIndex: 0,
                timer: null
            };
            
            // Démarrer le défilement automatique pour les carrousels avec plusieurs images
            if (imgs.length > 1) {
                startCarousel(i);
            }
            
            // Arrêter le défilement lorsque la souris survole le carrousel
            c.addEventListener('mouseenter', () => {
                if (carousels[i].timer) {
                    clearInterval(carousels[i].timer);
                    carousels[i].timer = null;
                }
            });
            
            // Redémarrer le défilement lorsque la souris quitte le carrousel
            c.addEventListener('mouseleave', () => {
                if (imgs.length > 1 && !carousels[i].timer) {
                    startCarousel(i);
                }
            });
        });
        
        // Fonction pour démarrer le défilement automatique
        function startCarousel(index) {
            if (carousels[index].timer) {
                clearInterval(carousels[index].timer);
            }
            
            // Démarrer un nouvel intervalle de 2 secondes
            carousels[index].timer = setInterval(() => {
                nextSlide(index);
            }, 2000);
        }
        
        function showImage(index, i) {
            carousels[index].images.forEach((img, j) => {
                img.classList.toggle('active', j === i);
            });
            carousels[index].currentIndex = i;
            
            // Mettre à jour les points indicateurs
            const dots = document.querySelectorAll(`#dots-${index} .carousel-dot`);
            dots.forEach((dot, j) => {
                dot.classList.toggle('active', j === i);
            });
        }
        
        function showSlide(index, slideIndex) {
            // Réinitialiser le timer
            if (carousels[index].images.length > 1) {
                startCarousel(index);
            }
            showImage(index, slideIndex);
        }
        
        function prevSlide(i) {
            // Réinitialiser le timer
            if (carousels[i].images.length > 1) {
                startCarousel(i);
            }
            
            const c = carousels[i];
            const n = (c.currentIndex - 1 + c.images.length) % c.images.length;
            showImage(i, n);
        }
        
        function nextSlide(i) {
            // Réinitialiser le timer
            if (carousels[i].images.length > 1) {
                startCarousel(i);
            }
            
            const c = carousels[i];
            const n = (c.currentIndex + 1) % c.images.length;
            showImage(i, n);
        }

        // COMMANDE
        function ouvrirCommande(id, nom, img, prixFinal, desc, prixOriginal, promotion) {
            produitId = id;
            produitNom = nom;
            produitImage = img;
            produitPrixFinal = prixFinal;
            produitDescription = desc;
            produitPrixOriginal = prixOriginal;
            produitPromotion = promotion;
            latitude = longitude = null;

            document.getElementById('overlay').style.display = 'flex';

            document.getElementById('quantite').value = 1;

            // Vérifier si le client est connecté
            if (isConnected && telephone) {
                // Client connecté : passer à l'étape 2 (demande de localisation)
                document.getElementById('step2').style.display = 'block';
            } else {
                // Client non connecté - demander le numéro
                document.getElementById('numeroClient').value = '';
                document.getElementById('step1').style.display = 'block';
            }
        }

        function fermerModals() {
            document.getElementById('overlay').style.display = 'none';
            document.querySelectorAll('.modal-content').forEach(e => e.style.display = 'none');
        }

        function nextStep1() {
            telephone = document.getElementById('numeroClient').value.trim();
            if (!telephone.match(/^\d{6,}$/)) {
                alert("<?php echo $trans['invalid_phone']; ?>");
                return;
            }
            document.getElementById('step1').style.display = 'none';
            document.getElementById('step2').style.display = 'block';
        }

        function autoriserLocalisation() {
            navigator.geolocation.getCurrentPosition(pos => {
                latitude = pos.coords.latitude;
                longitude = pos.coords.longitude;
                document.getElementById('step2').style.display = 'none';
                ouvrirStep3();
            }, () => {
                alert("<?php echo $trans['location_error']; ?>");
                ignorerLocalisation();
            });
        }
        
        function ignorerLocalisation() {
            latitude = longitude = null;
            document.getElementById('step2').style.display = 'none';
            ouvrirStep3();
        }

        function updateQuantity(change) {
            const input = document.getElementById('quantite');
            let value = parseInt(input.value) || 1;
            value += change;
            if (value < 1) value = 1;
            input.value = value;
            
            // Mettre à jour le lien WhatsApp après chaque changement
            mettreAJourLienWhatsApp();
        }

        function ouvrirStep3() {
            document.getElementById('modalImage').src = produitImage;
            document.getElementById('produitNom').textContent = produitNom;
            
            // Afficher le prix avec promotion si applicable
            if (produitPromotion) {
                document.getElementById('produitPrix').innerHTML = `
                    <span style="text-decoration: line-through; color: #999; font-size: 1rem;">
                        ${parseFloat(produitPrixOriginal).toLocaleString('fr-FR')} FCFA
                    </span>
                    <span style="color: #e74c3c; font-weight: 700;">
                        ${parseFloat(produitPrixFinal).toLocaleString('fr-FR')} FCFA
                    </span>
                `;
            } else {
                document.getElementById('produitPrix').textContent = 
                    parseFloat(produitPrixFinal).toLocaleString('fr-FR') + " FCFA";
            }
            
            document.getElementById('produitDescription').textContent = produitDescription;

            // Configurer le champ de date
            const dateInput = document.getElementById('dateLivraison');
            const today = new Date();
            const minDate = new Date();
            minDate.setDate(today.getDate() + 1); // Demain
            const maxDate = new Date();
            maxDate.setDate(today.getDate() + 30); // Dans 30 jours
            
            // Formater les dates pour le champ date
            dateInput.min = formatDate(minDate);
            dateInput.max = formatDate(maxDate);
            
            // Définir une date par défaut (dans 3 jours)
            const defaultDate = new Date();
            defaultDate.setDate(today.getDate() + 3);
            dateInput.value = formatDate(defaultDate);

            // Mise à jour initiale du lien WhatsApp
            mettreAJourLienWhatsApp();
            
            // Ajout d'un écouteur pour les modifications manuelles de quantité
            document.getElementById('quantite').addEventListener('input', mettreAJourLienWhatsApp);
            
            // Ajout d'un écouteur pour les modifications de date
            dateInput.addEventListener('change', mettreAJourLienWhatsApp);

            document.getElementById('step3').style.display = 'block';
        }

        // Fonction utilitaire pour formater une date au format YYYY-MM-DD
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function mettreAJourLienWhatsApp() {
            const quantite = document.getElementById('quantite').value || 1;
            const dateLivraison = document.getElementById('dateLivraison').value;
            const numeroEntreprise = "23560000000";

            const imageUrl = "http://votre-domaine.com/" + produitImage.replace(/^\/+/, '');

            // Formater la date pour l'affichage avec la locale appropriée
            const formattedDate = new Date(dateLivraison).toLocaleDateString('<?php echo $js_locale; ?>', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            // Calculer le total
            const total = parseFloat(produitPrixFinal) * parseInt(quantite);
            
            // Construire le message avec les traductions
            let message = "<?php echo $trans['hello_order']; ?>:\n";
            message += "<?php echo $trans['product']; ?>: " + produitNom + "\n";
            message += "<?php echo $trans['quantity']; ?>: " + quantite + "\n";
            
            if (produitPromotion) {
                message += "<?php echo $trans['unit_price']; ?>: " + parseFloat(produitPrixFinal).toLocaleString('fr-FR') + " FCFA (Promo)\n";
                message += "<?php echo $trans['old_price']; ?>: " + parseFloat(produitPrixOriginal).toLocaleString('fr-FR') + " FCFA\n";
            } else {
                message += "<?php echo $trans['unit_price']; ?>: " + parseFloat(produitPrixFinal).toLocaleString('fr-FR') + " FCFA\n";
            }
            
            message += "<?php echo $trans['total']; ?>: " + total.toLocaleString('fr-FR') + " FCFA\n";
            message += "<?php echo $trans['desired_delivery_date']; ?>: " + formattedDate + "\n";
            message += "<?php echo $trans['description']; ?>: " + produitDescription + "\n";
            message += "<?php echo $trans['image']; ?>: " + imageUrl + "\n";
            message += "<?php echo $trans['my_number']; ?>: " + telephone;

            const encodedMessage = encodeURIComponent(message);

            // Détection du type d'appareil (mobile ou PC)
            const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);

            // Lien adapté selon le device
            const whatsappLink = isMobile
                ? `https://api.whatsapp.com/send?phone=${numeroEntreprise}&text=${encodedMessage}`
                : `https://web.whatsapp.com/send?phone=${numeroEntreprise}&text=${encodedMessage}`;

            document.getElementById('btnWhatsapp').href = whatsappLink;
        }

        function validerCommande() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'envoyer_commande.php';

            // Récupérer la date de livraison
            const dateLivraison = document.getElementById('dateLivraison').value;

            // Champs cachés pour transmettre les données
            const fields = {
                produit_id: produitId,
                telephone: telephone,
                quantite: document.getElementById('quantite').value || 1,
                latitude: latitude,
                longitude: longitude,
                date_livraison_prevue: dateLivraison,
                prix_unitaire: produitPrixFinal,
                promotion: produitPromotion ? 1 : 0
            };

            for (const key in fields) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = fields[key] ?? '';
                form.appendChild(input);
            }

            document.body.appendChild(form);
            form.submit();
        }

          // Gestion du slider de promotions
        const slider = document.querySelector('.slides-container');
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.slider-dot');
        const prevBtn = document.querySelector('.slider-prev');
        const nextBtn = document.querySelector('.slider-next');
        
        let currentIndex = 0;
        let autoSlideInterval;
        let countdownIntervals = {};
        
        // Initialisation du slider
        function initSlider() {
            updateSliderPosition();
            startAutoSlide();
            initCountdowns();
        }
        
        // Mettre à jour la position du slider
        function updateSliderPosition() {
            slider.style.transform = `translateX(-${currentIndex * 100}%)`;
            
            // Mettre à jour les points indicateurs
            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === currentIndex);
            });
        }
        
        // Passer au slide suivant
        function nextSlideSlider() {
            currentIndex = (currentIndex + 1) % slides.length;
            updateSliderPosition();
        }
        
        // Passer au slide précédent
        function prevSlideSlider() {
            currentIndex = (currentIndex - 1 + slides.length) % slides.length;
            updateSliderPosition();
        }
        
        // Démarrer le défilement automatique
        function startAutoSlide() {
            autoSlideInterval = setInterval(() => {
                nextSlideSlider();
            }, 5000);
        }
        
        // Arrêter le défilement automatique
        function stopAutoSlide() {
            clearInterval(autoSlideInterval);
        }
        
        // Événements
        nextBtn.addEventListener('click', () => {
            stopAutoSlide();
            nextSlideSlider();
            startAutoSlide();
        });
        
        prevBtn.addEventListener('click', () => {
            stopAutoSlide();
            prevSlideSlider();
            startAutoSlide();
        });
        
        // Navigation par points
        dots.forEach(dot => {
            dot.addEventListener('click', () => {
                stopAutoSlide();
                currentIndex = parseInt(dot.dataset.index);
                updateSliderPosition();
                startAutoSlide();
            });
        });
        
        // Arrêter le défilement automatique au survol
        slider.addEventListener('mouseenter', stopAutoSlide);
        slider.addEventListener('mouseleave', startAutoSlide);
        
        // Initialiser les comptes à rebours
        function initCountdowns() {
            // Arrêter tous les intervalles existants
            for (const id in countdownIntervals) {
                clearInterval(countdownIntervals[id]);
            }
            
            // Démarrer les comptes à rebours pour chaque slide
            slides.forEach(slide => {
                if (slide.dataset.fin) {
                    const promoId = slide.dataset.promoId;
                    const endDate = new Date(slide.dataset.fin + 'T23:59:59');
                    
                    // Mettre à jour immédiatement
                    updateCountdown(promoId, endDate);
                    
                    // Démarrer l'intervalle pour ce compte à rebours
                    countdownIntervals[promoId] = setInterval(() => {
                        updateCountdown(promoId, endDate);
                    }, 1000);
                }
            });
        }
        
        // Fonction de mise à jour du compte à rebours
        function updateCountdown(promoId, endDate) {
            const now = new Date();
            const diff = endDate - now;
            
            // Éléments DOM pour ce compte à rebours
            const daysEl = document.getElementById(`days-${promoId}`);
            const hoursEl = document.getElementById(`hours-${promoId}`);
            const minutesEl = document.getElementById(`minutes-${promoId}`);
            const secondsEl = document.getElementById(`seconds-${promoId}`);
            
            // Vérifier si les éléments existent
            if (!daysEl || !hoursEl || !minutesEl || !secondsEl) return;
            
            if (diff <= 0) {
                // La promotion est terminée
                daysEl.textContent = '00';
                hoursEl.textContent = '00';
                minutesEl.textContent = '00';
                secondsEl.textContent = '00';
                
                // Ajouter un effet visuel pour indiquer la fin de la promotion
                daysEl.classList.add('countdown-flash');
                hoursEl.classList.add('countdown-flash');
                minutesEl.classList.add('countdown-flash');
                secondsEl.classList.add('countdown-flash');
                
                // Arrêter l'intervalle pour ce compte à rebours
                if (countdownIntervals[promoId]) {
                    clearInterval(countdownIntervals[promoId]);
                    delete countdownIntervals[promoId];
                }
                
                return;
            }
            
            // Retirer l'effet flash si la promotion est encore active
            daysEl.classList.remove('countdown-flash');
            hoursEl.classList.remove('countdown-flash');
            minutesEl.classList.remove('countdown-flash');
            secondsEl.classList.remove('countdown-flash');
            
            // Calculer le temps restant
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            
            // Mettre à jour les éléments
            daysEl.textContent = days.toString().padStart(2, '0');
            hoursEl.textContent = hours.toString().padStart(2, '0');
            minutesEl.textContent = minutes.toString().padStart(2, '0');
            secondsEl.textContent = seconds.toString().padStart(2, '0');
        }
        
        // Initialiser le slider
        initSlider();
        
        // Countdown timer
        function updateCountdown() {
            const now = new Date();
            const targetDate = new Date();
            targetDate.setDate(now.getDate() + 7);
            
            const diff = targetDate - now;
            
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            
            document.getElementById('days').textContent = days.toString().padStart(2, '0');
            document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
            document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
            document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
        }
        
        // Update countdown every second
        setInterval(updateCountdown, 1000);
        updateCountdown();
        
        // Animation au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const slideText = document.querySelector('.slide-text');
            if (slideText) {
                slideText.style.animation = 'fadeInUp 0.8s ease-out';
            }
        });
    
    // ... (dans la section JavaScript)

// Compte à rebours pour la bannière principale
function updateMainCountdown() {
    const banner = document.querySelector('.promo-banner');
    if (!prochaineFinPromo) {
        if (banner) banner.style.display = 'none';
        return;
    }

    const now = new Date();
    const targetDate = new Date(prochaineFinPromo);

    if (targetDate < now) {
        if (banner) banner.style.display = 'none';
        return;
    }

    const diff = targetDate - now;
    
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);
    
    if (document.getElementById('main-days')) {
        document.getElementById('main-days').textContent = days.toString().padStart(2, '0');
        document.getElementById('main-hours').textContent = hours.toString().padStart(2, '0');
        document.getElementById('main-minutes').textContent = minutes.toString().padStart(2, '0');
        document.getElementById('main-seconds').textContent = seconds.toString().padStart(2, '0');
    }
}

// Mettre à jour le compte à rebours toutes les secondes
setInterval(updateMainCountdown, 1000);
updateMainCountdown(); // Initial call

// ... (dans la fonction initCountdowns pour les sliders)

// Fonction de mise à jour du compte à rebours pour un produit spécifique
function updateProductCountdown(promoId, endDate) {
    const now = new Date();
    const diff = endDate - now;
    
    // Éléments DOM pour ce compte à rebours
    const daysEl = document.getElementById(`days-${promoId}`);
    const hoursEl = document.getElementById(`hours-${promoId}`);
    const minutesEl = document.getElementById(`minutes-${promoId}`);
    const secondsEl = document.getElementById(`seconds-${promoId}`);
    
    // Vérifier si les éléments existent
    if (!daysEl || !hoursEl || !minutesEl || !secondsEl) return;
    
    if (diff <= 0) {
        // La promotion est terminée
        daysEl.textContent = '00';
        hoursEl.textContent = '00';
        minutesEl.textContent = '00';
        secondsEl.textContent = '00';
        
        // Ajouter un effet visuel pour indiquer la fin de la promotion
        daysEl.classList.add('countdown-flash');
        hoursEl.classList.add('countdown-flash');
        minutesEl.classList.add('countdown-flash');
        secondsEl.classList.add('countdown-flash');
        
        // Arrêter l'intervalle pour ce compte à rebours
        if (countdownIntervals[promoId]) {
            clearInterval(countdownIntervals[promoId]);
            delete countdownIntervals[promoId];
        }
        
        return;
    }
    
    // Retirer l'effet flash si la promotion est encore active
    daysEl.classList.remove('countdown-flash');
    hoursEl.classList.remove('countdown-flash');
    minutesEl.classList.remove('countdown-flash');
    secondsEl.classList.remove('countdown-flash');
    
    // Calculer le temps restant
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);
    
    // Mettre à jour les éléments
    daysEl.textContent = days.toString().padStart(2, '0');
    hoursEl.textContent = hours.toString().padStart(2, '0');
    minutesEl.textContent = minutes.toString().padStart(2, '0');
    secondsEl.textContent = seconds.toString().padStart(2, '0');
}

// ... (dans la fonction initCountdowns)

// Initialiser les comptes à rebours
function initCountdowns() {
    // Arrêter tous les intervalles existants
    for (const id in countdownIntervals) {
        clearInterval(countdownIntervals[id]);
    }
    
    // Démarrer les comptes à rebours pour chaque slide
    slides.forEach(slide => {
        if (slide.dataset.fin) {
            const promoId = slide.dataset.promoId;
            const endDate = new Date(slide.dataset.fin + 'T23:59:59');
            
            // Mettre à jour immédiatement
            updateProductCountdown(promoId, endDate);
            
            // Démarrer l'intervalle pour ce compte à rebours
            countdownIntervals[promoId] = setInterval(() => {
                updateProductCountdown(promoId, endDate);
            }, 1000);
        }
    });
}

// Gestion de la notation par étoiles
        const stars = document.querySelectorAll('.rating-star');
        const noteInput = document.getElementById('note');
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const value = parseInt(this.getAttribute('data-value'));
                noteInput.value = value;
                
                // Mettre à jour l'apparence des étoiles
                stars.forEach((s, i) => {
                    if (i < value) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
            
            star.addEventListener('mouseover', function() {
                const value = parseInt(this.getAttribute('data-value'));
                
                // Survol - prévisualisation
                stars.forEach((s, i) => {
                    if (i < value) {
                        s.classList.add('hover');
                    } else {
                        s.classList.remove('hover');
                    }
                });
            });
            
            star.addEventListener('mouseout', function() {
                // Retirer la prévisualisation au survol
                stars.forEach(s => s.classList.remove('hover'));
            });
        });
        
        // Validation du formulaire d'avis
        const avisForm = document.querySelector('.avis-form form');
        if (avisForm) {
            avisForm.addEventListener('submit', function(e) {
                if (parseInt(noteInput.value) === 0) {
                    e.preventDefault();
                    alert('<?php echo $trans['please_rate']; ?>');
                }
            });
        }
    
    </script>
    
</body>
</html>