<div class="bg-base-100 rounded-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold mb-6 text-primary">Todo List</h2>
    
    <!-- Add new task form -->
    <form wire:submit="addTask" class="flex gap-2 mb-6">
        <input 
            type="text" 
            wire:model="newTask" 
            placeholder="Add a new task..." 
            class="input input-bordered flex-1"
            required
        >
        <button type="submit" class="btn btn-primary">
            Add
        </button>
    </form>
    
    <!-- Task filters -->
    <div class="flex justify-between items-center mb-4">
        <div class="tabs tabs-boxed">
            <a wire:click="$set('showCompleted', null)" class="tab {{ $showCompleted === null ? 'tab-active' : '' }}">All</a>
            <a wire:click="$set('showCompleted', false)" class="tab {{ $showCompleted === false ? 'tab-active' : '' }}">Active</a>
            <a wire:click="$set('showCompleted', true)" class="tab {{ $showCompleted === true ? 'tab-active' : '' }}">Completed</a>
        </div>
        
        <button wire:click="clearCompleted" class="btn btn-sm btn-ghost">
            Clear completed
        </button>
    </div>
    
    <!-- Task list -->
    @if(count($filteredTasks) > 0)
        <ul class="space-y-3">
            @foreach($filteredTasks as $index => $task)
                <li class="flex items-center justify-between p-3 bg-base-200 rounded-lg transition-all {{ $task['completed'] ? 'opacity-70' : '' }}">
                    <div class="flex items-center gap-3">
                        <input 
                            type="checkbox" 
                            wire:click="toggleComplete({{ $index }})"
                            class="checkbox checkbox-primary" 
                            {{ $task['completed'] ? 'checked' : '' }}
                        >
                        <span class="{{ $task['completed'] ? 'line-through text-base-content/70' : '' }}">
                            {{ $task['text'] }}
                        </span>
                    </div>
                    <button 
                        wire:click="deleteTask({{ $index }})" 
                        class="btn btn-ghost btn-sm text-error"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </li>
            @endforeach
        </ul>
    @else
        <div class="text-center py-8 text-base-content/70">
            <p>No tasks to display</p>
        </div>
    @endif
    
    <!-- Task stats -->
    @if(count($tasks) > 0)
        <div class="mt-6 text-sm text-base-content/70 flex justify-between">
            <span>{{ count(array_filter($tasks, fn($task) => !$task['completed'])) }} items left</span>
            <span>{{ count(array_filter($tasks, fn($task) => $task['completed'])) }} completed</span>
        </div>
    @endif
</div>
