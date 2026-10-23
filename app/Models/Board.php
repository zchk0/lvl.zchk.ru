<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Board extends Model
{
    protected $fillable = [
        'name',
        'pin_code',
    ];

    protected $hidden = ['api_key'];

    protected $casts = ['api_key' => 'encrypted'];

    protected static function booted(): void
    {
        static::creating(function (self $board): void {
            $board->api_key = self::generateApiKey();
        });
    }

    public function resetApiKey(): void
    {
        $this->api_key = self::generateApiKey();
        $this->save();
    }

    private static function generateApiKey(): string
    {
        return 'lvl_'.Str::random(64);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }
}
