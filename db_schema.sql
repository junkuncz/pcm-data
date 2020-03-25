# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: localhost (MySQL 5.7.21)
# Database: cafe_original
# Generation Time: 2020-03-24 10:16:47 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table cafe_products_attributes_en
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cafe_products_attributes_en`;

CREATE TABLE `cafe_products_attributes_en` (
  `sku` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `value` varchar(16384) DEFAULT NULL,
  KEY `sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table cafe_products_attributes_en_backup
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cafe_products_attributes_en_backup`;

CREATE TABLE `cafe_products_attributes_en_backup` (
  `sku` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `value` varchar(16384) DEFAULT NULL,
  KEY `sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table cafe_products_attributes_fr
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cafe_products_attributes_fr`;

CREATE TABLE `cafe_products_attributes_fr` (
  `sku` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `value` varchar(16384) DEFAULT NULL,
  KEY `sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table cafe_products_attributes_fr_backup
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cafe_products_attributes_fr_backup`;

CREATE TABLE `cafe_products_attributes_fr_backup` (
  `sku` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `value` varchar(16384) DEFAULT NULL,
  KEY `sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table cafe_products_backup
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cafe_products_backup`;

CREATE TABLE `cafe_products_backup` (
  `category` int(10) NOT NULL DEFAULT '0',
  `sku` varchar(255) DEFAULT NULL,
  `upc` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `discontinuedModelFlag` tinyint(1) NOT NULL DEFAULT '0',
  `linkText` varchar(255) DEFAULT NULL,
  `metatag` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `siteTitle` varchar(255) DEFAULT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `descriptionMedium` text,
  `modelStatus` varchar(255) DEFAULT NULL,
  `keywords` varchar(255) DEFAULT NULL,
  `realWeight` varchar(255) DEFAULT NULL,
  `weight` varchar(255) DEFAULT NULL,
  `depth` varchar(255) DEFAULT NULL,
  `depthInches` varchar(255) DEFAULT NULL,
  `descriptionLong` text,
  `height` varchar(255) DEFAULT NULL,
  `heightInches` varchar(255) DEFAULT NULL,
  `length` varchar(255) DEFAULT NULL,
  `lengthInches` varchar(255) DEFAULT NULL,
  `realDepth` varchar(255) DEFAULT NULL,
  `realDepthInches` varchar(255) DEFAULT NULL,
  `realHeight` varchar(255) DEFAULT NULL,
  `realHeightInches` varchar(255) DEFAULT NULL,
  `realLength` varchar(255) DEFAULT NULL,
  `realLengthInches` varchar(255) DEFAULT NULL,
  `html` text,
  `modelStartDate` varchar(255) DEFAULT NULL,
  `modelEndDate` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table cafe_products_documents_en
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cafe_products_documents_en`;

CREATE TABLE `cafe_products_documents_en` (
  `sku` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(4096) NOT NULL DEFAULT '',
  `downloaded` tinyint(1) NOT NULL DEFAULT '0',
  KEY `sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table cafe_products_documents_en_backup
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cafe_products_documents_en_backup`;

CREATE TABLE `cafe_products_documents_en_backup` (
  `sku` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(4096) NOT NULL DEFAULT '',
  `downloaded` tinyint(1) NOT NULL DEFAULT '0',
  KEY `sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table cafe_products_documents_fr
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cafe_products_documents_fr`;

CREATE TABLE `cafe_products_documents_fr` (
  `sku` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(4096) NOT NULL DEFAULT '',
  `downloaded` tinyint(1) NOT NULL DEFAULT '0',
  KEY `sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table cafe_products_en
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cafe_products_en`;

CREATE TABLE `cafe_products_en` (
  `sku` varchar(255) NOT NULL,
  `category` int(10) unsigned NOT NULL DEFAULT '0',
  `upc` varchar(255) DEFAULT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `modelStatus` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `linkText` varchar(255) DEFAULT NULL,
  `metatag` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `siteTitle` varchar(255) DEFAULT NULL,
  `descriptionMedium` text,
  `descriptionLong` text,
  `keywords` varchar(255) DEFAULT NULL,
  `realWeight` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `weight` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `depth` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `depthInches` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `height` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `heightInches` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `length` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `lengthInches` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `realDepth` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `realDepthInches` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `realHeight` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `realHeightInches` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `realLength` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `realLengthInches` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `html` text,
  `modelStartDate` varchar(50) NOT NULL DEFAULT '',
  `modelEndDate` varchar(50) NOT NULL DEFAULT '',
  `features` text,
  PRIMARY KEY (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table cafe_products_fr
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cafe_products_fr`;

CREATE TABLE `cafe_products_fr` (
  `sku` varchar(255) NOT NULL,
  `category` int(10) unsigned NOT NULL DEFAULT '0',
  `upc` varchar(255) DEFAULT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `modelStatus` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `linkText` varchar(255) DEFAULT NULL,
  `metatag` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `siteTitle` varchar(255) DEFAULT NULL,
  `descriptionMedium` text,
  `descriptionLong` text,
  `keywords` varchar(255) DEFAULT NULL,
  `realWeight` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `weight` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `depth` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `depthInches` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `height` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `heightInches` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `length` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `lengthInches` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `realDepth` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `realDepthInches` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `realHeight` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `realHeightInches` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `realLength` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `realLengthInches` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `html` text,
  `modelStartDate` varchar(50) NOT NULL DEFAULT '',
  `modelEndDate` varchar(50) NOT NULL DEFAULT '',
  `features` text,
  PRIMARY KEY (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table cafe_products_fr_backup
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cafe_products_fr_backup`;

CREATE TABLE `cafe_products_fr_backup` (
  `sku` varchar(255) NOT NULL,
  `category` int(10) unsigned NOT NULL DEFAULT '0',
  `upc` varchar(255) DEFAULT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `modelStatus` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `linkText` varchar(255) DEFAULT NULL,
  `metatag` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `siteTitle` varchar(255) DEFAULT NULL,
  `descriptionMedium` text,
  `descriptionLong` text,
  `keywords` varchar(255) DEFAULT NULL,
  `realWeight` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `weight` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `depth` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `depthInches` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `height` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `heightInches` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `length` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `lengthInches` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `realDepth` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `realDepthInches` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `realHeight` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `realHeightInches` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `realLength` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `realLengthInches` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `html` text,
  `modelStartDate` varchar(50) NOT NULL DEFAULT '',
  `modelEndDate` varchar(50) NOT NULL DEFAULT '',
  `features` text,
  PRIMARY KEY (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table cafe_products_highlights_en
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cafe_products_highlights_en`;

CREATE TABLE `cafe_products_highlights_en` (
  `sku` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `imageUrl` varchar(4096) DEFAULT NULL,
  `videoUrl` varchar(4096) DEFAULT NULL,
  `downloaded` tinyint(1) NOT NULL DEFAULT '0',
  KEY `sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table cafe_products_highlights_en_backup
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cafe_products_highlights_en_backup`;

CREATE TABLE `cafe_products_highlights_en_backup` (
  `sku` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `imageUrl` varchar(4096) DEFAULT NULL,
  `videoUrl` varchar(4096) DEFAULT NULL,
  `downloaded` tinyint(1) NOT NULL DEFAULT '0',
  KEY `sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table cafe_products_highlights_fr
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cafe_products_highlights_fr`;

CREATE TABLE `cafe_products_highlights_fr` (
  `sku` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `imageUrl` varchar(4096) DEFAULT NULL,
  `videoUrl` varchar(4096) DEFAULT NULL,
  `downloaded` tinyint(1) NOT NULL DEFAULT '0',
  KEY `sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table cafe_products_highlights_fr_backup
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cafe_products_highlights_fr_backup`;

CREATE TABLE `cafe_products_highlights_fr_backup` (
  `sku` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `imageUrl` varchar(4096) DEFAULT NULL,
  `videoUrl` varchar(4096) DEFAULT NULL,
  `downloaded` tinyint(1) NOT NULL DEFAULT '0',
  KEY `sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table cafe_products_images_en
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cafe_products_images_en`;

CREATE TABLE `cafe_products_images_en` (
  `sku` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(255) NOT NULL DEFAULT '',
  `label` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(4096) NOT NULL DEFAULT '',
  `downloaded` tinyint(1) NOT NULL DEFAULT '0',
  KEY `sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table cafe_products_images_en_backup
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cafe_products_images_en_backup`;

CREATE TABLE `cafe_products_images_en_backup` (
  `sku` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(255) NOT NULL DEFAULT '',
  `label` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(4096) NOT NULL DEFAULT '',
  `downloaded` tinyint(1) NOT NULL DEFAULT '0',
  KEY `sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table cafe_products_images_fr
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cafe_products_images_fr`;

CREATE TABLE `cafe_products_images_fr` (
  `sku` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT '',
  `label` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(4096) NOT NULL DEFAULT '',
  `downloaded` tinyint(1) NOT NULL DEFAULT '0',
  KEY `sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
