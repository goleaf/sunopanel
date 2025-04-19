<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class MainPagesTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test the dashboard page.
     *
     * @return void
     */
    public function testDashboardPage()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->assertSee('Dashboard')
                    ->screenshot('dashboard')
                    ->assertPresent('.dashboard-stats')
                    ->assertPresent('.dashboard-chart');
        });
    }

    /**
     * Test the tracks index page.
     *
     * @return void
     */
    public function testTracksPage()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/tracks')
                    ->assertSee('Tracks')
                    ->screenshot('tracks-index')
                    ->assertPresent('.search-form')
                    ->assertPresent('.tracks-table')
                    ->click('@create-track-button')
                    ->assertPathIs('/tracks/create')
                    ->screenshot('track-create');
        });
    }

    /**
     * Test the genres index page.
     *
     * @return void
     */
    public function testGenresPage()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/genres')
                    ->assertSee('Genres')
                    ->screenshot('genres-index')
                    ->assertPresent('.genres-list')
                    ->click('@create-genre-button')
                    ->assertPathIs('/genres/create')
                    ->screenshot('genre-create');
        });
    }

    /**
     * Test the playlists index page.
     *
     * @return void
     */
    public function testPlaylistsPage()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/playlists')
                    ->assertSee('Playlists')
                    ->screenshot('playlists-index')
                    ->assertPresent('.playlists-grid')
                    ->click('@create-playlist-button')
                    ->assertPathIs('/playlists/create')
                    ->screenshot('playlist-create');
        });
    }

    /**
     * Test form submission and validation feedback.
     *
     * @return void
     */
    public function testFormSubmission()
    {
        $this->browse(function (Browser $browser) {
            // Test track form
            $browser->visit('/tracks/create')
                    ->type('title', 'Test Track')
                    ->type('artist', 'Test Artist')
                    ->press('Save')
                    ->assertPathIsNot('/tracks/create')
                    ->screenshot('track-submit-success');

            // Test genre form with validation error
            $browser->visit('/genres/create')
                    ->press('Save')
                    ->assertSee('The name field is required')
                    ->screenshot('genre-validation-error')
                    ->type('name', 'Test Genre')
                    ->press('Save')
                    ->assertPathIsNot('/genres/create')
                    ->screenshot('genre-submit-success');

            // Test playlist form
            $browser->visit('/playlists/create')
                    ->type('title', 'Test Playlist')
                    ->press('Save')
                    ->assertPathIsNot('/playlists/create')
                    ->screenshot('playlist-submit-success');
        });
    }

    /**
     * Test the track player functionality.
     *
     * @return void
     */
    public function testAudioPlayer()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/tracks')
                    ->whenAvailable('.play-button', function ($button) {
                        $button->click();
                    })
                    ->pause(1000)
                    ->assertPresent('.audio-player')
                    ->screenshot('audio-player');
        });
    }

    /**
     * Test the advanced search component.
     *
     * @return void
     */
    public function testAdvancedSearch()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/tracks')
                    ->click('@toggle-advanced-search')
                    ->assertVisible('.advanced-filters')
                    ->screenshot('advanced-search-open')
                    ->type('search', 'test')
                    ->select('filter[genre]', '1')
                    ->press('Apply Filters')
                    ->assertQueryStringHas('search', 'test')
                    ->screenshot('search-results');
        });
    }

    /**
     * Test dark mode toggle.
     *
     * @return void
     */
    public function testDarkModeToggle()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->click('@theme-toggle')
                    ->pause(500)
                    ->assertPresent('html.dark')
                    ->screenshot('dark-mode')
                    ->click('@theme-toggle')
                    ->pause(500)
                    ->assertMissing('html.dark')
                    ->screenshot('light-mode');
        });
    }

    /**
     * Test responsive design on different screen sizes.
     *
     * @return void
     */
    public function testResponsiveDesign()
    {
        $this->browse(function (Browser $browser) {
            // Mobile view
            $browser->resize(375, 667)
                    ->visit('/')
                    ->screenshot('dashboard-mobile')
                    ->assertPresent('.mobile-menu-button')
                    ->click('.mobile-menu-button')
                    ->pause(500)
                    ->assertVisible('.mobile-menu')
                    ->screenshot('mobile-menu-open');

            // Tablet view
            $browser->resize(768, 1024)
                    ->visit('/')
                    ->screenshot('dashboard-tablet');

            // Desktop view
            $browser->resize(1920, 1080)
                    ->visit('/')
                    ->screenshot('dashboard-desktop');
        });
    }

    /**
     * Test notification component.
     *
     * @return void
     */
    public function testNotification()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->script("window.dispatchEvent(new CustomEvent('notify', {
                        detail: {
                            message: 'Test notification message',
                            type: 'success'
                        }
                    }))");
            
            $browser->pause(500)
                    ->assertVisible('.notification')
                    ->screenshot('notification-visible')
                    ->click('.notification .close-button')
                    ->pause(500)
                    ->assertMissing('.notification')
                    ->screenshot('notification-dismissed');
        });
    }
} 