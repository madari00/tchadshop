-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mer. 03 sep. 2025 à 18:48
-- Version du serveur : 9.1.0
-- Version de PHP : 8.1.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `tchadshop_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `admin`
--

DROP TABLE IF EXISTS `admin`;
CREATE TABLE IF NOT EXISTS `admin` (
  `nom` varchar(220) NOT NULL,
  `mot_de_passe` varchar(10) NOT NULL,
  `adresse` varchar(20) NOT NULL,
  `email` varchar(40) NOT NULL,
  `id` int NOT NULL AUTO_INCREMENT,
  `telephone` int NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `admin`
--

INSERT INTO `admin` (`nom`, `mot_de_passe`, `adresse`, `email`, `id`, `telephone`, `reset_token`, `reset_expiry`) VALUES
('issakha daoud', 'madari', 'N\'djamena,tchad', 'issakhadaoudabdelkerim95@gmail.com', 1, 2147483647, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `avis_clients`
--

DROP TABLE IF EXISTS `avis_clients`;
CREATE TABLE IF NOT EXISTS `avis_clients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `nom_client` varchar(100) NOT NULL,
  `note` tinyint NOT NULL,
  `commentaire` text NOT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `approuve` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `avis_clients`
--

INSERT INTO `avis_clients` (`id`, `client_id`, `nom_client`, `note`, `commentaire`, `date_creation`, `approuve`) VALUES
(7, 4, 'mahamat issa', 5, '\"J\'ai commandé un smartphone sur TchadShop et j\'ai été impressionné par la rapidité de la livraison. Le produit était exactement comme sur la photo et à un prix imbattable.\"', '2025-08-17 23:32:12', 1);

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

DROP TABLE IF EXISTS `clients`;
CREATE TABLE IF NOT EXISTS `clients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) NOT NULL,
  `adresse` text,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `invite` tinyint(1) DEFAULT '1',
  `vu` tinyint(1) DEFAULT '0',
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expire` int DEFAULT NULL,
  `langue` varchar(5) DEFAULT 'fr',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`id`, `nom`, `email`, `telephone`, `adresse`, `password`, `created_at`, `invite`, `vu`, `reset_token`, `reset_token_expire`, `langue`) VALUES
(1, 'issakha daoud', 'issakhadaoudabdelkerim95@gmail.com', '12345678', 'goudji charafa', '$2y$10$f8kYgQBpc11gF6Zsi0kHueO.Hugmyb8tMgFd10zR67/tD41UD0IO6', '2025-07-04 23:00:45', 0, 1, NULL, NULL, 'fr'),
(3, 'moussa', 'moussa@gmai.com', '+23566', NULL, NULL, '2025-07-06 12:14:28', 0, 1, NULL, NULL, 'fr'),
(4, 'mahamat issa', 'filsissakha11@gmail.com', '+23599', 'N\'djamena', '$2y$10$eMnLlShH.CkDWWA3bVJGOegEGHi6oIu3BuAMDqS4mkMY/OdIzpgjG', '2025-07-08 17:40:55', 0, 1, 'cd7a7e0398f5cc501a790a5fe64f972957728ec3b6d3eaf708c5ae5268bf61c6', 1754479003, 'fr'),
(5, 'ali hamza', 'hamza@gmail.com', '+23590001915', NULL, NULL, '2025-07-16 13:31:41', 0, 1, NULL, NULL, 'fr'),
(6, 'gjhkldfvbnm', 'hjkl@gmail.com', '44567890', NULL, NULL, '2025-07-16 15:17:48', 0, 1, NULL, NULL, 'fr'),
(7, 'ryjkl', 'sdfghjk@gmail.com', '45876543', NULL, NULL, '2025-07-16 15:19:28', 0, 1, NULL, NULL, 'fr'),
(10, 'ali moussa', 'ali@gmail.com', '+23590654323', NULL, NULL, '2025-07-16 15:30:25', 0, 1, NULL, NULL, 'fr'),
(11, 'ejfkslaf', 'jk@gmail.com', '4567890', NULL, NULL, '2025-07-16 15:36:38', 0, 1, NULL, NULL, 'fr'),
(12, 'fghj', 'dfghj@gmail.com', '356890', NULL, NULL, '2025-07-16 16:06:19', 0, 1, NULL, NULL, 'fr'),
(13, 'ertjk', 'hj@gmail.com', '456789', NULL, NULL, '2025-07-16 16:47:15', 0, 1, NULL, NULL, 'fr'),
(14, 'fghj', 'vbhjuy@gmail.com', '345678', NULL, NULL, '2025-07-16 16:56:38', 0, 1, NULL, NULL, 'fr'),
(15, 'Invité', NULL, '66391859', NULL, NULL, '2025-08-01 11:45:38', 1, 1, NULL, NULL, 'fr'),
(16, 'Invité', NULL, '456789076', NULL, NULL, '2025-08-01 12:08:53', 1, 1, NULL, NULL, 'fr'),
(17, 'Invité', NULL, '9876543456789', NULL, NULL, '2025-08-01 12:09:54', 1, 1, NULL, NULL, 'fr'),
(18, 'Invité', NULL, '87654345678', NULL, NULL, '2025-08-01 12:12:28', 1, 1, NULL, NULL, 'fr'),
(19, 'Invité', NULL, '34567890', NULL, NULL, '2025-08-01 12:14:00', 1, 1, NULL, NULL, 'fr'),
(20, 'Invité', NULL, '87654378', NULL, NULL, '2025-08-01 12:14:51', 1, 1, NULL, NULL, 'fr'),
(21, 'Invité', NULL, '9654389', NULL, NULL, '2025-08-01 12:26:47', 1, 1, NULL, NULL, 'fr'),
(22, 'Invité', NULL, '876543456789', NULL, NULL, '2025-08-01 12:27:58', 1, 1, NULL, NULL, 'fr'),
(23, 'Invité', NULL, '45678987654', NULL, NULL, '2025-08-01 15:12:48', 1, 1, NULL, NULL, 'fr'),
(27, 'ismail hamza', 'ismail@gmail.com', '1234567890', 'farcha', '$2y$10$iSF3GbvsrYuL6qeYYevuRu/5BnlRhC/2iA/sCLvEkZSCRXBDTlLai', '2025-08-02 11:18:44', 0, 1, NULL, NULL, 'fr'),
(28, 'Invité', NULL, '248464076770', NULL, NULL, '2025-08-02 20:13:20', 1, 1, NULL, NULL, 'fr'),
(29, 'Client 09876567890', NULL, '09876567890', NULL, NULL, '2025-08-18 00:13:44', 1, 1, NULL, NULL, 'fr'),
(30, '', '', '', NULL, NULL, '2025-08-19 12:12:14', 0, 1, NULL, NULL, 'fr'),
(31, 'Client 1234567', NULL, '1234567', NULL, NULL, '2025-08-22 02:21:40', 1, 0, NULL, NULL, 'fr');

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

DROP TABLE IF EXISTS `commandes`;
CREATE TABLE IF NOT EXISTS `commandes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int DEFAULT NULL,
  `livreur_id` int DEFAULT NULL,
  `statut` enum('en attente','en cours','livré','échec') DEFAULT 'en attente',
  `total` decimal(10,2) NOT NULL,
  `date_commande` datetime DEFAULT CURRENT_TIMESTAMP,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `date_livraison` datetime DEFAULT NULL,
  `temps_livraison` varchar(50) DEFAULT NULL,
  `date_livraison_prevue` datetime DEFAULT NULL,
  `audio_path` varchar(255) DEFAULT NULL,
  `vu` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `commandes_ibfk_1` (`client_id`),
  KEY `commandes_ibfk_2` (`livreur_id`)
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `commandes`
--

INSERT INTO `commandes` (`id`, `client_id`, `livreur_id`, `statut`, `total`, `date_commande`, `latitude`, `longitude`, `adresse`, `date_livraison`, `temps_livraison`, `date_livraison_prevue`, `audio_path`, `vu`) VALUES
(1, 1, NULL, 'livré', 0.00, '2025-07-04 23:00:45', NULL, NULL, 'nnnn', NULL, NULL, NULL, NULL, 1),
(8, 1, NULL, 'livré', 123456.01, '2025-07-05 00:23:56', NULL, NULL, 'eeee', '2025-07-07 23:52:07', '0', NULL, NULL, 1),
(15, 1, NULL, 'livré', 10000.00, '2025-07-05 17:19:20', NULL, NULL, 'aaaaa', NULL, NULL, NULL, NULL, 1),
(17, 3, NULL, 'livré', 123456.01, '2025-07-06 12:14:28', NULL, NULL, 'uuuuu', NULL, NULL, NULL, NULL, 1),
(18, 1, NULL, 'livré', 123456.01, '2025-07-06 23:49:19', NULL, NULL, 'bbbbbb', NULL, '0', '2025-07-08 21:39:00', NULL, 1),
(19, 1, NULL, 'livré', 123456.01, '2025-07-07 19:49:57', 12.37140000, -1.51970000, NULL, NULL, '30', '2025-07-08 21:30:00', NULL, 1),
(20, 4, NULL, 'livré', 10000.00, '2025-07-08 17:40:55', NULL, NULL, 'farcha', '2025-07-09 11:06:06', '0', '2025-07-09 10:40:00', NULL, 1),
(21, 1, NULL, 'livré', 10000.00, '2025-07-09 11:10:22', NULL, NULL, 'goudji charafa', '2025-07-09 11:11:05', NULL, '2025-07-09 11:11:00', NULL, 1),
(25, 1, 2, 'livré', 10000.00, '2025-07-14 12:54:08', NULL, NULL, 'farcha', '2025-07-14 13:28:38', '30', '2025-07-15 12:53:00', NULL, 1),
(28, 1, 2, 'livré', 7890.00, '2025-07-18 20:25:22', NULL, NULL, 'farcha', '2025-07-18 20:26:09', '30', '2025-07-18 21:00:00', NULL, 1),
(29, 15, 2, 'échec', 123456.01, '2025-08-01 11:45:42', 12.35890190, -1.50244065, 'Kalgodin, Ouagadougou, Kadiogo, Centre, Burkina Faso', NULL, '30', NULL, NULL, 1),
(32, 17, NULL, 'en attente', 250000.00, '2025-08-01 12:09:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(33, 17, NULL, 'en attente', 250000.00, '2025-08-01 12:11:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(34, 18, NULL, 'en attente', 250000.00, '2025-08-01 12:12:28', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(35, 19, NULL, 'en attente', 123456.01, '2025-08-01 12:14:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(36, 20, NULL, 'en attente', 250000.00, '2025-08-01 12:14:51', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(37, 21, NULL, 'en attente', 250000.00, '2025-08-01 12:26:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(38, 22, NULL, 'en attente', 123456.01, '2025-08-01 12:27:58', 12.35890190, -1.50244065, NULL, NULL, NULL, NULL, NULL, 1),
(39, 23, NULL, 'en attente', 14000.00, '2025-08-01 15:12:48', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(40, 28, 2, 'échec', 250000.00, '2025-08-02 20:13:20', NULL, NULL, NULL, NULL, '30', NULL, NULL, 1),
(41, 4, 2, 'livré', 7000.00, '2025-08-03 00:28:08', NULL, NULL, NULL, '2025-08-03 00:56:15', '10', NULL, NULL, 1),
(42, 4, 2, 'livré', 7000.00, '2025-08-03 00:32:29', 12.35890190, -1.50244065, NULL, '2025-08-03 00:55:08', '30', NULL, NULL, 1),
(43, 4, 2, 'livré', 1578.00, '2025-08-03 00:59:18', NULL, NULL, NULL, '2025-08-03 00:59:57', '30', NULL, NULL, 1),
(44, 4, 2, 'livré', 10000.00, '2025-08-03 01:01:40', NULL, NULL, NULL, '2025-08-03 01:07:51', '6', NULL, NULL, 1),
(45, 4, 2, 'livré', 789.00, '2025-08-03 01:08:18', NULL, NULL, NULL, '2025-08-04 11:20:46', '30', NULL, NULL, 1),
(46, 4, 2, 'livré', 7000.00, '2025-08-04 12:04:17', NULL, NULL, NULL, '2025-08-04 12:08:15', '30', NULL, NULL, 1),
(47, 4, 2, 'livré', 789.00, '2025-08-04 12:28:26', NULL, NULL, NULL, '2025-08-04 12:29:08', '30', NULL, NULL, 1),
(48, 4, 2, 'échec', 7000.00, '2025-08-04 12:29:24', NULL, NULL, NULL, NULL, '30', NULL, NULL, 1),
(49, 4, 2, 'échec', 789.00, '2025-08-04 12:33:08', NULL, NULL, NULL, NULL, '30', NULL, NULL, 1),
(50, 4, 2, 'échec', 10000.00, '2025-08-04 13:12:51', NULL, NULL, NULL, NULL, '30', NULL, NULL, 1),
(54, 28, NULL, 'en attente', 591.75, '2025-08-18 00:20:49', NULL, NULL, NULL, NULL, NULL, '2025-08-21 00:00:00', NULL, 1),
(55, 4, NULL, 'en attente', 591.75, '2025-08-18 00:36:08', NULL, NULL, NULL, NULL, NULL, '2025-08-21 00:00:00', NULL, 1),
(56, 4, NULL, 'en attente', 7000.00, '2025-08-18 00:36:18', NULL, NULL, NULL, NULL, NULL, '2025-08-21 00:00:00', NULL, 1),
(57, 28, NULL, 'en attente', 591.75, '2025-08-18 00:41:22', NULL, NULL, NULL, NULL, NULL, '2025-08-21 00:00:00', NULL, 1),
(58, 4, NULL, 'en attente', 1183.50, '2025-08-18 00:45:00', NULL, NULL, NULL, NULL, NULL, '2025-08-21 00:00:00', NULL, 1),
(59, 4, 2, 'en cours', 591.75, '2025-08-19 11:16:45', NULL, NULL, NULL, NULL, '30', '2025-08-22 00:00:00', NULL, 1),
(60, 4, 2, 'livré', 591.75, '2025-08-19 11:21:39', NULL, NULL, NULL, '2025-08-19 12:31:29', '15', '2025-08-22 00:00:00', NULL, 1),
(61, 4, 2, 'livré', 591.75, '2025-08-19 11:24:18', NULL, NULL, NULL, '2025-08-19 15:21:58', '60', '2025-08-22 00:00:00', NULL, 1),
(62, 4, 2, 'livré', 591.75, '2025-08-19 16:05:42', NULL, NULL, NULL, '2025-08-19 16:06:12', '30', '2025-08-22 00:00:00', NULL, 1),
(63, 4, NULL, 'en attente', 250000.00, '2025-08-19 16:09:04', 12.35890110, -1.50243474, NULL, NULL, NULL, '2025-08-22 00:00:00', NULL, 1),
(64, 31, NULL, 'en attente', 10000.00, '2025-08-22 02:21:40', NULL, NULL, NULL, NULL, NULL, '2025-08-25 00:00:00', NULL, 0),
(65, 28, NULL, 'en attente', 591.75, '2025-08-30 12:41:58', NULL, NULL, NULL, NULL, NULL, '2025-08-31 00:00:00', NULL, 0);

-- --------------------------------------------------------

--
-- Structure de la table `configuration`
--

DROP TABLE IF EXISTS `configuration`;
CREATE TABLE IF NOT EXISTS `configuration` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parametre` varchar(100) NOT NULL,
  `valeur` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `configuration`
--

INSERT INTO `configuration` (`id`, `parametre`, `valeur`) VALUES
(1, 'site_name', 'TchadShop'),
(2, 'email_support', 'support@Tchadshop.com'),
(3, 'currency', 'FCFA'),
(4, 'default_language', 'fr'),
(5, 'maintenance_mode', 'off'),
(6, 'logo', '6881757ebd2cb_pngamma.ico'),
(7, '2fa', 'off'),
(8, 'admin_password', '');

-- --------------------------------------------------------

--
-- Structure de la table `contacts`
--

DROP TABLE IF EXISTS `contacts`;
CREATE TABLE IF NOT EXISTS `contacts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int DEFAULT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `sujet` varchar(255) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `message` text,
  `vu` tinyint(1) DEFAULT '0',
  `audio_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `contacts`
--

INSERT INTO `contacts` (`id`, `client_id`, `nom`, `email`, `sujet`, `telephone`, `message`, `vu`, `audio_path`, `created_at`) VALUES
(1, 0, 'Jean Dupont', 'jean.dupont@example.com', '', '+23566123456', 'Je souhaite en savoir plus sur vos produits locaux', 1, NULL, '2025-08-12 21:22:35'),
(2, NULL, 'Jean Dupont', 'jean.dupont@example.com', '', '+23566123456', 'Je souhaite en savoir plus sur vos produits locaux', 1, NULL, '2025-08-12 21:22:55'),
(3, NULL, 'Jean Dupont', 'jean.dupont@example.com', '', '+23566123456', 'Je souhaite en savoir plus sur vos produits locaux', 1, NULL, '2025-08-12 21:24:02'),
(4, NULL, 'Jean Dupont', 'jean.dupont@example.com', '', '+23566123456', 'Je souhaite en savoir plus sur vos produits locaux', 1, NULL, '2025-08-12 21:24:37'),
(5, NULL, 'issakha daoud', 'daoud@gmail.com', '', '4567898763', 'j\'aimerais en savoir plus sur vos produits', 1, NULL, '2025-08-12 21:35:06'),
(6, NULL, 'issakha daoud', 'daoud@gmail.com', '', '4567898763', 'j\'aimerais en savoir plus sur vos produits', 1, NULL, '2025-08-12 21:39:04'),
(7, NULL, 'issakha daoud', 'daoud@gmail.com', '', '4567898763', 'j\'aimerais en savoir plus sur vos produits', 1, NULL, '2025-08-12 21:39:13'),
(8, NULL, 'Jean Dupont', 'jean.dupont@example.com', '', '+23566123456', 'Je souhaite en savoir plus sur vos produits locaux', 1, NULL, '2025-08-12 21:40:08'),
(9, NULL, 'Jean Dupont', 'jean.dupont@example.com', '', '+23566123456', 'Je souhaite en savoir plus sur vos produits locaux', 1, NULL, '2025-08-12 21:40:13'),
(10, NULL, 'issakha daoud', 'daoud@gmail.com', '', '4567898763', 'j\'aimerais en savoir plus sur vos produits', 1, NULL, '2025-08-12 21:48:08'),
(11, 4, 'mahamat issa', 'filsissakha11@gmail.com', '', '+2359956789', 'sjfdiofjosdjfsdio', 1, NULL, '2025-08-14 16:27:56'),
(12, 4, 'mahamat issa', 'filsissakha11@gmail.com', '', '+2359956789', 'sjfdiofjosdjfsdio', 1, NULL, '2025-08-14 16:28:04');

-- --------------------------------------------------------

--
-- Structure de la table `details_commandes`
--

DROP TABLE IF EXISTS `details_commandes`;
CREATE TABLE IF NOT EXISTS `details_commandes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `commande_id` int NOT NULL,
  `produit_id` int NOT NULL,
  `quantite` int NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `promotion` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `commande_id` (`commande_id`),
  KEY `produit_id` (`produit_id`)
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `details_commandes`
--

INSERT INTO `details_commandes` (`id`, `commande_id`, `produit_id`, `quantite`, `prix_unitaire`, `promotion`) VALUES
(1, 1, 5, 1, 0.00, 0),
(23, 15, 5, 1, 0.00, 0),
(25, 17, 11, 1, 0.00, 0),
(28, 8, 13, 1, 0.00, 0),
(30, 19, 9, 1, 0.00, 0),
(32, 18, 11, 1, 0.00, 0),
(35, 20, 5, 1, 0.00, 0),
(36, 21, 5, 1, 0.00, 0),
(40, 25, 5, 1, 10000.00, 0),
(44, 28, 7, 10, 789.00, 0),
(45, 29, 9, 1, 123456.01, 0),
(48, 32, 16, 1, 250000.00, 0),
(49, 33, 16, 1, 250000.00, 0),
(50, 34, 16, 1, 250000.00, 0),
(51, 35, 13, 1, 123456.01, 0),
(52, 36, 16, 1, 250000.00, 0),
(53, 37, 16, 1, 250000.00, 0),
(54, 38, 13, 1, 123456.01, 0),
(55, 39, 17, 2, 7000.00, 0),
(56, 40, 16, 1, 250000.00, 0),
(57, 41, 17, 1, 7000.00, 0),
(58, 42, 17, 1, 7000.00, 0),
(59, 43, 7, 2, 789.00, 0),
(60, 44, 5, 1, 10000.00, 0),
(61, 45, 7, 1, 789.00, 0),
(62, 46, 17, 1, 7000.00, 0),
(63, 47, 7, 1, 789.00, 0),
(64, 48, 17, 1, 7000.00, 0),
(65, 49, 7, 1, 789.00, 0),
(66, 50, 5, 1, 10000.00, 0),
(70, 54, 16, 1, 591.75, 0),
(71, 55, 7, 1, 591.75, 1),
(72, 56, 17, 1, 7000.00, 0),
(73, 57, 7, 1, 591.75, 1),
(74, 58, 13, 2, 591.75, 0),
(75, 59, 7, 1, 591.75, 1),
(76, 60, 7, 1, 591.75, 1),
(85, 61, 7, 1, 591.75, 1),
(86, 62, 7, 1, 591.75, 1),
(87, 63, 16, 1, 250000.00, 0),
(88, 64, 5, 1, 10000.00, 0),
(89, 65, 7, 1, 591.75, 1);

-- --------------------------------------------------------

--
-- Structure de la table `historique_achats`
--

DROP TABLE IF EXISTS `historique_achats`;
CREATE TABLE IF NOT EXISTS `historique_achats` (
  `id` int NOT NULL AUTO_INCREMENT,
  `produit_id` int NOT NULL,
  `commande_id` int DEFAULT NULL,
  `client_id` int DEFAULT NULL,
  `quantite` int NOT NULL,
  `date_achat` datetime DEFAULT CURRENT_TIMESTAMP,
  `prix_unitaire` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `produit_id` (`produit_id`),
  KEY `commande_id` (`commande_id`),
  KEY `client_id` (`client_id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `historique_achats`
--

INSERT INTO `historique_achats` (`id`, `produit_id`, `commande_id`, `client_id`, `quantite`, `date_achat`, `prix_unitaire`) VALUES
(13, 5, 15, 1, 1, '2025-07-05 17:19:20', 10000.00),
(15, 11, 17, 3, 1, '2025-07-06 12:14:28', 123456.01),
(16, 9, 19, 1, 1, '2025-07-07 23:45:05', 0.00),
(17, 13, 8, 1, 1, '2025-07-07 23:52:08', 0.00),
(18, 5, 20, 4, 1, '2025-07-09 11:06:06', 0.00),
(20, 5, 21, 1, 1, '2025-07-09 11:11:05', 0.00),
(30, 7, NULL, 14, 1, '2025-07-16 16:56:38', 789.00),
(31, 7, NULL, NULL, 1, '2025-07-16 17:31:56', 789.00),
(32, 7, NULL, 1, 1, '2025-07-16 17:32:28', 789.00),
(33, 7, 28, 1, 10, '2025-07-18 20:26:09', 789.00),
(34, 7, NULL, 1, 1, '2025-07-22 21:03:10', 789.00),
(35, 7, NULL, NULL, 1, '2025-07-22 23:13:26', 789.00),
(36, 7, NULL, NULL, 1, '2025-07-22 23:14:22', 789.00),
(37, 7, NULL, NULL, 1, '2025-07-22 23:14:40', 789.00),
(38, 17, 42, 4, 1, '2025-08-03 00:55:08', 7000.00),
(39, 17, 41, 4, 1, '2025-08-03 00:56:16', 7000.00),
(40, 7, 43, 4, 2, '2025-08-03 00:59:57', 789.00),
(41, 5, 44, 4, 1, '2025-08-03 01:07:51', 10000.00),
(42, 7, 45, 4, 1, '2025-08-04 11:20:47', 789.00),
(43, 17, 46, 4, 1, '2025-08-04 12:08:15', 7000.00),
(44, 7, 47, 4, 1, '2025-08-04 12:29:08', 789.00),
(45, 7, NULL, NULL, 1, '2025-08-19 00:28:09', 591.75),
(46, 7, NULL, 1, 1, '2025-08-19 01:07:29', 591.75),
(47, 7, 60, 4, 1, '2025-08-19 12:31:29', 591.75),
(48, 10, 61, 4, 1, '2025-08-19 12:34:11', 123456.01),
(49, 5, NULL, 1, 1, '2025-08-19 13:47:03', 10000.00),
(50, 7, NULL, NULL, 1, '2025-08-19 13:47:28', 591.75),
(51, 7, 62, 4, 1, '2025-08-19 16:06:12', 591.75);

-- --------------------------------------------------------

--
-- Structure de la table `images_produit`
--

DROP TABLE IF EXISTS `images_produit`;
CREATE TABLE IF NOT EXISTS `images_produit` (
  `id` int NOT NULL AUTO_INCREMENT,
  `produit_id` int DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `produit_id` (`produit_id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `images_produit`
--

INSERT INTO `images_produit` (`id`, `produit_id`, `image`) VALUES
(16, 5, 'https://res.cloudinary.com/dut2whqwx/image/upload/v1753993035/tchadshop/g6o5yb7pikj7aan2sg0x.png'),
(17, 5, 'https://res.cloudinary.com/dut2whqwx/image/upload/v1753993041/tchadshop/fzyglnrqgespdf87ax96.png'),
(20, 7, 'https://res.cloudinary.com/dut2whqwx/image/upload/v1753993044/tchadshop/fwjy3tdlwqjmd6ugnqy5.png'),
(22, 9, 'https://res.cloudinary.com/dut2whqwx/image/upload/v1753993049/tchadshop/tycywn4zbnhvkjhzkoqw.png'),
(23, 10, 'https://res.cloudinary.com/dut2whqwx/image/upload/v1753993054/tchadshop/tvmscb2zvr51d6eldzke.png'),
(24, 11, 'https://res.cloudinary.com/dut2whqwx/image/upload/v1753993058/tchadshop/wpy3bmklfa52yjjcamwt.png'),
(25, 12, 'https://res.cloudinary.com/dut2whqwx/image/upload/v1753993062/tchadshop/ujt0qezfsk5hsafhqio2.png'),
(26, 13, 'https://res.cloudinary.com/dut2whqwx/image/upload/v1753993067/tchadshop/fy34sxxnftqo2crmgvri.png'),
(28, 13, 'https://res.cloudinary.com/dut2whqwx/image/upload/v1753993068/tchadshop/u9ipfsjpju6rwjb8hgpa.png'),
(31, 13, 'https://res.cloudinary.com/dut2whqwx/image/upload/v1753993073/tchadshop/pof1qsiseqi74ltleqoq.png'),
(34, 16, 'https://res.cloudinary.com/dut2whqwx/image/upload/v1753993079/tchadshop/rfkwm9pkr4g7d8obtntu.png'),
(35, 16, 'https://res.cloudinary.com/dut2whqwx/image/upload/v1753993083/tchadshop/jz4jbz0uwgmr4rrsuuv4.png'),
(36, 16, 'https://res.cloudinary.com/dut2whqwx/image/upload/v1753993085/tchadshop/xjdqhxhqgjbik5lxvhqs.png'),
(37, 16, 'https://res.cloudinary.com/dut2whqwx/image/upload/v1753993090/tchadshop/pj8wfwgytoek9k8luwjy.png'),
(38, 16, 'https://res.cloudinary.com/dut2whqwx/image/upload/v1753993093/tchadshop/xam9bakcfksgyyupnzpw.png'),
(39, 17, 'https://res.cloudinary.com/dut2whqwx/image/upload/v1753995973/tchadshop/te1jfu3qcuh7h44hzh3c.jpg'),
(40, 17, 'https://res.cloudinary.com/dut2whqwx/image/upload/v1753995975/tchadshop/vv57mikbbbgqvlgyfptg.jpg');

-- --------------------------------------------------------

--
-- Structure de la table `livreurs`
--

DROP TABLE IF EXISTS `livreurs`;
CREATE TABLE IF NOT EXISTS `livreurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `actif` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `livreurs`
--

INSERT INTO `livreurs` (`id`, `nom`, `telephone`, `email`, `password`, `actif`, `created_at`) VALUES
(2, 'ismail moussa', '+23590098850', 'moussaismail2@gmail.com', '$2y$10$prIwwdQkv8Hn4wy4rqWSFOPilNrE0SXRDbFo8JgX0pVlDIFnCr/zO', 1, '2025-07-13 11:15:04');

-- --------------------------------------------------------

--
-- Structure de la table `messages_clients`
--

DROP TABLE IF EXISTS `messages_clients`;
CREATE TABLE IF NOT EXISTS `messages_clients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `sujet` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `message` text NOT NULL,
  `reponse` text,
  `lu` tinyint(1) DEFAULT '0',
  `repondu` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `audio_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `messages_clients`
--

INSERT INTO `messages_clients` (`id`, `client_id`, `sujet`, `message`, `reponse`, `lu`, `repondu`, `created_at`, `updated_at`, `audio_path`) VALUES
(52, 4, 'Support client', '', NULL, 1, 0, '2025-08-08 00:28:23', NULL, 'uploads/audio_support/audio_1754612903_cfa37739.webm'),
(53, 4, 'Réponse admin', '', NULL, 1, 1, '2025-08-08 00:29:01', NULL, 'uploads/audio_support/audio_admin_1754612941_48d6c05d.webm'),
(54, 4, 'Support client', 'itrjdf', NULL, 1, 0, '2025-08-08 00:33:04', NULL, NULL),
(55, 4, 'Support client', 'sre5ye6 r', NULL, 1, 0, '2025-08-08 00:33:18', NULL, NULL),
(56, 4, 'Support client', '', NULL, 1, 0, '2025-08-08 00:33:24', NULL, 'uploads/audio_support/audio_1754613204_345437c2.webm'),
(57, 4, 'Support client', 'ghewretyj', NULL, 1, 0, '2025-08-10 20:16:24', NULL, NULL),
(58, 4, 'Réponse admin', 'salut je pense que le probleme a ete regle hein.', NULL, 1, 1, '2025-08-10 20:17:02', NULL, NULL),
(60, 4, 'Support client', '', NULL, 1, 0, '2025-08-12 22:13:29', NULL, 'uploads/audio_support/audio_1755036809_c4a8dce2.webm');

-- --------------------------------------------------------

--
-- Structure de la table `messages_livreurs`
--

DROP TABLE IF EXISTS `messages_livreurs`;
CREATE TABLE IF NOT EXISTS `messages_livreurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `livreur_id` int NOT NULL,
  `sujet` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `reponse` text NOT NULL,
  `lu` tinyint(1) DEFAULT '0',
  `repondu` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `audio_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `livreur_id` (`livreur_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `messages_livreurs`
--

INSERT INTO `messages_livreurs` (`id`, `livreur_id`, `sujet`, `message`, `reponse`, `lu`, `repondu`, `created_at`, `audio_path`) VALUES
(3, 2, 'livraison recue', 'Bonjour boss, la commande #123 a ete effectue avec succes. ?', 'D\'accord,bon travail.', 1, 1, '2025-07-13 11:15:54', NULL),
(4, 2, 'Réponse admin', 'hgfdfghjk', '', 1, 1, '2025-08-10 20:38:05', NULL),
(5, 2, 'Réponse admin', '', '', 1, 1, '2025-08-10 20:38:12', 'uploads/audio_support/audio_admin_1754858292_a9812100.webm');

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `lien` varchar(255) DEFAULT NULL,
  `lu` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `produits`
--

DROP TABLE IF EXISTS `produits`;
CREATE TABLE IF NOT EXISTS `produits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) DEFAULT NULL,
  `description` text,
  `prix` decimal(10,2) DEFAULT NULL,
  `stock` int DEFAULT NULL,
  `statut` enum('disponible','rupture','bientôt') DEFAULT 'disponible',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `vu` tinyint(1) DEFAULT '0',
  `promotion` decimal(5,2) DEFAULT '0.00' COMMENT 'Pourcentage de réduction',
  `prix_promotion` decimal(10,2) DEFAULT NULL COMMENT 'Prix après réduction',
  `date_debut_promo` date DEFAULT NULL,
  `date_fin_promo` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `produits`
--

INSERT INTO `produits` (`id`, `nom`, `description`, `prix`, `stock`, `statut`, `created_at`, `vu`, `promotion`, `prix_promotion`, `date_debut_promo`, `date_fin_promo`) VALUES
(5, 'montre', 'wwwwwwwwwwwwww', 10000.00, 19, 'disponible', '2025-07-03 09:32:36', 1, 0.00, NULL, NULL, NULL),
(7, 'uijuib', 'uyjhc gcg', 789.00, 8924, 'disponible', '2025-07-04 15:41:02', 0, 25.00, 591.75, '2025-08-17', '2025-09-17'),
(9, 'uihuii', 'iouytfds', 123456.01, 5, 'disponible', '2025-07-04 15:44:27', 1, 0.00, NULL, NULL, NULL),
(10, 'uihuii', 'iouytfds', 123456.01, 5, 'disponible', '2025-07-04 15:45:03', 1, 0.00, NULL, NULL, NULL),
(11, 'uihuii', 'iouytfds', 123456.01, 7, 'disponible', '2025-07-04 15:46:03', 0, 0.00, NULL, NULL, NULL),
(12, 'uihuii', 'iouytfds', 123456.01, 6, 'disponible', '2025-07-04 15:46:48', 0, 0.00, NULL, NULL, NULL),
(13, 'uihuii', 'iouytfds', 123456.01, 1, 'disponible', '2025-07-04 15:47:23', 1, 0.00, NULL, NULL, NULL),
(16, 'ordinateur', 'sdjfsldfjsldfjsdlf', 250000.00, 7, 'disponible', '2025-07-22 22:30:21', 0, 0.00, NULL, NULL, NULL),
(17, 'power bank', 'sjfs fsd fsd fjsdf', 7000.00, 16, 'disponible', '2025-07-31 20:58:53', 0, 0.00, NULL, NULL, NULL);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `produits`
--
ALTER TABLE `produits` ADD FULLTEXT KEY `nom` (`nom`,`description`);

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `avis_clients`
--
ALTER TABLE `avis_clients`
  ADD CONSTRAINT `avis_clients_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`);

--
-- Contraintes pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD CONSTRAINT `commandes_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `commandes_ibfk_2` FOREIGN KEY (`livreur_id`) REFERENCES `livreurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `details_commandes`
--
ALTER TABLE `details_commandes`
  ADD CONSTRAINT `details_commandes_ibfk_1` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `details_commandes_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `historique_achats`
--
ALTER TABLE `historique_achats`
  ADD CONSTRAINT `historique_achats_ibfk_1` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historique_achats_ibfk_2` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historique_achats_ibfk_3` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `images_produit`
--
ALTER TABLE `images_produit`
  ADD CONSTRAINT `images_produit_ibfk_1` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `messages_clients`
--
ALTER TABLE `messages_clients`
  ADD CONSTRAINT `messages_clients_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `messages_livreurs`
--
ALTER TABLE `messages_livreurs`
  ADD CONSTRAINT `messages_livreurs_ibfk_1` FOREIGN KEY (`livreur_id`) REFERENCES `livreurs` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
