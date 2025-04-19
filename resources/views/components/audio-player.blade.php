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
    
    $uniqueId = $trackId ?? uniqid();
@endphp

<div {{ $attributes->merge(['class' => 'audio-player rounded-lg shadow-md ' . $class]) }} id="player-{{ $uniqueId }}">
    <div class="flex flex-col p-3 bg-base-200 border border-base-300 rounded-lg">
        <!-- Track title and duration -->
        <div class="flex justify-between items-center mb-2">
            <div class="font-medium text-base-content truncate max-w-[70%]">{{ $trackName }}</div>
            <div class="text-xs text-base-content/70 time-display flex items-center gap-1">
                <span class="current-time">00:00</span>
                <span>/</span>
                <span class="total-time">00:00</span>
            </div>
        </div>
        
        <!-- Progress bar -->
        <div class="w-full mb-2">
            <input 
                type="range" 
                min="0" 
                max="100" 
                value="0" 
                class="progress-bar range range-primary range-xs w-full"
                oninput="updateAudioPosition(this)"
            >
        </div>
        
        <!-- Controls -->
        <div class="flex items-center justify-between">
            <!-- Main controls -->
            <div class="flex items-center gap-2">
                <!-- Backward 10s -->
                <button 
                    type="button" 
                    class="backward-btn btn btn-sm btn-circle btn-ghost"
                    onclick="seekAudio(this, -10)"
                    title="Backward 10 seconds"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z" />
                    </svg>
                </button>
                
                <!-- Play/Pause -->
                <button 
                    type="button" 
                    class="play-pause-btn btn btn-sm btn-circle btn-primary"
                    onclick="togglePlayPause(this)"
                    title="Play/Pause"
                >
                    <svg class="play-icon h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                    </svg>
                    <svg class="pause-icon h-4 w-4 hidden" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </button>
                
                <!-- Forward 10s -->
                <button 
                    type="button" 
                    class="forward-btn btn btn-sm btn-circle btn-ghost"
                    onclick="seekAudio(this, 10)"
                    title="Forward 10 seconds"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zM19.933 12.8a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z" />
                    </svg>
                </button>
            </div>
            
            <!-- Secondary controls -->
            <div class="flex items-center gap-2">
                <!-- Speed control -->
                <div class="dropdown dropdown-top dropdown-end">
                    <label tabindex="0" class="btn btn-sm btn-circle btn-ghost" title="Playback speed">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </label>
                    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-36">
                        <li><a onclick="changePlaybackSpeed(this, 0.5)" class="playback-speed-option">0.5x</a></li>
                        <li><a onclick="changePlaybackSpeed(this, 0.75)" class="playback-speed-option">0.75x</a></li>
                        <li><a onclick="changePlaybackSpeed(this, 1.0)" class="playback-speed-option active">1.0x</a></li>
                        <li><a onclick="changePlaybackSpeed(this, 1.25)" class="playback-speed-option">1.25x</a></li>
                        <li><a onclick="changePlaybackSpeed(this, 1.5)" class="playback-speed-option">1.5x</a></li>
                        <li><a onclick="changePlaybackSpeed(this, 2.0)" class="playback-speed-option">2.0x</a></li>
                    </ul>
                </div>
                
                <!-- Loop toggle -->
                <button 
                    type="button" 
                    class="loop-btn btn btn-sm btn-circle btn-ghost"
                    onclick="toggleLoop(this)"
                    title="Toggle loop"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>

                <!-- Volume -->
                <div class="dropdown dropdown-top dropdown-end">
                    <label tabindex="0" class="volume-btn btn btn-sm btn-circle btn-ghost" onclick="toggleMute(this)" title="Volume">
                        <svg class="volume-icon h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243a1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828a1 1 0 010-1.415z" clip-rule="evenodd" />
                        </svg>
                        <svg class="mute-icon h-4 w-4 hidden" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM12.293 7.293a1 1 0 011.414 0L15 8.586l1.293-1.293a1 1 0 111.414 1.414L16.414 10l1.293 1.293a1 1 0 01-1.414 1.414L15 11.414l-1.293 1.293a1 1 0 01-1.414-1.414L13.586 10l-1.293-1.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </label>
                    <div tabindex="0" class="dropdown-content z-[1] p-2 shadow bg-base-100 rounded-box">
                        <input 
                            type="range" 
                            min="0" 
                            max="100" 
                            value="100" 
                            class="volume-slider range range-primary range-sm h-24"
                            oninput="adjustVolume(this)"
                            orient="vertical"
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <audio 
        id="audio-{{ $uniqueId }}" 
        src="{{ $audioUrl }}" 
        preload="metadata"
        class="hidden"
        {{ $loop ? 'loop' : '' }}
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
        const currentTimeDisplay = playerElement.querySelector('.current-time');
        const totalTimeDisplay = playerElement.querySelector('.total-time');
        const loopBtn = playerElement.querySelector('.loop-btn');
        
        if (!audio || !progressBar) return;
        
        // Apply loop state from attribute
        if (audio.hasAttribute('loop')) {
            loopBtn.classList.add('btn-active');
        }
        
        // Update progress bar and time display during playback
        audio.addEventListener('timeupdate', function() {
            if (audio.duration) {
                const percent = (audio.currentTime / audio.duration) * 100;
                progressBar.value = percent;
                currentTimeDisplay.textContent = formatTime(audio.currentTime);
            }
        });
        
        // Update duration when metadata is loaded
        audio.addEventListener('loadedmetadata', function() {
            totalTimeDisplay.textContent = formatTime(audio.duration);
            currentTimeDisplay.textContent = formatTime(0);
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
        
        // Handle end of track
        audio.addEventListener('ended', function() {
            if (!audio.loop) {
                const btn = playerElement.querySelector('.play-pause-btn');
                btn.querySelector('.play-icon').classList.remove('hidden');
                btn.querySelector('.pause-icon').classList.add('hidden');
                progressBar.value = 0;
                currentTimeDisplay.textContent = formatTime(0);
            }
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
            
            audio.play().catch(error => {
                console.error('Error playing audio:', error);
            });
        } else {
            audio.pause();
        }
    }
    
    function updateAudioPosition(rangeInput) {
        const playerElement = rangeInput.closest('.audio-player');
        const audio = playerElement.querySelector('audio');
        const currentTimeDisplay = playerElement.querySelector('.current-time');
        
        if (audio.duration) {
            const position = (rangeInput.value / 100) * audio.duration;
            audio.currentTime = position;
            currentTimeDisplay.textContent = formatTime(position);
        }
    }
    
    function toggleMute(button) {
        const playerElement = button.closest('.audio-player');
        const audio = playerElement.querySelector('audio');
        const volumeSlider = playerElement.querySelector('.volume-slider');
        
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
    
    function adjustVolume(slider) {
        const playerElement = slider.closest('.audio-player');
        const audio = playerElement.querySelector('audio');
        const volumeBtn = playerElement.querySelector('.volume-btn');
        const volumeIcon = volumeBtn.querySelector('.volume-icon');
        const muteIcon = volumeBtn.querySelector('.mute-icon');
        
        const volume = slider.value / 100;
        audio.volume = volume;
        
        // Update mute status based on volume
        if (volume === 0) {
            audio.muted = true;
            volumeIcon.classList.add('hidden');
            muteIcon.classList.remove('hidden');
        } else {
            audio.muted = false;
            volumeIcon.classList.remove('hidden');
            muteIcon.classList.add('hidden');
        }
    }
    
    function seekAudio(button, seconds) {
        const playerElement = button.closest('.audio-player');
        const audio = playerElement.querySelector('audio');
        const progressBar = playerElement.querySelector('.progress-bar');
        const currentTimeDisplay = playerElement.querySelector('.current-time');
        
        if (audio.duration) {
            const newTime = Math.max(0, Math.min(audio.duration, audio.currentTime + seconds));
            audio.currentTime = newTime;
            progressBar.value = (newTime / audio.duration) * 100;
            currentTimeDisplay.textContent = formatTime(newTime);
        }
    }
    
    function toggleLoop(button) {
        const playerElement = button.closest('.audio-player');
        const audio = playerElement.querySelector('audio');
        
        audio.loop = !audio.loop;
        
        // Toggle visual state
        if (audio.loop) {
            button.classList.add('btn-active');
        } else {
            button.classList.remove('btn-active');
        }
    }
    
    function changePlaybackSpeed(link, speed) {
        const playerElement = link.closest('.audio-player');
        const audio = playerElement.querySelector('audio');
        const speedOptions = playerElement.querySelectorAll('.playback-speed-option');
        
        // Set the playback rate
        audio.playbackRate = speed;
        
        // Update the UI
        speedOptions.forEach(option => {
            option.classList.remove('active');
        });
        link.classList.add('active');
    }
    
    function formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
</script>

<style>
    /* Custom styling for vertical volume slider */
    .volume-slider[orient="vertical"] {
        -webkit-appearance: slider-vertical;
        writing-mode: bt-lr;
    }
    
    /* Playback speed active item */
    .playback-speed-option.active {
        font-weight: bold;
        background-color: hsl(var(--p) / 0.2);
    }
</style> 