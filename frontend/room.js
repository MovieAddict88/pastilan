// Room Management Logic
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    const icons = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-exclamation-circle',
        'warning': 'fas fa-exclamation-triangle',
        'info': 'fas fa-info-circle'
    };
    notification.innerHTML = `<i class="${icons[type] || icons.info}"></i><span>${message}</span>`;
    document.body.appendChild(notification);
    setTimeout(() => { notification.remove(); }, 4000);
}

function showError(message) {
    showNotification(message, 'error');
}

document.addEventListener('DOMContentLoaded', () => {
    const createRoomForm = document.getElementById('create-room-form');
    const roomListElement = document.getElementById('room-list');
    const joinRoomModal = document.getElementById('join-room-modal');
    const joinRoomForm = document.getElementById('join-room-form');
    const roomCodeDisplay = document.getElementById('room-code-display');
    const roomCodeSpan = document.getElementById('room-code');

    if (createRoomForm) {
        fetchRooms();
        createRoomForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const roomName = document.getElementById('room-name').value;
            const username = document.getElementById('creator-username').value;
            const password = document.getElementById('room-password').value;
            const formData = new FormData();
            formData.append('action', 'create_room');
            formData.append('room_name', roomName);
            formData.append('username', username);
            formData.append('password', password);
            try {
                const response = await fetch('../backend/api/room_handler.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    roomCodeSpan.textContent = result.room_code;
                    roomCodeDisplay.style.display = 'block';
                    showNotification('Room created successfully!', 'success');
                    fetchRooms();
                } else {
                    showError(result.message || 'Failed to create room.');
                }
            } catch (error) {
                showError('An error occurred while creating the room.');
            }
        });
        joinRoomForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = document.getElementById('join-username').value;
            const roomCode = document.getElementById('join-room-code').value;
            const formData = new FormData();
            formData.append('action', 'join_room');
            formData.append('username', username);
            formData.append('room_code', roomCode);
            try {
                const response = await fetch('../backend/api/room_handler.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    showNotification('Joined room successfully!', 'success');
                    closeModal();
                    fetchRooms();
                } else {
                    showError(result.message || 'Failed to join room.');
                }
            } catch (error) {
                showError('An error occurred while joining the room.');
            }
        });
        async function fetchRooms() {
            try {
                const response = await fetch('../backend/api/room_handler.php');
                const rooms = await response.json();
                renderRooms(rooms);
            } catch (error) {
                showError('Failed to fetch rooms.');
            }
        }
        function renderRooms(rooms) {
            roomListElement.innerHTML = '';
            if (rooms.length === 0) {
                roomListElement.innerHTML = '<li>No rooms available. Create one!</li>';
                return;
            }
            rooms.forEach(room => {
                const li = document.createElement('li');
                const personText = room.member_count === 1 ? 'person' : 'people';
                li.innerHTML = `<span>${room.room_name} (${room.member_count} ${personText})</span><button class="btn join-btn" data-room-id="${room.id}">Join</button>`;
                roomListElement.appendChild(li);
            });
        }
        roomListElement.addEventListener('click', (e) => {
            if (e.target.classList.contains('join-btn')) {
                openModal();
            }
        });
        function openModal() { joinRoomModal.style.display = 'flex'; }
        function closeModal() { joinRoomModal.style.display = 'none'; }
        const closeButton = joinRoomModal.querySelector('.close-button');
        closeButton.addEventListener('click', closeModal);
        window.addEventListener('click', (e) => {
            if (e.target === joinRoomModal) {
                closeModal();
            }
        });
    }
});
