<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

final class Todo extends BaseComponent
{
    use WithPagination;

    /** @var bool */
    public bool $shouldRenderOnServer = true;

    /** @var string */
    public string $newTask = '';

    /** @var Collection */
    public Collection $tasks;

    /** @var bool */
    public bool $showCompleted = true;

    public function mount(): void
    {
        $this->tasks = collect([]);
        // Initial sample tasks
        $this->tasks->push(['id' => 1, 'text' => 'Learn Livewire', 'completed' => true]);
        $this->tasks->push(['id' => 2, 'text' => 'Build Todo Component', 'completed' => false]);
        $this->tasks->push(['id' => 3, 'text' => 'Implement Server Rendering', 'completed' => false]);
    }

    public function addTask(): void
    {
        if (empty(trim($this->newTask))) {
            return;
        }

        $this->tasks->push([
            'id' => $this->tasks->count() > 0 ? $this->tasks->max('id') + 1 : 1,
            'text' => trim($this->newTask),
            'completed' => false,
        ]);

        $this->newTask = '';
    }

    public function toggleTask(int $taskId): void
    {
        $taskIndex = $this->tasks->search(fn ($task) => $task['id'] === $taskId);
        
        if ($taskIndex !== false) {
            $task = $this->tasks[$taskIndex];
            $task['completed'] = !$task['completed'];
            $this->tasks[$taskIndex] = $task;
        }
    }

    public function deleteTask(int $taskId): void
    {
        $this->tasks = $this->tasks->reject(fn ($task) => $task['id'] === $taskId);
    }

    public function clearCompleted(): void
    {
        $this->tasks = $this->tasks->reject(fn ($task) => $task['completed']);
    }

    #[Computed]
    public function filteredTasks(): Collection
    {
        return $this->tasks->filter(function ($task) {
            return $this->showCompleted || !$task['completed'];
        })->values();
    }

    public function render(): View
    {
        return $this->renderWithServerRendering('livewire.todo');
    }
}
