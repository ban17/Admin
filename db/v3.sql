-- Adminer 4.2.2 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

CREATE DATABASE admin_test  COLLATE utf8_unicode_ci;
USE admin_test;

DROP TABLE IF EXISTS `blog_categories`;
CREATE TABLE `blog_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `id_parent` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `blog_posts`;
CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8_unicode_ci NOT NULL,
  `excerpt` text COLLATE utf8_unicode_ci NOT NULL,
  `id_author` int(11) DEFAULT NULL,
  `id_parent` int(11) DEFAULT NULL,
  `id_website` int(11) DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `status` enum('published','draft','deleted','archive') COLLATE utf8_unicode_ci NOT NULL,
  `comment_status` enum('allowed','closed','hidden') COLLATE utf8_unicode_ci NOT NULL,
  `menu_order` int(11) NOT NULL,
  `post_type` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_author` (`id_author`),
  CONSTRAINT `blog_posts_ibfk_1` FOREIGN KEY (`id_author`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `blog_post_categories`;
CREATE TABLE `blog_post_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_post` int(11) NOT NULL,
  `id_category` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_post` (`id_post`),
  KEY `id_category` (`id_category`),
  CONSTRAINT `blog_post_categories_ibfk_1` FOREIGN KEY (`id_post`) REFERENCES `blog_posts` (`id`),
  CONSTRAINT `blog_post_categories_ibfk_2` FOREIGN KEY (`id_category`) REFERENCES `blog_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `blog_post_tags`;
CREATE TABLE `blog_post_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_post` int(11) NOT NULL,
  `id_tag` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_post` (`id_post`),
  KEY `id_tag` (`id_tag`),
  CONSTRAINT `blog_post_tags_ibfk_1` FOREIGN KEY (`id_post`) REFERENCES `blog_posts` (`id`),
  CONSTRAINT `blog_post_tags_ibfk_2` FOREIGN KEY (`id_tag`) REFERENCES `blog_tags` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `blog_tags`;
CREATE TABLE `blog_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pass` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `validated` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
  `reset_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `phone` varchar(24) COLLATE utf8_unicode_ci NOT NULL,
  `ip_history` varchar(1600) COLLATE utf8_unicode_ci NOT NULL,
  `last_login` datetime NOT NULL,
  `registration_ip` char(15) COLLATE utf8_unicode_ci NOT NULL,
  `registration_date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

