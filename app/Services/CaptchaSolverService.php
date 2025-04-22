<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CaptchaSolverService
{
    /**
     * @var string API key for 2Captcha service
     */
    protected string $apiKey;
    
    /**
     * @var string Base URL for 2Captcha API
     */
    protected string $baseUrl = 'https://2captcha.com/';
    
    /**
     * @var int Timeout in seconds to wait for CAPTCHA solution
     */
    protected int $timeout = 300;
    
    /**
     * CaptchaSolverService constructor
     */
    public function __construct()
    {
        // Get API key from environment variable
        $this->apiKey = env('TWOCAPTCHA_API_KEY', '');
        
        if (empty($this->apiKey)) {
            Log::warning('2Captcha API key is not set');
        }
    }
    
    /**
     * Solve hCaptcha
     *
     * @param string $siteKey The site key
     * @param string $url The URL where the CAPTCHA is located
     * @return string|null The solution token or null if it failed
     */
    public function solveHCaptcha(string $siteKey, string $url): ?string
    {
        try {
            Log::info('Starting to solve hCaptcha', [
                'siteKey' => $siteKey,
                'url' => $url
            ]);
            
            // Step 1: Send the CAPTCHA for solving
            $response = Http::get($this->baseUrl . 'in.php', [
                'key' => $this->apiKey,
                'method' => 'hcaptcha',
                'sitekey' => $siteKey,
                'pageurl' => $url,
                'json' => 1
            ]);
            
            $response->throw();
            $data = $response->json();
            
            if (!isset($data['status']) || $data['status'] !== 1) {
                Log::error('Failed to send CAPTCHA for solving', [
                    'response' => $data
                ]);
                return null;
            }
            
            $captchaId = $data['request'];
            Log::info('CAPTCHA sent for solving', [
                'captchaId' => $captchaId
            ]);
            
            // Step 2: Wait for the CAPTCHA to be solved
            $startTime = time();
            
            while (time() - $startTime < $this->timeout) {
                // Wait between requests to avoid rate limiting
                sleep(5);
                
                // Check if the CAPTCHA has been solved
                $response = Http::get($this->baseUrl . 'res.php', [
                    'key' => $this->apiKey,
                    'action' => 'get',
                    'id' => $captchaId,
                    'json' => 1
                ]);
                
                $response->throw();
                $data = $response->json();
                
                if (!isset($data['status'])) {
                    Log::error('Invalid response when checking CAPTCHA status', [
                        'response' => $data
                    ]);
                    return null;
                }
                
                if ($data['status'] === 1) {
                    // CAPTCHA has been solved
                    Log::info('CAPTCHA solved successfully', [
                        'captchaId' => $captchaId
                    ]);
                    
                    return $data['request'];
                }
                
                if ($data['request'] !== 'CAPCHA_NOT_READY') {
                    // An error occurred
                    Log::error('Error checking CAPTCHA status', [
                        'response' => $data
                    ]);
                    
                    return null;
                }
                
                // CAPTCHA is not ready yet, continue waiting
                Log::info('CAPTCHA not ready yet, waiting...', [
                    'captchaId' => $captchaId
                ]);
            }
            
            Log::error('Timeout waiting for CAPTCHA solution', [
                'captchaId' => $captchaId
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('Error in CaptchaSolverService::solveHCaptcha', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }
    
    /**
     * Get 2Captcha API balance
     *
     * @return float|null The account balance or null if it failed
     */
    public function getBalance(): ?float
    {
        try {
            $response = Http::get($this->baseUrl . 'res.php', [
                'key' => $this->apiKey,
                'action' => 'getbalance',
                'json' => 1
            ]);
            
            $response->throw();
            $data = $response->json();
            
            if (!isset($data['status']) || $data['status'] !== 1) {
                Log::error('Failed to get 2Captcha balance', [
                    'response' => $data
                ]);
                return null;
            }
            
            return (float) $data['request'];
        } catch (\Exception $e) {
            Log::error('Error in CaptchaSolverService::getBalance', [
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }
} 