<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Board extends Model
{
    protected $fillable = [
        'name',
        'pin_code',
    ];

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }
}
