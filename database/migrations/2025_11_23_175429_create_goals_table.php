<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('url')->nullable();
            $table->string('author_name')->nullable();
            $table->decimal('target_amount', 12, 2)->default(0);
            $table->decimal('saved_amount', 12, 2)->default(0);
            $table->tinyInteger('priority')->default(2); // 1-высокий, 2-средний, 3-низкий, 4-архив
            $table->boolean('is_completed')->default(false);
            $table->unsignedInteger('position')->default(0); // порядок внутри колонки
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
