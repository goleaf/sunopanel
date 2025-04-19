<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ComponentTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test the dashboard widget component.
     *
     * @return void
     */
    public function testDashboardWidget()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->assertPresent('.dashboard-widget')
                    ->screenshot('dashboard-widget')
                    ->mouseover('.dashboard-widget')
                    ->screenshot('dashboard-widget-hover');
        });
    }

    /**
     * Test the advanced search component.
     *
     * @return void
     */
    public function testAdvancedSearchComponent()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/tracks')
                    ->assertPresent('.advanced-search')
                    ->click('@toggle-advanced-search')
                    ->waitForText('Advanced Filters')
                    ->screenshot('advanced-search-open')
                    ->assertVisible('.advanced-filters');
        });
    }

    /**
     * Test the notifications component.
     *
     * @return void
     */
    public function testNotificationsComponent()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->script("window.dispatchEvent(new CustomEvent('notify', {
                        detail: {
                            message: 'Success notification',
                            type: 'success'
                        }
                    }))");
                    
            $browser->pause(500)
                    ->assertVisible('.notification')
                    ->assertSee('Success notification')
                    ->screenshot('success-notification');
                    
            $browser->script("window.dispatchEvent(new CustomEvent('notify', {
                        detail: {
                            message: 'Error notification',
                            type: 'error'
                        }
                    }))");
                    
            $browser->pause(500)
                    ->assertVisible('.notification.notification-error')
                    ->assertSee('Error notification')
                    ->screenshot('error-notification');
                    
            $browser->script("window.dispatchEvent(new CustomEvent('notify', {
                        detail: {
                            message: 'Warning notification',
                            type: 'warning'
                        }
                    }))");
                    
            $browser->pause(500)
                    ->assertVisible('.notification.notification-warning')
                    ->assertSee('Warning notification')
                    ->screenshot('warning-notification');
                    
            $browser->script("window.dispatchEvent(new CustomEvent('notify', {
                        detail: {
                            message: 'Info notification',
                            type: 'info'
                        }
                    }))");
                    
            $browser->pause(500)
                    ->assertVisible('.notification.notification-info')
                    ->assertSee('Info notification')
                    ->screenshot('info-notification');
        });
    }

    /**
     * Test the button component.
     *
     * @return void
     */
    public function testButtonComponent()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/tracks/create')
                    ->assertPresent('.btn')
                    ->screenshot('button-component')
                    ->mouseover('.btn')
                    ->screenshot('button-hover');
                    
            // Test all button variants
            $browser->visit('/tracks/create')
                    ->assertPresent('.btn-primary')
                    ->screenshot('primary-button')
                    ->assertPresent('.btn-secondary')
                    ->screenshot('secondary-button')
                    ->assertPresent('.btn-danger')
                    ->screenshot('danger-button');
        });
    }

    /**
     * Test the table component.
     *
     * @return void
     */
    public function testTableComponent()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/tracks')
                    ->assertPresent('table')
                    ->screenshot('table-component')
                    ->mouseover('tr:first-child')
                    ->screenshot('table-row-hover');
        });
    }

    /**
     * Test the audio player component.
     *
     * @return void
     */
    public function testAudioPlayerComponent()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/tracks')
                    ->whenAvailable('.play-button', function ($button) {
                        $button->click();
                    })
                    ->pause(1000)
                    ->assertPresent('.audio-player')
                    ->screenshot('audio-player-component')
                    ->click('.audio-player .play-pause-btn')
                    ->pause(500)
                    ->screenshot('audio-player-paused');
        });
    }

    /**
     * Test the bulk actions component.
     *
     * @return void
     */
    public function testBulkActionsComponent()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/tracks')
                    ->check('input[type="checkbox"]:first-child')
                    ->pause(500)
                    ->assertVisible('.bulk-actions')
                    ->screenshot('bulk-actions-visible')
                    ->check('input[type="checkbox"]:nth-child(2)')
                    ->pause(500)
                    ->screenshot('bulk-actions-multiple');
        });
    }

    /**
     * Test the confirmation dialog component.
     *
     * @return void
     */
    public function testConfirmationDialogComponent()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/tracks')
                    ->whenAvailable('.delete-button', function ($button) {
                        $button->click();
                    })
                    ->pause(500)
                    ->assertVisible('.confirmation-dialog')
                    ->screenshot('confirmation-dialog')
                    ->press('Cancel')
                    ->pause(500)
                    ->assertMissing('.confirmation-dialog');
        });
    }

    /**
     * Test the form components.
     *
     * @return void
     */
    public function testFormComponents()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/tracks/create')
                    ->assertPresent('form')
                    ->screenshot('form-component')
                    ->type('title', '')  // Clear field to trigger validation
                    ->press('Save')
                    ->pause(500)
                    ->assertVisible('.invalid-feedback')
                    ->screenshot('form-validation');
        });
    }
} 