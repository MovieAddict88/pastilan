<?php
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/room_handler_errors.log');
error_log("Room handler script started.");

require_once '../includes/db.php';

header('Content-Type: application/json');

function generate_room_code() {
    return substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_room') {
        error_log("Action: create_room");
        $room_name = $_POST['room_name'] ?? '';
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($room_name) || empty($username) || empty($password)) {
            error_log("Validation failed: All fields are required.");
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit;
        }

        $room_code = generate_room_code();
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            error_log("Database transaction started.");
            $pdo->beginTransaction();

            error_log("Inserting into rooms table.");
            $stmt = $pdo->prepare("INSERT INTO rooms (room_name, password, room_code) VALUES (?, ?, ?)");
            $stmt->execute([$room_name, $hashed_password, $room_code]);
            $room_id = $pdo->lastInsertId();
            error_log("Room inserted with ID: " . $room_id);

            error_log("Inserting into room_members table.");
            $stmt = $pdo->prepare("INSERT INTO room_members (room_id, user_name) VALUES (?, ?)");
            $stmt->execute([$room_id, $username]);
            error_log("Room member inserted.");

            $pdo->commit();
            error_log("Database transaction committed.");

            error_log("Room created successfully with code: " . $room_code);
            echo json_encode(['success' => true, 'room_code' => $room_code]);
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Error creating room: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error creating room: ' . $e->getMessage()]);
        }
    } elseif ($action === 'join_room') {
        error_log("Action: join_room");
        $username = $_POST['username'] ?? '';
        $room_code = $_POST['room_code'] ?? '';

        if (empty($username) || empty($room_code)) {
            error_log("Validation failed: Username and room code are required.");
            echo json_encode(['success' => false, 'message' => 'Username and room code are required.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT id FROM rooms WHERE room_code = ?");
            $stmt->execute([$room_code]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($room) {
                $room_id = $room['id'];
                $stmt = $pdo->prepare("INSERT INTO room_members (room_id, user_name) VALUES (?, ?)");
                $stmt->execute([$room_id, $username]);
                error_log("User {$username} joined room {$room_id}.");
                echo json_encode(['success' => true, 'message' => 'Successfully joined the room.']);
            } else {
                error_log("Invalid room code: {$room_code}");
                echo json_encode(['success' => false, 'message' => 'Invalid room code.']);
            }
        } catch (Exception $e) {
            error_log('Error joining room: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error joining room: ' . $e->getMessage()]);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    error_log("Action: get_rooms");
    try {
        $stmt = $pdo->query("SELECT r.id, r.room_name, COUNT(m.id) AS member_count FROM rooms r LEFT JOIN room_members m ON r.id = m.room_id GROUP BY r.id");
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Rooms fetched: " . json_encode($rooms));
        echo json_encode($rooms);
    } catch (Exception $e) {
        error_log('Error fetching rooms: ' . $e->getMessage());
        echo json_encode([]);
    }
}
