<?php

declare(strict_types=1);

namespace App\Services;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
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
                
                // Wait for content to load (adjust selector as needed)
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
} 