--
-- Tabella per le sottoscrizioni push
--
CREATE TABLE IF NOT EXISTS `push_subscriptions` (
  `user_id` int(11) NOT NULL,
  `endpoint` varchar(512) NOT NULL,
  `p256dh` varchar(255) NOT NULL,
  `auth` varchar(255) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
