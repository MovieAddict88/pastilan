<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create or Join a Room</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="room-container">
        <div class="create-room-section">
            <h2>Create a New Room</h2>
            <form id="create-room-form">
                <input type="text" id="room-name" placeholder="Room Name" required>
                <input type="text" id="creator-username" placeholder="Username" required>
                <input type="password" id="room-password" placeholder="Password" required>
                <button type="submit" class="btn">Create Room</button>
            </form>
            <div id="room-code-display" style="display:none;">
                <h3>Room Code: <span id="room-code"></span></h3>
            </div>
        </div>

        <div class="room-list-section">
            <h2>Available Rooms</h2>
            <ul id="room-list">
                <!-- Room items will be dynamically inserted here -->
            </ul>
        </div>
    </div>

    <!-- Join Room Modal -->
    <div id="join-room-modal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Join Room</h2>
            <form id="join-room-form">
                <input type="text" id="join-username" placeholder="Your Name" required>
                <input type="text" id="join-room-code" placeholder="8-Digit Room Code" required>
                <button type="submit" class="btn">Join</button>
            </form>
        </div>
    </div>

    <script src="room.js" defer></script>
</body>
</html>
