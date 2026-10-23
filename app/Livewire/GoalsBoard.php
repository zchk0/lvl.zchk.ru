<?php

namespace App\Livewire;

use App\Models\Board;
use App\Models\Goal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;

class GoalsBoard extends Component
{
    public bool $showModal = false;
    public bool $showViewModal = false;
    public bool $showBoardSettingsModal = false;
    public ?Goal $viewingGoal = null;
    public ?int $confirmSubgoalCostsGoalId = null;
    public float $pendingSubgoalCostsTotal = 0.0;
    public int $pendingSubgoalCostsCount = 0;
    public float $pendingDoneSubgoalCostsTotal = 0.0;
    public int $pendingDoneSubgoalCostsCount = 0;
    public string $subgoalCostsDestination = 'target';

    // Синхронизируем с URL: ?priority=1|2|3|4|all
    #[Url(as: 'priority', except: 'all')]
    public string $visiblePriority = 'all';

    // Поиск
    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'closed', except: false)]
    public bool $showClosed = false;

    public ?int $editingId = null;

    public string $title = '';
    public string $description = '';
    public string $description_format = 'raw';
    public string $url = '';
    public string $author_name = '';
    public string $authorTagInput = '';
    public string $target_amount = '';
    public string $saved_amount = '';
    public int $priority = 2;
    public bool $is_completed = false;

    public function render()
    {
        $boardId = $this->currentBoardId();
        $currentBoard = Board::findOrFail($boardId);
        $term = trim($this->search);
        $authorNames = $this->showModal || $term !== ''
            ? Goal::where('board_id', $boardId)->whereNotNull('author_name')->distinct()->pluck('author_name')
            : collect();
        $query = Goal::query()
            ->where('board_id', $boardId)
            ->orderBy('priority')
            ->orderBy('position')
            ->orderBy('id');

        // Фильтр по выбранной колонке (если не all)
        if ($this->visiblePriority !== 'all') {
            $query->where('priority', (int) $this->visiblePriority);
        }

        if (! $this->showClosed) {
            $query->where('is_completed', false);
        }

        // Поиск по карточкам
        if ($term !== '' && (mb_strlen($term) > 0)) {
            $like = '%' . mb_strtolower($term) . '%';
            // PHP handles Cyrillic case folding even when SQLite's LOWER only handles ASCII.
            $matchingAuthors = $authorNames
                ->filter(static fn (string $authors): bool => mb_stripos($authors, $term) !== false)
                ->values()->all();
            $query->where(function ($q) use ($like, $matchingAuthors) {
                $q->whereRaw('LOWER(title) LIKE ?', [$like])
                    ->orWhereRaw('description LIKE ?', [$like])
                    ->orWhereIn('author_name', $matchingAuthors);
            });
        }

        $goals = $query->get()->groupBy('priority');

        return view('livewire.goals-board', [
            'highGoals' => $goals[1] ?? collect(),
            'mediumGoals' => $goals[2] ?? collect(),
            'lowGoals' => $goals[3] ?? collect(),
            'archive' => $goals[4] ?? collect(),
            'currentBoard' => $currentBoard,
            'availableAuthorTags' => $this->showModal
                ? $authorNames->flatMap(fn (string $authors) => Goal::parseAuthorTags($authors))
                    ->unique(fn (string $tag) => mb_strtolower($tag))
                    ->sortBy(fn (string $tag) => mb_strtolower($tag), SORT_NATURAL)
                    ->values()->all()
                : [],
        ]);
    }

    public function openBoardSettings(): void
    {
        $this->showBoardSettingsModal = true;
    }

    public function closeBoardSettings(): void
    {
        $this->showBoardSettingsModal = false;
    }

    public function resetBoardApiKey(): void
    {
        Board::findOrFail($this->currentBoardId())->resetApiKey();
        $this->dispatch('board-api-key-reset');
    }

    public function openCreate(int $priority): void
    {
        $this->resetForm();
        $this->priority = $priority;
        $this->showModal = true;
    }

    public function openView(int $id): void
    {
        $this->viewingGoal = $this->findGoalForCurrentBoard($id);
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->viewingGoal = null;
        $this->resetSubgoalCostsConfirmation();
    }

    public function openEdit(int $id): void
    {
        $this->closeViewModal();

        $goal = $this->findGoalForCurrentBoard($id);

        $this->editingId = $goal->id;
        $this->title = $goal->title;
        $this->description = $goal->description ?? '';
        $this->description_format = in_array($goal->description_format, Goal::DESCRIPTION_FORMATS, true)
            ? $goal->description_format
            : 'raw';
        $this->url = $goal->url ?? '';
        $this->author_name = (string) $goal->author_name;
        $this->authorTagInput = '';
        $this->target_amount = (string) $goal->target_amount;
        $this->saved_amount = (string) $goal->saved_amount;
        $this->priority = (int) $goal->priority;
        $this->is_completed = (bool) $goal->is_completed;

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->author_name = implode(', ', Goal::parseAuthorTags($this->author_name . ',' . $this->authorTagInput));
        $this->authorTagInput = '';

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'description_format' => ['required', 'string', 'in:raw,markdown,html'],
            'url' => ['nullable', 'string'],
            'author_name' => ['nullable', 'string', 'max:255'],
            'target_amount' => ['nullable', 'numeric', 'min:0'],
            'saved_amount' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['required', 'integer', 'in:1,2,3,4'],
            'is_completed' => ['boolean'],
        ], [
            'author_name.max' => 'Общая длина тегов автора не должна превышать 255 символов.',
        ]);

        $data = [
            'title' => $this->title,
            'description' => $this->description ?: null,
            'description_format' => $this->description_format,
            'url' => $this->url,
            'author_name' => $this->author_name ?: null,
            'target_amount' => $this->target_amount === '' ? 0 : (float) $this->target_amount,
            'saved_amount' => $this->saved_amount === '' ? 0 : (float) $this->saved_amount,
            'priority' => $this->priority,
            'is_completed' => $this->is_completed,
        ];

        if ($this->editingId) {
            $goal = $this->findGoalForCurrentBoard($this->editingId);
            $goal->update($data);
        } else {
            $boardId = $this->currentBoardId();

            DB::transaction(function () use ($data, $boardId) {
                // Сдвигаем все позиции вниз внутри priority
                // Новая карточка становится первой
                Goal::where('board_id', $boardId)
                    ->where('priority', $data['priority'])
                    ->increment('position');
                $data['position'] = 1;
                $data['board_id'] = $boardId;

                Goal::create($data);
            });
        }

        $this->closeModal();
    }

    public function moveToPriority(int $id, int $priority): void
    {
        $boardId = $this->currentBoardId();
        $goal = $this->findGoalForCurrentBoard($id);

        if ($goal->priority === $priority) {
            return;
        }

        $maxPosition = Goal::where('board_id', $boardId)
            ->where('priority', $priority)
            ->max('position') ?? 0;

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

        $boardId = $this->currentBoardId();
        foreach ($normalized as $priority => $ids) {
            foreach ($ids as $index => $id) {
                Goal::where('board_id', $boardId)
                    ->whereKey($id)
                    ->update([
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

        $boardId = $this->currentBoardId();
        $goal = Goal::where('board_id', $boardId)->find($id);
        if (! $goal) {
            return;
        }

        $ids = Goal::where('board_id', $boardId)
            ->where('priority', $goal->priority)
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

        $boardId = $this->currentBoardId();
        $goal = Goal::where('board_id', $boardId)->find($id);
        if (! $goal) {
            return;
        }

        $ids = Goal::where('board_id', $boardId)
            ->where('priority', $goal->priority)
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

    public function requestSubgoalCostsSync(int $goalId): void
    {
        $goal = $this->findGoalForCurrentBoard($goalId);
        [$total, $count, $doneTotal, $doneCount] = $this->calculateSubgoalCosts($goal);

        $this->confirmSubgoalCostsGoalId = $goalId;
        $this->pendingSubgoalCostsTotal = $total;
        $this->pendingSubgoalCostsCount = $count;
        $this->pendingDoneSubgoalCostsTotal = $doneTotal;
        $this->pendingDoneSubgoalCostsCount = $doneCount;
        $this->subgoalCostsDestination = 'target';
    }

    public function cancelSubgoalCostsSync(): void
    {
        $this->resetSubgoalCostsConfirmation();
    }

    public function confirmSubgoalCostsSync(): void
    {
        if ($this->confirmSubgoalCostsGoalId === null) {
            return;
        }

        $goalId = $this->confirmSubgoalCostsGoalId;
        $destination = $this->subgoalCostsDestination;
        $this->resetSubgoalCostsConfirmation();
        $this->syncSubgoalCostsToGoal($goalId, $destination);
    }

    public function syncSubgoalCostsToGoal(int $goalId, string $destination = 'target'): void
    {
        $goal = $this->findGoalForCurrentBoard($goalId);
        [$total, $count, $doneTotal] = $this->calculateSubgoalCosts($goal);

        if ($count === 0) {
            return;
        }

        $amount = max(0, $total);

        if ($destination === 'split_done') {
            $goal->target_amount = $amount;
            $goal->saved_amount = max(0, $doneTotal);
        } elseif ($destination === 'saved') {
            $goal->saved_amount = $amount;
        } else {
            $goal->target_amount = $amount;

            if ($goal->is_completed) {
                $goal->saved_amount = $goal->target_amount;
            }
        }

        $goal->save();

        if ($this->viewingGoal?->id === $goal->id) {
            $this->viewingGoal = $goal->fresh();
        }
    }

    public function toggleCompleted(int $id): void
    {
        $goal = $this->findGoalForCurrentBoard($id);

        if (! $goal->is_completed) {
            $goal->is_completed = true;

            if ($goal->target_amount > 0 && $goal->saved_amount < $goal->target_amount) {
                $goal->saved_amount = $goal->target_amount;
            }
        } else {
            $goal->is_completed = false;
        }

        $goal->save();
    }

    public function togglePublic(int $id): void
    {
        $goal = $this->findGoalForCurrentBoard($id);
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
        Goal::where('board_id', $this->currentBoardId())
            ->whereKey($id)
            ->delete();

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
        $this->description_format = 'raw';
        $this->url = '';
        $this->author_name = '';
        $this->authorTagInput = '';
        $this->target_amount = '';
        $this->saved_amount = '';
        $this->priority = 2;
        $this->is_completed = false;
    }

    private function updateSubgoalsForGoal(int $goalId, callable $mutator): void
    {
        $goal = $this->findGoalForCurrentBoard($goalId);
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

    private function currentBoardId(): int
    {
        $boardId = (int) session('board_id', 0);
        abort_if($boardId <= 0, 403);

        return $boardId;
    }

    private function findGoalForCurrentBoard(int $goalId): Goal
    {
        return Goal::where('board_id', $this->currentBoardId())->findOrFail($goalId);
    }

    private function resetSubgoalCostsConfirmation(): void
    {
        $this->confirmSubgoalCostsGoalId = null;
        $this->pendingSubgoalCostsTotal = 0.0;
        $this->pendingSubgoalCostsCount = 0;
        $this->pendingDoneSubgoalCostsTotal = 0.0;
        $this->pendingDoneSubgoalCostsCount = 0;
        $this->subgoalCostsDestination = 'target';
    }

    private function calculateSubgoalCosts(Goal $goal): array
    {
        $subgoals = $this->normalizeSubgoals($goal->subgoals);
        $total = 0.0;
        $count = 0;
        $doneTotal = 0.0;
        $doneCount = 0;

        foreach ($subgoals as $subgoal) {
            [$subgoalTotal, $subgoalCount] = $this->sumRubleAmounts((string) ($subgoal['title'] ?? ''));
            $total += $subgoalTotal;
            $count += $subgoalCount;

            if ((bool) ($subgoal['is_done'] ?? false)) {
                $doneTotal += $subgoalTotal;
                $doneCount += $subgoalCount;
            }
        }

        return [$total, $count, $doneTotal, $doneCount];
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

    private function sumRubleAmounts(string $text): array
    {
        $text = str_replace("\xc2\xa0", ' ', $text);

        $matched = preg_match_all(
            '/([+\-]?)([0-9][0-9 ]*)\s*(?:\x{20BD}|\x{0440}\x{0443}\x{0431}\.?|\x{0440}\.?)(?!\p{L})/iu',
            $text,
            $matches,
            PREG_SET_ORDER
        );

        if ($matched === false) {
            return [0.0, 0];
        }

        $total = 0.0;
        foreach ($matches as $match) {
            $sign = $match[1] ?? '';
            $amount = str_replace(' ', '', $match[2] ?? '0');
            $value = (float) $amount;

            $total += $sign === '-' ? -$value : $value;
        }

        return [$total, count($matches)];
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
