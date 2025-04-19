@props([
    'track' => null,
    'audioUrl' => '',
    'trackName' => 'Unknown Track',
    'trackId' => null,
    'showControls' => true,
    'autoplay' => false,
    'loop' => false,
    'class' => '',
    'src' => '',
    'trackTitle' => null,
    'showDownload' => true
])

@php
    // If a track object is passed, use its properties
    if ($track) {
        $audioUrl = $track->audio_url;
        $trackName = $track->title;
        $trackId = $track->id;
    }
    
    // For backward compatibility with new implementation
    if (!empty($src)) {
        $audioUrl = $src;
    }
    
    if (!empty($trackTitle)) {
        $trackName = $trackTitle;
    }
    
    $uniqueId = $trackId ?? uniqid();
@endphp

<div x-data="{
    player: null,
    isPlaying: false,
    currentTime: 0,
    duration: 0,
    volume: 80,
    isMuted: false,
    loading: true,
    playbackRate: 1.0,
    isLooping: {{ $loop ? 'true' : 'false' }},
    formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    },
    setPlayer() {
        this.player = this.$refs.player;
        this.player.volume = this.volume / 100;
        this.player.loop = this.isLooping;
        
        this.player.addEventListener('loadedmetadata', () => {
            this.duration = this.player.duration;
            this.loading = false;
        });
        
        this.player.addEventListener('timeupdate', () => {
            this.currentTime = this.player.currentTime;
        });
        
        this.player.addEventListener('ended', () => {
            if (!this.isLooping) {
                this.isPlaying = false;
            }
        });
        
        this.player.addEventListener('play', () => {
            this.isPlaying = true;
        });
        
        this.player.addEventListener('pause', () => {
            this.isPlaying = false;
        });
    },
    togglePlay() {
        if (this.isPlaying) {
            this.player.pause();
        } else {
            // Pause all other audio elements first
            document.querySelectorAll('audio').forEach(function(otherAudio) {
                if (otherAudio !== this.player && !otherAudio.paused) {
                    otherAudio.pause();
                }
            });
            
            this.player.play().catch(error => {
                console.error('Error playing audio:', error);
            });
        }
    },
    updateProgress(event) {
        const progressBar = event.currentTarget;
        const rect = progressBar.getBoundingClientRect();
        const percent = (event.clientX - rect.left) / rect.width;
        this.player.currentTime = percent * this.player.duration;
    },
    updateVolume() {
        this.player.volume = this.volume / 100;
        if (this.volume > 0) {
            this.isMuted = false;
            this.player.muted = false;
        } else {
            this.isMuted = true;
            this.player.muted = true;
        }
    },
    toggleMute() {
        this.isMuted = !this.isMuted;
        this.player.muted = this.isMuted;
    },
    seek(seconds) {
        if (this.player.duration) {
            const newTime = Math.max(0, Math.min(this.player.duration, this.player.currentTime + seconds));
            this.player.currentTime = newTime;
        }
    },
    toggleLoop() {
        this.isLooping = !this.isLooping;
        this.player.loop = this.isLooping;
    },
    changePlaybackSpeed(speed) {
        this.playbackRate = speed;
        this.player.playbackRate = speed;
    }
}" x-init="setPlayer()" {{ $attributes->merge(['class' => 'audio-player rounded-lg shadow-md ' . $class]) }} id="player-{{ $uniqueId }}">
    <div class="flex flex-col p-3 bg-base-200 border border-base-300 rounded-lg">
        <!-- Track title and duration -->
        <div class="flex justify-between items-center mb-2">
            <div class="font-medium text-base-content truncate max-w-[70%]">{{ $trackName }}</div>
            <div class="text-xs text-base-content/70 flex items-center gap-1">
                <span x-text="formatTime(currentTime)">00:00</span>
                <span>/</span>
                <span x-text="formatTime(duration)">00:00</span>
            </div>
        </div>
        
        <!-- Progress bar -->
        <div class="w-full mb-2">
            <div 
                @click="updateProgress($event)"
                class="progress progress-primary h-2 cursor-pointer"
            >
                <div 
                    class="absolute bg-primary h-2 rounded-full"
                    :style="`width: ${(currentTime / duration) * 100}%`"
                ></div>
            </div>
        </div>
        
        <!-- Controls -->
        <div class="flex items-center justify-between">
            <!-- Main controls -->
            <div class="flex items-center gap-2">
                <!-- Backward 10s -->
                <x-button 
                    type="button" 
                    size="sm"
                    color="ghost"
                    icon
                    @click="seek(-10)"
                    title="Backward 10 seconds"
                >
                    <x-icon name="rewind" size="4" />
                </x-button>
                
                <!-- Play/Pause -->
                <x-button 
                    type="button" 
                    size="sm"
                    :color="isPlaying ? 'primary' : 'base'"
                    icon
                    @click="togglePlay()"
                    title="Play/Pause"
                >
                    <template x-if="!isPlaying">
                        <x-icon name="play" size="4" />
                    </template>
                    <template x-if="isPlaying">
                        <x-icon name="pause" size="4" />
                    </template>
                </x-button>
                
                <!-- Forward 10s -->
                <x-button 
                    type="button"
                    size="sm"
                    color="ghost"
                    icon
                    @click="seek(10)"
                    title="Forward 10 seconds"
                >
                    <x-icon name="fast-forward" size="4" />
                </x-button>
            </div>
            
            <!-- Secondary controls -->
            <div class="flex items-center gap-2">
                <!-- Speed control -->
                <div class="dropdown dropdown-top dropdown-end">
                    <x-button 
                        size="sm"
                        color="ghost"
                        icon
                        tabindex="0" 
                        title="Playback speed"
                    >
                        <x-icon name="lightning" size="4" />
                    </x-button>
                    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-36">
                        <li><a @click="changePlaybackSpeed(0.5)" :class="{'active': playbackRate === 0.5}">0.5x</a></li>
                        <li><a @click="changePlaybackSpeed(0.75)" :class="{'active': playbackRate === 0.75}">0.75x</a></li>
                        <li><a @click="changePlaybackSpeed(1.0)" :class="{'active': playbackRate === 1.0}">1.0x</a></li>
                        <li><a @click="changePlaybackSpeed(1.25)" :class="{'active': playbackRate === 1.25}">1.25x</a></li>
                        <li><a @click="changePlaybackSpeed(1.5)" :class="{'active': playbackRate === 1.5}">1.5x</a></li>
                        <li><a @click="changePlaybackSpeed(2.0)" :class="{'active': playbackRate === 2.0}">2.0x</a></li>
                    </ul>
                </div>
                
                <!-- Loop toggle -->
                <x-button 
                    type="button"
                    size="sm"
                    :color="isLooping ? 'primary' : 'ghost'"
                    icon
                    @click="toggleLoop()"
                    title="Toggle loop"
                >
                    <x-icon name="loop" size="4" />
                </x-button>

                <!-- Volume -->
                <div class="dropdown dropdown-top dropdown-end">
                    <x-button 
                        size="sm"
                        color="ghost"
                        icon
                        tabindex="0" 
                        @click="toggleMute()" 
                        title="Volume"
                    >
                        <template x-if="!isMuted">
                            <x-icon name="volume" size="4" />
                        </template>
                        <template x-if="isMuted">
                            <x-icon name="volume-off" size="4" />
                        </template>
                    </x-button>
                    <div tabindex="0" class="dropdown-content z-[1] p-2 shadow bg-base-100 rounded-box">
                        <input 
                            type="range" 
                            min="0" 
                            max="100" 
                            x-model="volume"
                            @input="updateVolume()"
                            class="range range-primary range-sm h-24"
                            orient="vertical"
                        >
                    </div>
                </div>
                
                <!-- Download button if enabled -->
                @if($showDownload)
                <x-button 
                    :href="$audioUrl" 
                    download 
                    size="sm"
                    color="ghost"
                    icon
                    title="Download"
                >
                    <x-icon name="download" size="4" />
                </x-button>
                @endif
            </div>
        </div>
        
        <!-- Loading indicator -->
        <div x-show="loading" class="flex justify-center mt-2">
            <span class="loading loading-spinner loading-sm text-primary"></span>
        </div>
    </div>
    
    <audio 
        x-ref="player" 
        preload="metadata"
        class="hidden"
    >
        <source src="{{ $audioUrl }}" type="audio/mpeg">
        Your browser does not support the audio element.
    </audio>
</div>

<style>
    /* Custom styling for vertical volume slider */
    .range[orient="vertical"] {
        -webkit-appearance: slider-vertical;
        writing-mode: bt-lr;
    }
    
    /* Playback speed active item */
    .active {
        font-weight: bold;
        background-color: hsl(var(--p) / 0.2);
    }
</style> 