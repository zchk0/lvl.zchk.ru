<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Goal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalsImportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_version_two_export_and_import_preserve_board_assignments(): void
    {
        $path = 'storage/app/goals_export_v2_test.json';
        $absolutePath = base_path($path);
        $longUrl = 'https://example.com/'.str_repeat('long-segment/', 30);
        $secondaryBoard = Board::create([
            'name' => 'Secondary board',
            'pin_code' => '9876',
        ]);

        Goal::create([
            'board_id' => $secondaryBoard->id,
            'title' => 'Mapped goal',
            'url' => $longUrl,
            'priority' => 2,
            'position' => 1,
        ]);

        try {
            $this->artisan('goals:export', ['path' => $path])
                ->assertSuccessful();

            $export = json_decode((string) file_get_contents($absolutePath), true, flags: JSON_THROW_ON_ERROR);

            $this->assertSame(2, $export['version']);
            $this->assertCount(2, $export['boards']);
            $this->assertCount(1, $export['goals']);

            Goal::query()->delete();
            Board::query()->delete();

            $this->artisan('goals:import', ['path' => $path])
                ->assertSuccessful();
        } finally {
            @unlink($absolutePath);
        }

        $importedGoal = Goal::query()->where('title', 'Mapped goal')->firstOrFail();

        $this->assertSame('9876', $importedGoal->board->pin_code);
        $this->assertSame('Secondary board', $importedGoal->board->name);
        $this->assertSame($longUrl, $importedGoal->url);
    }
}
