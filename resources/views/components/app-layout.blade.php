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