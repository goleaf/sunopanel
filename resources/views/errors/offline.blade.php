<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#4f46e5">
    <title>{{ config('app.name', 'SunoPanel') }} - Offline</title>
    <style>
        /* Simple CSS for offline page */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f9fafb;
        }
        .container {
            max-width: 480px;
            padding: 2rem;
            text-align: center;
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #4f46e5;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        p {
            margin-bottom: 1.5rem;
            color: #6b7280;
        }
        .icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 1.5rem;
            display: block;
        }
        .btn {
            display: inline-block;
            background-color: #4f46e5;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            text-decoration: none;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #4338ca;
        }
    </style>
</head>
<body>
    <div class="container">
        <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#4f46e5">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
        </svg>
        <h1>You're Offline</h1>
        <p>It looks like you lost your internet connection. Please check your network settings and try again when you're back online.</p>
        <button class="btn" onclick="window.location.reload()">Try Again</button>
    </div>
</body>
</html> 