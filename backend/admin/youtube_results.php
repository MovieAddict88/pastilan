<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once '../config.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

function search_youtube($query, $apiKey, $type = 'video,playlist', $maxResults = 50) {
    if (empty($query)) {
        return ['error' => 'Search query is required'];
    }

    // Build search URL with multiple types
    $searchUrl = "https://www.googleapis.com/youtube/v3/search?" . http_build_query([
        'part' => 'snippet',
        'q' => $query,
        'type' => $type,
        'key' => $apiKey,
        'maxResults' => $maxResults
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $searchUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

function get_playlist_items($playlistId, $apiKey, $maxResults = 50) {
    $url = "https://www.googleapis.com/youtube/v3/playlistItems?" . http_build_query([
        'part' => 'snippet',
        'playlistId' => $playlistId,
        'key' => $apiKey,
        'maxResults' => $maxResults
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// Main search execution
$searchResults = search_youtube($query, YOUTUBE_API_KEY, 'video,playlist', 50);
$allResults = [];

if (isset($searchResults['items'])) {
    foreach ($searchResults['items'] as $item) {
        $kind = $item['id']['kind'];
        
        if ($kind == 'youtube#video') {
            // Individual video
            $allResults[] = [
                'type' => 'video',
                'videoId' => $item['id']['videoId'],
                'title' => $item['snippet']['title'],
                'thumbnail' => $item['snippet']['thumbnails']['medium']['url'] ?? $item['snippet']['thumbnails']['default']['url'],
                'channelTitle' => $item['snippet']['channelTitle'],
                'itemCount' => 1
            ];
            
        } elseif ($kind == 'youtube#playlist') {
            // Playlist (collection)
            $playlistId = $item['id']['playlistId'];
            
            // Optional: Get playlist item count (requires another API call)
            // For now, we'll just show it as a playlist
            $allResults[] = [
                'type' => 'playlist',
                'playlistId' => $playlistId,
                'title' => $item['snippet']['title'],
                'thumbnail' => $item['snippet']['thumbnails']['medium']['url'] ?? $item['snippet']['thumbnails']['default']['url'],
                'channelTitle' => $item['snippet']['channelTitle'],
                'itemCount' => 'Multiple songs',
                'description' => substr($item['snippet']['description'], 0, 100) . '...'
            ];
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>YouTube Search: "<?php echo htmlspecialchars($query); ?>"</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { padding: 20px; }
        .result-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 15px;
            padding: 15px;
            transition: box-shadow 0.3s;
        }
        .result-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .result-video {
            background-color: #f9f9f9;
        }
        .result-playlist {
            background-color: #f0f7ff;
            border-left: 4px solid #007bff;
        }
        .thumbnail {
            width: 160px;
            height: 120px;
            object-fit: cover;
            border-radius: 4px;
        }
        .badge-type {
            font-size: 0.7rem;
            padding: 3px 8px;
            margin-right: 8px;
        }
        .btn-playlist {
            background-color: #28a745;
            border-color: #28a745;
        }
        .btn-playlist:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>YouTube Search Results</h2>
                <p class="text-muted">Search: <strong><?php echo htmlspecialchars($query); ?></strong></p>
            </div>
            <a href="?q=<?php echo urlencode($query); ?>&type=video" class="btn btn-outline-primary btn-sm">Videos Only</a>
            <a href="?q=<?php echo urlencode($query); ?>&type=playlist" class="btn btn-outline-primary btn-sm">Playlists Only</a>
            <a href="?q=<?php echo urlencode($query); ?>&type=video,playlist" class="btn btn-primary btn-sm">Both</a>
        </div>
        
        <?php if (isset($searchResults['error'])): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($searchResults['error']['message'] ?? 'Search failed'); ?>
            </div>
        <?php elseif (empty($allResults)): ?>
            <div class="alert alert-info">
                No results found for your query.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($allResults as $item): ?>
                <div class="col-md-12">
                    <div class="result-card <?php echo $item['type'] == 'playlist' ? 'result-playlist' : 'result-video'; ?>">
                        <div class="d-flex">
                            <div class="mr-3">
                                <img src="<?php echo htmlspecialchars($item['thumbnail']); ?>" 
                                     alt="Thumbnail" 
                                     class="thumbnail">
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5>
                                            <span class="badge badge-type badge-<?php echo $item['type'] == 'playlist' ? 'primary' : 'secondary'; ?>">
                                                <?php echo strtoupper($item['type']); ?>
                                            </span>
                                            <?php echo htmlspecialchars($item['title']); ?>
                                        </h5>
                                        <p class="text-muted mb-1">
                                            <small>Channel: <?php echo htmlspecialchars($item['channelTitle']); ?></small>
                                        </p>
                                        <p class="mb-1">
                                            <small class="text-info">
                                                <strong>
                                                    <?php echo $item['type'] == 'playlist' ? 'Collection: ' . $item['itemCount'] . ' songs' : 'Single video'; ?>
                                                </strong>
                                            </small>
                                        </p>
                                        <?php if ($item['type'] == 'playlist' && isset($item['description'])): ?>
                                            <p class="text-muted mb-2">
                                                <small><?php echo htmlspecialchars($item['description']); ?></small>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ($item['type'] == 'video'): ?>
                                            <button class="btn btn-success add-song-btn"
                                                    data-video-id="<?php echo htmlspecialchars($item['videoId']); ?>"
                                                    data-title="<?php echo htmlspecialchars($item['title']); ?>"
                                                    data-artist="<?php echo htmlspecialchars($item['channelTitle']); ?>">
                                                Add Song
                                            </button>
                                            <a href="https://www.youtube.com/watch?v=<?php echo $item['videoId']; ?>" 
                                               target="_blank" 
                                               class="btn btn-outline-secondary btn-sm">
                                                Preview
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-playlist view-playlist-btn"
                                                    data-playlist-id="<?php echo htmlspecialchars($item['playlistId']); ?>"
                                                    data-title="<?php echo htmlspecialchars($item['title']); ?>">
                                                View Collection (<?php echo $item['itemCount']; ?>)
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal for Playlist View -->
    <div class="modal fade" id="playlistModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="playlistModalTitle">Playlist Songs</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="playlistModalBody">
                    <!-- Playlist songs will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
    // Add individual song
    document.querySelectorAll('.add-song-btn').forEach(button => {
        button.addEventListener('click', function() {
            const videoId = this.dataset.videoId;
            const title = this.dataset.title;
            const artist = this.dataset.artist;
            
            this.disabled = true;
            this.textContent = 'Adding...';

            const formData = new FormData();
            formData.append('title', title);
            formData.append('artist', artist);
            formData.append('video_link', `https://www.youtube.com/watch?v=${videoId}`);

            fetch('add_song_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.textContent = 'Added!';
                    this.classList.remove('btn-success');
                    this.classList.add('btn-secondary');

                    // Refresh the opener window if it's the dashboard
                    if (window.opener && !window.opener.closed) {
                        window.opener.location.reload();
                    }
                } else {
                    this.disabled = false;
                    this.textContent = 'Add Song';
                    alert('Error: ' + (data.message || 'Could not add song.'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.disabled = false;
                this.textContent = 'Add Song';
                alert('An error occurred.');
            });
        });
    });

    // View playlist contents
    document.querySelectorAll('.view-playlist-btn').forEach(button => {
        button.addEventListener('click', function() {
            const playlistId = this.dataset.playlistId;
            const title = this.dataset.title;
            
            $('#playlistModalTitle').text(title + ' - Songs');
            $('#playlistModalBody').html('<div class="text-center"><div class="spinner-border" role="status"></div><p>Loading songs...</p></div>');
            $('#playlistModal').modal('show');
            
            // Load playlist items via AJAX
            $.get('get_playlist_items.php', {
                playlist_id: playlistId
            }, function(response) {
                if (response.success) {
                    let html = '';
                    if (response.songs.length > 0) {
                        html += '<div class="list-group">';
                        response.songs.forEach(function(song) {
                            html += `
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>${song.title}</strong><br>
                                        <small class="text-muted">${song.channel}</small>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-success add-from-playlist"
                                                data-video-id="${song.videoId}"
                                                data-title="${song.title}"
                                                data-artist="${song.channel}">
                                            Add
                                        </button>
                                        <a href="https://www.youtube.com/watch?v=${song.videoId}" 
                                           target="_blank" 
                                           class="btn btn-sm btn-outline-secondary">
                                            Preview
                                        </a>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                        if (response.hasMore) {
                            html += `<div class="mt-3 text-center">
                                <button class="btn btn-primary" id="loadMorePlaylist" data-playlist-id="${playlistId}" data-page-token="${response.nextPageToken}">
                                    Load More Songs
                                </button>
                            </div>`;
                        }
                    } else {
                        html = '<p class="text-center">No songs found in this playlist.</p>';
                    }
                    $('#playlistModalBody').html(html);
                } else {
                    $('#playlistModalBody').html(`<div class="alert alert-danger">${response.message}</div>`);
                }
            });
        });
    });

    // Add songs from playlist modal
    $(document).on('click', '.add-from-playlist', function() {
        const button = $(this);
        const videoId = button.data('video-id');
        const title = button.data('title');
        const artist = button.data('artist');
        
        button.prop('disabled', true).text('Adding...');

        $.post('add_song_api.php', {
            title: title,
            artist: artist,
            video_link: `https://www.youtube.com/watch?v=${videoId}`
        }, function(response) {
            if (response.success) {
                button.text('Added!')
                     .removeClass('btn-success')
                     .addClass('btn-secondary');

                if (window.opener && !window.opener.closed) {
                    window.opener.location.reload();
                }
            } else {
                button.prop('disabled', false).text('Add');
                alert('Error: ' + (response.message || 'Could not add song.'));
            }
        });
    });

    // Handle "Load More" for playlist items
    $(document).on('click', '#loadMorePlaylist', function() {
        const button = $(this);
        const playlistId = button.data('playlist-id');
        const pageToken = button.data('page-token');

        button.prop('disabled', true).text('Loading...');

        $.get('get_playlist_items.php', {
            playlist_id: playlistId,
            page_token: pageToken
        }, function(response) {
            if (response.success && response.songs.length > 0) {
                let newSongsHtml = '';
                response.songs.forEach(function(song) {
                    newSongsHtml += `
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${song.title}</strong><br>
                                <small class="text-muted">${song.channel}</small>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-success add-from-playlist"
                                        data-video-id="${song.videoId}"
                                        data-title="${song.title}"
                                        data-artist="${song.channel}">
                                    Add
                                </button>
                                <a href="https://www.youtube.com/watch?v=${song.videoId}"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-secondary">
                                    Preview
                                </a>
                            </div>
                        </div>
                    `;
                });

                // Append new songs and remove the old button's container
                $('#playlistModalBody .list-group').append(newSongsHtml);
                button.parent().remove(); 

                // Add new "Load More" button if there are more pages
                if (response.hasMore) {
                    const newButtonHtml = `<div class="mt-3 text-center">
                        <button class="btn btn-primary" id="loadMorePlaylist" data-playlist-id="${playlistId}" data-page-token="${response.nextPageToken}">
                            Load More Songs
                        </button>
                    </div>`;
                    $('#playlistModalBody').append(newButtonHtml);
                }
            } else {
                // Handle no more songs or an error
                button.parent().html('<p class="text-muted text-center">No more songs found.</p>');
            }
        }).fail(function() {
            alert('Failed to load more songs. Please try again.');
            button.prop('disabled', false).text('Load More Songs');
        });
    });
    </script>
</body>
</html>