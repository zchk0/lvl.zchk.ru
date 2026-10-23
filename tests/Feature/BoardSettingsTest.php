<?php

namespace Tests\Feature;

use App\Livewire\GoalsBoard;
use App\Models\Board;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class BoardSettingsTest extends TestCase
{
    use RefreshDatabase;

    private Board $board;

    protected function setUp(): void
    {
        parent::setUp();

        $this->board = Board::query()->firstOrFail();
        $this->withSession(['board_id' => $this->board->id]);
    }

    public function test_existing_board_receives_an_encrypted_key_hidden_from_serialization(): void
    {
        $key = $this->board->api_key;

        $this->assertMatchesRegularExpression('/^lvl_[a-zA-Z0-9]{64}$/', $key);
        $this->assertNotSame($key, DB::table('boards')->where('id', $this->board->id)->value('api_key'));
        $this->assertArrayNotHasKey('api_key', $this->board->toArray());
    }

    public function test_new_boards_automatically_receive_distinct_keys(): void
    {
        $other = Board::create(['name' => 'Other board', 'pin_code' => '9876']);

        $this->assertMatchesRegularExpression('/^lvl_[a-zA-Z0-9]{64}$/', $other->fresh()->api_key);
        $this->assertNotSame($this->board->api_key, $other->api_key);
    }

    public function test_key_is_only_rendered_in_settings_for_the_current_board(): void
    {
        $other = Board::create(['name' => 'Other board', 'pin_code' => '9876']);

        Livewire::test(GoalsBoard::class)
            ->assertDontSee($this->board->api_key)
            ->assertDontSee($other->api_key)
            ->call('openBoardSettings')
            ->assertSet('showBoardSettingsModal', true)
            ->assertSee($this->board->api_key)
            ->assertDontSee($other->api_key)
            ->call('closeBoardSettings')
            ->assertSet('showBoardSettingsModal', false)
            ->assertDontSee($this->board->api_key);
    }

    public function test_reset_replaces_only_the_current_boards_key_and_persists_it(): void
    {
        $originalKey = $this->board->api_key;
        $other = Board::create(['name' => 'Other board', 'pin_code' => '9876']);
        $otherKey = $other->api_key;

        $component = Livewire::test(GoalsBoard::class)
            ->call('openBoardSettings')
            ->call('resetBoardApiKey')
            ->assertDispatched('board-api-key-reset')
            ->assertDontSee($originalKey);

        $newKey = $this->board->fresh()->api_key;
        $this->assertNotSame($originalKey, $newKey);
        $this->assertMatchesRegularExpression('/^lvl_[a-zA-Z0-9]{64}$/', $newKey);
        $this->assertSame($otherKey, $other->fresh()->api_key);

        $component->assertSee($newKey)
            ->call('closeBoardSettings')
            ->call('openBoardSettings')
            ->assertSee($newKey);

        $this->assertSame($newKey, $this->board->fresh()->api_key);
    }

    public function test_settings_follow_the_board_selected_in_the_session(): void
    {
        $other = Board::create(['name' => 'Other board', 'pin_code' => '9876']);
        $this->withSession(['board_id' => $other->id]);

        Livewire::test(GoalsBoard::class)
            ->call('openBoardSettings')
            ->assertSee($other->api_key)
            ->assertDontSee($this->board->api_key);
    }
}
