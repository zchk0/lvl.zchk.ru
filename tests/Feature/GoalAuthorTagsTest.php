<?php

namespace Tests\Feature;

use App\Livewire\GoalsBoard;
use App\Models\Board;
use App\Models\Goal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GoalAuthorTagsTest extends TestCase
{
    use RefreshDatabase;

    private Board $board;

    protected function setUp(): void
    {
        parent::setUp();

        $this->board = Board::query()->firstOrFail();
        $this->withSession(['board_id' => $this->board->id]);
    }

    public function test_saving_combines_selected_tags_and_uncommitted_input_without_duplicates(): void
    {
        Livewire::test(GoalsBoard::class)
            ->call('openCreate', 2)
            ->set('title', 'Tagged goal')
            ->set('author_name', '  Анна  Иванова, Борис ')
            ->set('authorTagInput', "анна иванова; Виктор\nБОРИС")
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false)
            ->assertSet('authorTagInput', '');

        $goal = Goal::where('title', 'Tagged goal')->firstOrFail();
        $this->assertSame('Анна Иванова, Борис, Виктор', $goal->author_name);
        $this->assertSame(['Анна Иванова', 'Борис', 'Виктор'], $goal->author_tags);
    }

    public function test_suggestions_include_each_tag_once_and_only_from_the_current_board(): void
    {
        $this->createGoal(['author_name' => 'Анна, Борис']);
        $this->createGoal(['author_name' => 'анна; Виктор', 'is_completed' => true, 'priority' => 4]);
        $otherBoard = Board::create(['name' => 'Other board', 'pin_code' => '9876']);
        $this->createGoal(['board_id' => $otherBoard->id, 'author_name' => 'Private author']);

        Livewire::test(GoalsBoard::class)
            ->set('search', 'nothing matches')
            ->set('visiblePriority', '1')
            ->call('openCreate', 2)
            ->assertViewHas('availableAuthorTags', ['Анна', 'Борис', 'Виктор']);
    }

    public function test_tag_search_ignores_cyrillic_case_and_respects_board_and_filters(): void
    {
        $matching = $this->createGoal(['title' => 'Matching goal', 'author_name' => 'Анна Иванова, Борис']);
        $this->createGoal(['title' => 'Unrelated goal', 'author_name' => 'Виктор']);
        $this->createGoal(['title' => 'Closed goal', 'author_name' => 'АННА ИВАНОВА', 'is_completed' => true]);
        $this->createGoal(['title' => 'Low priority goal', 'author_name' => 'Анна Иванова', 'priority' => 3]);
        $otherBoard = Board::create(['name' => 'Other board', 'pin_code' => '9876']);
        $this->createGoal(['title' => 'Private goal', 'board_id' => $otherBoard->id, 'author_name' => 'Анна Иванова']);

        Livewire::test(GoalsBoard::class)
            ->set('visiblePriority', '2')
            ->set('search', 'иВаНоВа')
            ->assertViewHas('mediumGoals', fn ($goals) => $goals->modelKeys() === [$matching->id])
            ->assertDontSee('Unrelated goal')
            ->assertDontSee('Closed goal')
            ->assertDontSee('Low priority goal')
            ->assertDontSee('Private goal')
            ->set('showClosed', true)
            ->assertSee('Closed goal');
    }

    public function test_legacy_author_loads_as_a_tag_and_tags_can_be_removed(): void
    {
        $goal = $this->createGoal(['author_name' => 'Старый автор']);
        $this->assertSame(['Старый автор'], $goal->author_tags);

        Livewire::test(GoalsBoard::class)
            ->call('openEdit', $goal->id)
            ->assertSet('author_name', 'Старый автор')
            ->set('author_name', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($goal->fresh()->author_name);
    }

    public function test_tag_length_validation_keeps_the_modal_and_entered_tags(): void
    {
        $longTag = str_repeat('Я', 256);

        Livewire::test(GoalsBoard::class)
            ->call('openCreate', 2)
            ->set('title', 'Too long')
            ->set('authorTagInput', $longTag)
            ->call('save')
            ->assertHasErrors(['author_name' => 'max'])
            ->assertSet('showModal', true)
            ->assertSet('author_name', $longTag);

        $this->assertDatabaseMissing('goals', ['title' => 'Too long']);
    }

    public function test_closing_the_modal_discards_the_pending_tag(): void
    {
        Livewire::test(GoalsBoard::class)
            ->call('openCreate', 2)
            ->set('author_name', 'Selected')
            ->set('authorTagInput', 'Uncommitted')
            ->call('closeModal')
            ->call('openCreate', 2)
            ->assertSet('author_name', '')
            ->assertSet('authorTagInput', '');
    }

    private function createGoal(array $attributes = []): Goal
    {
        return Goal::create(array_merge([
            'board_id' => $this->board->id,
            'title' => 'Goal',
            'priority' => 2,
            'position' => 1,
        ], $attributes));
    }
}
