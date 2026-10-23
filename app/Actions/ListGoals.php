<?php

namespace App\Actions;

use App\Models\Goal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use PDO;

class ListGoals
{
    public function handle(int $boardId, array $filters): LengthAwarePaginator
    {
        $query = Goal::where('board_id', $boardId);
        $lower = $this->casefoldFunction($query);

        foreach (['id', 'priority', 'position', 'description_format', 'is_completed', 'is_public', 'target_amount', 'saved_amount'] as $field) {
            if (array_key_exists($field, $filters)) {
                $query->where($field, $filters[$field]);
            }
        }

        foreach (['title', 'description', 'url'] as $field) {
            if (isset($filters[$field])) {
                $query->whereRaw("{$lower}({$field}) LIKE ? ESCAPE '!'", [$this->containsPattern($filters[$field])]);
            }
        }

        if (isset($filters['q'])) {
            $pattern = $this->containsPattern($filters['q']);
            $query->where(function (Builder $search) use ($lower, $pattern): void {
                foreach (['title', 'description', 'author_name'] as $field) {
                    $search->orWhereRaw("{$lower}({$field}) LIKE ? ESCAPE '!'", [$pattern]);
                }
            });
        }

        foreach (['target_amount', 'saved_amount'] as $field) {
            foreach (['_min' => '>=', '_max' => '<='] as $suffix => $operator) {
                if (isset($filters[$field . $suffix])) {
                    $query->where($field, $operator, $filters[$field . $suffix]);
                }
            }
        }

        foreach (['created', 'updated'] as $field) {
            foreach (['_from' => '>=', '_to' => '<='] as $suffix => $operator) {
                if (isset($filters[$field . $suffix])) {
                    $query->whereDate($field . '_at', $operator, $filters[$field . $suffix]);
                }
            }
        }

        if (! empty($filters['tags'])) {
            $wanted = array_map(
                mb_strtolower(...),
                Goal::parseAuthorTags(implode(', ', $filters['tags']))
            );
            // Tags share the legacy author_name column. Parse distinct values before SQL pagination.
            $authors = (clone $query)
                ->whereNotNull('author_name')
                ->distinct()
                ->pluck('author_name');

            $matchingAuthors = $authors->filter(function (string $authors) use ($wanted, $filters): bool {
                $tags = array_map(mb_strtolower(...), Goal::parseAuthorTags($authors));
                $matches = array_intersect($wanted, $tags);

                return ($filters['tags_mode'] ?? 'all') === 'any'
                    ? $matches !== []
                    : count($matches) === count($wanted);
            })->values()->all();
            $query->whereIn('author_name', $matchingAuthors);
        }

        if (isset($filters['sort'])) {
            $query->orderBy($filters['sort'], $filters['direction'] ?? 'asc');
        } else {
            $query->orderBy('priority')->orderBy('position');
        }

        $query->orderBy('id');

        return $query->paginate(
            (int) ($filters['per_page'] ?? 20),
            ['*'],
            'page',
            (int) ($filters['page'] ?? 1)
        )->appends(Arr::except($filters, ['page']));
    }

    private function containsPattern(string $value): string
    {
        return '%' . str_replace(
            ['!', '%', '_'],
            ['!!', '!%', '!_'],
            mb_strtolower($value)
        ) . '%';
    }

    private function casefoldFunction(Builder $query): string
    {
        $connection = $query->getConnection();

        if ($connection->getDriverName() !== 'sqlite') {
            return 'LOWER';
        }

        // SQLite's built-in LOWER handles ASCII only; API search must also handle Cyrillic.
        $connection->getPdo()->sqliteCreateFunction(
            'goal_api_lower',
            static fn(?string $value): ?string => $value === null ? null : mb_strtolower($value),
            1,
            PDO::SQLITE_DETERMINISTIC,
        );

        return 'goal_api_lower';
    }
}
