-- Adminer 4.1.0 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';

CREATE TABLE `attacks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team` int(11) NOT NULL,
  `challenge` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `additional` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `team` (`team`,`challenge`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `bonus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team` int(11) NOT NULL,
  `value` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `type` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `challenges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `description` text NOT NULL,
  `score` int(11) NOT NULL,
  `key` text NOT NULL,
  `category` int(11) NOT NULL,
  `deactivated` tinyint(1) NOT NULL,
  `hidden` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `daily` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `used` tinyint(4) NOT NULL,
  `active` tinyint(4) NOT NULL,
  `difficulty` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `interact` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from` int(11) NOT NULL,
  `to` int(11) NOT NULL,
  `success` int(11) NOT NULL,
  `value` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `members` text NOT NULL,
  `code` text NOT NULL,
  `fails` int(11) NOT NULL,
  `lastlogin` datetime NOT NULL,
  `money` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `teams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `score` int(11) NOT NULL,
  `location` int(11) NOT NULL,
  `registration` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `local` tinyint(1) NOT NULL,
  `password` text NOT NULL,
  `email` text NOT NULL,
  `ip` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `temp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `registration` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `local` tinyint(4) NOT NULL,
  `password` text NOT NULL,
  `email` text NOT NULL,
  `key` text NOT NULL,
  `ip` text NOT NULL,
  `approved` tinyint(4) NOT NULL,
  `clicked` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `ticker` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `text` text NOT NULL,
  `image` text NOT NULL,
  `sound` text NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `script` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


-- 2015-01-12 04:54:55
