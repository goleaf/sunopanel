<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

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
            'show_youtube_column' => 'boolean',
        ]);

        // Update YouTube visibility filter
        Setting::set(
            'youtube_visibility_filter',
            $request->input('youtube_visibility_filter'),
            'string',
            'Global filter for YouTube upload visibility: all, uploaded, not_uploaded'
        );

        // Update show YouTube column setting
        Setting::set(
            'show_youtube_column',
            $request->boolean('show_youtube_column'),
            'boolean',
            'Whether to show YouTube status column in track listings'
        );

        // Clear all settings cache
        Setting::clearCache();

        return redirect()->route('settings.index')
            ->with('success', 'Settings updated successfully!');
    }

    /**
     * Reset settings to defaults
     */
    public function reset(): RedirectResponse
    {
        Setting::set('youtube_visibility_filter', 'all', 'string');
        Setting::set('show_youtube_column', true, 'boolean');
        
        Setting::clearCache();

        return redirect()->route('settings.index')
            ->with('success', 'Settings reset to defaults!');
    }
}
