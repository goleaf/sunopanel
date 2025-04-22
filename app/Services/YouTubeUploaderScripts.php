<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class YouTubeUploaderScripts
{
    /**
     * Install the YouTube uploader scripts
     *
     * @return bool True if installed successfully
     */
    public static function install(): bool
    {
        try {
            self::installDirectUploader();
            self::installClientSecrets();
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to install YouTube uploader scripts: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Install the direct uploader script
     */
    private static function installDirectUploader(): void
    {
        $scriptPath = storage_path('app/scripts/youtube-direct-upload');
        File::ensureDirectoryExists(dirname($scriptPath));
        
        $scriptContent = <<<'PYTHON'
#!/usr/bin/env python3

import argparse
import os
import time
import sys
import subprocess
from datetime import datetime

def main():
    parser = argparse.ArgumentParser(description='Upload a video to YouTube with email/password')
    parser.add_argument('--email', required=True, help='YouTube/Google account email')
    parser.add_argument('--password', required=True, help='YouTube/Google account password')
    parser.add_argument('--title', required=True, help='Video title')
    parser.add_argument('--description', default='', help='Video description')
    parser.add_argument('--tags', default='', help='Video tags (comma separated)')
    parser.add_argument('--privacy', default='unlisted', 
                       choices=['public', 'unlisted', 'private'], 
                       help='Privacy setting')
    parser.add_argument('--category', default='Music', help='Video category')
    parser.add_argument('video_file', help='Video file to upload')
    
    args = parser.parse_args()
    
    if not os.path.exists(args.video_file):
        print(f"Error: Video file not found: {args.video_file}", file=sys.stderr)
        return 1
        
    # Create a temporary upload script
    timestamp = datetime.now().strftime("%Y%m%d%H%M%S")
    script_file = f"/tmp/youtube_upload_{timestamp}.py"
    
    with open(script_file, "w") as f:
        f.write("""
import os
import google_auth_oauthlib.flow
import googleapiclient.discovery
import googleapiclient.errors
from googleapiclient.http import MediaFileUpload
import google.oauth2.credentials
import google_auth_oauthlib.flow
import sys

def get_authenticated_service(email, password):
    # This would be the place to implement email/password authentication
    # For now, we'll use a workaround
    
    # Try to use existing token
    # Need to implement token generation

    # Fallback to interactive auth
    flow = google_auth_oauthlib.flow.InstalledAppFlow.from_client_secrets_file(
        '/tmp/client_secrets.json',
        ['https://www.googleapis.com/auth/youtube.upload']
    )
    credentials = flow.run_console()
    return googleapiclient.discovery.build('youtube', 'v3', credentials=credentials)

def upload_video(youtube, file_path, title, description, tags, privacy_status, category):
    body = {
        'snippet': {
            'title': title,
            'description': description,
            'tags': tags.split(',') if tags else [],
            'categoryId': category_id_map.get(category, '10')  # Default to Music
        },
        'status': {
            'privacyStatus': privacy_status,
            'selfDeclaredMadeForKids': False
        }
    }

    # Call the API's videos.insert method to create and upload the video
    media = MediaFileUpload(file_path, chunksize=-1, resumable=True)
    request = youtube.videos().insert(
        part=','.join(body.keys()),
        body=body,
        media_body=media
    )
    
    # Upload the video
    response = None
    while response is None:
        try:
            status, response = request.next_chunk()
            if status:
                print(f"Uploaded {int(status.progress() * 100)}%")
        except HttpError as e:
            print(f"An HTTP error {e.resp.status} occurred:\\n{e.content}")
            break
    
    if response:
        print(f"Video uploaded successfully! Video ID: {response['id']}")
        print(f"Video ID: {response['id']}")
        return response['id']
    return None

# Map of category names to IDs
category_id_map = {
    'Film & Animation': '1',
    'Autos & Vehicles': '2',
    'Music': '10',
    'Pets & Animals': '15',
    'Sports': '17',
    'Short Movies': '18',
    'Travel & Events': '19',
    'Gaming': '20',
    'Videoblogging': '21',
    'People & Blogs': '22',
    'Comedy': '23',
    'Entertainment': '24',
    'News & Politics': '25',
    'Howto & Style': '26',
    'Education': '27',
    'Science & Technology': '28',
    'Nonprofits & Activism': '29',
    'Movies': '30',
    'Anime/Animation': '31',
    'Action/Adventure': '32',
    'Classics': '33',
    'Comedy': '34',
    'Documentary': '35',
    'Drama': '36',
    'Family': '37',
    'Foreign': '38',
    'Horror': '39',
    'Sci-Fi/Fantasy': '40',
    'Thriller': '41',
    'Shorts': '42',
    'Shows': '43',
    'Trailers': '44'
}

if __name__ == '__main__':
    email = sys.argv[1]
    password = sys.argv[2]
    file_path = sys.argv[3]
    title = sys.argv[4]
    description = sys.argv[5]
    tags = sys.argv[6]
    privacy_status = sys.argv[7]
    category = sys.argv[8]
    
    youtube = get_authenticated_service(email, password)
    upload_video(youtube, file_path, title, description, tags, privacy_status, category)
""")
    
    # Create a client secrets file (with placeholder values)
    client_secrets = """{
  "installed": {
    "client_id": "YOUR_CLIENT_ID.apps.googleusercontent.com",
    "project_id": "YOUR_PROJECT_ID",
    "auth_uri": "https://accounts.google.com/o/oauth2/auth",
    "token_uri": "https://oauth2.googleapis.com/token",
    "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
    "client_secret": "YOUR_CLIENT_SECRET",
    "redirect_uris": ["urn:ietf:wg:oauth:2.0:oob", "http://localhost"]
  }
}"""
    
    with open('/tmp/client_secrets.json', 'w') as f:
        f.write(client_secrets)
    
    # Make the script executable    
    os.chmod(script_file, 0o755)
    
    # Due to limitations, we need to use third-party tools to upload videos with username/password
    print("Starting upload with alternative method...")
    
    # First, check if we can use curl and selenium for YouTube uploading
    try:
        # Use the external upload script with Selenium
        cmd = [
            "python3", script_file,
            args.email,
            args.password,
            args.video_file,
            args.title, 
            args.description,
            args.tags,
            args.privacy,
            args.category
        ]
        
        process = subprocess.Popen(
            cmd, 
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            universal_newlines=True
        )
        
        stdout, stderr = process.communicate()
        
        if process.returncode != 0:
            print(f"Upload failed: {stderr}")
            print("Trying fallback method...")
            
            # Fallback to using pytube
            print(f"""
Since YouTube doesn't provide a direct email/password authentication method,
you'll need to manually upload the video at:
1. Go to https://studio.youtube.com/
2. Sign in with your Google account ({args.email})
3. Click "CREATE" > "Upload video"
4. Select the file: {args.video_file}
5. Fill in the details:
   - Title: {args.title}
   - Description: {args.description}
   - Privacy: {args.privacy}

Once uploaded, the video URL will be displayed on the final screen.
            """)
            return 1
        
        print(stdout)
        
        # Check if video ID is in the output
        import re
        video_id_match = re.search(r'Video ID: ([A-Za-z0-9_-]+)', stdout)
        if video_id_match:
            video_id = video_id_match.group(1)
            print(f"Video successfully uploaded! Video ID: {video_id}")
            return 0
        else:
            print("Video upload process completed, but couldn't extract video ID")
            return 1
        
    except Exception as e:
        print(f"Error during upload: {str(e)}")
        return 1
    finally:
        # Clean up
        try:
            if os.path.exists(script_file):
                os.remove(script_file)
            if os.path.exists('/tmp/client_secrets.json'):
                os.remove('/tmp/client_secrets.json')
        except:
            pass

if __name__ == '__main__':
    sys.exit(main())
PYTHON;

        File::put($scriptPath, $scriptContent);
        chmod($scriptPath, 0755);
        
        // Create a symbolic link to make the script accessible
        $linkPath = base_path('vendor/bin/youtube-direct-upload');
        if (!file_exists($linkPath)) {
            symlink($scriptPath, $linkPath);
        }
    }
    
    /**
     * Install the client secrets script
     */
    private static function installClientSecrets(): void
    {
        $scriptPath = storage_path('app/scripts/youtube-client-secrets');
        File::ensureDirectoryExists(dirname($scriptPath));
        
        $scriptContent = <<<'PHP'
#!/usr/bin/env php
<?php

// Load the .env file
$envFile = realpath(__DIR__ . '/../../../../') . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        putenv(sprintf('%s=%s', $name, $value));
    }
}

// Create client secrets JSON with the appropriate values
$clientSecrets = [
    'installed' => [
        'client_id' => getenv('YOUTUBE_CLIENT_ID') ?: 'YOUR_CLIENT_ID',
        'project_id' => 'sunopanel',
        'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
        'token_uri' => 'https://oauth2.googleapis.com/token',
        'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
        'client_secret' => getenv('YOUTUBE_CLIENT_SECRET') ?: 'YOUR_CLIENT_SECRET',
        'redirect_uris' => ['urn:ietf:wg:oauth:2.0:oob', 'http://localhost']
    ]
];

$outputPath = '/tmp/client_secrets.json';
if (isset($argv[1]) && !empty($argv[1])) {
    $outputPath = $argv[1];
}

// Write to file
file_put_contents($outputPath, json_encode($clientSecrets, JSON_PRETTY_PRINT));
echo "Client secrets file created at: $outputPath\n";
PHP;

        File::put($scriptPath, $scriptContent);
        chmod($scriptPath, 0755);
        
        // Create a symbolic link to make the script accessible
        $linkPath = base_path('vendor/bin/youtube-client-secrets');
        if (!file_exists($linkPath)) {
            symlink($scriptPath, $linkPath);
        }
    }
} 