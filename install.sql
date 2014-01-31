CREATE TABLE `webim_settings` (
	  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	  `uid` int(11) unsigned NOT NULL,
	  `data` text,
	  `created_at` datetime DEFAULT NULL,
	  `updated_at` datetime DEFAULT NULL,
	  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

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
	  `created_at` date DEFAULT NULL,
	  `updated_at` date DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  KEY `timestamp` (`timestamp`),
	  KEY `to` (`to`),
	  KEY `from` (`from`),
	  KEY `send` (`send`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

