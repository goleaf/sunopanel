@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Users</h1>
        <x-button href="{{ route('users.create') }}" color="indigo">
            <x-icon name="plus" class="-ml-1 mr-2 h-5 w-5" />
            Create User
        </x-button>
    </div>

    @if(session('success'))
        <x-alert type="success" :message="session('success')" class="mb-4" />
    @endif

    <div class="bg-white shadow-sm rounded-lg overflow-hidden mb-6">
        <x-data-table
            :headers="$headers"
            :sortable="true"
            :sortColumn="$sort"
            :sortDirection="$order"
            :searchable="true"
            :searchTerm="$search"
        >
            @foreach ($users as $user)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $user->id }}</td>
                    <td class="px-4 py-3">{{ $user->name }}</td>
                    <td class="px-4 py-3">{{ $user->email }}</td>
                    <td class="px-4 py-3">
                        <x-action-buttons
                            :view="route('users.show', $user->id)"
                            :edit="route('users.edit', $user->id)"
                            :delete="route('users.destroy', $user->id)"
                            confirmMessage="Are you sure you want to delete this user?"
                        />
                    </td>
                </tr>
            @endforeach
            
            <x-slot name="pagination">
                {{ $users->links() }}
            </x-slot>
        </x-data-table>
    </div>
</div>
@endsection 