@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6 text-base-content">Data Table Example</h1>
    
    @php
    $users = [
        [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'Admin',
            'status' => 'Active',
            'actions' => view('components.row-action', [
                'href' => '/users/1',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>',
                'label' => 'View',
                'color' => 'primary'
            ]) . 
            view('components.row-action', [
                'href' => '/users/1/edit',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>',
                'label' => 'Edit',
                'color' => 'secondary'
            ]) .
            view('components.row-action', [
                'href' => '/users/1/delete',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>',
                'label' => 'Delete',
                'color' => 'error'
            ])
        ],
        [
            'id' => 2,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'role' => 'User',
            'status' => 'Inactive',
            'actions' => view('components.row-action', [
                'href' => '/users/2',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>',
                'label' => 'View',
                'color' => 'primary'
            ]) . 
            view('components.row-action', [
                'href' => '/users/2/edit',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>',
                'label' => 'Edit',
                'color' => 'secondary'
            ]) .
            view('components.row-action', [
                'href' => '/users/2/delete',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>',
                'label' => 'Delete',
                'color' => 'error'
            ])
        ],
    ];

    $headers = [
        'id' => 'ID',
        'name' => 'Name',
        'email' => 'Email',
        'role' => 'Role',
        'status' => 'Status',
    ];
    @endphp

    <x-data-table 
        :headers="$headers"
        :data="$users"
        :actions="true"
        hasSearch="true"
        searchPlaceholder="Search users..."
        searchRoute="{{ route('users.index') }}"
        sortRoute="{{ route('users.index') }}"
        currentSort="{{ request('sort', 'id') }}"
        currentOrder="{{ request('order', 'asc') }}"
        emptyMessage="No users found."
        :showPagination="true"
    >
        <tr>
            <td colspan="6" class="px-6 py-4 text-center text-base-content opacity-70">
                No users available
            </td>
        </tr>
    </x-data-table>
</div>
@endsection 