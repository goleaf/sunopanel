@props(['src', 'trackId', 'trackName'])

<div class="audio-player bg-white rounded-lg shadow-md p-4 w-full">
    <div class="flex items-center justify-between mb-2">
        <h3 class="text-sm font-medium text-gray-900 truncate" title="{{ $trackName }}">{{ $trackName }}</h3>
        <div class="flex items-center space-x-2">
            <span class="text-xs text-gray-500 duration-display">0:00</span>
            <span class="text-xs text-gray-500">/</span>
            <span class="text-xs text-gray-500 total-duration">0:00</span>
        </div>
    </div>
    
    <div class="relative h-1 bg-gray-200 rounded-full mb-2 progress-container">
        <div class="absolute h-1 bg-indigo-600 rounded-full progress-bar" style="width: 0%"></div>
    </div>
    
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <button type="button" class="play-pause-btn text-indigo-600 hover:text-indigo-800 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 play-icon" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 pause-icon hidden" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
        
        <div class="flex items-center space-x-2">
            <button type="button" class="mute-btn text-gray-500 hover:text-gray-700 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 volume-on" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd" />
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 volume-off hidden" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM12.293 7.293a1 1 0 011.414 0L15 8.586l1.293-1.293a1 1 0 111.414 1.414L16.414 10l1.293 1.293a1 1 0 01-1.414 1.414L15 11.414l-1.293 1.293a1 1 0 01-1.414-1.414L13.586 10l-1.293-1.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
            <div class="w-20 h-1 bg-gray-200 rounded-full volume-container">
                <div class="h-full bg-indigo-600 rounded-full volume-bar" style="width: 100%"></div>
            </div>
        </div>
    </div>
    
    <audio id="audio-{{ $trackId }}" src="{{ $src }}" preload="metadata" class="hidden"></audio>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const players = document.querySelectorAll('.audio-player');
    
    players.forEach(player => {
        const audio = player.querySelector('audio');
        const playPauseBtn = player.querySelector('.play-pause-btn');
        const playIcon = player.querySelector('.play-icon');
        const pauseIcon = player.querySelector('.pause-icon');
        const progressBar = player.querySelector('.progress-bar');
        const progressContainer = player.querySelector('.progress-container');
        const durationDisplay = player.querySelector('.duration-display');
        const totalDuration = player.querySelector('.total-duration');
        const muteBtn = player.querySelector('.mute-btn');
        const volumeOn = player.querySelector('.volume-on');
        const volumeOff = player.querySelector('.volume-off');
        const volumeBar = player.querySelector('.volume-bar');
        const volumeContainer = player.querySelector('.volume-container');
        
        // Format time in minutes and seconds
        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            return `${minutes}:${remainingSeconds < 10 ? '0' : ''}${remainingSeconds}`;
        }
        
        // Update progress bar and time display
        function updateProgress() {
            const percent = (audio.currentTime / audio.duration) * 100;
            progressBar.style.width = `${percent}%`;
            durationDisplay.textContent = formatTime(audio.currentTime);
        }
        
        // Set up audio metadata when loaded
        audio.addEventListener('loadedmetadata', () => {
            totalDuration.textContent = formatTime(audio.duration);
        });
        
        // Play/pause toggle
        playPauseBtn.addEventListener('click', () => {
            if (audio.paused) {
                // Pause all other audio elements first
                document.querySelectorAll('audio').forEach(a => {
                    if (a !== audio && !a.paused) {
                        a.pause();
                        const parentPlayer = a.closest('.audio-player');
                        if (parentPlayer) {
                            parentPlayer.querySelector('.play-icon').classList.remove('hidden');
                            parentPlayer.querySelector('.pause-icon').classList.add('hidden');
                        }
                    }
                });
                
                audio.play();
                playIcon.classList.add('hidden');
                pauseIcon.classList.remove('hidden');
            } else {
                audio.pause();
                playIcon.classList.remove('hidden');
                pauseIcon.classList.add('hidden');
            }
        });
        
        // Update progress as audio plays
        audio.addEventListener('timeupdate', updateProgress);
        
        // Allow clicking on progress bar to seek
        progressContainer.addEventListener('click', (e) => {
            const clickPosition = (e.offsetX / progressContainer.offsetWidth);
            audio.currentTime = clickPosition * audio.duration;
            updateProgress();
        });
        
        // Handle audio end
        audio.addEventListener('ended', () => {
            audio.currentTime = 0;
            playIcon.classList.remove('hidden');
            pauseIcon.classList.add('hidden');
        });
        
        // Mute toggle
        muteBtn.addEventListener('click', () => {
            audio.muted = !audio.muted;
            if (audio.muted) {
                volumeOn.classList.add('hidden');
                volumeOff.classList.remove('hidden');
                volumeBar.style.width = '0%';
            } else {
                volumeOn.classList.remove('hidden');
                volumeOff.classList.add('hidden');
                volumeBar.style.width = `${audio.volume * 100}%`;
            }
        });
        
        // Volume control
        volumeContainer.addEventListener('click', (e) => {
            const newVolume = e.offsetX / volumeContainer.offsetWidth;
            audio.volume = newVolume;
            volumeBar.style.width = `${newVolume * 100}%`;
            
            if (newVolume === 0) {
                audio.muted = true;
                volumeOn.classList.add('hidden');
                volumeOff.classList.remove('hidden');
            } else if (audio.muted) {
                audio.muted = false;
                volumeOn.classList.remove('hidden');
                volumeOff.classList.add('hidden');
            }
        });
    });
});
</script> 