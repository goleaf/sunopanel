<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\YouTubeUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

final class YouTubeAuthController extends Controller
{
    private YouTubeUploader $youtubeUploader;
    
    public function __construct(YouTubeUploader $youtubeUploader)
    {
        $this->youtubeUploader = $youtubeUploader;
    }
    
    /**
     * Show the authentication page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('youtube.auth');
    }
    
    /**
     * Redirect the user to the YouTube auth page
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToProvider()
    {
        $authUrl = $this->youtubeUploader->getAuthUrl();
        return redirect()->away($authUrl);
    }
    
    /**
     * Handle the YouTube OAuth callback
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleProviderCallback(Request $request)
    {
        $code = $request->input('code');
        
        if (!$code) {
            Log::error('No authorization code provided by YouTube');
            return redirect()->route('home')->with('error', 'Authorization failed. No code provided.');
        }
        
        $tokenData = $this->youtubeUploader->handleAuthCallback($code);
        
        if (!$tokenData) {
            Log::error('Failed to get tokens from YouTube');
            return redirect()->route('home')->with('error', 'Failed to authenticate with YouTube.');
        }
        
        // Save these values to the .env file
        $this->updateEnvFile([
            'YOUTUBE_ACCESS_TOKEN' => $tokenData['access_token'],
            'YOUTUBE_REFRESH_TOKEN' => $tokenData['refresh_token'] ?? '',
            'YOUTUBE_TOKEN_EXPIRES_AT' => $tokenData['expires_at'],
        ]);
        
        return redirect()->route('youtube.auth')->with('success', 'Successfully authenticated with YouTube!');
    }
    
    /**
     * Show the simple authentication form
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('youtube.login');
    }
    
    /**
     * Save YouTube credentials
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveCredentials(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'use_simple_uploader' => 'nullable|boolean',
        ]);
        
        $useSimpleUploader = isset($validated['use_simple_uploader']) && $validated['use_simple_uploader'] ? 'true' : 'false';
        
        $this->updateEnvFile([
            'YOUTUBE_EMAIL' => $validated['email'],
            'YOUTUBE_PASSWORD' => $validated['password'],
            'YOUTUBE_USE_SIMPLE_UPLOADER' => $useSimpleUploader,
            'YOUTUBE_USE_OAUTH' => 'false', // If simple login is used, disable OAuth
        ]);
        
        return redirect()->route('youtube.auth')->with('success', 'YouTube credentials saved successfully!');
    }
    
    /**
     * Toggle OAuth setting
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleOAuth(Request $request)
    {
        $useOAuth = (bool) $request->input('use_oauth', false);
        
        $this->updateEnvFile([
            'YOUTUBE_USE_OAUTH' => $useOAuth ? 'true' : 'false',
            'YOUTUBE_USE_SIMPLE_UPLOADER' => $useOAuth ? 'false' : 'true',
        ]);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Toggle simple uploader setting
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleSimple(Request $request)
    {
        $useSimple = (bool) $request->input('use_simple', false);
        
        $this->updateEnvFile([
            'YOUTUBE_USE_SIMPLE_UPLOADER' => $useSimple ? 'true' : 'false',
            'YOUTUBE_USE_OAUTH' => $useSimple ? 'false' : 'true',
        ]);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Update .env file with new values
     *
     * @param array $data
     * @return bool
     */
    private function updateEnvFile(array $data): bool
    {
        try {
            $envFile = base_path('.env');
            $contentArray = file($envFile, FILE_IGNORE_NEW_LINES);
            
            foreach ($data as $key => $value) {
                $found = false;
                
                foreach ($contentArray as $index => $line) {
                    if (strpos($line, "$key=") === 0) {
                        $contentArray[$index] = "$key=\"$value\"";
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    $contentArray[] = "$key=\"$value\"";
                }
            }
            
            file_put_contents($envFile, implode("\n", $contentArray));
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update .env file: ' . $e->getMessage());
            return false;
        }
    }
} 