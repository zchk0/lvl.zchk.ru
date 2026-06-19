<?php

namespace App\Livewire;

use App\Models\Goal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    // Поиск
    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'hide_done', except: false)]
    public bool $hideCompleted = false;

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
        $query = Goal::query()
            ->orderBy('priority')
            ->orderBy('position')
            ->orderBy('id');

        // Фильтр по выбранной колонке (если не all)
        if ($this->visiblePriority !== 'all') {
            $query->where('priority', (int) $this->visiblePriority);
        }

        if ($this->hideCompleted) {
            $query->where('is_completed', false);
        }

        // Поиск по карточкам
        $term = trim($this->search);
        if ($term !== '' && (mb_strlen($term) > 0)) {
            $like = '%' . mb_strtolower($term) . '%';
            $query->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(title) LIKE ?', [$like])
                    ->orWhereRaw('description LIKE ?', [$like]);
            });
        }

        $goals = $query->get()->groupBy('priority');

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
            DB::transaction(function () use ($data) {
                // Сдвигаем все позиции вниз внутри priority
                // Новая карточка становится первой
                Goal::where('priority', $data['priority'])
                    ->increment('position');
                $data['position'] = 1;

                Goal::create($data);
            });
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

    public function syncGoalOrder(array $columns): void
    {
        $normalized = [];

        foreach ($columns as $column) {
            if (!is_array($column)) {
                continue;
            }

            $priority = isset($column['priority']) ? (int) $column['priority'] : null;
            if (!in_array($priority, [1, 2, 3, 4], true)) {
                continue;
            }

            $ids = $column['ids'] ?? [];
            if (!is_array($ids)) {
                continue;
            }

            $ids = array_values(array_filter(array_map('intval', $ids), static fn ($id) => $id > 0));

            $normalized[$priority] = $ids;
        }

        if ($normalized === []) {
            return;
        }

        foreach ($normalized as $priority => $ids) {
            foreach ($ids as $index => $id) {
                Goal::whereKey($id)->update([
                    'priority' => $priority,
                    'position' => $index + 1,
                ]);
            }
        }
    }

    public function moveWithinPriority(int $id, string $direction): void
    {
        $direction = strtolower($direction);
        if (!in_array($direction, ['up', 'down'], true)) {
            return;
        }

        if (trim($this->search) !== '') {
            return;
        }

        $goal = Goal::find($id);
        if (! $goal) {
            return;
        }

        $ids = Goal::where('priority', $goal->priority)
            ->orderBy('position')
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn ($goalId) => (int) $goalId)
            ->all();

        $index = array_search($goal->id, $ids, true);
        if ($index === false) {
            return;
        }

        $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;
        if ($swapIndex < 0 || $swapIndex >= count($ids)) {
            return;
        }

        [$ids[$index], $ids[$swapIndex]] = [$ids[$swapIndex], $ids[$index]];

        $this->syncGoalOrder([
            [
                'priority' => $goal->priority,
                'ids' => $ids,
            ],
        ]);
    }

    public function moveToTopWithinPriority(int $id): void
    {
        if (trim($this->search) !== '') {
            return;
        }

        $goal = Goal::find($id);
        if (! $goal) {
            return;
        }

        $ids = Goal::where('priority', $goal->priority)
            ->orderBy('position')
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn ($goalId) => (int) $goalId)
            ->all();

        $index = array_search($goal->id, $ids, true);
        if ($index === false || $index === 0) {
            return;
        }

        array_splice($ids, $index, 1);
        array_unshift($ids, $goal->id);

        $this->syncGoalOrder([
            [
                'priority' => $goal->priority,
                'ids' => $ids,
            ],
        ]);
    }

    public function addSubgoal(int $goalId): void
    {
        $newSubgoalId = null;

        $this->updateSubgoalsForGoal($goalId, function (array $subgoals) use (&$newSubgoalId): array {
            $newSubgoalId = $this->makeSubgoalId();
            $subgoals[] = [
                'id' => $newSubgoalId,
                'title' => 'Новая подзадача',
                'is_done' => false,
                'created_at' => $this->currentSubgoalTimestamp(),
                'position' => count($subgoals) + 1,
            ];

            return $subgoals;
        });

        if ($newSubgoalId !== null) {
            $this->dispatch('subgoal-created', goalId: $goalId, subgoalId: $newSubgoalId);
        }
    }

    public function saveSubgoalAndAddNext(int $goalId, string $subgoalId, string $title): void
    {
        $subgoalId = trim($subgoalId);
        if ($subgoalId === '') {
            return;
        }

        $normalizedTitle = trim((string) preg_replace('/\s+/u', ' ', $title));
        $newSubgoalId = null;

        $this->updateSubgoalsForGoal($goalId, function (array $subgoals) use ($subgoalId, $normalizedTitle, &$newSubgoalId): array {
            $currentIndex = null;

            foreach ($subgoals as $index => &$subgoal) {
                if (($subgoal['id'] ?? '') !== $subgoalId) {
                    continue;
                }

                $currentIndex = $index;

                if ($normalizedTitle !== '') {
                    $subgoal['title'] = $normalizedTitle;
                }
                break;
            }
            unset($subgoal);

            $newSubgoalId = $this->makeSubgoalId();
            $newSubgoal = [
                'id' => $newSubgoalId,
                'title' => 'Новая подзадача...',
                'is_done' => false,
                'created_at' => $this->currentSubgoalTimestamp(),
                'position' => 0,
            ];

            if (is_int($currentIndex) && $currentIndex >= 0) {
                array_splice($subgoals, $currentIndex + 1, 0, [$newSubgoal]);
            } else {
                $subgoals[] = $newSubgoal;
            }

            return $subgoals;
        });

        if ($newSubgoalId !== null) {
            $this->dispatch('subgoal-created', goalId: $goalId, subgoalId: $newSubgoalId);
        }
    }

    public function toggleSubgoal(int $goalId, string $subgoalId): void
    {
        $subgoalId = trim($subgoalId);
        if ($subgoalId === '') {
            return;
        }

        $this->updateSubgoalsForGoal($goalId, function (array $subgoals) use ($subgoalId): array {
            foreach ($subgoals as &$subgoal) {
                if (($subgoal['id'] ?? '') === $subgoalId) {
                    $subgoal['is_done'] = !((bool) ($subgoal['is_done'] ?? false));
                    break;
                }
            }
            unset($subgoal);

            return $subgoals;
        });
    }

    public function renameSubgoal(int $goalId, string $subgoalId, string $title): void
    {
        $subgoalId = trim($subgoalId);
        if ($subgoalId === '') {
            return;
        }

        $normalizedTitle = trim((string) preg_replace('/\s+/u', ' ', $title));
        if ($normalizedTitle === '') {
            return;
        }

        $this->updateSubgoalsForGoal($goalId, function (array $subgoals) use ($subgoalId, $normalizedTitle): array {
            foreach ($subgoals as &$subgoal) {
                if (($subgoal['id'] ?? '') === $subgoalId) {
                    $subgoal['title'] = $normalizedTitle;
                    break;
                }
            }
            unset($subgoal);

            return $subgoals;
        });
    }

    public function deleteSubgoal(int $goalId, string $subgoalId): void
    {
        $subgoalId = trim($subgoalId);
        if ($subgoalId === '') {
            return;
        }

        $this->updateSubgoalsForGoal($goalId, function (array $subgoals) use ($subgoalId): array {
            return array_values(array_filter(
                $subgoals,
                static fn (array $subgoal): bool => ($subgoal['id'] ?? '') !== $subgoalId
            ));
        });
    }

    public function reorderSubgoals(int $goalId, array $orderedIds): void
    {
        $orderedIds = array_values(array_unique(array_filter(
            array_map(static function ($id): string {
                return is_scalar($id) ? trim((string) $id) : '';
            }, $orderedIds),
            static fn (string $id): bool => $id !== ''
        )));

        if ($orderedIds === []) {
            return;
        }

        $this->updateSubgoalsForGoal($goalId, function (array $subgoals) use ($orderedIds): array {
            $subgoalsById = [];
            foreach ($subgoals as $subgoal) {
                $subgoalsById[$subgoal['id']] = $subgoal;
            }

            $reordered = [];
            foreach ($orderedIds as $id) {
                if (!isset($subgoalsById[$id])) {
                    continue;
                }

                $reordered[] = $subgoalsById[$id];
                unset($subgoalsById[$id]);
            }

            foreach ($subgoals as $subgoal) {
                $id = $subgoal['id'];
                if (!isset($subgoalsById[$id])) {
                    continue;
                }

                $reordered[] = $subgoal;
                unset($subgoalsById[$id]);
            }

            return $reordered;
        });
    }

    public function toggleCompleted(int $id): void
    {
        $goal = Goal::findOrFail($id);

        if (! $goal->is_completed) {
            $goal->is_completed = true;
            $goal->saved_amount = $goal->target_amount;
        } else {
            $goal->is_completed = false;
        }

        $goal->save();
    }

    public function togglePublic(int $id): void
    {
        $goal = Goal::findOrFail($id);
        $goal->is_public = ! $goal->is_public;

        if ($goal->is_public && empty($goal->public_token)) {
            $goal->public_token = $this->generatePublicToken();
        }

        $goal->save();

        if ($this->viewingGoal?->id === $goal->id) {
            $this->viewingGoal = $goal->fresh();
        }
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

    private function updateSubgoalsForGoal(int $goalId, callable $mutator): void
    {
        $goal = Goal::findOrFail($goalId);
        $subgoals = $this->normalizeSubgoals($goal->subgoals);
        $updatedSubgoals = $mutator($subgoals);

        if (!is_array($updatedSubgoals)) {
            return;
        }

        $goal->subgoals = $this->normalizeSubgoals($updatedSubgoals, false);
        $goal->save();

        if ($this->viewingGoal?->id === $goal->id) {
            $this->viewingGoal = $goal->fresh();
        }
    }

    private function normalizeSubgoals(mixed $subgoals, bool $sortByPosition = true): array
    {
        if (!is_array($subgoals)) {
            return [];
        }

        $normalized = [];
        foreach ($subgoals as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $normalizedItem = $this->normalizeSubgoalItem($item, $index + 1);
            if ($normalizedItem === null) {
                continue;
            }

            $normalized[] = $normalizedItem;
        }

        if ($sortByPosition) {
            usort($normalized, static function (array $a, array $b): int {
                $positionCmp = ($a['position'] ?? 0) <=> ($b['position'] ?? 0);
                if ($positionCmp !== 0) {
                    return $positionCmp;
                }

                return strcmp((string) ($a['id'] ?? ''), (string) ($b['id'] ?? ''));
            });
        }

        foreach ($normalized as $index => &$item) {
            $item['position'] = $index + 1;
        }
        unset($item);

        return array_values($normalized);
    }

    private function normalizeSubgoalItem(array $item, int $fallbackPosition): ?array
    {
        $title = trim((string) preg_replace('/\s+/u', ' ', (string) ($item['title'] ?? '')));
        if ($title === '') {
            $title = 'Новая подзадача';
        }

        $id = isset($item['id']) && is_scalar($item['id'])
            ? trim((string) $item['id'])
            : '';
        if ($id === '') {
            $id = $this->makeSubgoalId();
        }

        $position = isset($item['position']) ? (int) $item['position'] : $fallbackPosition;
        if ($position <= 0) {
            $position = $fallbackPosition;
        }

        return [
            'id' => $id,
            'title' => $title,
            'is_done' => (bool) ($item['is_done'] ?? false),
            'created_at' => $this->normalizeSubgoalCreatedAt($item['created_at'] ?? null),
            'position' => $position,
        ];
    }

    private function normalizeSubgoalCreatedAt(mixed $createdAt): ?string
    {
        if ($createdAt === null || !is_scalar($createdAt)) {
            return null;
        }

        $value = trim((string) $createdAt);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    private function currentSubgoalTimestamp(): string
    {
        return now()->format('Y-m-d H:i:s');
    }

    private function makeSubgoalId(): string
    {
        return (string) Str::ulid();
    }

    private function generatePublicToken(): string
    {
        do {
            $token = Str::random();
        } while (Goal::where('public_token', $token)->exists());

        return $token;
    }
}
