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
        $stmt = $conn->prepare("SELECT s.song_number, s.title, s.artist FROM room_queues rq JOIN songs s ON rq.song_id = s.id WHERE rq.room_id = ? ORDER BY rq.created_at ASC");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $queue = [];
        while ($row = $result->fetch_assoc()) {
            $queue[] = $row;
        }
        $stmt->close();
        echo json_encode($queue);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        $song_id = $data->song_id ?? 0;

        if ($song_id > 0) {
            $stmt = $conn->prepare("INSERT INTO room_queues (room_id, song_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $room_id, $song_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add song to queue']);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Song ID is required']);
        }
        break;

    case 'DELETE':
        // Deletes the first song in the queue (i.e., the one that just finished playing)
        $stmt = $conn->prepare("DELETE FROM room_queues WHERE room_id = ? ORDER BY created_at ASC LIMIT 1");
        $stmt->bind_param("i", $room_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove song from queue']);
        }
        $stmt->close();
        break;

    default:
        header("HTTP/1.0 405 Method Not Allowed");
        break;
}

$conn->close();
?>
