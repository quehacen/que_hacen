-- phpMyAdmin SQL Dump
-- version 3.4.10.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 08, 2012 at 07:25 PM
-- Server version: 5.5.28
-- PHP Version: 5.3.10-1ubuntu3.4

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `votaciones`
--

-- --------------------------------------------------------

--
-- Table structure for table `diputado`
--

CREATE TABLE IF NOT EXISTS `diputado` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `iniciativa`
--

CREATE TABLE IF NOT EXISTS `iniciativa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `num_expediente` varchar(16) NOT NULL,
  `url` varchar(500) CHARACTER SET ascii NOT NULL,
  `html` text NOT NULL,
  `titulo` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `num_expediente` (`num_expediente`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=451 ;

-- --------------------------------------------------------

--
-- Table structure for table `pending_url`
--

CREATE TABLE IF NOT EXISTS `pending_url` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(500) CHARACTER SET ascii NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=91 ;

-- --------------------------------------------------------

--
-- Table structure for table `session`
--

CREATE TABLE IF NOT EXISTS `session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(500) CHARACTER SET ascii NOT NULL,
  `fecha` varchar(10) NOT NULL,
  `html` text NOT NULL,
  `num_sesion` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=112 ;

-- --------------------------------------------------------

--
-- Table structure for table `votacion`
--

CREATE TABLE IF NOT EXISTS `votacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `xml_url` varchar(500) CHARACTER SET armscii8 NOT NULL,
  `xml` text NOT NULL,
  `fecha` varchar(10) NOT NULL,
  `legislatura` int(11) NOT NULL,
  `sesion` int(11) NOT NULL,
  `num` int(11) NOT NULL,
  `num_expediente` varchar(16) NOT NULL,
  `puntos` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `xml_url` (`xml_url`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=717 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
