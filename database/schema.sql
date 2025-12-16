CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- You should insert a default admin user.
-- For example, to create a user 'admin' with password 'password' (hashed):
-- INSERT INTO `users` (username, password) VALUES ('admin', '$2y$10$w4I.5iN.d8Y2/3h9e.fGJO/f.U.z.f.sD.E.fGJO/f.U.z.f.sD');
-- NOTE: The hash is just an example, you should generate a secure one.

CREATE TABLE IF NOT EXISTS `songs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `song_number` varchar(6) NOT NULL,
  `title` varchar(255) NOT NULL,
  `artist` varchar(255) NOT NULL,
  `source_type` varchar(50) NOT NULL,
  `video_source` varchar(1024) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `song_number` (`song_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
