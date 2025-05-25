<?php

namespace App\Http\Controllers;

use App\Models\YouTubeAccount;
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
    public function redirect(Request $request)
    {
        // If using simple uploader, redirect to login form instead
        if (config('youtube.use_simple_uploader')) {
            return redirect()->route('youtube.auth.login_form');
        }
        
        // Optionally pass an account name to use
        $accountName = $request->input('account_name');
        if ($accountName) {
            session(['pending_youtube_account_name' => $accountName]);
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
        
        // Check if we have a pending account name
        $accountName = session('pending_youtube_account_name');
        
        // Handle the callback and save as a new account
        $account = $this->youtubeService->handleAuthCallbackAndSaveAccount($code, $accountName);
        
        if (!$account) {
            return redirect()->route('youtube.status')->with('error', 'Authorization failed: Could not create account');
        }
        
        // Clear the pending account name
        session()->forget('pending_youtube_account_name');
        
        return redirect()->route('youtube.status')->with('success', 'Successfully added YouTube account: ' . $account->getDisplayName());
    }
    
    /**
     * Set the active YouTube account
     */
    public function setActiveAccount(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:youtube_accounts,id',
        ]);
        
        $account = YouTubeAccount::findOrFail($validated['account_id']);
        $success = $this->youtubeService->setAccount($account);
        
        if (!$success) {
            return back()->with('error', 'Failed to set active account. The token may have expired.');
        }
        
        return back()->with('success', 'Successfully switched to YouTube account: ' . $account->getDisplayName());
    }
    
    /**
     * Delete a YouTube account
     */
    public function deleteAccount(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:youtube_accounts,id',
        ]);
        
        $account = YouTubeAccount::findOrFail($validated['account_id']);
        $displayName = $account->getDisplayName();
        
        // If this is the active account, try to activate another one
        if ($account->is_active) {
            $anotherAccount = YouTubeAccount::where('id', '!=', $account->id)->first();
            if ($anotherAccount) {
                $this->youtubeService->setAccount($anotherAccount);
            }
        }
        
        $account->delete();
        
        return back()->with('success', 'Successfully removed YouTube account: ' . $displayName);
    }
    
    /**
     * Show YouTube authentication status
     */
    public function status()
    {
        // Get all accounts
        $accounts = YouTubeAccount::orderBy('is_active', 'desc')
            ->orderBy('last_used_at', 'desc')
            ->get();
        
        $activeAccount = $accounts->where('is_active', true)->first();
        $isAuthenticated = $activeAccount !== null;
        
        return view('youtube.status', [
            'accounts' => $accounts,
            'activeAccount' => $activeAccount,
            'isAuthenticated' => $isAuthenticated,
            'useOAuth' => true,
            'useSimple' => false,
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