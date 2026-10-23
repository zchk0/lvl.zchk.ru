<?php

namespace App\Actions;

use App\Models\Board;
use App\Models\Goal;
use Illuminate\Support\Facades\DB;

class UpdateGoal
{
    public function handle(int $boardId, string $goalId, array $data): Goal
    {
        return DB::transaction(function () use ($boardId, $goalId, $data): Goal {
            $board = Board::whereKey($boardId)->lockForUpdate()->firstOrFail();
            $goal = $board->goals()->whereKey($goalId)->lockForUpdate()->first();
            abort_if($goal === null, 404, 'Цель не найдена.');

            if (isset($data['priority']) && (int) $data['priority'] !== $goal->priority) {
                $data['position'] = ($board->goals()->where('priority', $data['priority'])->max('position') ?? 0) + 1;
            }

            $goal->update($data);

            return $goal->refresh();
        }, 3);
    }
}
