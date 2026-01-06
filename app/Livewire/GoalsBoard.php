<?php

namespace App\Livewire;

use App\Models\Goal;
use Livewire\Attributes\Url;
use Livewire\Component;

class GoalsBoard extends Component
{
    public bool $showModal = false;
    public bool $showViewModal = false;
    public ?Goal $viewingGoal = null;

    // Синхронизируем с URL: ?priority=1|2|3|4|all
    #[Url(as: 'priority', except: 'all')]
    public string $visiblePriority = 'all';

    public ?int $editingId = null;

    public string $title = '';
    public string $description = '';
    public string $url = '';
    public string $author_name = '';
    public string $target_amount = '';
    public string $saved_amount = '';
    public int $priority = 2;
    public bool $is_completed = false;

    public function render()
    {
        $goals = Goal::orderBy('priority')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->groupBy('priority');

        return view('livewire.goals-board', [
            'highGoals' => $goals[1] ?? collect(),
            'mediumGoals' => $goals[2] ?? collect(),
            'lowGoals' => $goals[3] ?? collect(),
            'archive' => $goals[4] ?? collect(),
        ]);
    }

    public function openCreate(int $priority): void
    {
        $this->resetForm();
        $this->priority = $priority;
        $this->showModal = true;
    }

    public function openView(int $id): void
    {
        $this->viewingGoal = Goal::findOrFail($id);
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->viewingGoal = null;
    }

    public function openEdit(int $id): void
    {
        $this->closeViewModal();

        $goal = Goal::findOrFail($id);

        $this->editingId = $goal->id;
        $this->title = $goal->title;
        $this->description = $goal->description ?? '';
        $this->url = $goal->url ?? '';
        $this->author_name = (string) $goal->author_name;
        $this->target_amount = (string) $goal->target_amount;
        $this->saved_amount = (string) $goal->saved_amount;
        $this->priority = (int) $goal->priority;
        $this->is_completed = (bool) $goal->is_completed;

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'url' => ['nullable', 'string'],
            'author_name' => ['nullable', 'string', 'max:255'],
            'target_amount' => ['nullable', 'numeric', 'min:0'],
            'saved_amount' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['required', 'integer', 'in:1,2,3,4'],
            'is_completed' => ['boolean'],
        ]);

        $data = [
            'title' => $this->title,
            'description' => $this->description ?: null,
            'url' => $this->url,
            'author_name' => $this->author_name ?: null,
            'target_amount' => $this->target_amount === '' ? 0 : (float) $this->target_amount,
            'saved_amount' => $this->saved_amount === '' ? 0 : (float) $this->saved_amount,
            'priority' => $this->priority,
            'is_completed' => $this->is_completed,
        ];

        if ($this->editingId) {
            $goal = Goal::findOrFail($this->editingId);
            $goal->update($data);
        } else {
            $maxPosition = Goal::where('priority', $data['priority'])->max('position') ?? 0;
            $data['position'] = $maxPosition + 1;

            Goal::create($data);
        }

        $this->closeModal();
    }

    public function moveToPriority(int $id, int $priority): void
    {
        $goal = Goal::findOrFail($id);

        if ($goal->priority === $priority) {
            return;
        }

        $maxPosition = Goal::where('priority', $priority)->max('position') ?? 0;

        $goal->priority = $priority;
        $goal->position = $maxPosition + 1;
        $goal->save();
    }

    public function toggleCompleted(int $id): void
    {
        $goal = Goal::findOrFail($id);
        $goal->is_completed = ! $goal->is_completed;
        $goal->saved_amount = $goal->is_completed ? (int)$goal->target_amount : 0;
        $goal->save();
    }

    public function delete(int $id): void
    {
        Goal::whereKey($id)->delete();

        if ($this->editingId === $id) {
            $this->closeModal();
        }

        if ($this->viewingGoal?->id === $id) {
            $this->closeViewModal();
        }
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->description = '';
        $this->url = '';
        $this->author_name = '';
        $this->target_amount = '';
        $this->saved_amount = '';
        $this->priority = 2;
        $this->is_completed = false;
    }
}
