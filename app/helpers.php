<?php

if (!function_exists('formatDuration')) {
    /**
     * Format a duration string or seconds into a human-readable duration.
     *
     * @param string|int|null $duration Duration in format 'mm:ss' or total seconds
     * @return string Formatted duration string
     */
    function formatDuration($duration): string {
        if (empty($duration)) {
            return '0:00';
        }

        // If it's already in mm:ss format, validate and return
        if (is_string($duration) && str_contains($duration, ':')) {
            // Validate format
            if (preg_match('/^\d+:\d{2}$/', $duration)) {
                return $duration;
            }
            
            // Try to parse it
            $duration = parseDuration($duration);
        }

        // Convert seconds to mm:ss format
        $minutes = floor((int)$duration / 60);
        $seconds = (int)$duration % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }
}

if (!function_exists('parseDuration')) {
    /**
     * Parse a duration string into seconds.
     *
     * @param string|null $duration Duration string in various formats (mm:ss, hh:mm:ss)
     * @return int Total seconds
     */
    function parseDuration(?string $duration): int {
        if (empty($duration)) {
            return 0;
        }

        // Already a number?
        if (is_numeric($duration)) {
            return (int)$duration;
        }

        // Handle hh:mm:ss format
        if (preg_match('/^(\d+):(\d{1,2}):(\d{1,2})$/', $duration, $matches)) {
            $hours = (int)$matches[1];
            $minutes = (int)$matches[2];
            $seconds = (int)$matches[3];
            
            return $hours * 3600 + $minutes * 60 + $seconds;
        }
        
        // Handle mm:ss format
        if (preg_match('/^(\d+):(\d{1,2})$/', $duration, $matches)) {
            $minutes = (int)$matches[1];
            $seconds = (int)$matches[2];
            
            return $minutes * 60 + $seconds;
        }
        
        // Fallback for invalid format
        return 0;
    }
} 