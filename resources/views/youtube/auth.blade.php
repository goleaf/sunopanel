@extends('layouts.app')

@section('content')
<div class="p-6 bg-base-100 rounded-box shadow-xl">
    <h1 class="text-2xl font-bold mb-6">YouTube Authentication</h1>
    
    <div class="alert alert-info mb-6">
        <div class="flex-1">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="w-6 h-6 mx-2 stroke-current">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <label>To upload videos to YouTube, you need to authenticate with your Google account first.</label>
        </div>
    </div>
    
    <div class="card mb-6 bg-base-200">
        <div class="card-body">
            <h2 class="card-title mb-4">Authentication Status</h2>
            
            @if(config('youtube.access_token') && config('youtube.refresh_token'))
                <div class="alert alert-success">
                    <div class="flex-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mx-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <label>You are authenticated with YouTube.</label>
                    </div>
                </div>
                
                <p class="mt-4">Token expires: {{ config('youtube.token_expires_at') ? date('Y-m-d H:i:s', config('youtube.token_expires_at')) : 'Unknown' }}</p>
                
                <div class="card-actions mt-4">
                    <a href="{{ route('youtube.upload.form') }}" class="btn btn-primary">Upload Videos</a>
                    <a href="{{ route('youtube.auth.redirect') }}" class="btn">Re-authenticate</a>
                </div>
            @else
                <div class="alert alert-warning">
                    <div class="flex-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mx-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <label>You are not authenticated with YouTube.</label>
                    </div>
                </div>
                
                <div class="card-actions mt-4">
                    <a href="{{ route('youtube.auth.redirect') }}" class="btn btn-primary">Authenticate with YouTube</a>
                </div>
            @endif
        </div>
    </div>
    
    <div class="card mb-6 bg-base-200">
        <div class="card-body">
            <h2 class="card-title mb-4">Authentication Options</h2>
            
            <div class="tabs tabs-boxed mb-4">
                <a class="tab tab-active" id="tab-oauth">OAuth</a>
                <a class="tab" id="tab-simple">Simple Login</a>
            </div>
            
            <div id="oauth-content">
                <p class="mb-4">OAuth authentication allows SunoPanel to upload videos on your behalf without storing your password.</p>
                <p class="mb-4">When you click the authenticate button, you'll be redirected to Google to grant permission.</p>
                
                <ul class="list-disc list-inside mb-4">
                    <li>More secure - we don't store your password</li>
                    <li>Allows access to more YouTube API features</li>
                    <li>Required for playlist management</li>
                </ul>
                
                <div class="form-control">
                    <label class="cursor-pointer label">
                        <span class="label-text">Use OAuth authentication</span>
                        <input type="checkbox" class="toggle toggle-primary" {{ config('youtube.use_oauth') ? 'checked' : '' }} id="toggle-oauth" />
                    </label>
                </div>
            </div>
            
            <div id="simple-content" class="hidden">
                <p class="mb-4">Simple login uses your YouTube email and password for authentication.</p>
                <p class="mb-4 font-bold text-warning">Note: This method has been deprecated by YouTube and may not work reliably.</p>
                
                <form action="{{ route('youtube.auth.save_credentials') }}" method="POST" class="form-control">
                    @csrf
                    <div class="form-control mb-4">
                        <label class="label">
                            <span class="label-text">YouTube Email</span>
                        </label>
                        <input type="email" name="email" class="input input-bordered" value="{{ config('youtube.email') }}" required />
                    </div>
                    
                    <div class="form-control mb-4">
                        <label class="label">
                            <span class="label-text">YouTube Password</span>
                        </label>
                        <input type="password" name="password" class="input input-bordered" required />
                    </div>
                    
                    <div class="form-control">
                        <label class="cursor-pointer label">
                            <span class="label-text">Use simple uploader</span>
                            <input type="checkbox" name="use_simple_uploader" class="toggle toggle-primary" {{ config('youtube.use_simple_uploader') ? 'checked' : '' }} id="toggle-simple" />
                        </label>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Save Credentials</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const oauthTab = document.getElementById('tab-oauth');
        const simpleTab = document.getElementById('tab-simple');
        const oauthContent = document.getElementById('oauth-content');
        const simpleContent = document.getElementById('simple-content');
        const toggleOauth = document.getElementById('toggle-oauth');
        const toggleSimple = document.getElementById('toggle-simple');
        
        oauthTab.addEventListener('click', function() {
            oauthTab.classList.add('tab-active');
            simpleTab.classList.remove('tab-active');
            oauthContent.classList.remove('hidden');
            simpleContent.classList.add('hidden');
        });
        
        simpleTab.addEventListener('click', function() {
            simpleTab.classList.add('tab-active');
            oauthTab.classList.remove('tab-active');
            simpleContent.classList.remove('hidden');
            oauthContent.classList.add('hidden');
        });
        
        toggleOauth.addEventListener('change', function() {
            fetch('/youtube/toggle-oauth', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    use_oauth: this.checked
                })
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                      if (this.checked) {
                          toggleSimple.checked = false;
                      }
                  }
              });
        });
        
        toggleSimple.addEventListener('change', function() {
            fetch('/youtube/toggle-simple', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    use_simple: this.checked
                })
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                      if (this.checked) {
                          toggleOauth.checked = false;
                      }
                  }
              });
        });
    });
</script>
@endpush
@endsection 