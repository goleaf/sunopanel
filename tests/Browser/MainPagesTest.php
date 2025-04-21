<?php

declare(strict_types=1);

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class MainPagesTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_DashboardPage(): void {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->assertSee('Dashboard')
                    ->screenshot('dashboard')
                    ->assertPresent('.dashboard-stats')
                    ->assertPresent('.dashboard-chart');
        });
    }

    public function test_TracksPage(): void {
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

    public function test_GenresPage(): void {
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

    public function test_PlaylistsPage(): void {
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

    public function test_FormSubmission(): void {
        $this->browse(function (Browser $browser) {
            $browser->visit('/tracks/create')
                    ->type('title', 'Test Track')
                    ->type('artist', 'Test Artist')
                    ->press('Save')
                    ->assertPathIsNot('/tracks/create')
                    ->screenshot('track-submit-success');
            $browser->visit('/genres/create')
                    ->press('Save')
                    ->assertSee('The name field is required')
                    ->screenshot('genre-validation-error')
                    ->type('name', 'Test Genre')
                    ->press('Save')
                    ->assertPathIsNot('/genres/create')
                    ->screenshot('genre-submit-success');
            $browser->visit('/playlists/create')
                    ->type('title', 'Test Playlist')
                    ->press('Save')
                    ->assertPathIsNot('/playlists/create')
                    ->screenshot('playlist-submit-success');
        });
    }

    public function test_AudioPlayer(): void {
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

    public function test_AdvancedSearch(): void {
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

    public function test_DarkModeToggle(): void {
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

    public function test_ResponsiveDesign(): void {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                    ->visit('/')
                    ->screenshot('dashboard-mobile')
                    ->assertPresent('.mobile-menu-button')
                    ->click('.mobile-menu-button')
                    ->pause(500)
                    ->assertVisible('.mobile-menu')
                    ->screenshot('mobile-menu-open');
            $browser->resize(768, 1024)
                    ->visit('/')
                    ->screenshot('dashboard-tablet');
            $browser->resize(1920, 1080)
                    ->visit('/')
                    ->screenshot('dashboard-desktop');
        });
    }

    public function test_Notification(): void {
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