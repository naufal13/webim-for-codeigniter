
DROP TABLE IF EXISTS `webim_settings`;
CREATE TABLE `webim_settings` (
	  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	  `uid` varchar(40) NOT NULL DEFAULT '',
	  `data` text,
	  `created` datetime DEFAULT NULL,
	  `updated` datetime DEFAULT NULL,
	  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `webim_histories`;
CREATE TABLE `webim_histories` (
	  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	  `send` tinyint(1) DEFAULT NULL,
	  `type` varchar(20) DEFAULT NULL,
	  `to` varchar(50) NOT NULL,
	  `from` varchar(50) NOT NULL,
	  `nick` varchar(20) DEFAULT NULL COMMENT 'from nick',
	  `body` text,
	  `style` varchar(150) DEFAULT NULL,
	  `timestamp` double DEFAULT NULL,
	  `todel` tinyint(1) NOT NULL DEFAULT '0',
	  `fromdel` tinyint(1) NOT NULL DEFAULT '0',
	  `created` date DEFAULT NULL,
	  `updated` date DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  KEY `timestamp` (`timestamp`),
	  KEY `to` (`to`),
	  KEY `from` (`from`),
	  KEY `send` (`send`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `webim_rooms`;
CREATE TABLE `webim_rooms` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `owner` varchar(40) NOT NULL DEFAULT '',
      `name` varchar(40) NOT NULL,
      `nick` varchar(60) NOT NULL DEFAULT '',
      `topic` varchar(60) DEFAULT NULL,
      `created` datetime DEFAULT NULL,
      `updated` datetime DEFAULT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `room_name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `webim_members`;
CREATE TABLE `webim_members` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `room` varchar(60) NOT NULL DEFAULT '',
      `nick` varchar(60) DEFAULT NULL,
      `uid` varchar(40) NOT NULL DEFAULT '',
      `joined` datetime DEFAULT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `member_room_uid` (`room`,`uid`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `webim_blocked`;
CREATE TABLE `webim_blocked` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `uid` varchar(40) NOT NULL DEFAULT '',
      `room` varchar(60) NOT NULL DEFAULT '',
      `blocked` datetime DEFAULT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `blocked_room_uid` (`uid`,`room`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


