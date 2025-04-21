<?php

namespace Tests\Browser;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DesignAnalysisTest extends DuskTestCase
{
    /**
     * Test to capture a screenshot of the dashboard for AI analysis.
     */
    public function testCaptureDashboardForAnalysis(): void
    {
        $this->browse(function (Browser $browser) {
            // Set screenshot directory
            $screenshotDir = storage_path('app/ai-analysis');
            
            // Create directory if it doesn't exist
            if (!File::isDirectory($screenshotDir)) {
                File::makeDirectory($screenshotDir, 0755, true);
            }
            
            // Path for the screenshot
            $screenshotPath = "$screenshotDir/dashboard-analysis.png";
            
            try {
                // Login and capture screenshot
                $browser->visit('/login')
                    ->type('email', env('DUSK_ADMIN_EMAIL', 'admin@example.com'))
                    ->type('password', env('DUSK_ADMIN_PASSWORD', 'password'))
                    ->press('Login')
                    ->waitForLocation('/dashboard')
                    ->waitUntilMissing('.loading-indicator') // Wait for any loading indicators to disappear
                    ->pause(1000) // Wait a moment for the dashboard to fully render
                    ->resize(1440, 900) // Set a standard resolution for analysis
                    ->screenshot('dashboard-analysis');
                
                // Verify the screenshot was created
                if (File::exists($screenshotPath)) {
                    Log::info("Dashboard screenshot captured successfully at $screenshotPath");
                    $this->assertTrue(true);
                } else {
                    Log::error("Failed to capture dashboard screenshot at $screenshotPath");
                    $this->fail("Screenshot was not created at $screenshotPath");
                }
            } catch (\Exception $e) {
                Log::error("Error capturing dashboard screenshot", ['exception' => $e]);
                $this->fail("Error capturing screenshot: " . $e->getMessage());
            }
        });
    }
} 