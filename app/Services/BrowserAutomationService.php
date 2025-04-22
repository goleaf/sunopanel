<?php

declare(strict_types=1);

namespace App\Services;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverKeys;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BrowserAutomationService
{
    /**
     * @var string URL for the Selenium WebDriver hub
     */
    protected string $seleniumUrl = 'http://localhost:4444/wd/hub';
    
    /**
     * @var int Cache time for fetched data (in seconds)
     */
    protected int $cacheTime = 3600; // 1 hour
    
    /**
     * @var CaptchaSolverService
     */
    protected CaptchaSolverService $captchaSolverService;
    
    /**
     * BrowserAutomationService constructor
     * 
     * @param CaptchaSolverService $captchaSolverService
     */
    public function __construct(CaptchaSolverService $captchaSolverService)
    {
        $this->captchaSolverService = $captchaSolverService;
    }
    
    /**
     * Fetch tracks by music style from Suno.com using browser automation
     *
     * @param string $style Music style to fetch tracks for
     * @return array Array of track data
     */
    public function fetchTracksByStyle(string $style): array
    {
        // Check cache first
        $cacheKey = 'suno_tracks_' . str_replace(' ', '_', strtolower($style));
        
        if (Cache::has($cacheKey)) {
            Log::info('Returning cached tracks data for style', [
                'style' => $style
            ]);
            return Cache::get($cacheKey);
        }
        
        Log::info('Fetching tracks by style using browser automation', [
            'style' => $style
        ]);
        
        try {
            // Set up Chrome options
            $chromeOptions = new ChromeOptions();
            $chromeOptions->addArguments([
                '--headless',
                '--disable-gpu',
                '--window-size=1920,1080',
                '--no-sandbox',
                '--disable-dev-shm-usage',
                '--disable-extensions',
                '--disable-infobars',
                '--start-maximized',
                '--remote-debugging-port=9222'
            ]);
            
            // Create desired capabilities
            $capabilities = DesiredCapabilities::chrome();
            $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);
            
            // Create a WebDriver instance
            $driver = RemoteWebDriver::create($this->seleniumUrl, $capabilities);
            
            try {
                // Prepare the URL with the music style
                $url = 'https://suno.com/style/' . urlencode($style);
                
                // Navigate to the URL
                $driver->get($url);
                
                // Wait for the page to load
                $driver->wait(10, 500)->until(
                    WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('body'))
                );
                
                // Check if we're being blocked by Cloudflare
                $pageTitle = $driver->getTitle();
                
                if (strpos($pageTitle, 'Just a moment') !== false || strpos($pageTitle, 'Checking your browser') !== false) {
                    Log::info('Detected Cloudflare protection, attempting to bypass...', [
                        'title' => $pageTitle
                    ]);
                    
                    // Handle Cloudflare protection
                    if (!$this->handleCloudflareProtection($driver, $url)) {
                        Log::error('Failed to bypass Cloudflare protection');
                        
                        return [
                            'error' => true,
                            'message' => 'Failed to bypass Cloudflare protection',
                            'style_requested' => $style
                        ];
                    }
                    
                    Log::info('Successfully bypassed Cloudflare protection');
                }
                
                // Wait for content to load
                $driver->wait(20, 1000)->until(
                    WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('script#__NEXT_DATA__'))
                );
                
                // Extract the track data from the __NEXT_DATA__ script
                $nextDataScript = $driver->findElement(WebDriverBy::cssSelector('script#__NEXT_DATA__'));
                $jsonData = $nextDataScript->getAttribute('innerHTML');
                $data = json_decode($jsonData, true);
                
                // Process the data to extract tracks
                $tracks = $this->extractTracksFromData($data, $style);
                
                // Cache the result
                Cache::put($cacheKey, $tracks, $this->cacheTime);
                
                return $tracks;
            } finally {
                // Always quit the driver to clean up
                $driver->quit();
            }
        } catch (\Exception $e) {
            Log::error('Error in browser automation', [
                'style' => $style,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return a fallback response
            return [
                'error' => true,
                'message' => 'Browser automation failed: ' . $e->getMessage(),
                'style_requested' => $style
            ];
        }
    }
    
    /**
     * Extract track data from the JSON data
     *
     * @param array $data The JSON data from __NEXT_DATA__
     * @param string $style The requested style
     * @return array The extracted tracks
     */
    protected function extractTracksFromData(array $data, string $style): array
    {
        $tracks = [];
        
        // Extract tracks from the data structure
        // The actual structure may vary, so this is a generic approach
        if (isset($data['props']['pageProps']['songs'])) {
            $tracks = $data['props']['pageProps']['songs'];
        } elseif (isset($data['props']['pageProps']['tracks'])) {
            $tracks = $data['props']['pageProps']['tracks'];
        } elseif (isset($data['props']['pageProps']['initialData']['songs'])) {
            $tracks = $data['props']['pageProps']['initialData']['songs'];
        }
        
        // If no tracks were found, return an empty array
        if (empty($tracks)) {
            Log::warning('No tracks found in data for style', [
                'style' => $style
            ]);
            
            return [];
        }
        
        return $tracks;
    }
    
    /**
     * Handle Cloudflare protection
     *
     * @param RemoteWebDriver $driver WebDriver instance
     * @param string $url The URL being accessed
     * @return bool True if bypassed successfully, false otherwise
     */
    protected function handleCloudflareProtection(RemoteWebDriver $driver, string $url): bool
    {
        try {
            // Wait for the Cloudflare challenge to load
            $driver->wait(10, 500)->until(function () use ($driver) {
                // Check if we've already passed the challenge
                if (strpos($driver->getTitle(), 'Just a moment') === false) {
                    return true;
                }
                
                // Check if there's a CAPTCHA
                $captchaFrames = $driver->findElements(WebDriverBy::cssSelector('iframe[src*="hcaptcha"]'));
                
                return count($captchaFrames) > 0 || $this->isCloudflareCheckpointLoaded($driver);
            });
            
            // Check if we've already passed the challenge
            if (strpos($driver->getTitle(), 'Just a moment') === false) {
                return true;
            }
            
            // Check for hCaptcha
            $captchaFrames = $driver->findElements(WebDriverBy::cssSelector('iframe[src*="hcaptcha"]'));
            
            if (count($captchaFrames) > 0) {
                Log::info('hCaptcha detected, solving...');
                
                // Extract the site key
                $iframe = $captchaFrames[0];
                $src = $iframe->getAttribute('src');
                $matches = [];
                if (preg_match('/sitekey=([^&]+)/', $src, $matches)) {
                    $siteKey = $matches[1];
                    
                    // Solve the CAPTCHA using 2Captcha
                    $solution = $this->captchaSolverService->solveHCaptcha($siteKey, $url);
                    
                    if ($solution) {
                        Log::info('CAPTCHA solved, applying solution');
                        
                        // Execute JavaScript to set the hCaptcha response
                        $driver->executeScript("document.querySelector('textarea[name=\"h-captcha-response\"]').value = arguments[0];", [$solution]);
                        
                        // Submit the form
                        $driver->executeScript("document.querySelector('form').submit();");
                        
                        // Wait for the page to load after submission
                        $driver->wait(30, 1000)->until(
                            WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('script#__NEXT_DATA__'))
                        );
                        
                        return true;
                    } else {
                        Log::error('Failed to solve CAPTCHA');
                        return false;
                    }
                } else {
                    Log::error('Could not extract site key from hCaptcha iframe');
                    return false;
                }
            }
            
            // Check for Cloudflare checkpoint
            if ($this->isCloudflareCheckpointLoaded($driver)) {
                Log::info('Cloudflare checkpoint detected, attempting to navigate...');
                
                // Wait a bit for any animations to complete
                sleep(3);
                
                // Try clicking the "I am human" verification
                try {
                    $checkbox = $driver->findElement(WebDriverBy::cssSelector('input[type="checkbox"]'));
                    $checkbox->click();
                    
                    // Wait for verification to complete
                    sleep(5);
                } catch (\Exception $e) {
                    Log::warning('Could not find checkbox, attempting to continue anyway');
                }
                
                // Wait for the page to load further
                $driver->wait(30, 1000)->until(
                    WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('script#__NEXT_DATA__'))
                );
                
                return true;
            }
            
            // If we got here, we weren't able to bypass the protection
            return false;
        } catch (\Exception $e) {
            Log::error('Error handling Cloudflare protection', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Check if the Cloudflare checkpoint is loaded
     *
     * @param RemoteWebDriver $driver WebDriver instance
     * @return bool True if the checkpoint is loaded, false otherwise
     */
    protected function isCloudflareCheckpointLoaded(RemoteWebDriver $driver): bool
    {
        try {
            // Check for the common elements in Cloudflare's checkpoint page
            $elements = $driver->findElements(WebDriverBy::cssSelector('#challenge-stage, #cf-challenge-body, #challenge-form'));
            
            return count($elements) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
} 