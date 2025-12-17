<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . "/../includes/db.php";

// Set pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = ($page - 1) * $limit;

// Get search term
$search = isset($_GET['search']) ? $_GET['search'] : '';

try {
    // Get total number of songs
    $total_songs_sql = "SELECT COUNT(*) as total FROM songs";
    $params_total = [];
    if (!empty($search)) {
        $total_songs_sql .= " WHERE title LIKE :search OR artist LIKE :search OR song_number LIKE :search";
        $params_total[':search'] = "%" . $search . "%";
    }
    $stmt_total = $conn->prepare($total_songs_sql);
    $stmt_total->execute($params_total);
    $total_songs_row = $stmt_total->fetch(PDO::FETCH_ASSOC);
    $total_songs = $total_songs_row ? $total_songs_row['total'] : 0;

    // Fetch a paginated list of songs
    $sql = "SELECT song_number, title, artist, video_source FROM songs";
    $params = [];
    if (!empty($search)) {
        $sql .= " WHERE title LIKE :search OR artist LIKE :search OR song_number LIKE :search";
        $params[':search'] = "%" . $search . "%";
    }
    $sql .= " ORDER BY CAST(song_number AS UNSIGNED) ASC, song_number ASC LIMIT :limit OFFSET :offset";

    $stmt = $conn->prepare($sql);

    // Bind parameters
    if (!empty($search)) {
        $stmt->bindParam(':search', $params[':search']);
    }
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare the response
    $response = [
        'total' => (int)$total_songs,
        'page' => $page,
        'limit' => $limit,
        'songs' => $songs ?: []
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
} finally {
    // Close connection
    $stmt = null;
    $stmt_total = null;
    $conn = null;
}
?>
