<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit;
}

require_once "../includes/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $artist = isset($_POST['artist']) ? trim($_POST['artist']) : '';
    $video_link = isset($_POST['video_link']) ? trim($_POST['video_link']) : '';

    if (empty($title) || empty($artist) || empty($video_link)) {
        echo json_encode(['success' => false, 'message' => 'Missing required song data.']);
        exit;
    }

    try {
        $sql_check_duplicate = "SELECT id FROM songs WHERE video_source = :video_source";
        $stmt_check = $conn->prepare($sql_check_duplicate);
        $stmt_check->bindParam(':video_source', $video_link, PDO::PARAM_STR);
        $stmt_check->execute();
        if ($stmt_check->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'This song already exists in the database.']);
            exit;
        }

        do {
            $song_number = rand(100000, 999999);
            $sql_check = "SELECT id FROM songs WHERE song_number = :song_number";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bindParam(':song_number', $song_number, PDO::PARAM_STR);
            $stmt_check->execute();
            $is_duplicate = $stmt_check->rowCount() > 0;
        } while ($is_duplicate);

        $source_type = 'link';
        $sql_insert = "INSERT INTO songs (song_number, title, artist, source_type, video_source) VALUES (:song_number, :title, :artist, :source_type, :video_source)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bindParam(':song_number', $song_number, PDO::PARAM_STR);
        $stmt_insert->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt_insert->bindParam(':artist', $artist, PDO::PARAM_STR);
        $stmt_insert->bindParam(':source_type', $source_type, PDO::PARAM_STR);
        $stmt_insert->bindParam(':video_source', $video_link, PDO::PARAM_STR);

        if ($stmt_insert->execute()) {
            echo json_encode(['success' => true, 'message' => 'Song added successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add the song.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    unset($conn);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
