<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('pin_code')->unique();
            $table->timestamps();
        });

        $defaultBoardId = DB::table('boards')->insertGetId([
            'name' => env('DEFAULT_BOARD_NAME', 'Основная доска'),
            'pin_code' => env('PIN_CODE', '1011'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('goals', function (Blueprint $table) {
            $table->foreignId('board_id')
                ->nullable()
                ->after('id')
                ->constrained('boards')
                ->cascadeOnDelete();

            $table->index(['board_id', 'priority', 'position']);
        });

        DB::table('goals')->whereNull('board_id')->update([
            'board_id' => $defaultBoardId,
        ]);
    }

    public function down(): void
    {
        Schema::table('goals', function (Blueprint $table) {
            $table->dropForeign(['board_id']);
            $table->dropIndex(['board_id', 'priority', 'position']);
            $table->dropColumn('board_id');
        });

        Schema::dropIfExists('boards');
    }
};
