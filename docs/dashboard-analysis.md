# Dashboard Analysis Feature

This feature uses the OpenAI Vision API to analyze dashboard screenshots and provide design improvement recommendations.

## Prerequisites

1. An OpenAI API key with access to GPT-4 Vision
2. Laravel Dusk properly configured for browser testing
3. PHP 8.2+ and Laravel 12+

## Setup

1. Add your OpenAI API key to your `.env` file:

```
OPENAI_API_KEY=your_api_key_here
```

2. Add Dusk test credentials to your `.env` file:

```
DUSK_ADMIN_EMAIL=admin@example.com
DUSK_ADMIN_PASSWORD=password
```

## How to Use

### 1. Capture Dashboard Screenshot

Run the Dusk test to capture a screenshot of your dashboard:

```bash
php artisan dusk tests/Browser/DesignAnalysisTest.php
```

This will:
- Log in to the application using the credentials in your `.env` file
- Navigate to the dashboard
- Capture a screenshot at 1440x900 resolution
- Save the screenshot to `storage/app/ai-analysis/dashboard-analysis.png`

### 2. Analyze the Dashboard

Run the dashboard analysis command:

```bash
php artisan dashboard:analyze
```

This will:
- Send the screenshot to OpenAI's Vision API
- Generate design improvement recommendations
- Save the recommendations to `storage/app/ai-analysis/design-recommendations.md`

### Optional Parameters

You can customize the command with these options:

```bash
php artisan dashboard:analyze --api-key=your_custom_key --screenshot=path/to/custom/screenshot.png
```

## Viewing Results

The analysis results are saved as a Markdown file at `storage/app/ai-analysis/design-recommendations.md`.

You can view the file with:

```bash
cat storage/app/ai-analysis/design-recommendations.md
```

## Example Results

The analysis provides recommendations in these areas:

1. Color scheme and visual hierarchy
2. Layout and spacing
3. Typography and readability
4. Data visualization effectiveness
5. Modern UI/UX best practices
6. Accessibility considerations

Each recommendation explains why it would improve the user experience.

## Troubleshooting

- **Error: Screenshot not found**: Make sure the Dusk test ran successfully
- **API Error**: Verify your OpenAI API key is valid and has access to the Vision API
- **Browser testing failures**: Check Dusk configuration and browser driver compatibility

## Implementation Details

- `tests/Browser/DesignAnalysisTest.php`: Dusk test for capturing screenshots
- `app/Console/Commands/AnalyzeDashboardDesign.php`: Artisan command for analyzing the dashboard
- `storage/app/ai-analysis/`: Directory where screenshots and analysis results are stored 