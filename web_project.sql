-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 23, 2025 at 08:46 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `web_project`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `product_id` int(11) UNSIGNED NOT NULL,
  `quantity` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `game_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `user_id`, `game_name`, `price`, `quantity`, `created_at`, `updated_at`) VALUES
(20, 1, 'NBA 2K25', 41.99, 1, '2025-08-22 13:10:04', '2025-08-22 13:10:04'),
(21, 1, 'Elden Ring', 49.99, 1, '2025-08-22 13:10:08', '2025-08-22 13:10:08'),
(22, 1, 'SPACE INVADERS', 19.99, 1, '2025-08-22 13:13:10', '2025-08-22 13:13:10'),
(23, 1, 'Mario Kart 8 Deluxe', 59.99, 1, '2025-08-22 13:13:25', '2025-08-22 13:13:25'),
(24, 1, 'Sea of Thieves', 39.99, 1, '2025-08-22 13:13:31', '2025-08-22 13:13:31'),
(25, 1, 'Retro Arcade Console', 199.99, 1, '2025-08-22 13:13:43', '2025-08-22 13:13:43'),
(72, 5, 'Xbox Series X', 499.99, 1, '2025-08-23 17:24:13', '2025-08-23 17:24:13'),
(73, 5, 'Retro Arcade Console', 199.99, 2, '2025-08-23 17:53:17', '2025-08-23 17:53:44');

-- --------------------------------------------------------

--
-- Table structure for table `featured_games`
--

CREATE TABLE `featured_games` (
  `id` int(11) NOT NULL,
  `game_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `price` decimal(10,2) NOT NULL,
  `platform` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `featured_games`
--

INSERT INTO `featured_games` (`id`, `game_name`, `quantity`, `price`, `platform`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Hogwarts Legacy', 50, 59.99, 'PS5/Xbox/PC', 'Experience the magic of Hogwarts in this immersive open-world adventure. Cast spells, brew potions, and uncover ancient secrets.', '2025-08-23 18:28:51', '2025-08-23 18:28:51'),
(2, 'Marvel\'s Spider-Man 2', 45, 59.49, 'PS5', 'Swing through New York City as both Peter Parker and Miles Morales in this epic superhero adventure.', '2025-08-23 18:28:51', '2025-08-23 18:28:51'),
(3, 'Call of Duty: Modern Warfare III', 60, 69.99, 'PS5/Xbox/PC', 'Experience the ultimate first-person shooter with cutting-edge graphics and intense multiplayer action.', '2025-08-23 18:28:51', '2025-08-23 18:28:51'),
(4, 'NBA 2K25', 55, 41.99, 'PS5/Xbox/PC/Switch', 'Hit the court with the most realistic basketball simulation ever created. Updated rosters and enhanced gameplay.', '2025-08-23 18:28:51', '2025-08-23 18:28:51'),
(5, 'Elden Ring', 40, 49.99, 'PS5/Xbox/PC', 'Explore a vast fantasy world filled with mystery and danger. From the creators of Dark Souls.', '2025-08-23 18:28:51', '2025-08-23 18:28:51'),
(6, 'Uncharted 4', 35, 31.99, 'PS5/PS4', 'Join Nathan Drake on his final adventure in this action-packed treasure hunting experience.', '2025-08-23 18:28:51', '2025-08-23 18:28:51');

-- --------------------------------------------------------

--
-- Table structure for table `nintendo_games`
--

CREATE TABLE `nintendo_games` (
  `id` int(11) NOT NULL,
  `game_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `emoji` varchar(10) DEFAULT '?',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nintendo_games`
--

INSERT INTO `nintendo_games` (`id`, `game_name`, `price`, `quantity`, `emoji`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Super Mario Odyssey', 59.99, 45, '👑', 'Join Mario on a massive, globe-trotting 3D adventure.', '2025-08-23 11:57:10', '2025-08-23 12:20:44'),
(2, 'Zelda: Breath of the Wild', 59.99, 43, '🗡️', 'Explore the wild in this epic open-world adventure.', '2025-08-23 11:57:10', '2025-08-23 13:16:37'),
(3, 'Mario Kart 8 Deluxe', 59.99, 60, '🏁', 'The ultimate version of the Mario Kart series.', '2025-08-23 11:57:10', '2025-08-23 11:57:10'),
(4, 'Animal Crossing: New Horizons', 59.99, 40, '🌸', 'Create your perfect island paradise.', '2025-08-23 11:57:10', '2025-08-23 11:57:10'),
(5, 'Super Smash Bros. Ultimate', 59.99, 35, '👊', 'The biggest Super Smash Bros. game ever!', '2025-08-23 11:57:10', '2025-08-23 11:57:10'),
(6, 'Splatoon 3', 59.99, 28, '🦑', 'Ink-splatting battles in the Splatlands.', '2025-08-23 11:57:10', '2025-08-23 14:04:05'),
(7, 'Pokémon Scarlet', 59.99, 25, '⚡', 'Open-world Pokémon adventure awaits.', '2025-08-23 11:57:10', '2025-08-23 11:57:10'),
(8, 'Kirby and the Forgotten Land', 59.99, 20, '🌟', 'Kirby\'s first fully 3D platforming adventure.', '2025-08-23 11:57:10', '2025-08-23 11:57:10'),
(9, 'Metroid Dread', 59.99, 15, '🚀', 'Samus returns in this intense 2D adventure.', '2025-08-23 11:57:10', '2025-08-23 11:57:10'),
(10, 'ARMS', 49.99, 10, '🥊', 'Spring-loaded boxing battles.', '2025-08-23 11:57:10', '2025-08-23 11:57:10'),
(11, 'Luigi\'s Mansion 3', 59.99, 18, '👻', 'Luigi\'s spookiest adventure yet.', '2025-08-23 11:57:10', '2025-08-23 11:57:10'),
(12, 'Xenoblade Chronicles 3', 59.99, 12, '⚔️', 'Epic JRPG adventure concludes the trilogy.', '2025-08-23 11:57:10', '2025-08-23 11:57:10'),
(13, 'Fire Emblem Engage', 59.99, 7, '🔥', 'Strategic RPG with legendary heroes.', '2025-08-23 11:57:10', '2025-08-23 13:51:05'),
(14, 'DK Country: Tropical Freeze', 49.99, 22, '🍌', 'Swing into action with DK and friends.', '2025-08-23 11:57:10', '2025-08-23 11:57:10'),
(15, 'Paper Mario: Origami King', 59.99, 16, '📜', 'Mario\'s paper-crafted adventure.', '2025-08-23 11:57:10', '2025-08-23 11:57:10'),
(16, 'Mario Party Superstars', 59.99, 28, '🎲', 'Classic Mario Party boards and minigames.', '2025-08-23 11:57:10', '2025-08-23 11:57:10'),
(17, 'Pikmin 4', 59.99, 11, '🌱', 'Command colorful creatures in this strategy game.', '2025-08-23 11:57:10', '2025-08-23 13:16:41'),
(18, 'Nintendo Switch Console', 299.99, 0, '🎮', 'The ultimate gaming console for home and on-the-go.', '2025-08-23 11:57:10', '2025-08-23 12:39:45');

-- --------------------------------------------------------

--
-- Table structure for table `ps5_games`
--

CREATE TABLE `ps5_games` (
  `id` int(11) NOT NULL,
  `game_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `emoji` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `description` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ps5_games`
--

INSERT INTO `ps5_games` (`id`, `game_name`, `price`, `quantity`, `emoji`, `description`) VALUES
(1, 'Spider-Man 2', 69.99, 52, '🕷️', 'Ultimate Spider-Man adventure with dual heroes.'),
(2, 'Gran Turismo 7', 59.99, 40, '🏎️', 'Ultimate racing simulation experience.'),
(3, 'God of War Ragnarök', 69.99, 46, '⚔️', 'Epic Norse mythology adventure.'),
(4, 'Cyberpunk 2077', 49.99, 30, '🌆', 'Futuristic open world RPG adventure.'),
(5, 'Horizon Forbidden West', 59.99, 35, '🦖', 'Post-apocalyptic world with robotic creatures.'),
(6, 'FIFA 24', 69.99, 60, '⚽', 'Latest FIFA with updated features.'),
(7, 'NBA 2K24', 59.99, 55, '🏀', 'Ultimate basketball simulation game.'),
(8, 'The Last of Us Part I', 69.99, 25, '🎸', 'Emotional survival adventure remake.'),
(9, 'Call of Duty: MW III', 69.99, 68, '🚁', 'Intense military action shooter.'),
(10, 'Stellar Blade', 59.99, 30, '🌟', 'Sci-fi action adventure game.'),
(11, 'Batman Arkham Collection', 39.99, 18, '🦇', 'Complete Batman trilogy bundle.'),
(12, 'Mortal Kombat 1', 69.99, 30, '🎭', 'Ultimate fighting game experience.'),
(13, 'Assassin\'s Creed Mirage', 49.99, 25, '🌊', 'Return to classic stealth gameplay.'),
(14, 'Resident Evil 4', 59.99, 28, '🔫', 'Survival horror masterpiece remake.'),
(15, 'F1 23', 59.99, 15, '🏁', 'Official Formula 1 racing game.'),
(16, 'Ratchet & Clank: Rift Apart', 49.99, 22, '⚡', 'Interdimensional action adventure.'),
(17, 'Ghost of Tsushima Director\'s Cut', 69.99, 30, '🗡️', 'Samurai epic with enhanced content.'),
(18, 'PlayStation 5', 999.99, 9, '🎮', 'The ultimate home gaming console.');

-- --------------------------------------------------------

--
-- Table structure for table `retro_games`
--

CREATE TABLE `retro_games` (
  `id` int(11) NOT NULL,
  `game_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `platform` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `emoji` varchar(10) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `retro_games`
--

INSERT INTO `retro_games` (`id`, `game_name`, `quantity`, `platform`, `price`, `emoji`, `description`, `created_at`, `updated_at`) VALUES
(1, 'PAC-MAN', 50, 'Arcade', 24.99, '🟡', 'The legendary arcade classic that started it all.', '2025-08-23 17:32:22', '2025-08-23 17:32:22'),
(2, 'SPACE INVADERS', 45, 'Arcade', 19.99, '👾', 'Defend Earth from the alien invasion!', '2025-08-23 17:32:22', '2025-08-23 17:32:22'),
(3, 'TETRIS', 60, 'Multiple', 29.99, '🧩', 'The ultimate puzzle game that never gets old.', '2025-08-23 17:32:22', '2025-08-23 17:32:22'),
(4, 'DONKEY KONG', 35, 'Arcade', 34.99, '🦍', 'Mario\'s first adventure climbing barrels.', '2025-08-23 17:32:22', '2025-08-23 17:32:22'),
(5, 'FROGGER', 40, 'Arcade', 22.99, '🐸', 'Help the frog cross the dangerous road.', '2025-08-23 17:32:22', '2025-08-23 17:32:22'),
(6, 'ASTEROIDS', 38, 'Arcade', 21.99, '🚀', 'Navigate through the asteroid field.', '2025-08-23 17:32:22', '2025-08-23 17:32:22'),
(7, 'STREET FIGHTER II', 55, 'Arcade', 39.99, '👊', 'The ultimate fighting game experience.', '2025-08-23 17:32:22', '2025-08-23 17:32:22'),
(8, 'GALAGA', 42, 'Arcade', 24.99, '🛸', 'Classic space shooter with formation flying.', '2025-08-23 17:32:22', '2025-08-23 17:32:22'),
(9, 'CENTIPEDE', 47, 'Arcade', 19.99, '🐛', 'Shoot the centipede before it reaches you.', '2025-08-23 17:32:22', '2025-08-23 17:32:22'),
(10, 'MISSILE COMMAND', 43, 'Arcade', 22.99, '🎯', 'Defend your cities from nuclear attack.', '2025-08-23 17:32:22', '2025-08-23 17:32:22'),
(11, 'Q*BERT', 39, 'Arcade', 26.99, '🔶', 'Hop on cubes and avoid the enemies.', '2025-08-23 17:32:22', '2025-08-23 17:32:22'),
(12, 'DIG DUG', 44, 'Arcade', 23.99, '⛏️', 'Dig tunnels and defeat underground enemies.', '2025-08-23 17:32:22', '2025-08-23 17:32:22'),
(13, 'DEFENDER', 41, 'Arcade', 25.99, '🛡️', 'Protect humanoids from alien abduction.', '2025-08-23 17:32:22', '2025-08-23 17:32:22'),
(14, 'JOUST', 46, 'Arcade', 27.99, '🦅', 'Medieval knights riding flying ostrich.', '2025-08-23 17:32:22', '2025-08-23 17:32:22'),
(15, 'ROBOTRON 2084', 37, 'Arcade', 29.99, '🤖', 'Survive the robot apocalypse in 2084.', '2025-08-23 17:32:22', '2025-08-23 17:32:22'),
(16, 'TEMPEST', 48, 'Arcade', 31.99, '🌪️', 'Navigate the geometric tube shooter.', '2025-08-23 17:32:22', '2025-08-23 17:32:22'),
(17, 'PHOENIX', 52, 'Arcade', 28.99, '🔥', 'Battle alien birds in space combat.', '2025-08-23 17:32:22', '2025-08-23 17:32:22'),
(18, 'Retro Arcade Console', 23, 'Hardware', 199.99, '🕹️', 'Complete retro gaming console with 100+ pre-installed games.', '2025-08-23 17:32:22', '2025-08-23 17:53:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `failed_attempts` int(11) DEFAULT 0,
  `last_failed_attempt` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `email`, `password`, `created_at`, `is_active`, `failed_attempts`, `last_failed_attempt`, `last_login`, `updated_at`) VALUES
(1, 'abd', 'abddweikat2@gmail.com', '$2y$10$6VqHslIwp39nE.WAsKIZYuCxnwGPGscFA0Sn/FBNIlc8NIEveZb8S', '2025-08-16 11:37:33', 1, 0, NULL, '2025-08-17 12:42:52', '2025-08-20 09:41:29'),
(2, 'abd', 's12324404@gmail.com', '$2y$10$LQxLCBrBq8ihh/LjWn2R4.RBbMKvLFMSN0oUepOjR/svDQnUhpyaK', '2025-08-17 09:07:26', 1, 0, NULL, NULL, '2025-08-17 09:42:20'),
(3, 'abd', 'sqwer@1234.com', '$2y$10$89UWnU0ndSLybN658RCaa..4J18mWBYGTFtduMUdj/qLzOgt0Sc4O', '2025-08-17 09:16:46', 1, 0, NULL, NULL, '2025-08-17 09:42:20'),
(4, 'abd', 'abd@gmail.com', '$2y$10$NQHVBiVx.3rQHhz.9vlWGOxGzrqfmYo6vZ1jwhSJ/s49o9fSrJsC2', '2025-08-17 09:33:59', 1, 0, NULL, NULL, '2025-08-17 09:42:20'),
(5, 'ahmed', 'ahmedhamad865@icloud.com', '$2y$10$bPmanP81BizaGzXVsFH8he2cJDN3QEY4b.HrK7Kdc6Nrq6s6Fc4Ru', '2025-08-22 20:10:57', 1, 0, NULL, NULL, '2025-08-22 20:11:06');

-- --------------------------------------------------------

--
-- Table structure for table `xbox_games`
--

CREATE TABLE `xbox_games` (
  `id` int(11) NOT NULL,
  `game_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `emoji` varchar(10) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `xbox_games`
--

INSERT INTO `xbox_games` (`id`, `game_name`, `price`, `quantity`, `emoji`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Halo Infinite', 59.99, 50, '⭕', 'Master Chief returns in the most ambitious Halo yet.', '2025-08-23 17:10:31', '2025-08-23 17:10:31'),
(2, 'Forza Horizon 5', 59.99, 40, '🏎️', 'Mexico\'s vibrant world awaits in this racing paradise.', '2025-08-23 17:10:31', '2025-08-23 17:10:31'),
(3, 'Gears 5', 39.99, 35, '⚙️', 'Epic third-person shooter with co-op campaign.', '2025-08-23 17:10:31', '2025-08-23 17:10:31'),
(4, 'Microsoft Flight Simulator', 59.99, 25, '✈️', 'Soar through realistic skies around the world.', '2025-08-23 17:10:31', '2025-08-23 17:10:31'),
(5, 'Sea of Thieves', 39.99, 30, '🏴‍☠️', 'Multiplayer pirate adventure on the high seas.', '2025-08-23 17:10:31', '2025-08-23 17:10:31'),
(6, 'Call of Duty: MW III', 69.99, 60, '🎯', 'Intense military action shooter experience.', '2025-08-23 17:10:31', '2025-08-23 17:10:31'),
(7, 'FIFA 24', 69.99, 55, '⚽', 'The world\'s game with enhanced features.', '2025-08-23 17:10:31', '2025-08-23 17:10:31'),
(8, 'Starfield', 69.99, 45, '🌌', 'Bethesda\'s epic space exploration RPG.', '2025-08-23 17:10:31', '2025-08-23 17:10:31'),
(9, 'Assassin\'s Creed Mirage', 49.99, 30, '🏛️', 'Return to classic stealth gameplay in Baghdad.', '2025-08-23 17:10:31', '2025-08-23 17:22:33'),
(10, 'Cyberpunk 2077', 49.99, 35, '🌆', 'Futuristic open world RPG adventure.', '2025-08-23 17:10:31', '2025-08-23 17:18:22'),
(11, 'Resident Evil 4', 59.99, 28, '🧟', 'Survival horror masterpiece remake.', '2025-08-23 17:10:31', '2025-08-23 17:10:31'),
(12, 'Diablo IV', 69.99, 32, '👹', 'Return to darkness in this action RPG.', '2025-08-23 17:10:31', '2025-08-23 17:10:31'),
(13, 'Mortal Kombat 1', 69.99, 30, '🥊', 'Ultimate fighting game experience.', '2025-08-23 17:10:31', '2025-08-23 17:10:31'),
(14, 'The Witcher 3: Wild Hunt', 39.99, 40, '🗡️', 'Epic fantasy RPG complete edition.', '2025-08-23 17:10:31', '2025-08-23 17:10:31'),
(15, 'NBA 2K24', 59.99, 45, '🏀', 'Ultimate basketball simulation game.', '2025-08-23 17:10:31', '2025-08-23 17:10:31'),
(16, 'Baldur\'s Gate 3', 59.99, 38, '🐉', 'Epic D&D adventure with deep storytelling.', '2025-08-23 17:10:31', '2025-08-23 17:10:31'),
(17, 'Age of Empires IV', 49.99, 25, '🏰', 'Real-time strategy masterpiece returns.', '2025-08-23 17:10:31', '2025-08-23 17:10:31'),
(18, 'Xbox Series X', 499.99, 19, '🎮', 'The most powerful Xbox console ever created.', '2025-08-23 17:10:31', '2025-08-23 17:24:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_product_key` (`user_id`,`product_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_game` (`user_id`,`game_name`);

--
-- Indexes for table `featured_games`
--
ALTER TABLE `featured_games`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nintendo_games`
--
ALTER TABLE `nintendo_games`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_game_name` (`game_name`),
  ADD KEY `idx_price` (`price`);

--
-- Indexes for table `ps5_games`
--
ALTER TABLE `ps5_games`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `game_name_unique` (`game_name`);

--
-- Indexes for table `retro_games`
--
ALTER TABLE `retro_games`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `xbox_games`
--
ALTER TABLE `xbox_games`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `featured_games`
--
ALTER TABLE `featured_games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `nintendo_games`
--
ALTER TABLE `nintendo_games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `ps5_games`
--
ALTER TABLE `ps5_games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `retro_games`
--
ALTER TABLE `retro_games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `xbox_games`
--
ALTER TABLE `xbox_games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
