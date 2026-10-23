<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->text('api_key')->nullable();
        });

        DB::table('boards')->select('id')->chunkById(100, function ($boards) {
            foreach ($boards as $board) {
                DB::table('boards')->where('id', $board->id)->update([
                    'api_key' => Crypt::encryptString('lvl_'.Str::random(64)),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->dropColumn('api_key');
        });
    }
};
