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

// Déterminer la langue actuelle si elle n'est pas déjà définie
if (!isset($current_language)) {
    $current_language = isset($_SESSION['language']) ? $_SESSION['language'] : 'fr';
}


?>

<footer class="footer">
    <div class="footer-container">
        <!-- Section À propos -->
        <div class="footer-section">
            <h3><i class="fas fa-store"></i> TchadShop</h3>
            <p><?php echo $trans['tchadshop_description']; ?></p>
            <div class="footer-social">
                <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </div>
        
        <!-- Liens rapides -->
        <div class="footer-section">
            <h3><i class="fas fa-link"></i> <?php echo $trans['quick_links']; ?></h3>
            <ul class="footer-links">
                <li><a href="index.php"><i class="fas fa-chevron-right"></i> <?php echo $trans['home']; ?></a></li>
                <li><a href="produits.php"><i class="fas fa-chevron-right"></i> <?php echo $trans['products']; ?></a></li>
                <li><a href="promotions.php"><i class="fas fa-chevron-right"></i> <?php echo $trans['promotions']; ?></a></li>
                <li><a href="contact.php"><i class="fas fa-chevron-right"></i> <?php echo $trans['contact']; ?></a></li>
                <li><a href="?lang=<?php echo $current_language == 'fr' ? 'ar' : 'fr'; ?>">
                    <i class="fas fa-chevron-right"></i> 
                    <?php echo $current_language == 'fr' ? $trans['arabic_version'] : $trans['french_version']; ?>
                </a></li>
            </ul>
        </div>
        
        <!-- Informations -->
        <div class="footer-section">
            <h3><i class="fas fa-info-circle"></i> <?php echo $trans['information']; ?></h3>
            <ul class="footer-links">
                <li><a href="informations.php?section=conditions"><i class="fas fa-chevron-right"></i> <?php echo $trans['terms']; ?></a></li>
                <li><a href="informations.php?section=confidentialite"><i class="fas fa-chevron-right"></i> <?php echo $trans['privacy']; ?></a></li>
                <li><a href="informations.php?section=faq"><i class="fas fa-chevron-right"></i> <?php echo $trans['faq']; ?></a></li>
                <li><a href="informations.php?section=about"><i class="fas fa-chevron-right"></i> <?php echo $trans['about']; ?></a></li>
                <li><a href="informations.php?section=carrieres"><i class="fas fa-chevron-right"></i> <?php echo $trans['careers']; ?></a></li>
            </ul>
        </div>
        
        <!-- Contact -->
        <div class="footer-section">
            <h3><i class="fas fa-headset"></i> <?php echo $trans['contact_us']; ?></h3>
            <ul class="footer-links">
                <li><i class="fas fa-map-marker-alt"></i> <?php echo $trans['ndjamena_chad']; ?></li>
                <li><i class="fas fa-phone"></i> +235 XX XX XX XX</li>
                <li><i class="fas fa-envelope"></i> contact@tchadshop.td</li>
                <li><i class="fas fa-clock"></i> <?php echo $trans['business_hours']; ?></li>
                <li><i class="fas fa-comments"></i> <?php echo $trans['support_24_7']; ?></li>
            </ul>
        </div>
    </div>
    
    <div class="footer-bottom">
        <div class="payment-methods">
            <i class="fab fa-cc-visa"></i>
            <i class="fab fa-cc-mastercard"></i>
            <i class="fab fa-cc-paypal"></i>
            <i class="fab fa-cc-apple-pay"></i>
        </div>
        <p>&copy; <?= date('Y') ?> TchadShop. <?php echo $trans['all_rights_reserved']; ?></p>
    </div>
</footer>

<style>
.footer {
    background: linear-gradient(135deg, #6a1b9a 0%, #8e24aa 100%);
    color: white;
    padding: 60px 0 30px;
    margin-top: 50px;
    position: relative;
    overflow: hidden;
}

.footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background: linear-gradient(90deg, #ff6b6b, #4c956c, #2c6e49);
}

.footer-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 40px;
}

.footer-section {
    position: relative;
    padding-bottom: 20px;
}
.footer-section p {
    color: rgba(0, 0, 0, 0.8);

}

.footer-section h3 {
    font-size: 1.4rem;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
    padding-bottom: 15px;
}

.footer-section h3::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60px;
    height: 3px;
    background: linear-gradient(to right, #ff6b6b, #4c956c);
    border-radius: 3px;
}

.footer-links {
    list-style: none;
}

.footer-links li {
    margin-bottom: 15px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.footer-links i {
    color: #ff6b6b;
    font-size: 1.1rem;
    min-width: 24px;
    margin-top: 4px;
}

.footer-links a, .footer-links li {
    color: rgba(255, 255, 255, 0.85);
    text-decoration: none;
    transition: all 0.3s ease;
    line-height: 1.6;
}

.footer-links a:hover {
    color: white;
    transform: translateX(5px);
}

.footer-social {
    display: flex;
    gap: 15px;
    margin-top: 25px;
}

.social-icon {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.social-icon:hover {
    background: #4c956c;
    transform: translateY(-5px) scale(1.1);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.footer-bottom {
    text-align: center;
    padding-top: 40px;
    margin-top: 30px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.7);
    font-size: 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.payment-methods {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    font-size: 2rem;
    color: rgba(255, 255, 255, 0.8);
}

.footer-bottom p {
    margin-top: 10px;
    color: rgba(0, 0, 0, 0.7);
}

/* Effet vague animé */
.wave-effect {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    overflow: hidden;
    line-height: 0;
    transform: rotate(180deg);
}

.wave-effect svg {
    position: relative;
    display: block;
    width: calc(100% + 1.3px);
    height: 80px;
}

.wave-effect .shape-fill {
    fill: rgba(0, 0, 0, 0.05);
}

/* Responsive */
@media (max-width: 992px) {
    .footer-container {
        grid-template-columns: repeat(2, 1fr);
        gap: 30px;
    }
}

@media (max-width: 768px) {
    .footer-container {
        grid-template-columns: 1fr;
    }
    
    .footer-section {
        text-align: center;
    }
    
    .footer-section h3 {
        justify-content: center;
    }
    
    .footer-section h3::after {
        left: 50%;
        transform: translateX(-50%);
    }
    
    .footer-links li {
        justify-content: center;
    }
    
    .footer-social {
        justify-content: center;
    }
    
    .payment-methods {
        flex-wrap: wrap;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .footer {
        padding: 50px 0 20px;
    }
    
    .footer-section h3 {
        font-size: 1.3rem;
    }
    
    .footer-links li {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
}
</style>