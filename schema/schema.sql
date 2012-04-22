SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `DaylightTimeline`
--
CREATE DATABASE `DaylightTimeline` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `DaylightTimeline`;

-- --------------------------------------------------------

--
-- Table structure for table `camImages`
--

CREATE TABLE IF NOT EXISTS `camImages` (
  `filename` varchar(50) NOT NULL,
  `uploadedAt` datetime NOT NULL,
  `averagePixelColorHex` varchar(6) NOT NULL,
  PRIMARY KEY (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
