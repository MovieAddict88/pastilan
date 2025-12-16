CREATE TABLE IF NOT EXISTS `users` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `songs` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `song_number` varchar(6) NOT NULL,
  `title` varchar(255) NOT NULL,
  `artist` varchar(255) NOT NULL,
  `source_type` varchar(50) NOT NULL,
  `video_source` varchar(1024) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `rooms` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `room_name` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `room_code` varchar(8) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `room_members` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `room_id` int(11) NOT NULL,
  `user_name` varchar(50) NOT NULL,
  `joined_at` datetime DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE
);
