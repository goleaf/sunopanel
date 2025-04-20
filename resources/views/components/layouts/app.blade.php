{{-- 
    DEPRECATED: This layout is deprecated in favor of resources/views/layouts/app.blade.php
    If you're seeing double navigation, you should use @extends('layouts.app') or <x-app-layout> instead
--}}

@props(['header' => null])

@extends('layouts.app')

@section('content')
    @if ($header)
        <div class="mb-6">
            {{ $header }}
        </div>
    @endif
    
    {{ $slot }}
@endsection 