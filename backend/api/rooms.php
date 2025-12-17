<?php
// backend/api/rooms.php
header("Content-Type: application/json; charset=UTF-8");
require_once "../includes/db.php";

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        get_rooms();
        break;
    case 'POST':
        handle_post();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function get_rooms() {
    global $conn;
    try {
        $stmt = $conn->query("SELECT r.id, r.name, COUNT(m.id) as member_count FROM rooms r LEFT JOIN room_members m ON r.id = m.room_id GROUP BY r.id, r.name");
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rooms);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch rooms: ' . $e->getMessage()]);
    }
}

function handle_post() {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['action'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No action specified']);
        return;
    }

    switch ($data['action']) {
        case 'create':
            create_room($data);
            break;
        case 'join':
            join_room($data);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

function create_room($data) {
    global $conn;

    if (!isset($data['room_name'], $data['username'], $data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields for creating a room']);
        return;
    }

    $room_name = $data['room_name'];
    $username = $data['username'];
    $password = $data['password'];
    $code = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("INSERT INTO rooms (name, password, code) VALUES (?, ?, ?)");
        $stmt->execute([$room_name, $hashed_password, $code]);
        $room_id = $conn->lastInsertId();

        $stmt = $conn->prepare("INSERT INTO room_members (room_id, username) VALUES (?, ?)");
        $stmt->execute([$room_id, $username]);

        $conn->commit();
        echo json_encode(['success' => true, 'code' => $code]);
    } catch (PDOException $e) {
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create room: ' . $e->getMessage()]);
    }
}

function join_room($data) {
    global $conn;

    if (!isset($data['username'], $data['code'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields for joining a room']);
        return;
    }

    $username = $data['username'];
    $code = $data['code'];

    try {
        $stmt = $conn->prepare("SELECT id FROM rooms WHERE code = ?");
        $stmt->execute([$code]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$room) {
            http_response_code(404);
            echo json_encode(['error' => 'Room not found']);
            return;
        }

        $room_id = $room['id'];

        $stmt = $conn->prepare("INSERT INTO room_members (room_id, username) VALUES (?, ?)");
        $stmt->execute([$room_id, $username]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to join room: ' . $e->getMessage()]);
    }
}
?>
