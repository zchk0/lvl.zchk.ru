<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->char('api_key_hash', 64)->nullable()->unique();
        });

        DB::table('boards')->select(['id', 'api_key'])->whereNotNull('api_key')->chunkById(100, function ($boards) {
            foreach ($boards as $board) {
                DB::table('boards')->where('id', $board->id)->update([
                    'api_key_hash' => hash('sha256', Crypt::decryptString($board->api_key)),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->dropUnique(['api_key_hash']);
            $table->dropColumn('api_key_hash');
        });
    }
};
