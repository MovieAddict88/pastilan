<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_GET['playlist_id'])) {
    echo json_encode(['success' => false, 'message' => 'Playlist ID required']);
    exit;
}

$playlistId = $_GET['playlist_id'];
$pageToken = $_GET['page_token'] ?? '';

$url = "https://www.googleapis.com/youtube/v3/playlistItems?" . http_build_query([
    'part' => 'snippet',
    'playlistId' => $playlistId,
    'key' => YOUTUBE_API_KEY,
    'maxResults' => 20,
    'pageToken' => $pageToken
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

if (isset($data['items'])) {
    $songs = [];
    foreach ($data['items'] as $item) {
        $songs[] = [
            'videoId' => $item['snippet']['resourceId']['videoId'],
            'title' => $item['snippet']['title'],
            'channel' => $item['snippet']['videoOwnerChannelTitle'] ?? $item['snippet']['channelTitle'],
            'thumbnail' => $item['snippet']['thumbnails']['default']['url']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'songs' => $songs,
        'hasMore' => !empty($data['nextPageToken']),
        'nextPageToken' => $data['nextPageToken'] ?? '',
        'totalResults' => $data['pageInfo']['totalResults'] ?? 0
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $data['error']['message'] ?? 'Could not fetch playlist'
    ]);
}