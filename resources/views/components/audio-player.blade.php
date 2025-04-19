@props([
    'track' => null,
    'audioUrl' => '',
    'trackName' => 'Unknown Track',
    'trackId' => null,
    'showControls' => true,
    'autoplay' => false,
    'loop' => false,
    'class' => '',
])

@php
    // If a track object is passed, use its properties
    if ($track) {
        $audioUrl = $track->audio_url;
        $trackName = $track->title;
        $trackId = $track->id;
    }
@endphp

<div {{ $attributes->merge(['class' => 'audio-player ' . $class]) }} id="player-{{ $trackId ?? uniqid() }}">
    <div class="flex items-center p-2 rounded-lg bg-gray-50 border border-gray-200">
        <button 
            type="button" 
            class="play-pause-btn flex-shrink-0 h-10 w-10 rounded-full bg-indigo-600 text-white flex items-center justify-center focus:outline-none hover:bg-indigo-700"
            onclick="togglePlayPause(this)"
        >
            <svg class="play-icon h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
            </svg>
            <svg class="pause-icon h-5 w-5 hidden" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
        </button>
        
        <div class="ml-4 flex-1">
            <div class="font-medium text-sm text-gray-900 truncate">{{ $trackName }}</div>
            <div class="flex items-center mt-1">
                <div class="flex-1">
                    <input 
                        type="range" 
                        min="0" 
                        max="100" 
                        value="0" 
                        class="progress-bar w-full h-1.5 bg-gray-200 rounded-full appearance-none cursor-pointer"
                        oninput="updateAudioPosition(this)"
                    >
                </div>
                <div class="text-xs text-gray-500 ml-2 time-display">00:00</div>
            </div>
        </div>
        
        <div class="ml-4 flex items-center">
            <button 
                type="button" 
                class="volume-btn flex-shrink-0 h-8 w-8 rounded-full text-gray-500 flex items-center justify-center focus:outline-none hover:bg-gray-100"
                onclick="toggleMute(this)"
            >
                <svg class="volume-icon h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd" />
                </svg>
                <svg class="mute-icon h-5 w-5 hidden" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM12.293 7.293a1 1 0 011.414 0L15 8.586l1.293-1.293a1 1 0 111.414 1.414L16.414 10l1.293 1.293a1 1 0 01-1.414 1.414L15 11.414l-1.293 1.293a1 1 0 01-1.414-1.414L13.586 10l-1.293-1.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>
    
    <audio 
        id="audio-{{ $trackId ?? uniqid() }}" 
        src="{{ $audioUrl }}" 
        {{ $showControls ? 'controls' : '' }} 
        {{ $autoplay ? 'autoplay' : '' }} 
        {{ $loop ? 'loop' : '' }}
        preload="metadata"
        class="hidden"
    ></audio>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all players
        document.querySelectorAll('.audio-player').forEach(setupPlayer);
    });
    
    function setupPlayer(playerElement) {
        const audio = playerElement.querySelector('audio');
        const progressBar = playerElement.querySelector('.progress-bar');
        const timeDisplay = playerElement.querySelector('.time-display');
        
        if (!audio || !progressBar || !timeDisplay) return;
        
        // Update progress bar and time display during playback
        audio.addEventListener('timeupdate', function() {
            const percent = (audio.currentTime / audio.duration) * 100;
            progressBar.value = percent;
            
            timeDisplay.textContent = formatTime(audio.currentTime);
        });
        
        // Update duration when metadata is loaded
        audio.addEventListener('loadedmetadata', function() {
            timeDisplay.textContent = formatTime(audio.duration);
        });
        
        // Handle play/pause state changes
        audio.addEventListener('play', function() {
            const btn = playerElement.querySelector('.play-pause-btn');
            btn.querySelector('.play-icon').classList.add('hidden');
            btn.querySelector('.pause-icon').classList.remove('hidden');
        });
        
        audio.addEventListener('pause', function() {
            const btn = playerElement.querySelector('.play-pause-btn');
            btn.querySelector('.play-icon').classList.remove('hidden');
            btn.querySelector('.pause-icon').classList.add('hidden');
        });
    }
    
    function togglePlayPause(button) {
        const playerElement = button.closest('.audio-player');
        const audio = playerElement.querySelector('audio');
        
        if (audio.paused) {
            // Pause all other audio elements first
            document.querySelectorAll('audio').forEach(function(otherAudio) {
                if (otherAudio !== audio && !otherAudio.paused) {
                    otherAudio.pause();
                }
            });
            
            audio.play();
        } else {
            audio.pause();
        }
    }
    
    function updateAudioPosition(rangeInput) {
        const playerElement = rangeInput.closest('.audio-player');
        const audio = playerElement.querySelector('audio');
        
        if (audio.duration) {
            const position = (rangeInput.value / 100) * audio.duration;
            audio.currentTime = position;
        }
    }
    
    function toggleMute(button) {
        const playerElement = button.closest('.audio-player');
        const audio = playerElement.querySelector('audio');
        
        audio.muted = !audio.muted;
        
        const volumeIcon = button.querySelector('.volume-icon');
        const muteIcon = button.querySelector('.mute-icon');
        
        if (audio.muted) {
            volumeIcon.classList.add('hidden');
            muteIcon.classList.remove('hidden');
        } else {
            volumeIcon.classList.remove('hidden');
            muteIcon.classList.add('hidden');
        }
    }
    
    function formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
</script> 