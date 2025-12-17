<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once "../includes/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_song'])) {
    $title = trim($_POST['title']);
    $artist = trim($_POST['artist']);
    $source_type = trim($_POST['source_type']);
    $video_source = '';

    try {
        do {
            $song_number = rand(100000, 999999);
            $sql_check = "SELECT id FROM songs WHERE song_number = :song_number";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bindParam(':song_number', $song_number, PDO::PARAM_STR);
            $stmt_check->execute();
            $is_duplicate = $stmt_check->rowCount() > 0;
        } while ($is_duplicate);

        if ($source_type === 'upload') {
            if (isset($_FILES["video_file"]) && $_FILES["video_file"]["error"] == 0) {
                $target_dir = "../uploads/";
                $target_file = $target_dir . basename($_FILES["video_file"]["name"]);
                if (move_uploaded_file($_FILES["video_file"]["tmp_name"], $target_file)) {
                    $video_source = 'uploads/' . basename($_FILES["video_file"]["name"]);
                } else {
                    echo "Sorry, there was an error uploading your file.";
                }
            }
        } else {
            $video_source = trim($_POST['video_link']);
        }

        if (!empty($title) && !empty($artist) && !empty($video_source)) {
            $sql = "INSERT INTO songs (song_number, title, artist, source_type, video_source) VALUES (:song_number, :title, :artist, :source_type, :video_source)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':song_number', $song_number, PDO::PARAM_STR);
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt->bindParam(':artist', $artist, PDO::PARAM_STR);
            $stmt->bindParam(':source_type', $source_type, PDO::PARAM_STR);
            $stmt->bindParam(':video_source', $video_source, PDO::PARAM_STR);
            $stmt->execute();
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

$songs = [];
try {
    $sql = "SELECT id, song_number, title, artist, video_source FROM songs ORDER BY id DESC";
    $result = $conn->query($sql);
    $songs = $result->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

unset($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .wrapper{ width: 80%; padding: 20px; margin: auto; margin-top: 50px; }
        .welcome-banner { margin-bottom: 20px; }
        .youtube-result {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            cursor: pointer;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.2s;
        }
        .youtube-result:hover {
            background-color: #f0f0f0;
        }
        .youtube-result img {
            width: 120px;
            height: 90px;
            margin-right: 15px;
            object-fit: cover;
            border-radius: 4px;
        }
        .youtube-result .info {
            flex-grow: 1;
        }
        .youtube-result .info h5 {
            margin: 0 0 5px 0;
            font-size: 1rem;
            font-weight: 600;
        }
        .youtube-result .info p {
            margin: 0;
            font-size: 0.85rem;
            color: #555;
        }
        .youtube-result .add-btn {
            margin-left: 15px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="d-flex justify-content-between welcome-banner">
            <h2>Admin Dashboard</h2>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>

        <h3>Search YouTube</h3>
        <form id="youtube_search_form" action="youtube_results.php" method="get">
            <div class="input-group mb-3">
                <input type="text" name="q" class="form-control" placeholder="Search for a karaoke song on YouTube..." required>
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit">Search</button>
                </div>
            </div>
        </form>
        <div id="youtube_results" class="mt-3"></div>
        <hr>

        <h3>Add New Song</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Artist</label>
                <input type="text" name="artist" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Source Type</label>
                <select name="source_type" class="form-control" id="source_type_selector">
                    <option value="link">Link (YouTube, Direct, etc.)</option>
                    <option value="upload">Upload</option>
                </select>
            </div>
            <div class="form-group" id="video_link_group">
                <label>Video Link</label>
                <input type="text" name="video_link" class="form-control">
            </div>
            <div class="form-group" id="video_upload_group" style="display: none;">
                 <label>Upload Video</label>
                <input type="file" name="video_file" class="form-control-file">
            </div>
            <div class="form-group">
                <input type="submit" name="submit_song" class="btn btn-primary" value="Add Song">
            </div>
        </form>

        <hr>

        <h3>Manage Songs</h3>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Song Number</th>
                    <th>Title</th>
                    <th>Artist</th>
                    <th>Source</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($songs as $song): ?>
                <tr>
                    <td><?php echo htmlspecialchars($song['song_number']); ?></td>
                    <td><?php echo htmlspecialchars($song['title']); ?></td>
                    <td><?php echo htmlspecialchars($song['artist']); ?></td>
                    <td><?php echo htmlspecialchars($song['video_source']); ?></td>
                    <td>
                        <a href="edit_song.php?id=<?php echo $song['id']; ?>" class="btn btn-sm btn-info">Edit</a>
                        <form action="delete_song.php" method="post" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $song['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this song?');">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        document.getElementById('source_type_selector').addEventListener('change', function() {
            var linkGroup = document.getElementById('video_link_group');
            var uploadGroup = document.getElementById('video_upload_group');
            if (this.value === 'upload') {
                linkGroup.style.display = 'none';
                uploadGroup.style.display = 'block';
            } else {
                linkGroup.style.display = 'block';
                uploadGroup.style.display = 'none';
            }
        });

        document.getElementById('youtube_search_form').addEventListener('submit', function(e) {
            e.preventDefault();
            const query = this.querySelector('input[name="q"]').value;
            if (query) {
                window.open(this.action + '?q=' + encodeURIComponent(query), 'youtube_results', 'width=800,height=600');
            }
        });
    </script>
</body>
</html>
