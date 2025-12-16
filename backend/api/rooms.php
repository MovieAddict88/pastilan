<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../includes/db.php";

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // List all rooms
        $sql = "SELECT r.id, r.name, COUNT(ru.id) as user_count FROM rooms r LEFT JOIN room_users ru ON r.id = ru.room_id GROUP BY r.id";
        $result = $conn->query($sql);
        $rooms = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $rooms[] = $row;
            }
        }
        echo json_encode($rooms);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        // Action: 'create' or 'join'
        $action = $data->action ?? '';

        if ($action == 'create') {
            // Create a new room
            $room_name = $data->room_name;
            $username = $data->username;
            $password = password_hash($data->password, PASSWORD_DEFAULT);
            $join_code = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);

            $stmt = $conn->prepare("INSERT INTO rooms (name, password, join_code) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $room_name, $password, $join_code);

            if ($stmt->execute()) {
                $room_id = $stmt->insert_id;
                $stmt->close();

                $stmt = $conn->prepare("INSERT INTO room_users (room_id, username) VALUES (?, ?)");
                $stmt->bind_param("is", $room_id, $username);
                $stmt->execute();
                $stmt->close();

                echo json_encode(['success' => true, 'join_code' => $join_code]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create room']);
            }
        } elseif ($action == 'join') {
            // Join an existing room
            $join_code = $data->join_code;
            $username = $data->username;

            $stmt = $conn->prepare("SELECT id FROM rooms WHERE join_code = ?");
            $stmt->bind_param("s", $join_code);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $room = $result->fetch_assoc();
                $room_id = $room['id'];

                $stmt = $conn->prepare("INSERT INTO room_users (room_id, username) VALUES (?, ?)");
                $stmt->bind_param("is", $room_id, $username);
                $stmt->execute();
                $stmt->close();

                echo json_encode(['success' => true, 'room_id' => $room_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid join code']);
            }
        }
        break;

    default:
        // Invalid request method
        header("HTTP/1.0 405 Method Not Allowed");
        break;
}

$conn->close();
?>
