<?php
// backend/includes/db.php

try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/../../database/karaoke.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}
