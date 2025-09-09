<?php
require_once 'init.php';
require_once('header.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) die("Erreur de connexion : " . $conn->connect_error);

// Déterminer la locale JavaScript appropriée
$js_locale = 'fr-FR'; // par défaut
if ($lang == 'en') {
    $js_locale = 'en-US';
} elseif ($lang == 'ar') {
    $js_locale = 'ar-SA';
}

// Variables de session pour les modales
$isConnected = isset($_SESSION['client_id']);
$telephone_connecte = '';
if ($isConnected) {
    $client_id = $_SESSION['client_id'];
    $res = $conn->query("SELECT telephone FROM clients WHERE id = $client_id LIMIT 1");
    $telephone_connecte = $res->fetch_assoc()['telephone'] ?? '';
}
echo "<script>const isConnected = " . ($isConnected ? 'true' : 'false') . ";</script>";
echo "<script>let telephone = '" . $telephone_connecte . "';</script>";

// Fonctions pour le design et la logique des produits
function getProduitImages($produit_id, $conn) {
    $sql = "SELECT image FROM images_produit WHERE produit_id = ? ORDER BY id ASC";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) return ["https://via.placeholder.com/400x300?text=Erreur+SQL"];
    $stmt->bind_param("i", $produit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $images = [];
    while ($row = $result->fetch_assoc()) $images[] = $row['image'];
    if (empty($images)) $images[] = "https://via.placeholder.com/400x300?text=TchadShop";
    return $images;
}

function formatPrix($prix) {
    return number_format($prix, 0, ',', ' ') . ' FCFA';
}

function calculerPrixPromotion($prix, $promotion) {
    $aujourdhui = date('Y-m-d');
    $conn = new mysqli("localhost", "root", "", "tchadshop_db");
    if ($conn->connect_error) die("Erreur de connexion : " . $conn->connect_error);
    $sql_promo = "SELECT date_debut_promo, date_fin_promo, prix_promotion FROM produits WHERE prix = ? AND promotion = ?";
    $stmt = $conn->prepare($sql_promo);
    if ($stmt === false) return $prix;
    $stmt->bind_param("di", $prix, $promotion);
    $stmt->execute();
    $result_promo = $stmt->get_result();
    $promo_data = $result_promo->fetch_assoc();
    $stmt->close();

    $promotionActive = false;
    if ($promotion > 0 && 
        (($promo_data['date_debut_promo'] === null && $promo_data['date_fin_promo'] === null) || 
         ($aujourdhui >= $promo_data['date_debut_promo'] && $aujourdhui <= $promo_data['date_fin_promo']))) {
        $promotionActive = true;
    }

    if ($promotionActive) {
        return $promo_data['prix_promotion'] !== null ? $promo_data['prix_promotion'] : $prix * (1 - $promotion / 100);
    }
    return $prix;
}

// Logique de recherche
$search_query = '';
$results = [];

if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
    $search_query = trim($_GET['q']);
    $clean_query = preg_replace('/[^a-zA-Z0-9\s]/', '', $search_query);

    $sql_search = "SELECT * FROM produits WHERE statut = 'disponible' AND (nom LIKE ? OR description LIKE ?)";
    $stmt = $conn->prepare($sql_search);
    if ($stmt === false) {
        echo "<p>Erreur lors de la préparation de la requête: " . $conn->error . "</p>";
    } else {
        $like_query = "%" . $clean_query . "%";
        $stmt->bind_param("ss", $like_query, $like_query);
        $stmt->execute();
        $result_search = $stmt->get_result();
        
        if ($result_search) {
            while ($row = $result_search->fetch_assoc()) {
                $row['images'] = getProduitImages($row['id'], $conn);
                $row['prixFinal'] = calculerPrixPromotion($row['prix'], $row['promotion']);
                $results[] = $row;
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats de Recherche - TchadShop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css"> 
    <style>
        /* Styles CSS de votre page de produits */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #f9f7ff 0%, #eef2ff 100%); color: #333; min-height: 100vh; padding-top: 10px; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .section-title { text-align: center; margin: 30px 0; padding: 20px; }
        .section-title h2 { font-size: 2.5rem; color: #6a1b9a; margin-bottom: 15px; position: relative; display: inline-block; }
        .section-title h2:after { content: ''; display: block; width: 80px; height: 4px; background: linear-gradient(135deg, #6a1b9a 0%, #8e24aa 100%); border-radius: 2px; margin: 15px auto 0; }
        .produits-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; padding: 20px; }
        .produit { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08); transition: transform 0.3s ease, box-shadow 0.3s ease; position: relative; }
        .produit:hover { transform: translateY(-10px); box-shadow: 0 15px 40px rgba(106, 27, 154, 0.2); }
        .stock-badge { position: absolute; top: 15px; left: 15px; background: #4CAF50; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; z-index: 2; }
        .promo-badge { position: absolute; top: 15px; right: 15px; background: #e74c3c; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; z-index: 2; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.05); } 100% { transform: scale(1); } }
        .carousel { position: relative; height: 250px; overflow: hidden; }
        .carousel img { width: 100%; height: 100%; object-fit: cover; display: none; transition: opacity 0.5s ease; position: absolute; top: 0; left: 0; }
        .carousel img.active { display: block; }
        .carousel .arrow { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.4); color: white; border: none; font-size: 18px; padding: 8px 12px; cursor: pointer; border-radius: 50%; z-index: 10; transition: all 0.3s ease; opacity: 0; }
        .carousel:hover .arrow { opacity: 1; }
        .carousel .arrow:hover { background: rgba(0,0,0,0.7); transform: translateY(-50%) scale(1.1); }
        .carousel .prev { left: 15px; }
        .carousel .next { right: 15px; }
        .carousel-dots { position: absolute; bottom: 10px; left: 0; right: 0; display: flex; justify-content: center; z-index: 5; }
        .carousel-dot { width: 8px; height: 8px; border-radius: 50%; background: rgba(255, 255, 255, 0.5); margin: 0 4px; cursor: pointer; transition: all 0.3s ease; }
        .carousel-dot.active { background: #fff; transform: scale(1.2); }
        .product-info { padding: 20px; text-align: center; }
        .product-info h3 { font-size: 1.4rem; color: #6a1b9a; margin-bottom: 10px; font-weight: 600; }
        .product-price { font-size: 1.3rem; color: #8e24aa; font-weight: 700; margin-bottom: 15px; }
        .old-price { text-decoration: line-through; color: #999; font-size: 1rem; margin-right: 8px; }
        .saving { color: #27ae60; font-size: 0.9rem; font-weight: 600; margin-top: 5px; }
        .product-description { color: #666; line-height: 1.6; margin-bottom: 20px; font-size: 0.95rem; height: 60px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; }
        .btn-commander { display: block; background: linear-gradient(135deg, #6a1b9a 0%, #8e24aa 100%); color: white; padding: 12px 20px; border-radius: 50px; font-weight: 600; text-decoration: none; text-align: center; transition: all 0.3s ease; border: none; cursor: pointer; font-size: 1.05rem; width: 100%; }
        .btn-commander:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(106, 27, 154, 0.3); }
        .btn-commander i { margin-right: 8px; }
        
        /* Styles pour les modales */
        .modal-overlay, .modal-content { position: fixed; display: none; z-index: 1001; }
        .modal-overlay { top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); }
        .modal-content { top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 20px; border-radius: 10px; width: 90%; max-width: 400px; max-height: 90vh; overflow-y: auto; }
        .modal-content img { max-width: 100%; margin-bottom: 10px; }
        .modal-header { text-align: center; margin-bottom: 25px; }
        .modal-header h3 { font-size: 1.8rem; color: #6a1b9a; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .modal-header p { color: #666; margin-top: 10px; }
        .modal-body { margin-bottom: 25px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #555; }
        .form-control { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 10px; font-size: 1rem; transition: border-color 0.3s ease; }
        .form-control:focus { border-color: #8e24aa; outline: none; box-shadow: 0 0 0 3px rgba(138, 43, 226, 0.2); }
        .modal-actions { display: flex; gap: 15px; justify-content: center; }
        .btn-modal { padding: 12px 30px; border-radius: 50px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; border: none; font-size: 1rem; display: flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(135deg, #6a1b9a 0%, #8e24aa 100%); color: white; }
        .btn-secondary { background: #f1f1f1; color: #555; }
        .btn-modal:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); }
        .whatsapp-btn { display: inline-block; background: #25D366; color: white; padding: 15px; border-radius: 10px; text-align: center; font-weight: 600; text-decoration: none; margin: 15px 0; transition: all 0.3s ease; width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .whatsapp-btn:hover { background: #128C7E; transform: translateY(-3px); box-shadow: 0 5px 15px rgba(37, 211, 102, 0.3); }
        .modal-image { width: 100%; height: 200px; object-fit: cover; border-radius: 10px; margin-bottom: 15px; }
        .quantity-control { display: flex; align-items: center; justify-content: center; gap: 15px; margin: 20px 0; }
        .quantity-btn { width: 40px; height: 40px; border-radius: 50%; background: #f1f1f1; border: none; font-size: 1.2rem; cursor: pointer; transition: all 0.3s ease; }
        .quantity-btn:hover { background: #e1e1e1; }
        .quantity-input { width: 60px; text-align: center; padding: 10px; border: 1px solid #ddd; border-radius: 10px; font-size: 1.1rem; }
        .delivery-date { margin: 15px 0; padding: 10px 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee; }
        .delivery-date label { display: block; margin-bottom: 8px; font-weight: 500; color: #555; }
        .delivery-date input { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 10px; font-size: 1rem; background: #f9f9f9; }
        /* Responsive */
        @media (max-width: 992px) { .produits-container { grid-template-columns: repeat(auto-fit, minmin(280px, 1fr)); } }
        @media (max-width: 768px) { .produits-container { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); padding: 10px; } .carousel { height: 220px; } .modal-actions { flex-direction: column; gap: 10px; } .btn-modal { width: 100%; } .section-title h2 { font-size: 2.2rem; } }
        @media (max-width: 576px) { 
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
            .section-title h2 { font-size: 2rem; } 
            .section-title p { font-size: 1rem; } 
            #step3 .modal-image { display: none; } 
            #step3 .modal-actions { display: flex; flex-direction: row; justify-content: space-between; gap: 10px; } 
             #step3 .modal-actions .btn-modal { flex-grow: 1; width: auto; } 
            }
        @media (max-width: 480px){ 
            .carousel { 
                height: 240px;
             }
            }
    </style>
</head>
<body>

  <div class="container content-section" style="margin-top: 150px;">
    <div class="section-title">
        <h2><?php echo $trans['search_results_for']; ?> "<?php echo htmlspecialchars($search_query); ?>"</h2>
    </div>
    
    <?php if (empty($search_query)): ?>
        <p style="text-align: center; font-size: 1.2rem; color: #888; padding: 50px;"><?php echo $trans['please_enter_search_term']; ?></p>
    <?php elseif (empty($results)): ?>
        <p style="text-align: center; font-size: 1.2rem; color: #888; padding: 50px;"><?php echo $trans['no_products_found']; ?> 😔</p>
    <?php else: ?>
        <div class="produits-container">
            <?php foreach ($results as $index => $produit): ?>
                <div class="produit">
                    <?php if ($produit['stock'] > 10): ?>
                        <div class="stock-badge"><?php echo $trans['in_stock']; ?></div>
                    <?php elseif ($produit['stock'] > 0): ?>
                        <div class="stock-badge" style="background: #ff9800;"><?php echo $trans['limited_stock']; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($produit['promotion'] > 0 && 
                               (date('Y-m-d') >= $produit['date_debut_promo'] && date('Y-m-d') <= $produit['date_fin_promo'])): ?>
                        <div class="promo-badge"><?php echo $trans['promo']; ?></div>
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
                        <p class="product-price">
                            <?php if ($produit['promotion'] > 0 && 
                                       (date('Y-m-d') >= $produit['date_debut_promo'] && date('Y-m-d') <= $produit['date_fin_promo'])): ?>
                                <span class="old-price"><?= formatPrix($produit['prix']) ?></span>
                                <span><?= formatPrix($produit['prixFinal']) ?></span>
                            <?php else: ?>
                                <span><?= formatPrix($produit['prix']) ?></span>
                            <?php endif; ?>
                        </p>
                        <p class="product-description"><?= htmlspecialchars($produit['description']) ?></p>
                        <button class="btn-commander" onclick="ouvrirCommande(
                            <?= $produit['id'] ?>,
                            '<?= htmlspecialchars($produit['nom']) ?>',
                            '<?= htmlspecialchars($produit['images'][0]) ?>',
                            '<?= $produit['prixFinal'] ?>',
                            `<?= htmlspecialchars($produit['description']) ?>`,
                            '<?= $produit['prix'] ?>',
                            <?= ($produit['promotion'] > 0 && (date('Y-m-d') >= $produit['date_debut_promo'] && date('Y-m-d') <= $produit['date_fin_promo'])) ? 'true' : 'false' ?>
                        )">
                            <i class="fas fa-shopping-cart"></i> <?php echo $trans['order']; ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
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
<script>
    // Définir les traductions JavaScript
    const jsTranslations = {
        invalid_phone: "<?php echo $trans['invalid_phone']; ?>",
        location_error: "<?php echo $trans['location_error']; ?>",
        order_success: "<?php echo $trans['order_success']; ?>",
        order_error: "<?php echo $trans['order_error']; ?>",
        hello_order: "<?php echo $trans['hello_order']; ?>"
    };

    // Le code JavaScript complet de votre page de produits
    // Fonctionnalités pour les carrousels, les modales, et les commandes
    let carousels = [];
    let timers = [];
    document.querySelectorAll('.carousel').forEach((c, i) => {
        const imgs = c.querySelectorAll('img');
        carousels[i] = { images: imgs, currentIndex: 0, timer: null };
        if (imgs.length > 1) startCarousel(i);
        c.addEventListener('mouseenter', () => { if (carousels[i].timer) { clearInterval(carousels[i].timer); carousels[i].timer = null; } });
        c.addEventListener('mouseleave', () => { if (imgs.length > 1 && !carousels[i].timer) startCarousel(i); });
    });
    function startCarousel(index) {
        if (carousels[index].timer) clearInterval(carousels[index].timer);
        carousels[index].timer = setInterval(() => nextSlide(index), 2000);
    }
    function showImage(index, i) {
        carousels[index].images.forEach((img, j) => img.classList.toggle('active', j === i));
        carousels[index].currentIndex = i;
        const dots = document.querySelectorAll(`#dots-${index} .carousel-dot`);
        dots.forEach((dot, j) => dot.classList.toggle('active', j === i));
    }
    function showSlide(index, slideIndex) {
        if (carousels[index].images.length > 1) startCarousel(index);
        showImage(index, slideIndex);
    }
    function prevSlide(i) {
        if (carousels[i].images.length > 1) startCarousel(i);
        const c = carousels[i];
        const n = (c.currentIndex - 1 + c.images.length) % c.images.length;
        showImage(i, n);
    }
    function nextSlide(i) {
        if (carousels[i].images.length > 1) startCarousel(i);
        const c = carousels[i];
        const n = (c.currentIndex + 1) % c.images.length;
        showImage(i, n);
    }
    let produitId = null, produitNom = '', produitImage = '', produitPrixFinal = '', produitDescription = '';
    let produitPrixOriginal = null, produitPromotion = false;
    let latitude = null, longitude = null;
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
            alert(jsTranslations.invalid_phone);
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
            alert(jsTranslations.location_error);
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
        mettreAJourLienWhatsApp();
    }
    function ouvrirStep3() {
        document.getElementById('modalImage').src = produitImage;
        document.getElementById('produitNom').textContent = produitNom;
        if (produitPromotion) {
            document.getElementById('produitPrix').innerHTML = `<span style="text-decoration: line-through; color: #999; font-size: 1rem;">${parseFloat(produitPrixOriginal).toLocaleString('fr-FR')} FCFA</span><span style="color: #e74c3c; font-weight: 700;">${parseFloat(produitPrixFinal).toLocaleString('fr-FR')} FCFA</span>`;
        } else {
            document.getElementById('produitPrix').textContent = parseFloat(produitPrixFinal).toLocaleString('fr-FR') + " FCFA";
        }
        document.getElementById('produitDescription').textContent = produitDescription;
        const dateInput = document.getElementById('dateLivraison');
        const today = new Date();
        const minDate = new Date();
        minDate.setDate(today.getDate() + 1);
        const maxDate = new Date();
        maxDate.setDate(today.getDate() + 30);
        dateInput.min = formatDate(minDate);
        dateInput.max = formatDate(maxDate);
        const defaultDate = new Date();
        defaultDate.setDate(today.getDate() + 3);
        dateInput.value = formatDate(defaultDate);
        mettreAJourLienWhatsApp();
        document.getElementById('quantite').addEventListener('input', mettreAJourLienWhatsApp);
        dateInput.addEventListener('change', mettreAJourLienWhatsApp);
        document.getElementById('step3').style.display = 'block';
    }
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

            const imageUrl = "http://192.168.102.1/AmmaShop/" + produitImage.replace(/^\/+/, '');

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
                message += "<?php echo $trans['unit_price']; ?>: " + parseFloat(produitPrixFinal).toLocaleString('fr-FR') + " FCFA (<?php echo $trans['promo']; ?>)\n";
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
            const quantite = document.getElementById('quantite').value || 1;

            // AJOUT CRITIQUE : Utiliser la variable téléphone globale
            const telValue = isConnected && telephone ? telephone : document.getElementById('numeroClient')?.value;

            // Champs cachés pour transmettre les données
            const fields = {
                produit_id: produitId,
                telephone: telValue, // <-- Correction ici
                quantite: quantite,
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
 // Empêcher la fermeture des modales au clic extérieur
        document.addEventListener('click', function(event) {
            // Si le clic est en dehors des modales et de l'overlay
            const isClickInsideModal = Array.from(document.querySelectorAll('.modal-content')).some(modal => 
                modal.contains(event.target)
            );
            
            const isClickOnOverlay = event.target === document.getElementById('overlay');
            
            // Ne rien faire si le clic est à l'intérieur d'une modale ou sur l'overlay
            if (isClickInsideModal || isClickOnOverlay) return;
            
            // Si les modales sont ouvertes, ne pas les fermer
            if (document.getElementById('overlay').style.display === 'flex') {
                event.stopPropagation();
            }
        });
</script>
<?php
$conn->close();
?>
</body>
</html>