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
        
        // Here you could save these values to your .env file or database
        // For demonstration, we'll just log them
        Log::info('YouTube auth successful', [
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'] ?? 'No refresh token',
            'expires_at' => $tokenData['expires_at'],
        ]);
        
        // In a real application, you would update your .env or database with these values
        // For example:
        // $this->updateEnvFile([
        //     'YOUTUBE_ACCESS_TOKEN' => $tokenData['access_token'],
        //     'YOUTUBE_REFRESH_TOKEN' => $tokenData['refresh_token'] ?? '',
        //     'YOUTUBE_TOKEN_EXPIRES_AT' => $tokenData['expires_at'],
        // ]);
        
        return redirect()->route('home')->with('success', 'Successfully authenticated with YouTube!');
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