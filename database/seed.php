<?php
require_once __DIR__ . '/../backend/includes/db.php';

try {
    $songs = [
        ['1001', 'Bohemian Rhapsody', 'Queen', 'youtube', 'fJ9rUzIMcZQ'],
        ['1002', 'Stairway to Heaven', 'Led Zeppelin', 'youtube', 'QkF3oxziUI4'],
        ['1003', 'Hotel California', 'Eagles', 'youtube', '098391-knqq'],
        ['1004', 'Sweet Child O\' Mine', 'Guns N\' Roses', 'youtube', '1w7OgIMMRc4'],
        ['1005', 'Smells Like Teen Spirit', 'Nirvana', 'youtube', 'hTWKbfoikeg'],
        ['1006', 'Imagine', 'John Lennon', 'youtube', 'YkgkThdzX-8'],
        ['1007', 'One', 'U2', 'youtube', 'ftjEcrrf7r0'],
        ['1008', 'Billie Jean', 'Michael Jackson', 'youtube', 'Zi_XLOBDo_Y'],
        ['1009', 'Hey Jude', 'The Beatles', 'youtube', 'A_MjCqQoLLA'],
        ['1010', 'Like a Rolling Stone', 'Bob Dylan', 'youtube', 'IwOfCgkyEj0']
    ];

    $stmt = $conn->prepare('INSERT INTO songs (song_number, title, artist, source_type, video_source) VALUES (?, ?, ?, ?, ?)');

    foreach ($songs as $song) {
        $stmt->execute($song);
    }

    echo "Database seeded successfully!\n";

} catch (PDOException $e) {
    die("Error seeding database: " . $e->getMessage() . "\n");
}
?>
