<?php
require_once 'init.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Vérifier si la connexion existe déjà (peut-être déjà établie par header.php)
if (!isset($conn)) {
    $conn = new mysqli("localhost", "root", "", "tchadshop_db");
    if ($conn->connect_error) die($trans['connection_error'] . " : " . $conn->connect_error);
}


?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informations - TchadShop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* RESET ET STYLES GÉNÉRAUX */
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
            line-height: 1.6;
            display: flex;
            flex-direction: column;
        }

        /* HEADER STYLES - Doit correspondre à votre header existant */
        /* ... Votre style de header existant ... */
        
        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            padding-top: 110px; /* Ajuster selon la hauteur de votre header */
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-title {
            text-align: center;
            margin-bottom: 40px;
            color: #6a1b9a;
            position: relative;
            padding-bottom: 20px;
        }
        
        .page-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(to right, #6a1b9a, #4c956c);
            border-radius: 2px;
        }

        /* ONGLETS */
        .info-tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .tab-btn {
            background: white;
            border: none;
            padding: 12px 20px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
        }

        .tab-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #6a1b9a 0%, #8e24aa 100%);
            color: white;
        }

        /* CONTENU DES SECTIONS */
        .info-content {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            padding: 40px;
            margin-bottom: 40px;
        }

        .content-section {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .content-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-title {
            color: #6a1b9a;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1.8rem;
        }

        .section-title i {
            background: linear-gradient(135deg, #6a1b9a 0%, #8e24aa 100%);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .info-text {
            margin-bottom: 30px;
            color: #555;
            line-height: 1.8;
            font-size: 1.05rem;
        }

        .info-text h3 {
            color: #6a1b9a;
            margin: 25px 0 15px;
            font-size: 1.4rem;
        }

        .info-list {
            margin: 20px 0 30px;
            padding-left: 25px;
        }

        .info-list li {
            margin-bottom: 12px;
            padding-left: 10px;
            position: relative;
        }

        .info-list li::before {
            content: '•';
            color: #6a1b9a;
            font-size: 1.4rem;
            position: absolute;
            left: -20px;
            top: -5px;
        }
        
        /* BOUTONS D'ACTION */
        .action-btn {
            display: inline-block;
            background: linear-gradient(135deg, #6a1b9a 0%, #8e24aa 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            margin-top: 15px;
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(106, 27, 154, 0.3);
        }
        
        .action-btn i {
            margin-right: 8px;
        }

        /* FAQ */
        .faq-container {
            margin-top: 20px;
        }
        
        .faq-item {
            margin-bottom: 15px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }
        
        .faq-question {
            background: linear-gradient(135deg, #f9f5ff 0%, #f0ebff 100%);
            padding: 20px 25px;
            font-weight: 600;
            color: #6a1b9a;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .faq-question:hover {
            background: linear-gradient(135deg, #f0e5ff 0%, #e6daff 100%);
        }
        
        .faq-question i {
            transition: transform 0.3s ease;
        }
        
        .faq-question.active i {
            transform: rotate(180deg);
        }
        
        .faq-answer {
            padding: 0 25px;
            max-height: 0;
            overflow: hidden;
            transition: all 0.4s ease;
            background: white;
        }
        
        .faq-question.active + .faq-answer {
            padding: 25px;
            max-height: 1000px;
        }
        
        /* POSTES */
        .job-position {
            background: linear-gradient(135deg, #f9f5ff 0%, #f0ebff 100%);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #6a1b9a;
        }
        
        .job-position h4 {
            color: #6a1b9a;
            margin-bottom: 10px;
        }
        
        .job-position p {
            margin-bottom: 5px;
        }
        
        /* RESPONSIVE */
        @media (max-width: 992px) {
            .info-content {
                padding: 30px;
            }
            
            .section-title {
                font-size: 1.6rem;
            }
            
            .tab-btn {
                padding: 10px 15px;
                font-size: 0.9rem;
            }
            
            .page-title {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding-top: 140px; /* Ajuster selon la hauteur de votre header mobile */
            }
            
            .info-content {
                padding: 25px 20px;
            }
            
            .section-title {
                font-size: 1.5rem;
            }
            
            .section-title i {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            
            .page-title {
                font-size: 1.6rem;
            }
            
            .info-tabs {
                flex-direction: column;
                align-items: center;
            }
            
            .tab-btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 1.4rem;
            }
            
            .section-title {
                font-size: 1.3rem;
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .section-title i {
                margin-bottom: 10px;
            }
            
            .info-content {
                padding: 20px 15px;
            }
        }
        
        /* FOOTER STYLES - Doit correspondre à votre footer existant */
        /* ... Votre style de footer existant ... */
    </style>
</head>
<body>
    <!-- HEADER - Doit être inclus -->
    <?php include 'header.php'; ?>

    <main class="main-content">
    <div class="container">
        <h1 class="page-title">
            <i class="fas fa-info-circle"></i> <?php echo $trans['information_about_tchadshop']; ?>
        </h1>
        
        <div class="info-tabs">
            <button class="tab-btn active" data-tab="conditions"><i class="fas fa-file-contract"></i> <?php echo $trans['terms']; ?></button>
            <button class="tab-btn" data-tab="confidentialite"><i class="fas fa-lock"></i> <?php echo $trans['privacy']; ?></button>
            <button class="tab-btn" data-tab="faq"><i class="fas fa-question-circle"></i> <?php echo $trans['faq']; ?></button>
            <button class="tab-btn" data-tab="about"><i class="fas fa-store"></i> <?php echo $trans['about']; ?></button>
            <button class="tab-btn" data-tab="carrieres"><i class="fas fa-briefcase"></i> <?php echo $trans['careers']; ?></button>
        </div>
        
        <div class="info-content">
            <!-- Section Conditions d'utilisation -->
            <div class="content-section active" id="conditions-section">
                <h2 class="section-title"><i class="fas fa-file-contract"></i> <?php echo $trans['terms_of_use']; ?></h2>
                
                <div class="info-text">
                    <p><?php echo $trans['terms_intro']; ?></p>
                    
                    <h3><?php echo $trans['acceptance_of_terms']; ?></h3>
                    <p><?php echo $trans['terms_acceptance']; ?></p>
                    
                    <h3><?php echo $trans['user_account']; ?></h3>
                    <p><?php echo $trans['account_responsibility']; ?></p>
                    
                    <h3><?php echo $trans['intellectual_property']; ?></h3>
                    <p><?php echo $trans['ip_rights']; ?></p>
                    
                    <h3><?php echo $trans['orders_and_payments']; ?></h3>
                    <ul class="info-list">
                        <li><?php echo $trans['order_commitment']; ?></li>
                        <li><?php echo $trans['order_refusal']; ?></li>
                        <li><?php echo $trans['price_changes']; ?></li>
                    </ul>
                    
                    <h3><?php echo $trans['liability_limitation']; ?></h3>
                    <p><?php echo $trans['liability_text']; ?></p>
                    
                    <a href="contact.php" class="action-btn">
                        <i class="fas fa-headset"></i> <?php echo $trans['contact_us']; ?>
                    </a>
                </div>
            </div>
            
            <!-- Section Confidentialité -->
            <div class="content-section" id="confidentialite-section">
                <h2 class="section-title"><i class="fas fa-lock"></i> <?php echo $trans['privacy_policy']; ?></h2>
                
                <div class="info-text">
                    <p><?php echo $trans['privacy_intro']; ?></p>
                    
                    <h3><?php echo $trans['collected_information']; ?></h3>
                    <p><?php echo $trans['collected_info_desc']; ?></p>
                    <ul class="info-list">
                        <li><?php echo $trans['create_account']; ?></li>
                        <li><?php echo $trans['make_purchase']; ?></li>
                        <li><?php echo $trans['contact_support']; ?></li>
                        <li><?php echo $trans['participate_survey']; ?></li>
                    </ul>
                    
                    <h3><?php echo $trans['information_usage']; ?></h3>
                    <p><?php echo $trans['info_usage_desc']; ?></p>
                    <ul class="info-list">
                        <li><?php echo $trans['process_orders']; ?></li>
                        <li><?php echo $trans['personalize_experience']; ?></li>
                        <li><?php echo $trans['improve_products']; ?></li>
                        <li><?php echo $trans['send_promotions']; ?></li>
                        <li><?php echo $trans['respond_requests']; ?></li>
                    </ul>
                    
                    <h3><?php echo $trans['information_protection']; ?></h3>
                    <p><?php echo $trans['info_protection_desc']; ?></p>
                    
                    <h3><?php echo $trans['information_sharing']; ?></h3>
                    <p><?php echo $trans['info_sharing_desc']; ?></p>
                    <ul class="info-list">
                        <li><?php echo $trans['service_providers']; ?></li>
                        <li><?php echo $trans['legal_requirements']; ?></li>
                        <li><?php echo $trans['protect_rights']; ?></li>
                    </ul>
                    
                    <h3><?php echo $trans['your_rights']; ?></h3>
                    <p><?php echo $trans['your_rights_desc']; ?></p>
                    <ul class="info-list">
                        <li><?php echo $trans['access_data']; ?></li>
                        <li><?php echo $trans['correct_data']; ?></li>
                        <li><?php echo $trans['delete_data']; ?></li>
                        <li><?php echo $trans['object_processing']; ?></li>
                    </ul>
                    
                    <a href="contact.php" class="action-btn">
                        <i class="fas fa-user-shield"></i> <?php echo $trans['exercise_rights']; ?>
                    </a>
                </div>
            </div>
            
            <!-- Section FAQ -->
            <div class="content-section" id="faq-section">
                <h2 class="section-title"><i class="fas fa-question-circle"></i> <?php echo $trans['faq_full']; ?></h2>
                
                <div class="info-text">
                    <p><?php echo $trans['faq_intro']; ?></p>
                    
                    <div class="faq-container">
                        <div class="faq-item">
                            <div class="faq-question">
                                <span><?php echo $trans['faq_create_account']; ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p><?php echo $trans['faq_create_account_desc']; ?></p>
                                <ol>
                                    <li><?php echo $trans['faq_step1']; ?></li>
                                    <li><?php echo $trans['faq_step2']; ?></li>
                                    <li><?php echo $trans['faq_step3']; ?></li>
                                    <li><?php echo $trans['faq_step4']; ?></li>
                                    <li><?php echo $trans['faq_step5']; ?></li>
                                </ol>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <span><?php echo $trans['faq_payment_methods']; ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p><?php echo $trans['faq_payment_methods_desc']; ?></p>
                                <ul>
                                    <li><?php echo $trans['faq_credit_card']; ?></li>
                                    <li><?php echo $trans['faq_mobile_payment']; ?></li>
                                    <li><?php echo $trans['faq_cash_on_delivery']; ?></li>
                                    <li><?php echo $trans['faq_bank_transfer']; ?></li>
                                </ul>
                                <p><?php echo $trans['faq_payment_security']; ?></p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <span><?php echo $trans['faq_return_policy']; ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p><?php echo $trans['faq_return_policy_desc']; ?></p>
                                <ul>
                                    <li><?php echo $trans['faq_return_condition1']; ?></li>
                                    <li><?php echo $trans['faq_return_condition2']; ?></li>
                                    <li><?php echo $trans['faq_return_condition3']; ?></li>
                                </ul>
                                <p><?php echo $trans['faq_refund_process']; ?></p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <span><?php echo $trans['faq_delivery_time']; ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p><?php echo $trans['faq_delivery_time_desc']; ?></p>
                                <ul>
                                    <li><?php echo $trans['faq_delivery_ndjamena']; ?></li>
                                    <li><?php echo $trans['faq_delivery_cities']; ?></li>
                                    <li><?php echo $trans['faq_delivery_rural']; ?></li>
                                </ul>
                                <p><?php echo $trans['faq_delivery_notice']; ?></p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <span><?php echo $trans['faq_contact_support']; ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p><?php echo $trans['faq_contact_support_desc']; ?></p>
                                <ul>
                                    <li><?php echo $trans['faq_contact_phone']; ?></li>
                                    <li><?php echo $trans['faq_contact_email']; ?></li>
                                    <li><?php echo $trans['faq_contact_chat']; ?></li>
                                    <li><?php echo $trans['faq_contact_social']; ?></li>
                                </ul>
                                <p><?php echo $trans['faq_contact_response']; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <a href="contact.php" class="action-btn">
                        <i class="fas fa-headset"></i> <?php echo $trans['ask_another_question']; ?>
                    </a>
                </div>
            </div>
            
            <!-- Section À propos -->
            <div class="content-section" id="about-section">
                <h2 class="section-title"><i class="fas fa-store"></i> <?php echo $trans['about_us']; ?></h2>
                
                <div class="info-text">
                    <p><?php echo $trans['about_intro']; ?></p>
                    
                    <h3><?php echo $trans['our_mission']; ?></h3>
                    <p><?php echo $trans['mission_text']; ?></p>
                    
                    <h3><?php echo $trans['our_vision']; ?></h3>
                    <p><?php echo $trans['vision_text']; ?></p>
                    
                    <h3><?php echo $trans['our_values']; ?></h3>
                    <ul class="info-list">
                        <li><strong><?php echo $trans['integrity']; ?>:</strong> <?php echo $trans['integrity_desc']; ?></li>
                        <li><strong><?php echo $trans['innovation']; ?>:</strong> <?php echo $trans['innovation_desc']; ?></li>
                        <li><strong><?php echo $trans['customer_service']; ?>:</strong> <?php echo $trans['customer_service_desc']; ?></li>
                        <li><strong><?php echo $trans['responsibility']; ?>:</strong> <?php echo $trans['responsibility_desc']; ?></li>
                        <li><strong><?php echo $trans['accessibility']; ?>:</strong> <?php echo $trans['accessibility_desc']; ?></li>
                    </ul>
                    
                    <h3><?php echo $trans['our_history']; ?></h3>
                    <p><?php echo $trans['history_text1']; ?></p>
                    <p><?php echo $trans['history_text2']; ?></p>
                    
                    <a href="contact.php" class="action-btn">
                        <i class="fas fa-handshake"></i> <?php echo $trans['become_partner']; ?>
                    </a>
                </div>
            </div>
            
            <!-- Section Carrières -->
            <div class="content-section" id="carrieres-section">
                <h2 class="section-title"><i class="fas fa-briefcase"></i> <?php echo $trans['careers_at_tchadshop']; ?></h2>
                
                <div class="info-text">
                    <p><?php echo $trans['careers_intro']; ?></p>
                    
                    <h3><?php echo $trans['why_work_at_tchadshop']; ?></h3>
                    <ul class="info-list">
                        <li><strong><?php echo $trans['significant_impact']; ?>:</strong> <?php echo $trans['impact_desc']; ?></li>
                        <li><strong><?php echo $trans['dynamic_environment']; ?>:</strong> <?php echo $trans['environment_desc']; ?></li>
                        <li><strong><?php echo $trans['professional_development']; ?>:</strong> <?php echo $trans['development_desc']; ?></li>
                        <li><strong><?php echo $trans['company_culture']; ?>:</strong> <?php echo $trans['culture_desc']; ?></li>
                        <li><strong><?php echo $trans['competitive_benefits']; ?>:</strong> <?php echo $trans['benefits_desc']; ?></li>
                    </ul>
                    
                    <h3><?php echo $trans['available_positions']; ?></h3>
                    <p><?php echo $trans['positions_intro']; ?></p>
                    
                    <div class="job-position">
                        <h4><?php echo $trans['full_stack_developer']; ?></h4>
                        <p><strong><?php echo $trans['type']; ?>:</strong> <?php echo $trans['full_time']; ?></p>
                        <p><strong><?php echo $trans['location']; ?>:</strong> <?php echo $trans['ndjamena']; ?></p>
                        <p><strong><?php echo $trans['description']; ?>:</strong> <?php echo $trans['developer_desc']; ?></p>
                        <a href="contact.php?subject=<?php echo urlencode($trans['application_subject_dev']); ?>" class="action-btn">
                            <i class="fas fa-paper-plane"></i> <?php echo $trans['apply_now']; ?>
                        </a>
                    </div>
                    
                    <div class="job-position">
                        <h4><?php echo $trans['digital_marketing_manager']; ?></h4>
                        <p><strong><?php echo $trans['type']; ?>:</strong> <?php echo $trans['full_time']; ?></p>
                        <p><strong><?php echo $trans['location']; ?>:</strong> <?php echo $trans['ndjamena']; ?></p>
                        <p><strong><?php echo $trans['description']; ?>:</strong> <?php echo $trans['marketing_desc']; ?></p>
                        <a href="contact.php?subject=<?php echo urlencode($trans['application_subject_marketing']); ?>" class="action-btn">
                            <i class="fas fa-paper-plane"></i> <?php echo $trans['apply_now']; ?>
                        </a>
                    </div>
                    
                    <div class="job-position">
                        <h4><?php echo $trans['customer_service_agent']; ?></h4>
                        <p><strong><?php echo $trans['type']; ?>:</strong> <?php echo $trans['full_time']; ?></p>
                        <p><strong><?php echo $trans['location']; ?>:</strong> <?php echo $trans['ndjamena']; ?></p>
                        <p><strong><?php echo $trans['description']; ?>:</strong> <?php echo $trans['service_agent_desc']; ?></p>
                        <a href="contact.php?subject=<?php echo urlencode($trans['application_subject_service']); ?>" class="action-btn">
                            <i class="fas fa-paper-plane"></i> <?php echo $trans['apply_now']; ?>
                        </a>
                    </div>
                    
                    <p class="info-text"><?php echo $trans['no_suitable_position']; ?> <a href="mailto:emploi@tchadshop.td">emploi@tchadshop.td</a></p>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- FOOTER - Doit être inclus -->
<?php include 'footer.php'; ?>

<script>
    // Gestion des onglets
    document.addEventListener('DOMContentLoaded', function() {
        const tabBtns = document.querySelectorAll('.tab-btn');
        const contentSections = document.querySelectorAll('.content-section');
        
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // Supprimer la classe active de tous les boutons et sections
                tabBtns.forEach(b => b.classList.remove('active'));
                contentSections.forEach(s => s.classList.remove('active'));
                
                // Ajouter la classe active au bouton cliqué
                btn.classList.add('active');
                
                // Afficher la section correspondante
                const tabId = btn.getAttribute('data-tab');
                document.getElementById(`${tabId}-section`).classList.add('active');
                
                // Faire défiler jusqu'à la section
                document.getElementById(`${tabId}-section`).scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
        
        // Gestion des liens dans le footer
        const tabLinks = document.querySelectorAll('.tab-link');
        tabLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Récupérer l'onglet à afficher
                const tabId = link.getAttribute('data-tab');
                
                // Trouver le bouton correspondant et simuler un clic
                const targetBtn = document.querySelector(`.tab-btn[data-tab="${tabId}"]`);
                if (targetBtn) {
                    targetBtn.click();
                }
            });
        });
        
        // Gestion des FAQ
        const faqQuestions = document.querySelectorAll('.faq-question');
        faqQuestions.forEach(question => {
            question.addEventListener('click', () => {
                // Fermer toutes les autres FAQ
                faqQuestions.forEach(q => {
                    if (q !== question) {
                        q.classList.remove('active');
                        q.nextElementSibling.style.maxHeight = null;
                        q.nextElementSibling.style.padding = '0 25px';
                    }
                });
                
                // Ouvrir/fermer la FAQ actuelle
                question.classList.toggle('active');
                const answer = question.nextElementSibling;
                
                if (question.classList.contains('active')) {
                    answer.style.maxHeight = answer.scrollHeight + 'px';
                    answer.style.padding = '25px';
                } else {
                    answer.style.maxHeight = null;
                    answer.style.padding = '0 25px';
                }
            });
        });
        
        // Ouvrir une section spécifique si paramètre dans l'URL
        const urlParams = new URLSearchParams(window.location.search);
        const sectionParam = urlParams.get('section');
        if (sectionParam) {
            // Trouver le bouton correspondant et simuler un clic
            const targetBtn = document.querySelector(`.tab-btn[data-tab="${sectionParam}"]`);
            if (targetBtn) {
                targetBtn.click();
            }
        }
    });
</script>
</body>
</html>