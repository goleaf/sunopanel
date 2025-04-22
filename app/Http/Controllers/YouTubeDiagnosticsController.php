<?php

namespace App\Http\Controllers;

use App\Services\SimpleYouTubeUploader;
use App\Services\YouTubeUploader;
use App\Services\YouTubePlaylistManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class YouTubeDiagnosticsController extends Controller
{
    private YouTubeUploader $youtubeUploader;
    private SimpleYouTubeUploader $simpleUploader;
    private YouTubePlaylistManager $playlistManager;
    
    public function __construct(
        YouTubeUploader $youtubeUploader,
        SimpleYouTubeUploader $simpleUploader,
        YouTubePlaylistManager $playlistManager
    ) {
        $this->youtubeUploader = $youtubeUploader;
        $this->simpleUploader = $simpleUploader;
        $this->playlistManager = $playlistManager;
    }
    
    /**
     * Show diagnostic page
     */
    public function index()
    {
        $diagnostics = $this->runDiagnostics();
        return view('youtube.diagnostics', ['diagnostics' => $diagnostics]);
    }
    
    /**
     * Run a test upload with a small video file
     */
    public function testUpload(Request $request)
    {
        $videoPath = storage_path('app/public/test-video.mp4');
        
        // Check if we have a test video, if not, generate one
        if (!file_exists($videoPath)) {
            $this->generateTestVideo($videoPath);
        }
        
        // Ensure the test video exists
        if (!file_exists($videoPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create test video file'
            ]);
        }
        
        try {
            // Try OAuth upload first if configured
            $useOAuth = Config::get('youtube.use_oauth', false) && 
                        Config::get('youtube.access_token') && 
                        Config::get('youtube.refresh_token');
            
            $result = null;
            $method = '';
            
            if ($useOAuth) {
                $method = 'OAuth';
                // Try OAuth upload
                $result = $this->youtubeUploader->upload(
                    $videoPath,
                    'Test Upload ' . date('Y-m-d H:i:s'),
                    'This is a test upload from SunoPanel diagnostics',
                    ['test', 'diagnostics'],
                    'unlisted',
                    '10' // Music
                );
            } else {
                $method = 'Simple Uploader';
                // Try simple uploader
                $result = $this->simpleUploader->upload(
                    $videoPath,
                    'Test Upload ' . date('Y-m-d H:i:s'),
                    'This is a test upload from SunoPanel diagnostics',
                    ['test', 'diagnostics'],
                    'unlisted',
                    'Music'
                );
            }
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Upload successful using ' . $method,
                    'video_id' => $result,
                    'video_url' => 'https://www.youtube.com/watch?v=' . $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload failed using ' . $method . '. Check logs for details.'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Test upload failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Upload failed with error: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Run diagnostics on YouTube integration
     */
    private function runDiagnostics(): array
    {
        $diagnostics = [];
        
        // Check OAuth configuration
        $diagnostics['oauth'] = [
            'enabled' => Config::get('youtube.use_oauth', false),
            'client_id' => !empty(Config::get('youtube.client_id')),
            'client_secret' => !empty(Config::get('youtube.client_secret')),
            'redirect_uri' => !empty(Config::get('youtube.redirect_uri')),
            'access_token' => !empty(Config::get('youtube.access_token')),
            'refresh_token' => !empty(Config::get('youtube.refresh_token')),
            'expired' => false
        ];
        
        if ($diagnostics['oauth']['access_token'] && $diagnostics['oauth']['refresh_token']) {
            $expiresAt = Config::get('youtube.token_expires_at');
            $diagnostics['oauth']['expired'] = $expiresAt && $expiresAt < time();
            $diagnostics['oauth']['expires_at'] = $expiresAt ? date('Y-m-d H:i:s', $expiresAt) : 'Unknown';
        }
        
        // Check simple uploader configuration
        $diagnostics['simple'] = [
            'enabled' => Config::get('youtube.use_simple_uploader', false),
            'email' => !empty(Config::get('youtube.email')),
            'password' => !empty(Config::get('youtube.password')),
            'script_exists' => file_exists(base_path('vendor/bin/youtube-direct-upload')),
            'client_secrets_exists' => file_exists(base_path('vendor/bin/youtube-client-secrets'))
        ];
        
        // Check package requirements
        $diagnostics['packages'] = [
            'google_client' => class_exists('Google_Client'),
            'google_service_youtube' => class_exists('Google_Service_YouTube')
        ];
        
        // Check if uploader is authenticated
        try {
            $diagnostics['authenticated'] = $this->youtubeUploader->isAuthenticated();
        } catch (\Exception $e) {
            $diagnostics['authenticated'] = false;
            $diagnostics['auth_error'] = $e->getMessage();
        }
        
        // Get recent uploads if authenticated
        $diagnostics['recent_uploads'] = [];
        if ($diagnostics['authenticated']) {
            try {
                // Placeholder for getting recent uploads - would require additional API methods
                $diagnostics['recent_uploads'] = [];
            } catch (\Exception $e) {
                $diagnostics['recent_uploads_error'] = $e->getMessage();
            }
        }
        
        // Check if log files exist and contain useful information
        $diagnostics['logs'] = [
            'path' => storage_path('logs'),
            'files_exist' => file_exists(storage_path('logs/laravel.log')),
        ];
        
        return $diagnostics;
    }
    
    /**
     * Generate a simple test video file
     */
    private function generateTestVideo(string $outputPath): bool
    {
        try {
            // Create a 5-second test video using ffmpeg
            $command = "ffmpeg -f lavfi -i color=c=blue:s=320x240:d=5 -vf drawtext=\"fontfile=/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf:text='SunoPanel Test':fontcolor=white:fontsize=24:x=(w-text_w)/2:y=(h-text_h)/2\" -c:v libx264 -y {$outputPath}";
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                Log::error('Failed to generate test video', [
                    'output' => $output,
                    'return_code' => $returnCode
                ]);
                
                // Try a different method without text
                $command = "ffmpeg -f lavfi -i color=c=blue:s=320x240:d=5 -c:v libx264 -y {$outputPath}";
                exec($command, $output, $returnCode);
                
                if ($returnCode !== 0) {
                    Log::error('Failed to generate simple test video', [
                        'output' => $output,
                        'return_code' => $returnCode
                    ]);
                    
                    // Last resort: copy an existing video if available
                    $existingVideos = glob(storage_path('app/public/videos/*.mp4'));
                    if (!empty($existingVideos)) {
                        return copy($existingVideos[0], $outputPath);
                    }
                    
                    return false;
                }
            }
            
            return file_exists($outputPath);
        } catch (\Exception $e) {
            Log::error('Exception during test video generation: ' . $e->getMessage());
            return false;
        }
    }
} 