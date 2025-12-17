<?php
// backend/includes/db.php

// Define the path to the SQLite database file
define('DB_PATH', __DIR__ . '/../../database/karaoke.db');

try {
    // Create a new PDO instance
    $conn = new PDO('sqlite:' . DB_PATH);

    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // If connection fails, die and show error
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}
?>
