<?php
$db_file = __DIR__ . '/../../database/karaoke.db';
$conn = new PDO("sqlite:$db_file");

if(!$conn){
    die("ERROR: Could not connect. " . $conn->lastErrorMsg());
}
?>
