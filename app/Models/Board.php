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

    protected $hidden = ['api_key', 'api_key_hash'];

    protected $casts = ['api_key' => 'encrypted'];

    protected static function booted(): void
    {
        static::creating(function (self $board): void {
            $board->generateApiKey();
        });
    }

    public function resetApiKey(): void
    {
        $this->generateApiKey();
        $this->save();
    }

    private function generateApiKey(): void
    {
        $key = 'lvl_'.Str::random(64);
        $this->api_key = $key;
        $this->api_key_hash = hash('sha256', $key);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }
}
