@extends('layouts.app')

@section('content')
<div class="p-6 bg-base-100 rounded-box shadow-xl">
    <h1 class="text-2xl font-bold mb-6">YouTube Upload Diagnostics</h1>
    
    <div class="alert alert-info mb-6">
        <div class="flex-1">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="w-6 h-6 mx-2 stroke-current">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <label>This tool helps diagnose issues with YouTube uploads.</label>
        </div>
    </div>
    
    <div class="card mb-6 bg-base-200">
        <div class="card-body">
            <h2 class="card-title">Test Upload</h2>
            <p class="mb-4">Upload a test video to YouTube to verify your configuration.</p>
            <div id="test-upload-status" class="mb-4"></div>
            <button id="test-upload-btn" class="btn btn-primary">Test Upload</button>
        </div>
    </div>
    
    <div class="card mb-6 bg-base-200">
        <div class="card-body">
            <h2 class="card-title">Diagnostics Report</h2>
            
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-2">OAuth Configuration</h3>
                <div class="overflow-x-auto">
                    <table class="table w-full table-compact">
                        <thead>
                            <tr>
                                <th>Setting</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Enabled</td>
                                <td>{!! $diagnostics['oauth']['enabled'] ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-warning">No</span>' !!}</td>
                            </tr>
                            <tr>
                                <td>Client ID</td>
                                <td>{!! $diagnostics['oauth']['client_id'] ? '<span class="badge badge-success">Set</span>' : '<span class="badge badge-error">Not Set</span>' !!}</td>
                            </tr>
                            <tr>
                                <td>Client Secret</td>
                                <td>{!! $diagnostics['oauth']['client_secret'] ? '<span class="badge badge-success">Set</span>' : '<span class="badge badge-error">Not Set</span>' !!}</td>
                            </tr>
                            <tr>
                                <td>Redirect URI</td>
                                <td>{!! $diagnostics['oauth']['redirect_uri'] ? '<span class="badge badge-success">Set</span>' : '<span class="badge badge-error">Not Set</span>' !!}</td>
                            </tr>
                            <tr>
                                <td>Access Token</td>
                                <td>{!! $diagnostics['oauth']['access_token'] ? '<span class="badge badge-success">Set</span>' : '<span class="badge badge-error">Not Set</span>' !!}</td>
                            </tr>
                            <tr>
                                <td>Refresh Token</td>
                                <td>{!! $diagnostics['oauth']['refresh_token'] ? '<span class="badge badge-success">Set</span>' : '<span class="badge badge-error">Not Set</span>' !!}</td>
                            </tr>
                            @if(isset($diagnostics['oauth']['expires_at']))
                            <tr>
                                <td>Token Expires</td>
                                <td>
                                    {{ $diagnostics['oauth']['expires_at'] }}
                                    {!! $diagnostics['oauth']['expired'] ? '<span class="badge badge-error">Expired</span>' : '<span class="badge badge-success">Valid</span>' !!}
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-2">Simple Uploader Configuration</h3>
                <div class="overflow-x-auto">
                    <table class="table w-full table-compact">
                        <thead>
                            <tr>
                                <th>Setting</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Enabled</td>
                                <td>{!! $diagnostics['simple']['enabled'] ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-warning">No</span>' !!}</td>
                            </tr>
                            <tr>
                                <td>Email</td>
                                <td>{!! $diagnostics['simple']['email'] ? '<span class="badge badge-success">Set</span>' : '<span class="badge badge-error">Not Set</span>' !!}</td>
                            </tr>
                            <tr>
                                <td>Password</td>
                                <td>{!! $diagnostics['simple']['password'] ? '<span class="badge badge-success">Set</span>' : '<span class="badge badge-error">Not Set</span>' !!}</td>
                            </tr>
                            <tr>
                                <td>Upload Script</td>
                                <td>{!! $diagnostics['simple']['script_exists'] ? '<span class="badge badge-success">Found</span>' : '<span class="badge badge-error">Missing</span>' !!}</td>
                            </tr>
                            <tr>
                                <td>Client Secrets Script</td>
                                <td>{!! $diagnostics['simple']['client_secrets_exists'] ? '<span class="badge badge-success">Found</span>' : '<span class="badge badge-error">Missing</span>' !!}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-2">Package Dependencies</h3>
                <div class="overflow-x-auto">
                    <table class="table w-full table-compact">
                        <thead>
                            <tr>
                                <th>Package</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Google Client</td>
                                <td>{!! $diagnostics['packages']['google_client'] ? '<span class="badge badge-success">Installed</span>' : '<span class="badge badge-error">Missing</span>' !!}</td>
                            </tr>
                            <tr>
                                <td>Google YouTube</td>
                                <td>{!! $diagnostics['packages']['google_service_youtube'] ? '<span class="badge badge-success">Installed</span>' : '<span class="badge badge-error">Missing</span>' !!}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-2">Authentication Status</h3>
                <div class="overflow-x-auto">
                    <table class="table w-full table-compact">
                        <thead>
                            <tr>
                                <th>Setting</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Authenticated</td>
                                <td>{!! isset($diagnostics['authenticated']) && $diagnostics['authenticated'] ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-error">No</span>' !!}</td>
                            </tr>
                            @if(isset($diagnostics['auth_error']))
                            <tr>
                                <td>Error</td>
                                <td><span class="text-error">{{ $diagnostics['auth_error'] }}</span></td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-2">Logs</h3>
                <div class="overflow-x-auto">
                    <table class="table w-full table-compact">
                        <thead>
                            <tr>
                                <th>Setting</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Log Path</td>
                                <td>{{ $diagnostics['logs']['path'] }}</td>
                            </tr>
                            <tr>
                                <td>Log Files</td>
                                <td>{!! $diagnostics['logs']['files_exist'] ? '<span class="badge badge-success">Found</span>' : '<span class="badge badge-error">Missing</span>' !!}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <h3 class="text-lg font-semibold mb-2">Troubleshooting Steps</h3>
            <div class="mb-4">
                <ul class="list-disc list-inside">
                    <li class="mb-2">For OAuth issues, verify that your Google API project has YouTube Data API v3 enabled and has the correct credentials</li>
                    <li class="mb-2">For simple uploader issues, check that the scripts exist and that your YouTube account credentials are correct</li>
                    <li class="mb-2">Ensure that your .env file contains the required YouTube configuration values</li>
                    <li class="mb-2">Check the logs for detailed error messages: <code>{{ storage_path('logs/laravel.log') }}</code></li>
                    <li class="mb-2">Make sure your YouTube account doesn't have any restrictions or limitations</li>
                </ul>
            </div>
            
            <h3 class="text-lg font-semibold mb-2">Manual YouTube Upload Link</h3>
            <p class="mb-4">If automatic uploads fail, you can still upload videos manually:</p>
            <a href="https://studio.youtube.com/channel/upload" target="_blank" class="btn btn-outline">Go to YouTube Studio</a>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const testUploadBtn = document.getElementById('test-upload-btn');
        const testUploadStatus = document.getElementById('test-upload-status');
        
        testUploadBtn.addEventListener('click', function() {
            testUploadBtn.classList.add('loading');
            testUploadStatus.innerHTML = '<div class="alert alert-info">Uploading test video...</div>';
            
            fetch('/youtube/diagnostics/test-upload', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                testUploadBtn.classList.remove('loading');
                
                if (data.success) {
                    testUploadStatus.innerHTML = `
                        <div class="alert alert-success">
                            ${data.message}<br/>
                            Video ID: ${data.video_id}<br/>
                            <a href="${data.video_url}" target="_blank" class="font-semibold">View on YouTube</a>
                        </div>
                    `;
                } else {
                    testUploadStatus.innerHTML = `
                        <div class="alert alert-error">
                            ${data.message}
                        </div>
                    `;
                }
            })
            .catch(error => {
                testUploadBtn.classList.remove('loading');
                testUploadStatus.innerHTML = `
                    <div class="alert alert-error">
                        Error: ${error.message}
                    </div>
                `;
            });
        });
    });
</script>
@endpush
@endsection 