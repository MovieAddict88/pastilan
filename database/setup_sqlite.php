<?php
$db_file = __DIR__ . '/karaoke.db';
$db = new PDO("sqlite:$db_file");

$db->exec("
CREATE TABLE IF NOT EXISTS rooms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    password TEXT NOT NULL,
    join_code TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
");

$db->exec("
CREATE TABLE IF NOT EXISTS room_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_id INTEGER NOT NULL,
    username TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms (id)
);
");

$db->exec("
CREATE TABLE IF NOT EXISTS songs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    song_number TEXT NOT NULL,
    title TEXT NOT NULL,
    artist TEXT NOT NULL,
    video_source TEXT NOT NULL,
    duration INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
");

$db->exec("
CREATE TABLE IF NOT EXISTS queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_id INTEGER NOT NULL,
    song_id INTEGER NOT NULL,
    user_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms (id),
    FOREIGN KEY (song_id) REFERENCES songs (id)
);
");

echo "Database and tables created successfully.";
?>
