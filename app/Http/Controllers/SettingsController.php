<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

final class SettingsController extends Controller
{
    /**
     * Display the settings page
     */
    public function index(): View
    {
        $settings = [
            'youtube_visibility_filter' => Setting::get('youtube_visibility_filter', 'all'),
            'show_youtube_column' => Setting::get('show_youtube_column', true),
        ];

        return view('settings.index', compact('settings'));
    }

    /**
     * Update settings
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'youtube_visibility_filter' => 'required|in:all,uploaded,not_uploaded',
            'show_youtube_column' => 'required|boolean',
        ]);

        try {
            // Update YouTube visibility filter
            Setting::set(
                'youtube_visibility_filter',
                $request->input('youtube_visibility_filter'),
                'string',
                'Filter tracks by YouTube upload status'
            );

            // Update show YouTube column setting
            Setting::set(
                'show_youtube_column',
                $request->boolean('show_youtube_column'),
                'boolean',
                'Show/hide YouTube column in track listings'
            );

            // Clear all settings cache
            Setting::clearCache();

            return redirect()->route('settings.index')
                ->with('success', 'Settings updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update settings', [
                'error' => $e->getMessage(),
                'settings' => $request->only(['youtube_visibility_filter', 'show_youtube_column']),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update settings. Please try again.');
        }
    }

    /**
     * Reset settings to defaults
     */
    public function reset(): RedirectResponse
    {
        try {
            Setting::set('youtube_visibility_filter', 'all', 'string', 'Filter tracks by YouTube upload status');
            Setting::set('show_youtube_column', true, 'boolean', 'Show/hide YouTube column in track listings');
            
            Setting::clearCache();

            return redirect()->route('settings.index')
                ->with('success', 'Settings reset to defaults successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to reset settings', ['error' => $e->getMessage()]);

            return back()->with('error', 'Failed to reset settings. Please try again.');
        }
    }
}
