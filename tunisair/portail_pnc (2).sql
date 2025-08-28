-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : jeu. 07 août 2025 à 14:52
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `portail_pnc`
--

-- --------------------------------------------------------

--
-- Structure de la table `convention`
--

CREATE TABLE `convention` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `description` text DEFAULT NULL,
  `responsable` varchar(255) DEFAULT NULL,
  `tel` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `piece_jointe` varchar(255) DEFAULT NULL,
  `type_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `convention`
--

INSERT INTO `convention` (`id`, `titre`, `nom`, `date_debut`, `date_fin`, `description`, `responsable`, `tel`, `image`, `piece_jointe`, `type_id`) VALUES
(13, 'promotions', 'Bilel', '2025-08-08', '2025-08-31', '', 'mabrouka', '20105604', '', '', 3),
(14, 'milexi saloun', 'mabr', '2025-08-23', '2025-08-31', '', 'jhgfc', '20105604', '', '', 2),
(15, 'nesrine', 'tunisie télécom', '2025-08-01', '2025-08-31', 'nesrine nesrine nesrine', 'NESRINA', '20106604', 'images/RA.jpeg', 'pieces/guide2024.pdf', 6);

-- --------------------------------------------------------

--
-- Structure de la table `type_convention`
--

CREATE TABLE `type_convention` (
  `id` int(11) NOT NULL,
  `code` varchar(10) DEFAULT NULL,
  `libelle` varchar(255) NOT NULL,
  `date_ajout` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `type_convention`
--

INSERT INTO `type_convention` (`id`, `code`, `libelle`, `date_ajout`) VALUES
(1, 'MED', 'Médical', '2025-08-06'),
(2, 'HOT', 'Hôtel', '2025-08-06'),
(3, 'MAG', 'Magasins', '2025-08-06'),
(4, 'RES', 'Restaurants', '2025-08-06'),
(5, 'ASS', 'Assurances', '2025-08-06'),
(6, 'OPE', 'Opérateurs', '2025-08-06'),
(7, 'SPO', 'Sport', '2025-08-06'),
(8, 'CLI', 'Clinique', '2025-08-06'),
(9, 'DOC', 'Docteur', '2025-08-06'),
(10, 'PARA', 'Paramédical', '2025-08-06');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `convention`
--
ALTER TABLE `convention`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `titre` (`titre`),
  ADD KEY `type_id` (`type_id`);

--
-- Index pour la table `type_convention`
--
ALTER TABLE `type_convention`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `convention`
--
ALTER TABLE `convention`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `type_convention`
--
ALTER TABLE `type_convention`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `convention`
--
ALTER TABLE `convention`
  ADD CONSTRAINT `convention_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `type_convention` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
