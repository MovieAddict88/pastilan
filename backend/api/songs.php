<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../includes/db.php";

// Set pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = ($page - 1) * $limit;

// Get search term
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Get total number of songs
$total_songs_sql = "SELECT COUNT(*) as total FROM songs";
$params = [];
if (!empty($search)) {
    $total_songs_sql .= " WHERE title LIKE ? OR artist LIKE ? OR song_number LIKE ?";
    $search_param = "%" . $search . "%";
    $params = [$search_param, $search_param, $search_param];
}
$stmt_total = $conn->prepare($total_songs_sql);
$stmt_total->execute($params);
$total_songs = $stmt_total->fetchColumn();

$songs = [];
// Fetch a paginated list of songs
$sql = "SELECT song_number, title, artist, video_source FROM songs";
$params = [];
if (!empty($search)) {
    $sql .= " WHERE title LIKE ? OR artist LIKE ? OR song_number LIKE ?";
    $search_param = "%" . $search . "%";
    $params = [$search_param, $search_param, $search_param];
}
$sql .= " ORDER BY CAST(song_number AS UNSIGNED) ASC, song_number ASC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare the response
$response = [
    'total' => (int)$total_songs,
    'page' => $page,
    'limit' => $limit,
    'songs' => $songs
];

echo json_encode($response);
?>