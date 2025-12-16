// Video Karaoke Player
let songList = [];
let songQueue = [];
let currentPage = 1;
let totalSongs = 0;
let currentSongIndex = -1;
let isPlaying = false;
let isFetching = false;
const QUEUE_STORAGE_KEY = 'karaokeQueueState';
const DB_NAME = 'KaraokePlayerDB';
const DB_VERSION = 1;
const STORE_NAME = 'queueStore';
let db = null;
let currentRoom = null;

// DOM Elements
const songListElement = document.getElementById('song-list');
const roomListElement = document.getElementById('room-list');
const createRoomBtn = document.getElementById('create-room-btn');
const createRoomModal = document.getElementById('create-room-modal');
const joinRoomModal = document.getElementById('join-room-modal');
const submitCreateRoomBtn = document.getElementById('submit-create-room');
const submitJoinRoomBtn = document.getElementById('submit-join-room');
const queueListElement = document.getElementById('queue-list');
const songNumberInput = document.getElementById('song-number-input');
const songSearchInput = document.getElementById('song-search');
const playButton = document.getElementById('play-button');
const nextButton = document.getElementById('next-button');
const pauseButton = document.getElementById('pause-button');
const clearQueueButton = document.getElementById('clear-queue');
const searchButton = document.getElementById('search-button');
const volumeUpButton = document.getElementById('volume-up');
const volumeDownButton = document.getElementById('volume-down');
const currentSongInfo = document.getElementById('current-song-info');
const playerStatus = document.getElementById('player-status');
const queueCount = document.getElementById('queue-count');
const songCount = document.getElementById('song-count');

// Initialize Video.js player with YouTube plugin
const player = videojs('video-player', {
    controls: true,
    autoplay: false,
    preload: 'auto',
    responsive: true,
    fluid: true,
    playbackRates: [0.5, 0.75, 1, 1.25, 1.5, 2],
    youtube: {
        ytControls: 2,
        enablePrivacyEnhancedMode: true,
        iv_load_policy: 1
    },
    controlBar: {
        children: [
            'playToggle',
            'volumePanel',
            'currentTimeDisplay',
            'timeDivider',
            'durationDisplay',
            'progressControl',
            'remainingTimeDisplay',
            'playbackRateMenuButton',
            'fullscreenToggle'
        ]
    }
});

// Initialize player ready state
player.ready(async function() {
    console.log('Professional Karaoke Player initialized');
    updatePlayerStatus('Ready to play', 'success');
    
    // Set initial volume
    player.volume(0.8);
    
    // Add keyboard shortcuts
    setupKeyboardShortcuts();
    
    // Initialize IndexedDB
    await initIndexedDB();
});

// Initialize IndexedDB
async function initIndexedDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);
        
        request.onerror = () => {
            console.warn('IndexedDB initialization failed, using localStorage only');
            resolve(false);
        };
        
        request.onsuccess = (event) => {
            db = event.target.result;
            console.log('IndexedDB initialized successfully');
            resolve(true);
        };
        
        request.onupgradeneeded = (event) => {
            db = event.target.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                db.createObjectStore(STORE_NAME, { keyPath: 'id' });
            }
            if (!db.objectStoreNames.contains('songListStore')) {
                db.createObjectStore('songListStore', { keyPath: 'song_number' });
            }
        };
    });
}

// Save to IndexedDB (as backup)
async function saveToIndexedDB(state) {
    if (!db) return false;
    
    return new Promise((resolve) => {
        const transaction = db.transaction([STORE_NAME], 'readwrite');
        const store = transaction.objectStore(STORE_NAME);
        const request = store.put({ 
            id: 'currentQueue',
            data: state,
            timestamp: Date.now()
        });
        
        request.onsuccess = () => {
            console.log('Queue saved to IndexedDB');
            resolve(true);
        };
        
        request.onerror = () => {
            console.warn('Failed to save to IndexedDB');
            resolve(false);
        };
    });
}

// Load from IndexedDB (as backup)
async function loadFromIndexedDB() {
    if (!db) return null;
    
    return new Promise((resolve) => {
        const transaction = db.transaction([STORE_NAME], 'readonly');
        const store = transaction.objectStore(STORE_NAME);
        const request = store.get('currentQueue');
        
        request.onsuccess = () => {
            if (request.result && request.result.data) {
                console.log('Queue loaded from IndexedDB');
                resolve(request.result.data);
            } else {
                resolve(null);
            }
        };
        
        request.onerror = () => {
            console.warn('Failed to load from IndexedDB');
            resolve(null);
        };
    });
}

// Clear IndexedDB
async function clearIndexedDB() {
    if (!db) return;
    
    return new Promise((resolve) => {
        const transaction = db.transaction([STORE_NAME], 'readwrite');
        const store = transaction.objectStore(STORE_NAME);
        const request = store.clear();
        
        request.onsuccess = () => {
            console.log('IndexedDB cleared');
            resolve(true);
        };
        
        request.onerror = () => {
            console.warn('Failed to clear IndexedDB');
            resolve(false);
        };
    });
}

// Save song list to IndexedDB
async function saveSongListToDB(songs) {
    if (!db) return false;

    return new Promise((resolve) => {
        const transaction = db.transaction(['songListStore'], 'readwrite');
        const store = transaction.objectStore('songListStore');
        store.clear(); // Clear old data first

        let completed = 0;
        songs.forEach(song => {
            const request = store.put(song);
            request.onsuccess = () => {
                completed++;
                if (completed === songs.length) {
                    console.log('Song list saved to IndexedDB');
                    resolve(true);
                }
            };
        });

        transaction.oncomplete = () => {
            console.log('All songs saved to IndexedDB');
        };

        transaction.onerror = () => {
            console.warn('Failed to save song list to IndexedDB');
            resolve(false);
        };
    });
}

// Load song list from IndexedDB
async function loadSongListFromDB() {
    if (!db) return null;

    return new Promise((resolve) => {
        const transaction = db.transaction(['songListStore'], 'readonly');
        const store = transaction.objectStore('songListStore');
        const request = store.getAll();

        request.onsuccess = () => {
            if (request.result && request.result.length > 0) {
                console.log('Song list loaded from IndexedDB');
                resolve(request.result);
            } else {
                resolve(null);
            }
        };

        request.onerror = () => {
            console.warn('Failed to load song list from IndexedDB');
            resolve(null);
        };
    });
}


// Show save indicator
function showSaveIndicator() {
    const indicator = document.createElement('div');
    indicator.className = 'save-indicator';
    indicator.innerHTML = '<i class="fas fa-check"></i> Queue saved';
    document.body.appendChild(indicator);
    
    setTimeout(() => {
        indicator.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        indicator.classList.remove('show');
        setTimeout(() => {
            if (indicator.parentNode) {
                indicator.parentNode.removeChild(indicator);
            }
        }, 300);
    }, 2000);
}

// Save queue state to localStorage and IndexedDB
async function saveQueueState() {
    const state = {
        queue: songQueue,
        currentSongIndex: currentSongIndex,
        isPlaying: isPlaying,
        currentTime: player.currentTime(),
        volume: player.volume()
    };

    try {
        localStorage.setItem(QUEUE_STORAGE_KEY, JSON.stringify(state));
        await saveToIndexedDB(state);
    } catch (e) {
        console.error('Failed to save queue state:', e);
        showError('Could not save queue. Your browser might be in private mode or storage is full.');
    }
}

// Load queue state from localStorage or IndexedDB
async function loadQueueState() {
    let state = null;
    try {
        const storedState = localStorage.getItem(QUEUE_STORAGE_KEY);
        if (storedState) {
            state = JSON.parse(storedState);
        } else {
            // Fallback to IndexedDB
            state = await loadFromIndexedDB();
        }
    } catch (e) {
        console.error('Failed to load queue state from localStorage:', e);
        state = await loadFromIndexedDB(); // Try IndexedDB if localStorage fails
    }

    if (state) {
        songQueue = state.queue || [];
        currentSongIndex = state.currentSongIndex || -1;
        isPlaying = state.isPlaying || false;

        updateQueueCount();
        renderQueue();

        if (songQueue.length > 0 && currentSongIndex !== -1) {
            const currentSong = songQueue[currentSongIndex];
            currentSongInfo.innerHTML = `
                <strong>${currentSong.title}</strong> - ${currentSong.artist}
                <br><small>Song #${currentSong.song_number}</small>
            `;

            const youtubeId = extractYouTubeId(currentSong.video_source);
            if (youtubeId) {
                player.src({
                    src: `https://www.youtube.com/watch?v=${youtubeId}`,
                    type: 'video/youtube'
                });

                player.one('loadedmetadata', () => {
                    player.currentTime(state.currentTime || 0);
                    if (isPlaying) {
                        player.play().catch(e => console.error("Autoplay failed:", e));
                    }
                });
            }
        }

        player.volume(state.volume || 0.8);
        showNotification('Queue state restored', 'info');
    }
}

// Setup keyboard shortcuts
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', (e) => {
        // Don't trigger if user is typing in input fields
        if (e.target.tagName === 'INPUT') return;
        
        switch(e.key.toLowerCase()) {
            case ' ':
                e.preventDefault();
                togglePlayPause();
                break;
            case 'n':
            case 'arrowright':
                if (e.ctrlKey || e.metaKey) {
                    e.preventDefault();
                    nextSong();
                }
                break;
            case 'arrowup':
                e.preventDefault();
                adjustVolume(0.1);
                break;
            case 'arrowdown':
                e.preventDefault();
                adjustVolume(-0.1);
                break;
            case 'escape':
                if (player.isFullscreen()) {
                    player.exitFullscreen();
                }
                break;
            case 's':
                if (e.ctrlKey || e.metaKey) {
                    e.preventDefault();
                    saveQueueState();
                    showNotification('Queue state saved manually', 'success');
                }
                break;
        }
    });
}

// Toggle play/pause
function togglePlayPause() {
    if (player.paused()) {
        if (songQueue.length > 0) {
            player.play();
        }
    } else {
        player.pause();
    }
}

// Adjust volume
function adjustVolume(delta) {
    const currentVolume = player.volume();
    const newVolume = Math.max(0, Math.min(1, currentVolume + delta));
    player.volume(newVolume);
    showNotification(`Volume: ${Math.round(newVolume * 100)}%`, 'info');
    saveQueueState(); // Save volume change
}

// Fetch and render room list
async function fetchAndRenderRooms() {
    try {
        const response = await fetch('../backend/api/rooms.php');
        const rooms = await response.json();
        roomListElement.innerHTML = '';
        if (rooms.length > 0) {
            rooms.forEach(room => {
                const li = document.createElement('li');
                li.innerHTML = `
                    <div class="room-info">${room.name} <span>(${room.user_count} users)</span></div>
                    <button class="control-btn join-room-btn" data-room-id="${room.id}">Join</button>
                `;
                li.querySelector('.join-room-btn').addEventListener('click', () => {
                    joinRoomModal.style.display = 'block';
                    // Store the room id for later use
                    joinRoomModal.dataset.roomId = room.id;
                });
                roomListElement.appendChild(li);
            });
        } else {
            roomListElement.innerHTML = '<div class="empty-message">No rooms available. Create one to get started!</div>';
        }
    } catch (error) {
        console.error('Error fetching rooms:', error);
        showError('Failed to load rooms.');
    }
}

// Create a new room
async function createRoom() {
    const roomName = document.getElementById('room-name-input').value.trim();
    const userName = document.getElementById('username-input').value.trim();
    const password = document.getElementById('room-password-input').value.trim();

    if (roomName && userName && password) {
        try {
            const response = await fetch('../backend/api/rooms.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'create', room_name: roomName, username: userName, password: password })
            });
            const data = await response.json();
            if (data.success) {
                showNotification(`Room "${roomName}" created! Join code: ${data.join_code}`, 'success');
                createRoomModal.style.display = 'none';
                fetchAndRenderRooms();
            } else {
                showError(data.message || 'Failed to create room.');
            }
        } catch (error) {
            console.error('Error creating room:', error);
            showError('An error occurred while creating the room.');
        }
    } else {
        showError('Please fill in all fields.');
    }
}

// Join a room
async function joinRoom() {
    const userName = document.getElementById('join-username-input').value.trim();
    const joinCode = document.getElementById('join-code-input').value.trim();

    if (userName && joinCode) {
        try {
            const response = await fetch('../backend/api/rooms.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'join', username: userName, join_code: joinCode })
            });
            const data = await response.json();
            if (data.success) {
                currentRoom = data.room_id;
                showNotification(`Joined room successfully!`, 'success');
                joinRoomModal.style.display = 'none';
                fetchRoomQueue();
                startQueuePolling();
            } else {
                showError(data.message || 'Failed to join room.');
            }
        } catch (error) {
            console.error('Error joining room:', error);
            showError('An error occurred while joining the room.');
        }
    } else {
        showError('Please fill in all fields.');
    }
}

// Fetch the queue for the current room
async function fetchRoomQueue() {
    if (!currentRoom) return;

    try {
        const response = await fetch(`../backend/api/queue.php?room_id=${currentRoom}`);
        const queue = await response.json();
        songQueue = queue;
        renderQueue();
        updateQueueCount();

        // Auto-play if there's a song and nothing is playing
        if (songQueue.length > 0 && !isPlaying) {
            playCurrentSong();
        }
    } catch (error) {
        console.error('Error fetching room queue:', error);
    }
}

// Start polling the room queue
let queueInterval;
function startQueuePolling() {
    stopQueuePolling(); // Stop any existing polling
    if (currentRoom) {
        queueInterval = setInterval(fetchRoomQueue, 5000); // Poll every 5 seconds
    }
}

function stopQueuePolling() {
    clearInterval(queueInterval);
}


// Fetch song list from API with pagination
async function fetchSongList(page = 1, search = '') {
    if (isFetching) return;

    isFetching = true;
    updatePlayerStatus('Loading songs...', 'loading');

    try {
        const response = await fetch(`../backend/api/songs.php?page=${page}&limit=50&search=${search}`);
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();
        if (data && Array.isArray(data.songs)) {
            if (page === 1) {
                songList = [];
            }
            songList.push(...data.songs);
            totalSongs = data.total;
            currentPage = page;

            populateSongList();
            updateSongCount();
            updatePlayerStatus(`Showing ${songList.length} of ${totalSongs} songs`, 'success');

        } else {
            throw new Error('Invalid data format received from API');
        }
    } catch (error) {
        console.error('Error fetching song list:', error);
        updatePlayerStatus('Error loading songs', 'error');
        showError('Failed to load songs. Please check the API connection.');
        if (page === 1) {
            songListElement.innerHTML = '<div class="empty-message">Failed to load songs. Please try refreshing.</div>';
        }
    } finally {
        isFetching = false;
    }
}

// Populate song list with filtering
function populateSongList(filter = '') {
    if (currentPage === 1) {
        songListElement.innerHTML = '';
    }
    
    if (songList.length === 0) {
        songListElement.innerHTML = '<div class="empty-message">No songs available</div>';
        return;
    }
    
    // Group songs by first letter of artist for better organization
    const groupedSongs = {};
    songList.forEach(song => {
        const firstLetter = song.artist.charAt(0).toUpperCase();
        if (!groupedSongs[firstLetter]) {
            groupedSongs[firstLetter] = [];
        }
        groupedSongs[firstLetter].push(song);
    });
    
    // Create alphabetical sections
    Object.keys(groupedSongs).sort().forEach(letter => {
        const section = document.createElement('div');
        section.className = 'artist-section';
        section.innerHTML = `<div class="artist-letter">${letter}</div>`;
        
        groupedSongs[letter].forEach(song => {
            const li = document.createElement('li');
            li.innerHTML = `
                <div>
                    <span class="song-number">${song.song_number}</span>
                    <strong>${song.title}</strong> - ${song.artist}
                    ${song.duration ? `<br><small><i class="far fa-clock"></i> ${formatDuration(song.duration)}</small>` : ''}
                </div>
                <button class="icon-btn small add-to-queue-btn" data-id="${song.song_number}" aria-label="Add ${song.title} to queue">
                    <i class="fas fa-plus"></i> Add
                </button>
            `;
            
            // Double click to play immediately
            li.addEventListener('dblclick', () => {
                playSongImmediately(song);
            });
            
            // Click to preview
            li.addEventListener('click', (e) => {
                if (!e.target.closest('button')) {
                    showSongPreview(song);
                }
            });
            
            // Add to queue button
            const addBtn = li.querySelector('.add-to-queue-btn');
            addBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                addSongToQueue(song);
            });
            
            section.appendChild(li);
        });
        
        songListElement.appendChild(section);
    });
}

// Format duration from seconds to MM:SS
function formatDuration(seconds) {
    if (!seconds) return '';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
}

// Show song preview
function showSongPreview(song) {
    currentSongInfo.innerHTML = `
        <strong>${song.title}</strong> - ${song.artist}
        <br><small>Song #${song.song_number}</small>
        <br><button class="preview-play-btn" data-id="${song.song_number}">
            <i class="fas fa-play"></i> Play Now
        </button>
    `;
    
    const playBtn = currentSongInfo.querySelector('.preview-play-btn');
    playBtn.addEventListener('click', () => {
        playSongImmediately(song);
    });
}

// Add song to queue
async function addSongToQueue(song) {
    if (!currentRoom) {
        showError("You must join a room to add songs to the queue.");
        return;
    }

    try {
        const response = await fetch(`../backend/api/queue.php?room_id=${currentRoom}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ song_id: song.id })
        });
        const data = await response.json();
        if (data.success) {
            showNotification(`Added "${song.title}" to the room's queue`, 'success');
            fetchRoomQueue();
        } else {
            showError(data.message || 'Failed to add song.');
        }
    } catch (error) {
        console.error('Error adding song to queue:', error);
        showError('An error occurred while adding the song.');
    }
}

// Play song immediately (clears queue and plays)
function playSongImmediately(song) {
    if (confirm(`Play "${song.title}" immediately? This will clear the current queue.`)) {
        songQueue = [song];
        currentSongIndex = 0;
        updateQueueCount();
        renderQueue();
        saveQueueState(); // Save after clearing and adding
        playCurrentSong();
    }
}

// Play current song in queue
async function playCurrentSong() {
    if (songQueue.length === 0) {
        updatePlayerStatus('Queue is empty', 'info');
        currentSongInfo.innerHTML = '<em>Select a song to begin</em>';
        return;
    }
    
    const currentSong = songQueue[0];
    currentSongInfo.innerHTML = `
        <strong>${currentSong.title}</strong> - ${currentSong.artist}
        <br><small>Song #${currentSong.song_number}</small>
    `;
    
    updatePlayerStatus(`Playing: ${currentSong.title}`, 'playing');
    
    // Extract YouTube ID from various URL formats
    const youtubeId = extractYouTubeId(currentSong.video_source);
    
    if (!youtubeId) {
        showError('Invalid YouTube URL format');
        return;
    }
    
    try {
        // Set the video source using Video.js YouTube plugin format
        player.src({
            src: `https://www.youtube.com/watch?v=${youtubeId}`,
            type: 'video/youtube'
        });
        
        await player.play();
        isPlaying = true;
        renderQueue();
        saveQueueState(); // Save after starting playback
    } catch (error) {
        console.error('Error playing video:', error);
        showError('Error playing video. Please try another song.');
        nextSong();
    }
}

// Extract YouTube ID from various URL formats
function extractYouTubeId(url) {
    if (!url) return null;
    
    const patterns = [
        /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/,
        /youtube\.com\/v\/([a-zA-Z0-9_-]{11})/,
        /youtube\.com\/.*[?&]v=([a-zA-Z0-9_-]{11})/,
        /^([a-zA-Z0-9_-]{11})$/
    ];
    
    for (const pattern of patterns) {
        const match = url.match(pattern);
        if (match && match[1]) {
            return match[1];
        }
    }
    
    return null;
}

// Render the song queue
function renderQueue() {
    queueListElement.innerHTML = '';
    
    if (songQueue.length === 0) {
        queueListElement.innerHTML = '<div class="empty-message">Queue is empty. Add songs to get started!</div>';
        return;
    }
    
    songQueue.forEach((song, index) => {
        const li = document.createElement('li');
        const isCurrent = index === 0 && isPlaying;
        
        li.innerHTML = `
            <div>
                <span class="queue-number">${index + 1}.</span>
                <strong>${song.title}</strong> - ${song.artist}
                ${isCurrent ? '<span class="now-playing-badge">Now Playing</span>' : ''}
            </div>
            <div class="queue-actions">
                <button class="icon-btn small remove-btn" data-index="${index}" aria-label="Remove from queue">
                    <i class="fas fa-times"></i>
                </button>
                ${!isCurrent ? `<button class="icon-btn small play-now-btn" data-index="${index}" aria-label="Play now">
                    <i class="fas fa-play"></i>
                </button>` : ''}
                ${index > 1 ? `<button class="icon-btn small move-up-btn" data-index="${index}" aria-label="Move up">
                    <i class="fas fa-arrow-up"></i>
                </button>` : ''}
            </div>
        `;
        
        // Remove from queue button
        const removeBtn = li.querySelector('.remove-btn');
        removeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            removeFromQueue(index);
        });
        
        // Play now button (for songs in queue)
        const playNowBtn = li.querySelector('.play-now-btn');
        if (playNowBtn) {
            playNowBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                playFromQueue(index);
            });
        }
        
        // Move up button
        const moveUpBtn = li.querySelector('.move-up-btn');
        if (moveUpBtn) {
            moveUpBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                moveInQueue(index, -1);
            });
        }
        
        queueListElement.appendChild(li);
    });
}

// Move song in queue (This would require a more complex API, so we'll disable it for now)
/*
function moveInQueue(index, direction) {
    if (index + direction >= 0 && index + direction < songQueue.length) {
        [songQueue[index], songQueue[index + direction]] = [songQueue[index + direction], songQueue[index]];
        renderQueue();
        showNotification('Queue updated', 'info');
    }
}
*/

// Remove song from queue (Also requires a more complex API)
/*
function removeFromQueue(index) {
    if (index >= 0 && index < songQueue.length) {
        const removedSong = songQueue.splice(index, 1)[0];
        updateQueueCount();
        renderQueue();
        showNotification(`Removed "${removedSong.title}" from queue`, 'info');
        
        if (index === 0 && isPlaying) {
            nextSong();
        }
    }
}
*/

// Play specific song from queue (Also requires a more complex API)
/*
function playFromQueue(index) {
    if (index > 0 && index < songQueue.length) {
        const [song] = songQueue.splice(index, 1);
        songQueue.unshift(song);
        updateQueueCount();
        renderQueue();
        playCurrentSong();
    }
}
*/

// Play next song
async function nextSong() {
    if (!currentRoom) return;

    try {
        await fetch(`../backend/api/queue.php?room_id=${currentRoom}`, { method: 'DELETE' });
        fetchRoomQueue(); // Fetch the updated queue
    } catch (error) {
        console.error('Error removing song from queue:', error);
    }
}

// Update player status with icon
function updatePlayerStatus(message, type = 'info') {
    const icons = {
        'success': '✓',
        'error': '✗',
        'warning': '⚠',
        'loading': '⏳',
        'playing': '▶',
        'paused': '⏸',
        'info': 'ℹ'
    };
    
    playerStatus.innerHTML = `${icons[type] || icons.info} ${message}`;
    playerStatus.className = `status-${type}`;
}

// Update queue count
function updateQueueCount() {
    queueCount.textContent = songQueue.length;
    queueCount.style.background = songQueue.length > 0 ? 'var(--success-color)' : 'var(--accent-color)';
}

// Update song count
function updateSongCount() {
    songCount.textContent = totalSongs;
}

// Show notification with icon
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(n => {
        n.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => n.remove(), 300);
    });
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    
    const icons = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-exclamation-circle',
        'warning': 'fas fa-exclamation-triangle',
        'info': 'fas fa-info-circle'
    };
    
    notification.innerHTML = `
        <i class="${icons[type] || icons.info}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    // Add animation styles if not already present
    if (!document.getElementById('notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                color: var(--text-dark);
                padding: 16px 24px;
                border-radius: 12px;
                z-index: 10000;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                animation: slideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                display: flex;
                align-items: center;
                gap: 12px;
                border-left: 4px solid;
                max-width: 400px;
                font-weight: 500;
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
            }
            .notification.success { border-left-color: var(--success-color); }
            .notification.error { border-left-color: var(--danger-color); }
            .notification.warning { border-left-color: var(--warning-color); }
            .notification.info { border-left-color: var(--info-color); }
            .notification i { font-size: 1.2em; }
        `;
        document.head.appendChild(style);
    }
    
    // Remove after 4 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

// Show error message
function showError(message) {
    showNotification(message, 'error');
}

// Event Listeners
playButton.addEventListener('click', () => {
    const searchTerm = songNumberInput.value.trim();
    if (searchTerm) {
        const song = songList.find(s => 
            s.song_number === searchTerm || 
            s.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
            s.artist.toLowerCase().includes(searchTerm.toLowerCase())
        );
        
        if (song) {
            addSongToQueue(song);
            songNumberInput.value = '';
            songNumberInput.focus();
        } else {
            showError('Song not found! Try searching by number, title, or artist.');
        }
    }
});

nextButton.addEventListener('click', nextSong);

pauseButton.addEventListener('click', () => {
    if (isPlaying) {
        player.pause();
        updatePlayerStatus('Paused', 'paused');
    } else if (songQueue.length > 0) {
        player.play();
        updatePlayerStatus('Playing', 'playing');
    }
});

searchButton.addEventListener('click', () => {
    const searchTerm = songNumberInput.value.trim();
    songList = [];
    currentPage = 1;
    fetchSongList(1, searchTerm);
});

songSearchInput.addEventListener('input', debounce((e) => {
    const searchTerm = e.target.value.trim();
    songList = [];
    currentPage = 1;
    fetchSongList(1, searchTerm);
}, 300));

songNumberInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        playButton.click();
    }
});

songNumberInput.addEventListener('input', debounce((e) => {
    if (e.target.value.length >= 2) {
        const searchTerm = e.target.value.trim();
        songList = [];
        currentPage = 1;
        fetchSongList(1, searchTerm);
    } else if (e.target.value.length === 0) {
        songList = [];
        currentPage = 1;
        fetchSongList(1, '');
    }
}, 300));

volumeUpButton.addEventListener('click', () => {
    adjustVolume(0.1);
});

volumeDownButton.addEventListener('click', () => {
    adjustVolume(-0.1);
});

clearQueueButton.addEventListener('click', async () => {
    if (confirm('Are you sure you want to clear the entire queue?')) {
        songQueue = [];
        currentSongIndex = -1;

        player.pause();
        player.reset();

        updateQueueCount();
        renderQueue();

        // Clear from storage
        localStorage.removeItem(QUEUE_STORAGE_KEY);
        await clearIndexedDB();

        showNotification('Queue cleared', 'success');
        currentSongInfo.innerHTML = '<em>Select a song to begin</em>';
        updatePlayerStatus('Ready', 'info');
    }
});

// Video player event listeners
player.on('ended', () => {
    console.log('Video ended, playing next song');
    nextSong();
});

player.on('error', (e) => {
    console.error('Video player error:', player.error());
    updatePlayerStatus('Playback error', 'error');
    showError('Error playing video. Please try another song.');
    setTimeout(nextSong, 2000); // Try next song after 2 seconds
});

player.on('playing', () => {
    isPlaying = true;
    updatePlayerStatus('Playing', 'playing');
    saveQueueState(); // Save playing state
});

player.on('pause', () => {
    isPlaying = false;
    updatePlayerStatus('Paused', 'paused');
    saveQueueState(); // Save paused state
});

player.on('volumechange', () => {
    const volume = player.volume();
    playerStatus.title = `Volume: ${Math.round(volume * 100)}%`;
    saveQueueState(); // Save volume changes
});

// Auto-save queue state periodically (every 30 seconds)
setInterval(() => {
    if (songQueue.length > 0) {
        saveQueueState();
    }
}, 30000);

// Save queue state before page unload
window.addEventListener('beforeunload', () => {
    if (songQueue.length > 0) {
        saveQueueState();
    }
});

// Debounce function for search input
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Add CSS for additional styles
const additionalStyles = document.createElement('style');
additionalStyles.textContent = `
    .artist-section {
        margin-bottom: 20px;
    }
    
    .artist-letter {
        background: var(--accent-color);
        color: white;
        padding: 8px 16px;
        border-radius: var(--border-radius-sm);
        font-weight: 700;
        margin-bottom: 10px;
        display: inline-block;
        box-shadow: var(--shadow-sm);
    }
    
    .preview-play-btn {
        background: var(--success-color);
        color: white;
        border: none;
        border-radius: 6px;
        padding: 6px 12px;
        margin-top: 8px;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 600;
        transition: var(--transition);
    }
    
    .preview-play-btn:hover {
        background: #059669;
        transform: translateY(-2px);
    }
    
    .queue-actions {
        display: flex;
        gap: 8px;
    }
    
    .move-up-btn {
        background: var(--warning-color);
    }
    
    .move-up-btn:hover {
        background: #d97706;
    }
    
    .status-success { color: var(--success-color); }
    .status-error { color: var(--danger-color); }
    .status-warning { color: var(--warning-color); }
    .status-info { color: var(--info-color); }
    .status-playing { color: var(--success-color); }
    .status-paused { color: var(--warning-color); }
    .status-loading { color: var(--info-color); }
    
    /* Save indicator */
    .save-indicator {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: var(--success-color);
        color: white;
        padding: 12px 20px;
        border-radius: 10px;
        font-size: 0.9rem;
        font-weight: 600;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 10000;
        box-shadow: var(--shadow-lg);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .save-indicator.show {
        opacity: 1;
        transform: translateY(0);
    }
    
    .save-indicator i {
        font-size: 1.2em;
    }
`;
document.head.appendChild(additionalStyles);

// Initial load
document.addEventListener('DOMContentLoaded', async () => {
    // Initialize IndexedDB first
    await initIndexedDB();
    
    // Load previous queue state
    await loadQueueState();

    // Then fetch songs
    await fetchSongList(1);

    // Fetch and render rooms
    await fetchAndRenderRooms();

    // Room modal event listeners
    createRoomBtn.addEventListener('click', () => {
        createRoomModal.style.display = 'block';
    });

    submitCreateRoomBtn.addEventListener('click', createRoom);
    submitJoinRoomBtn.addEventListener('click', joinRoom);

    // Close modal listeners
    document.querySelectorAll('.modal .close-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            createRoomModal.style.display = 'none';
            joinRoomModal.style.display = 'none';
        });
    });

    window.addEventListener('click', (event) => {
        if (event.target == createRoomModal || event.target == joinRoomModal) {
            createRoomModal.style.display = 'none';
            joinRoomModal.style.display = 'none';
        }
    });

    // Add scroll listener for infinite scrolling to the correct element
    const songListWrapper = songListElement.parentElement;
    songListWrapper.addEventListener('scroll', () => {
        if (isFetching || songList.length >= totalSongs) {
            return;
        }

        // Load more songs when user is 500px from the bottom
        if (songListWrapper.scrollTop + songListWrapper.clientHeight >= songListWrapper.scrollHeight - 500) {
            const searchTerm = songSearchInput.value.trim();
            fetchSongList(currentPage + 1, searchTerm);
        }
    });
    
    // Add loading animation to player
    player.on('waiting', () => {
        updatePlayerStatus('Buffering...', 'loading');
    });
    
    player.on('canplay', () => {
        updatePlayerStatus('Ready to play', 'info');
    });
});

// Service Worker registration for PWA capabilities
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js').catch(err => {
            console.log('ServiceWorker registration failed: ', err);
        });
    });
}

// Offline detection
window.addEventListener('online', () => {
    showNotification('Back online', 'success');
    if (songList.length === 0) {
        fetchSongList();
    }
});

window.addEventListener('offline', () => {
    showNotification('You are offline. Some features may be unavailable.', 'warning');
});
