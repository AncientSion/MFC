-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Erstellungszeit: 08. Mai 2019 um 22:00
-- Server-Version: 10.1.16-MariaDB
-- PHP-Version: 7.0.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `crawl`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `favs`
--

CREATE TABLE `favs` (
  `id` int(4) NOT NULL,
  `cardname` varchar(255) NOT NULL DEFAULT '',
  `setcode` varchar(3) NOT NULL DEFAULT '',
  `isFoil` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `favs`
--

INSERT INTO `favs` (`id`, `cardname`, `setcode`, `isFoil`) VALUES
(1, 'Mana Vault', 'UMA', 0),
(2, 'Sylvan Library', 'EMA', 1),
(3, 'Isochron Scepter', 'EMA', 1),
(4, 'Niv-Mizzet, Parun', 'GRN', 1),
(6, 'Saheeli Rai', 'KLD', 1),
(7, 'Saheeli Rai', 'KLD', 0),
(8, 'Scapeshift', 'M19', 0),
(9, 'Crucible of Worlds', 'M19', 0),
(10, 'Heart of Kiran', 'AER', 1),
(11, 'Fatal Push', 'AER', 1),
(18, 'Birthing Pod', 'NPH', 1);

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `favs`
--
ALTER TABLE `favs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `favs`
--
ALTER TABLE `favs`
  MODIFY `id` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
