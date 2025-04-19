@props(['genre' => null, 'submitRoute', 'submitMethod' => 'POST'])

<div class="bg-white rounded-lg shadow-md p-6">
    <x-heading :level="2" class="mb-4">
        {{ $genre ? 'Edit Genre' : 'Add New Genre' }}
    </x-heading>

    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ $submitRoute }}" method="POST">
        @csrf
        @if($submitMethod !== 'POST')
            @method($submitMethod)
        @endif

        <div class="mb-4">
            <x-label for="name" value="Genre Name" required />
            <x-input 
                id="name" 
                name="name" 
                type="text" 
                value="{{ old('name', $genre?->name) }}" 
                class="w-full mt-1 @error('name') border-red-500 @enderror" 
                required 
            />
            @error('name')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
            @if(!$genre)
                <p class="text-gray-600 text-sm mt-1">Enter a unique genre name (e.g., Rock, Jazz, Electronic)</p>
            @endif
        </div>

        <div class="mb-4">
            <x-label for="description" value="Description" />
            <x-textarea 
                id="description" 
                name="description" 
                rows="4"
                class="w-full mt-1 @error('description') border-red-500 @enderror"
            >{{ old('description', $genre?->description) }}</x-textarea>
            @error('description')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
            <p class="text-gray-600 text-sm mt-1">Provide a brief description of this genre (optional)</p>
        </div>

        <div class="flex justify-end">
            <x-button href="{{ route('genres.index') }}" color="ghost" class="mr-2">
                Cancel
            </x-button>
            <x-button type="submit" color="primary">
                {{ $genre ? 'Update Genre' : 'Save Genre' }}
            </x-button>
        </div>
    </form>
</div> 