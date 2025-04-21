<?php

namespace App\Http\Controllers;

use App\Services\YouTubeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class YouTubeAuthController extends Controller
{
    protected YouTubeService $youtubeService;
    
    public function __construct(YouTubeService $youtubeService)
    {
        $this->youtubeService = $youtubeService;
    }
    
    /**
     * Redirect to YouTube OAuth
     */
    public function redirect()
    {
        // If using simple uploader, redirect to login form instead
        if (config('youtube.use_simple_uploader')) {
            return redirect()->route('youtube.auth.login_form');
        }
        
        $authUrl = $this->youtubeService->getAuthUrl();
        return redirect($authUrl);
    }
    
    /**
     * Handle callback from YouTube OAuth
     */
    public function callback(Request $request)
    {
        $code = $request->get('code');
        
        if (!$code) {
            Log::error('No authorization code provided in YouTube callback');
            return redirect()->route('youtube.status')->with('error', 'Authorization failed: No code provided');
        }
        
        $success = $this->youtubeService->handleAuthCallback($code);
        
        if (!$success) {
            return redirect()->route('youtube.status')->with('error', 'Authorization failed: Could not retrieve access token');
        }
        
        return redirect()->route('youtube.status')->with('success', 'Successfully authenticated with YouTube');
    }
    
    /**
     * Show YouTube authentication status
     */
    public function status()
    {
        $isAuthenticated = false;
        
        if (config('youtube.use_simple_uploader')) {
            // Check if credentials are set
            $isAuthenticated = !empty(config('youtube.email')) && !empty(config('youtube.password'));
        } else {
            $isAuthenticated = $this->youtubeService->isAuthenticated();
        }
        
        return view('youtube.status', [
            'isAuthenticated' => $isAuthenticated,
            'useSimpleUploader' => config('youtube.use_simple_uploader'),
        ]);
    }
    
    /**
     * Show login form for simple uploader
     */
    public function showLoginForm()
    {
        // If not using simple uploader, redirect to regular auth
        if (!config('youtube.use_simple_uploader')) {
            return redirect()->route('youtube.auth.redirect');
        }
        
        return view('youtube.login');
    }
    
    /**
     * Save YouTube credentials
     */
    public function saveCredentials(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        
        // Update .env file
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);
        
        // Add or update email
        if (strpos($envContent, 'YOUTUBE_EMAIL=') !== false) {
            $envContent = preg_replace('/YOUTUBE_EMAIL=.*/', 'YOUTUBE_EMAIL=' . $validated['email'], $envContent);
        } else {
            $envContent .= "\nYOUTUBE_EMAIL=" . $validated['email'];
        }
        
        // Add or update password
        if (strpos($envContent, 'YOUTUBE_PASSWORD=') !== false) {
            $envContent = preg_replace('/YOUTUBE_PASSWORD=.*/', 'YOUTUBE_PASSWORD=' . $validated['password'], $envContent);
        } else {
            $envContent .= "\nYOUTUBE_PASSWORD=" . $validated['password'];
        }
        
        // Add simple uploader setting if not already there
        if (strpos($envContent, 'YOUTUBE_USE_SIMPLE_UPLOADER=') === false) {
            $envContent .= "\nYOUTUBE_USE_SIMPLE_UPLOADER=true";
        }
        
        file_put_contents($envFile, $envContent);
        
        // Clear config cache
        \Artisan::call('config:clear');
        
        return redirect()->route('youtube.status')
            ->with('success', 'YouTube credentials saved successfully');
    }
} 