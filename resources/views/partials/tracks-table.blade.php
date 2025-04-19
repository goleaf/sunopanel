<img src="{{ $track->image_url }}" alt="{{ $track->title }}" class="h-10 w-10 rounded-full object-cover">

<a href="{{ route('tracks.show', $track) }}" class="hover:text-indigo-600">{{ $track->title }}</a> 