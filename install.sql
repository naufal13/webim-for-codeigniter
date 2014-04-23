
DROP TABLE IF EXISTS `webim_settings`;
CREATE TABLE `webim_settings` (
	  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	  `uid` varchar(40) NOT NULL DEFAULT '',
	  `data` text,
	  `created` datetime DEFAULT NULL,
	  `updated` datetime DEFAULT NULL,
      UNIQUE KEY `webim_setting_uid` (`uid`),
	  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

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
	  KEY `webim_history_timestamp` (`timestamp`),
	  KEY `webim_history_to` (`to`),
	  KEY `webim_history_from` (`from`),
	  KEY `webim_history_send` (`send`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `webim_rooms`;
CREATE TABLE `webim_rooms` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `owner` varchar(40) NOT NULL,
      `name` varchar(40) NOT NULL,
      `nick` varchar(60) NOT NULL DEFAULT '',
      `topic` varchar(60) DEFAULT NULL,
      `url` varchar(100) DEFAULT '#',
      `created` datetime DEFAULT NULL,
      `updated` datetime DEFAULT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `webim_room_name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `webim_members`;
CREATE TABLE `webim_members` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `room` varchar(60) NOT NULL,
      `uid` varchar(40) NOT NULL,
      `nick` varchar(60) NOT NULL,
      `joined` datetime DEFAULT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `webim_member_room_uid` (`room`,`uid`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `webim_blocked`;
CREATE TABLE `webim_blocked` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `room` varchar(60) NOT NULL,
      `uid` varchar(40) NOT NULL,
      `blocked` datetime DEFAULT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `webim_blocked_room_uid` (`uid`,`room`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `webim_visitors`;
CREATE TABLE `webim_visitors` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `name` varchar(60) DEFAULT NULL,
      `ipaddr` varchar(60) DEFAULT NULL,
      `url` varchar(100) DEFAULT NULL,
      `referer` varchar(100) DEFAULT NULL,
      `location` varchar(100) DEFAULT NULL,
      `created` datetime DEFAULT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `webim_visitor_name` (`name`)
)ENGINE=MyISAM AUTO_INCREMENT=10000 DEFAULT CHARSET=utf8;

