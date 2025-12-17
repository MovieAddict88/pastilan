<?php
// database/setup_sqlite.php
$db_path = __DIR__ . '/karaoke.db';
$schema_path = __DIR__ . '/schema.sql';

// Delete existing database file to start fresh
if (file_exists($db_path)) {
    unlink($db_path);
}

try {
    // Create new SQLite database
    $conn = new PDO('sqlite:' . $db_path);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Read and execute schema
    $schema = file_get_contents($schema_path);
    $conn->exec($schema);

    echo "Database created and schema loaded successfully.\n";

    // Insert sample data
    $conn->exec("INSERT INTO songs (song_number, title, artist, source_type, video_source) VALUES ('1001', 'Never Gonna Give You Up', 'Rick Astley', 'youtube', 'dQw4w9WgXcQ')");
    $conn->exec("INSERT INTO songs (song_number, title, artist, source_type, video_source) VALUES ('1002', 'Bohemian Rhapsody', 'Queen', 'youtube', 'fJ9rUzIMcZQ')");

    echo "Sample data inserted successfully.\n";

} catch (PDOException $e) {
    die("Error setting up database: " . $e->getMessage() . "\n");
}
?>