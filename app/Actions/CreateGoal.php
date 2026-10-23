<?php

namespace App\Actions;

use App\Models\Board;
use App\Models\Goal;
use Illuminate\Support\Facades\DB;

class CreateGoal
{
    public function handle(int $boardId, array $data): Goal
    {
        return DB::transaction(function () use ($boardId, $data): Goal {
            $board = Board::whereKey($boardId)->lockForUpdate()->firstOrFail();
            $board->goals()->where('priority', $data['priority'])->increment('position');

            return $board->goals()->create([...$data, 'position' => 1])->refresh();
        }, 3);
    }
}
