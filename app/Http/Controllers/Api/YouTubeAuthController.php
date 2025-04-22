<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\YouTubeApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class YouTubeAuthController extends Controller
{
    protected $youtubeApiService;

    public function __construct(YouTubeApiService $youtubeApiService)
    {
        $this->youtubeApiService = $youtubeApiService;
    }

    /**
     * Show the YouTube authentication page.
     */
    public function index()
    {
        $isAuthenticated = $this->youtubeApiService->isAuthenticated();
        $authUrl = $isAuthenticated ? null : $this->youtubeApiService->getAuthUrl();

        return view('youtube.auth', [
            'isAuthenticated' => $isAuthenticated,
            'authUrl' => $authUrl,
            'useOAuth' => config('youtube.use_oauth', false),
            'useSimple' => config('youtube.use_simple', true),
        ]);
    }

    /**
     * Redirect to Google for authorization.
     */
    public function redirectToProvider()
    {
        return redirect($this->youtubeApiService->getAuthUrl());
    }

    /**
     * Handle the callback from Google.
     */
    public function handleProviderCallback(Request $request)
    {
        if ($request->has('error')) {
            Log::error('YouTube authentication error: ' . $request->input('error'));
            return redirect()->route('youtube.auth')
                ->with('error', 'Authentication failed: ' . $request->input('error'));
        }

        if (!$request->has('code')) {
            return redirect()->route('youtube.auth')
                ->with('error', 'No authorization code provided');
        }

        try {
            $this->youtubeApiService->fetchAccessTokenWithAuthCode($request->input('code'));
            $this->updateEnvVariable('YOUTUBE_USE_OAUTH', 'true');

            return redirect()->route('youtube.auth')
                ->with('success', 'Successfully authenticated with YouTube!');
        } catch (\Exception $e) {
            Log::error('Error handling YouTube callback: ' . $e->getMessage());
            return redirect()->route('youtube.auth')
                ->with('error', 'Authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Show login form for simple YouTube uploader.
     */
    public function showLoginForm()
    {
        return view('youtube.login_form', [
            'email' => config('youtube.email'),
            'password' => config('youtube.password') ? '*********' : '',
        ]);
    }

    /**
     * Save YouTube credentials for simple uploader.
     */
    public function saveCredentials(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $this->updateEnvVariable('YOUTUBE_EMAIL', $validated['email']);
        $this->updateEnvVariable('YOUTUBE_PASSWORD', $validated['password']);
        $this->updateEnvVariable('YOUTUBE_USE_SIMPLE', 'true');

        return redirect()->route('youtube.auth')
            ->with('success', 'YouTube credentials saved successfully!');
    }

    /**
     * Toggle OAuth authentication.
     */
    public function toggleOAuth(Request $request)
    {
        $currentStatus = config('youtube.use_oauth', false);
        $newStatus = !$currentStatus;

        $this->updateEnvVariable('YOUTUBE_USE_OAUTH', $newStatus ? 'true' : 'false');

        return redirect()->route('youtube.auth')
            ->with('success', 'YouTube OAuth ' . ($newStatus ? 'enabled' : 'disabled') . ' successfully!');
    }

    /**
     * Toggle simple uploader.
     */
    public function toggleSimple(Request $request)
    {
        $currentStatus = config('youtube.use_simple', true);
        $newStatus = !$currentStatus;

        $this->updateEnvVariable('YOUTUBE_USE_SIMPLE', $newStatus ? 'true' : 'false');

        return redirect()->route('youtube.auth')
            ->with('success', 'Simple YouTube uploader ' . ($newStatus ? 'enabled' : 'disabled') . ' successfully!');
    }

    /**
     * Update .env variable.
     */
    protected function updateEnvVariable($key, $value)
    {
        $path = base_path('.env');

        if (file_exists($path)) {
            $content = file_get_contents($path);

            // If the key exists, replace it
            if (strpos($content, $key . '=') !== false) {
                $content = preg_replace("/^{$key}=.*$/m", "{$key}=\"{$value}\"", $content);
            } else {
                // Otherwise, append it to the end of the file
                $content .= "\n{$key}=\"{$value}\"";
            }

            file_put_contents($path, $content);
        }
    }
} 