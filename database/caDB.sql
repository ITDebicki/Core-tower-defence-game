SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


CREATE DATABASE IF NOT EXISTS `caDB` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `caDB`;

CREATE TABLE IF NOT EXISTS `Friend` (
  `user_sender` varbinary(32) NOT NULL,
  `user_reciever` varbinary(32) NOT NULL,
  `accepted` tinyint(1) DEFAULT NULL,
  `opened` tinyint(1) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_sender`,`user_reciever`),
  KEY `fk_User_has_User_User2_idx` (`user_reciever`),
  KEY `fk_User_has_User_User1_idx` (`user_sender`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `LastLogin` (
  `user` varbinary(32) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user`,`timestamp`),
  KEY `fk_LastLogin_User1_idx` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `Map` (
  `idMap` int(4) NOT NULL AUTO_INCREMENT,
  `mapName` varchar(64) NOT NULL,
  `mapDescription` varchar(512) NOT NULL,
  `mapFile` varchar(128) NOT NULL,
  `mapImage` varchar(128) NOT NULL,
  `levelNo` int(4) NOT NULL,
  PRIMARY KEY (`idMap`),
  UNIQUE KEY `mapName_UNIQUE` (`mapName`),
  UNIQUE KEY `mapFile_UNIQUE` (`mapFile`),
  UNIQUE KEY `mapImage_UNIQUE` (`mapImage`),
  UNIQUE KEY `levelNo_UNIQUE` (`levelNo`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;

CREATE TABLE IF NOT EXISTS `Notification` (
  `idNotification` int(8) NOT NULL AUTO_INCREMENT,
  `user` varbinary(32) NOT NULL,
  `type` varchar(45) DEFAULT NULL COMMENT 'Link to a certain page. To be defined',
  `title` varchar(45) NOT NULL,
  `message` varchar(512) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `opened` tinyint(1) NOT NULL,
  PRIMARY KEY (`idNotification`),
  KEY `fk_Notification_User1_idx` (`user`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=49 ;

CREATE TABLE IF NOT EXISTS `Save` (
  `idSave` int(4) NOT NULL AUTO_INCREMENT,
  `lastUpdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user` varbinary(32) NOT NULL,
  `name` varchar(45) NOT NULL,
  `fileLocation` varchar(256) NOT NULL,
  `thumbnail` varchar(256) DEFAULT NULL,
  `map` int(4) NOT NULL,
  PRIMARY KEY (`idSave`),
  KEY `fk_Save_User_idx` (`user`),
  KEY `fk_Save_Map1_idx` (`map`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=22 ;

CREATE TABLE IF NOT EXISTS `Score` (
  `idScore` int(4) NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user` varbinary(32) NOT NULL,
  `map` int(4) NOT NULL,
  `scoreVal` int(32) DEFAULT NULL,
  PRIMARY KEY (`idScore`),
  KEY `fk_Score_User1_idx` (`user`),
  KEY `fk_Score_Map1_idx` (`map`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=65 ;

CREATE TABLE IF NOT EXISTS `User` (
  `username` varbinary(32) NOT NULL,
  `hash` varchar(256) NOT NULL,
  `email` varchar(128) NOT NULL,
  `experience` int(8) NOT NULL DEFAULT '0',
  `avatarFile` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


ALTER TABLE `Friend`
  ADD CONSTRAINT `fk_User_has_User_User1` FOREIGN KEY (`user_sender`) REFERENCES `User` (`username`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_User_has_User_User2` FOREIGN KEY (`user_reciever`) REFERENCES `User` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `LastLogin`
  ADD CONSTRAINT `fk_LastLogin_User1` FOREIGN KEY (`user`) REFERENCES `User` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `Notification`
  ADD CONSTRAINT `fk_Notification_User1` FOREIGN KEY (`user`) REFERENCES `User` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `Save`
  ADD CONSTRAINT `fk_Save_Map1` FOREIGN KEY (`map`) REFERENCES `Map` (`idMap`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_Save_User` FOREIGN KEY (`user`) REFERENCES `User` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `Score`
  ADD CONSTRAINT `fk_Score_Map1` FOREIGN KEY (`map`) REFERENCES `Map` (`idMap`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_Score_User1` FOREIGN KEY (`user`) REFERENCES `User` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

INSERT INTO `Map` (`idMap`, `mapName`, `mapDescription`, `mapFile`, `mapImage`, `levelNo`) VALUES
(1, 'exampleMap', 'Some description', 'exampleMap.mp', 'exampleMap.png', 1),
(2, 'exampleMap2', 'some other description', 'exampleMap2.mp', 'exampleMap2.png', 2),
(3, 'The Corridors', 'Three ways', 'theCorridors.mp', 'theCorridors.png', 3),
(4, 'Triple the fun...', 'Just in case you thought the others where too easy', 'tripleTheFun.mp', 'tripleTheFun.png', 4),
(5, 'The spiral', ' ', 'theSpiral.mp', 'theSpiral.png', 5),
(6, 'Cross roads', ' ', 'crossRoads.mp', 'crossRoads.png', 6);
