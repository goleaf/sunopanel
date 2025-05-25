@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h1 class="text-3xl font-bold mb-6">YouTube API Configuration Instructions</h1>
            
            <div class="alert alert-info mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div>
                    <h3 class="font-bold">Current Configuration Status</h3>
                    @if($hasOAuth)
                        <span class="text-success">✓ OAuth credentials are configured</span>
                    @else
                        <span class="text-warning">✗ OAuth credentials are not configured</span>
                    @endif
                    <br>
                    @if($hasSimple)
                        <span class="text-success">✓ Simple authentication is configured</span>
                    @else
                        <span class="text-warning">✗ Simple authentication is not configured</span>
                    @endif
                </div>
            </div>
            
            <div class="mb-8">
                <h2 class="text-2xl font-semibold mb-4">Step 1: Create a Google Cloud Project</h2>
                <ol class="list-decimal pl-6 space-y-2">
                    <li>Go to the <a href="https://console.cloud.google.com/" target="_blank" class="link link-primary">Google Cloud Console</a></li>
                    <li>Create a new project or select an existing project</li>
                    <li>Note the Project ID and Project Name for future reference</li>
                </ol>
            </div>
            
            <div class="mb-8">
                <h2 class="text-2xl font-semibold mb-4">Step 2: Enable the YouTube Data API v3</h2>
                <ol class="list-decimal pl-6 space-y-2">
                    <li>In your Google Cloud Project, go to <strong>APIs & Services</strong> > <strong>Library</strong></li>
                    <li>Search for "YouTube Data API v3"</li>
                    <li>Click on the API and then click <strong>Enable</strong></li>
                </ol>
            </div>
            
            <div class="mb-8">
                <h2 class="text-2xl font-semibold mb-4">Step 3: Configure OAuth Consent Screen</h2>
                <ol class="list-decimal pl-6 space-y-2">
                    <li>Go to <strong>APIs & Services</strong> > <strong>OAuth consent screen</strong></li>
                    <li>Select <strong>External</strong> for User Type (if you don't have a Google Workspace account) and click <strong>Create</strong></li>
                    <li>Fill in the required information:
                        <ul class="list-disc pl-6 mt-2">
                            <li>App name: <strong>SunoPanel</strong> (or your application name)</li>
                            <li>User support email: Your email address</li>
                            <li>Developer contact information: Your email address</li>
                        </ul>
                    </li>
                    <li>Click <strong>Save and Continue</strong></li>
                    <li>On the Scopes screen, click <strong>Add or Remove Scopes</strong> and add the following scopes:
                        <ul class="list-disc pl-6 mt-2">
                            <li>https://www.googleapis.com/auth/youtube</li>
                            <li>https://www.googleapis.com/auth/youtube.upload</li>
                        </ul>
                    </li>
                    <li>Click <strong>Save and Continue</strong></li>
                    <li>Add your Google account as a test user, then click <strong>Save and Continue</strong></li>
                    <li>Review your settings and click <strong>Back to Dashboard</strong></li>
                </ol>
            </div>
            
            <div class="mb-8">
                <h2 class="text-2xl font-semibold mb-4">Step 4: Create OAuth Credentials</h2>
                <ol class="list-decimal pl-6 space-y-2">
                    <li>Go to <strong>APIs & Services</strong> > <strong>Credentials</strong></li>
                    <li>Click <strong>Create Credentials</strong> > <strong>OAuth client ID</strong></li>
                    <li>Select <strong>Web application</strong> as the Application type</li>
                    <li>Name: <strong>SunoPanel Web Client</strong> (or your preferred name)</li>
                    <li>Add an authorized redirect URI:
                        <pre class="bg-base-200 p-3 rounded mt-2">{{ $callbackUrl }}</pre>
                    </li>
                    <li>Click <strong>Create</strong></li>
                    <li>A popup will appear with your <strong>Client ID</strong> and <strong>Client Secret</strong>. Copy these values</li>
                </ol>
            </div>
            
            <div class="mb-8">
                <h2 class="text-2xl font-semibold mb-4">Step 5: Add Credentials to Your .env File</h2>
                <p class="mb-4">Add the following lines to your <code>.env</code> file at the root of your application:</p>
                <pre class="bg-base-200 p-4 rounded overflow-x-auto">
# YouTube API Settings
YOUTUBE_USE_OAUTH=true
YOUTUBE_USE_SIMPLE=false
YOUTUBE_CLIENT_ID=your_client_id_here
YOUTUBE_CLIENT_SECRET=your_client_secret_here</pre>
                <p class="mt-4">Replace <code>your_client_id_here</code> and <code>your_client_secret_here</code> with the values you copied in Step 4.</p>
            </div>
            
            <div class="mb-8">
                <h2 class="text-2xl font-semibold mb-4">Step 6: Connect Your YouTube Account</h2>
                <ol class="list-decimal pl-6 space-y-2">
                    <li>After adding the credentials to your .env file, go to the <a href="{{ route('youtube.auth') }}" class="link link-primary">YouTube Authentication</a> page</li>
                    <li>Click on <strong>Connect with Google</strong> to authorize your application</li>
                    <li>Follow the Google OAuth flow to grant permissions to your application</li>
                </ol>
            </div>
            
            <div class="divider my-8"></div>
            
            <div class="mb-8">
                <h2 class="text-2xl font-semibold mb-4">Current Configuration</h2>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Setting</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($configItems as $key => $value)
                                <tr>
                                    <td><code>{{ $key }}</code></td>
                                    <td>{{ $value }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="mt-8 flex justify-center">
                <a href="{{ route('youtube.auth') }}" class="btn btn-primary">Go to YouTube Authentication</a>
            </div>
        </div>
    </div>
</div>
@endsection 