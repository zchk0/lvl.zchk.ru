<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    protected $fillable = [
        'title',
        'url',
        'author_name',
        'target_amount',
        'saved_amount',
        'priority',
        'is_completed',
        'position',
    ];

    // Акцессор прогресса в процентах
    public function getProgressPercentAttribute(): int
    {
        if ($this->target_amount <= 0) {
            return 0;
        }

        $percent = ($this->saved_amount / $this->target_amount) * 100;

        return (int) min(100, max(0, round($percent)));
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            1 => 'Высокий приоритет',
            2 => 'Средний приоритет',
            3 => 'Низкий приоритет',
            4 => 'Архив',
            default => 'Без приоритета',
        };
    }
}
