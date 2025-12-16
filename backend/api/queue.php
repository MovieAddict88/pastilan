<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../includes/db.php";

$method = $_SERVER['REQUEST_METHOD'];
$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;

if ($room_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Room ID is required']);
    exit;
}

switch ($method) {
    case 'GET':
        // Get the current queue for the room
        $stmt = $conn->prepare("SELECT s.song_number, s.title, s.artist FROM queue rq JOIN songs s ON rq.song_id = s.id WHERE rq.room_id = ? ORDER BY rq.created_at ASC");
        $stmt->execute([$room_id]);
        $queue = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($queue);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        $song_id = $data->song_id ?? 0;

        if ($song_id > 0) {
            $stmt = $conn->prepare("INSERT INTO queue (room_id, song_id) VALUES (?, ?)");
            if ($stmt->execute([$room_id, $song_id])) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add song to queue']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Song ID is required']);
        }
        break;

    case 'DELETE':
        // Deletes the first song in the queue (i.e., the one that just finished playing)
        $stmt = $conn->prepare("DELETE FROM queue WHERE id IN (SELECT id FROM queue WHERE room_id = ? ORDER BY created_at ASC LIMIT 1)");
        if ($stmt->execute([$room_id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove song from queue']);
        }
        break;

    default:
        header("HTTP/1.0 405 Method Not Allowed");
        break;
}
?>
