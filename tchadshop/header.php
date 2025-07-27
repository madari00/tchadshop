<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['admin_id'])) {
}
else{
     header('Location: index.php');
}


// 🔥 Lire la langue par défaut depuis la configuration
$langQuery = $conn->query("SELECT valeur FROM configuration WHERE parametre = 'default_language' LIMIT 1");
$lang = $langQuery->fetch_assoc()['valeur'] ?? 'fr'; // fallback en français si vide

// 🔥 Charger les traductions
$translations = include 'traductions.php';
$t = $translations[$lang] ?? $translations['fr']; // fallback FR


$isLoggedIn = isset($_SESSION['admin_id']);
$adminName = $_SESSION['admin_nom'] ?? 'Administrateur';

// 📨 Messages non lus
$nonLusClients = $conn->query("SELECT COUNT(*) AS total FROM messages_clients WHERE lu = 0")->fetch_assoc()['total'];
$nonLusLivreurs = $conn->query("SELECT COUNT(*) AS total FROM messages_livreurs WHERE lu = 0")->fetch_assoc()['total'];

// 📦 Commandes
$nouvellesCommandes = $conn->query("SELECT COUNT(*) AS total FROM commandes WHERE vu = 0")->fetch_assoc()['total'];


// 👥 Nouveaux clients aujourd'hui
$nouveauxClients = $conn->query("SELECT COUNT(*) AS total FROM clients WHERE vu = 0")->fetch_assoc()['total'];

// 📉 Produits en rupture
$produitsCritiques = $conn->query("SELECT COUNT(*) AS total FROM produits WHERE stock<= 5 AND vu=0")->fetch_assoc()['total'];

// 🔔 Total notifications
$totalNotifications = $nonLusClients + $nonLusLivreurs + $nouvellesCommandes + $nouveauxClients + $produitsCritiques;



if (!isset($page_title)) {
    $titles = [
        'tableau_bord.php' => $t['Tableau de bord'],
        'produits.php' => $t['Produits'],
        'ajouter_produit.php' => $t['Produits'],
        'modifier_produit.php' => $t['Produits'],
        'details_produit.php' => $t['Produits'],
        'historique_achats.php' => $t['Achats'],
        'ajouter_achat.php' => $t['Achats'],
        'toutes_commandes.php' => $t['Commandes'],
        'livraison_en_cours.php' => $t['Commandes'],
        'historique_livraison.php' => $t['Commandes'],
        'assigner_livreur.php' => $t['Commandes'],
        'details_commande.php' => $t['Commandes'],
        'ajouter_commande.php' => $t['Commandes'],
        'clients.php' => $t['Utilisateur-client'],
        'ajouter_client.php' => $t['Utilisateur-client'],
        'livreurs.php' => $t['Utilisateur-livreur'],
        'message_client.php' => $t['Messages-clients'],
        'message_livreur.php' => $t['Messages-livreurs'],
        'statistiques_ventes.php' => $t['Statistique-ventes'],
        'statistiques_chiffre_affaires.php' => $t['Statistique-CA'],
        'statistiques_produits.php' => $t['Statistique-produit'],
        'statistiques_livreurs.php' => $t['Statistique-livreur'],
        'statistiques_clients.php' => $t['Statistique-client'],
        'statistiques_stocks.php' => $t['Statistique-stock'],
        'configuration.php' => $t['configuration']
        
    ];
    $filename = basename($_SERVER['PHP_SELF']);
    $page_title = $titles[$filename] ?? 'Tableau de bord';
}
$config = [];
$resultat_logo_site = $conn->query("SELECT * FROM configuration");
while ($logo_site_header = $resultat_logo_site->fetch_assoc()) {
    $config[$logo_site_header['parametre']] = $logo_site_header['valeur'];
}
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
  <head>
    <meta charset="UTF-8" />
     <link rel="stylesheet" href="style4.css" />
     <link
      href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css"
      rel="stylesheet"
    />
  </head>
  <style>
    .d-flex .form-control{
          height: 50px;
    }
    .badge {
  background: red;
  color: white;
  border-radius: 50%;
  padding: 2px 6px;
  font-size: 0.75em;
  margin-left: 5px;
}
.sidebar-button .dashboard,.sidebar-button .bx{
  color: #04018dff;
}

  </style>
  <body>
        <div class="sidebar">
        <div class="logo-details">
          <img src="<?= htmlspecialchars($config['logo']) ?>"  alt="Logo TchadShop" class="logo-icon" />
         
        </div>

      <ul class="nav-links">
  <li>
    <a href="tableau_bord.php" class="<?= basename($_SERVER['PHP_SELF']) == 'tableau_bord.php' ? 'active' : '' ?>">
      <i class="bx bx-grid-alt"></i>
     <span class="links_name"><?= $t['tableau_de_bord'] ?></span>

    </a>
  </li>
<li class="submenu">
  <a href="#" class="dropdown-toggle">
     <i class="bx bx-cart"></i> 
    <span class="links_name"><?= $t['achats'] ?></span>
    <i class="bx bx-chevron-down arrow"></i>
  </a>
  <ul class="sub-menu">
    <li><a href="ajouter_achat.php"><i class="bx bx-plus"></i><?= $t['ajouter_achat'] ?></a></li>
    <li><a href="historique_achats.php"><i class="bx bx-history"></i> <?= $t['historique_achats'] ?></a></li>
  </ul>
</li>

  <li>
    <a href="produits.php">
      <i class="bx bx-box"></i>
      <span class="links_name"><?= $t['produits'] ?></span>
    </a>
  </li>

  <li class="submenu">
    <a href="#" class="dropdown-toggle">
      <i class="bx bx-task"></i>
      <span class="links_name"><?= $t['commandes'] ?></span>
      <i class="bx bx-chevron-down arrow"></i>
    </a>
    <ul class="sub-menu">
      <li><a href="toutes_commandes.php"><i class="bx bx-list-ul"></i><?= $t['toutes_commandes'] ?></a></li>
      <li><a href="livraison_en_cours.php"><i class="bx bx-time-five"></i> <?= $t['livraison_en_cours'] ?></a></li>
      <li><a href="historique_livraison.php"><i class="bx bx-history"></i> <?= $t['historique_livraison'] ?></a></li>
      <li><a href="assigner_livreur.php"><i class="bx bx-user-pin"></i> <?= $t['assigner_livreur'] ?></a></li>
    </ul>
  </li>

  <li class="submenu">
    <a href="#" class="dropdown-toggle">
      <i class="bx bx-user"></i>
      <span class="links_name"><?= $t['utilisateur'] ?></span>
      <i class="bx bx-chevron-down arrow"></i>
    </a>
    <ul class="sub-menu">
      <li><a href="clients.php"><i class="bx bx-user-circle"></i> <?= $t['clients'] ?></a></li>
      <li><a href="livreurs.php"><i class="bx bx-user-circle"></i> <?= $t['livreurs'] ?></a></li>
    </ul>
  </li>

  <li class="submenu">  
  <a href="#" class="dropdown-toggle">
    <i class="bx bx-bell"></i>
    <span class="links_name"><?= $t['notifications'] ?></span>
    <?php if ($totalNotifications > 0): ?>
      <span class="badge"><?= $totalNotifications ?></span>
    <?php endif; ?>
    <i class="bx bx-chevron-down arrow"></i>
  </a>
  <ul class="sub-menu">
    <!-- Messages Clients -->
    <li>
      <a href="message_client.php">
        <i class="bx bx-message"></i> <?= $t['messages_clients'] ?>
        <?php if ($nonLusClients > 0): ?>
          <span class="badge"><?= $nonLusClients ?></span>
        <?php endif; ?>
      </a>
    </li>

    <!-- Messages Livreurs -->
    <li>
      <a href="message_livreur.php">
        <i class="bx bx-mail-send"></i><?= $t['messages_livreurs'] ?>
        <?php if ($nonLusLivreurs > 0): ?>
          <span class="badge"><?= $nonLusLivreurs ?></span>
        <?php endif; ?>
      </a>
    </li>

    <!-- Commandes -->
    <li>
      <a href="liste_commandes.php">
        <i class="bx bx-cart"></i><?= $t['nouvelles_commandes'] ?>
        <?php if ($nouvellesCommandes > 0): ?>
          <span class="badge"><?= $nouvellesCommandes ?></span>
        <?php endif; ?>
      </a>
    </li>
   

    <!-- Nouveaux Clients -->
    <li>
      <a href="liste_clients.php">
        <i class="bx bx-user"></i><?= $t['nouveaux_clients'] ?>
        <?php if ($nouveauxClients > 0): ?>
          <span class="badge"><?= $nouveauxClients ?></span>
        <?php endif; ?>
      </a>
    </li>

    <!-- Stock Critique -->
    <li>
      <a href="liste_produits.php">
        <i class="bx bx-cube"></i> <?= $t['produits_critiques'] ?>
        <?php if ($produitsCritiques > 0): ?>
          <span class="badge"><?= $produitsCritiques ?></span>
        <?php endif; ?>
      </a>
    </li>
  </ul>
</li>


  <li class="submenu">
    <a href="#" class="dropdown-toggle">
        <i class="bx bx-bar-chart-alt-2"></i>
        <span class="links_name"><?= $t['statistiques'] ?></span>
        <i class="bx bx-chevron-down arrow"></i>
    </a>
    <ul class="sub-menu">
        <li><a href="statistiques_ventes.php"><i class="bx bx-cart"></i> <?= $t['ventes'] ?></a></li>
        <li><a href="statistiques_chiffre_affaires.php"><i class="bx bx-dollar"></i> <?= $t['chiffre_affaires'] ?></a></li>
        <li><a href="statistiques_produits.php"><i class="bx bx-star"></i><?= $t['produits_populaires'] ?></a></li>
        <li><a href="statistiques_livreurs.php"><i class="bx bx-bar-chart-square"></i> <?= $t['performance_livreurs'] ?></a></li>
        <li><a href="statistiques_clients.php"><i class="bx bx-heart-circle"></i> <?= $t['fidelite_clients'] ?></a></li>
        <li><a href="statistiques_stocks.php"><i class="bx bx-package"></i> <?= $t['stocks_faibles'] ?></a></li>
    </ul>
</li>


  <li>
    <a href="configuration.php">
      <i class="bx bx-cog"></i>
      <span class="links_name"><?= $t['configuration'] ?></span>
    </a>
  </li>

  <li class="log_out">
    <a href="deconnexion.php">
      <i class="bx bx-log-out"></i>
      <span class="links_name"><?= $t['deconnexion'] ?></span>
    </a>
  </li>
</ul>

    </div>
    <section class="home-section">
      <nav>
        <div class="sidebar-button">
          <i class="bx bx-menu sidebarBtn"></i>
          <span class="dashboard"><?= htmlspecialchars($page_title) ?></span>
        </div>


      <div class="search-box">
  <form action="<?php 
    if ($searchContext == 'produit') {
        echo 'produits.php';
    } 
    elseif ($searchContext == 'achat') {
        echo 'historique_achats.php';
    } elseif ($searchContext == 'ajoutp') {
        echo 'produits.php';
    } elseif ($searchContext == 'modifierp') {
        echo 'produits.php';
    }elseif ($searchContext == 'detailp') {
        echo 'produits.php';
    }
     elseif ($searchContext == 'ajout') {
        echo 'historique_achats.php';
    } 
    elseif ($searchContext == 'tcommade') {
        echo 'toutes_commandes.php';
    } 
    elseif ($searchContext == 'lcommande') {
        echo 'toutes_commandes.php';
    }
    elseif ($searchContext == 'hcommande') {
        echo 'historique_livraison.php';
    }
    elseif ($searchContext == 'tcommande') {
        echo 'toutes_commandes.php';
    }
    elseif ($searchContext == 'acommande') {
        echo 'toutes_commandes.php';
    } 
    elseif ($searchContext == 'dcommande') {
        echo 'toutes_commandes.php';
    }
    elseif ($searchContext == 'client') {
        echo 'clients.php';
    }elseif ($searchContext == 'aclient') {
        echo 'clients.php';
    }elseif ($searchContext == 'livreur') {
        echo 'livreurs.php';
    }
    elseif ($searchContext == 'messagec') {
        echo 'message_client.php';
    }elseif ($searchContext == 'messagel') {
        echo 'message_livreur.php';
    }else{
      echo 'tableau_bord.php';
    }
  ?>" method="get" class="d-flex">
    <input type="text" name="search" placeholder="<?= $t['recherche'] ?>" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" class="form-control" />
    <button type="submit" class="btn btn-primary ms-2">
      <i class="bx bx-search"></i>
    </button>
    
  </form>
</div>

        
        <div class="profile-details">
          <img src="images/user-solid.ico" alt="">
          <span class="admin_name"><?php echo htmlspecialchars($adminName); ?></span>
          <!--<i class="bx bx-chevron-down"></i>-->
        </div>
      </nav>


      <script>
      const profileDropdown = document.getElementById('profileDropdown');
      const toggleIcon = document.getElementById('dropdownToggle');

      if (toggleIcon) {
        toggleIcon.addEventListener('click', (e) => {
          e.stopPropagation(); // empêche le clic de se propager
          profileDropdown.classList.toggle('active');
        });
      }

      // Fermer si on clique ailleurs
      document.addEventListener('click', (e) => {
        if (profileDropdown && !profileDropdown.contains(e.target)) {
          profileDropdown.classList.remove('active');
        }
      });

      const sidebar = document.querySelector(".sidebar");
      const sidebarBtn = document.querySelector(".sidebarBtn");

      sidebarBtn.addEventListener("click", () => {
        sidebar.classList.toggle("active");
      });

      function toggleSubmenu(event) {
        event.preventDefault();
        const parent = event.currentTarget.closest('.submenu');
        parent.classList.toggle('active');
      }

      // Ajout des gestionnaires de clic sur tous les dropdown-toggle
      const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
      dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', toggleSubmenu);
      });

  </script>
</body>
</html>