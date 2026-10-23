<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Goal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiGoalUpdateTest extends TestCase
{
    use RefreshDatabase;

    private Board $board;

    protected function setUp(): void
    {
        parent::setUp();

        $this->board = Board::query()->firstOrFail();
    }

    public function test_partial_update_preserves_omitted_fields_order_and_public_access(): void
    {
        $goal = $this->createGoal([
            'description' => 'Original description',
            'description_format' => 'markdown',
            'url' => 'https://example.com',
            'author_name' => 'IVAN, Работа',
            'target_amount' => 30000,
            'saved_amount' => 5000,
            'position' => 5,
            'is_public' => true,
            'public_token' => 'existing-public-link',
            'subgoals' => [['id' => 'first', 'title' => 'Keep this', 'is_completed' => false]],
        ]);
        $before = $goal->getAttributes();
        $neighbour = $this->createGoal(['position' => 6]);

        $response = $this->withToken($this->board->api_key)->patchJson('/api/goals/'.$goal->id, ['title' => 'Updated title']);

        $response->assertOk()
            ->assertJsonPath('data.id', $goal->id)
            ->assertJsonPath('data.title', 'Updated title')
            ->assertJsonPath('data.board_id', $this->board->id)
            ->assertJsonPath('data.position', 5)
            ->assertJsonPath('data.tags', ['IVAN', 'Работа'])
            ->assertJsonPath('data.is_public', true);

        foreach ($before as $field => $value) {
            if (! in_array($field, ['title', 'updated_at'], true)) {
                $this->assertSame($value, $goal->fresh()->getAttributes()[$field], $field);
            }
        }

        foreach (['api_key', 'api_key_hash', 'pin_code', 'public_token', 'author_name', 'subgoals'] as $field) {
            $this->assertArrayNotHasKey($field, $response->json('data'));
        }

        $response->assertDontSee($this->board->api_key);
        $this->assertSame(6, $neighbour->fresh()->position);
        $this->assertDatabaseCount('goals', 2);
    }

    public function test_all_editable_fields_are_updated_and_tags_are_normalized(): void
    {
        $goal = $this->createGoal(['position' => 3]);
        $url = 'https://example.com/'.str_repeat('long-segment/', 30);

        $this->withToken($this->board->api_key)->patchJson('/api/goals/'.$goal->id, [
            'title' => 'Updated goal',
            'description' => '**New description**',
            'description_format' => 'markdown',
            'url' => $url,
            'tags' => ['IVAN', 'ivan', ' Анна   Иванова '],
            'target_amount' => 1234.50,
            'saved_amount' => 250.25,
            'priority' => 1,
            'is_completed' => true,
        ])->assertOk()
            ->assertJsonPath('data.title', 'Updated goal')
            ->assertJsonPath('data.description', '**New description**')
            ->assertJsonPath('data.description_format', 'markdown')
            ->assertJsonPath('data.url', $url)
            ->assertJsonPath('data.tags', ['IVAN', 'Анна Иванова'])
            ->assertJsonPath('data.target_amount', 1234.5)
            ->assertJsonPath('data.saved_amount', 250.25)
            ->assertJsonPath('data.priority', 1)
            ->assertJsonPath('data.position', 1)
            ->assertJsonPath('data.is_completed', true);

        $this->assertDatabaseHas('goals', ['id' => $goal->id, 'author_name' => 'IVAN, Анна Иванова', 'url' => $url]);
    }

    public function test_empty_request_leaves_the_goal_unchanged(): void
    {
        $goal = $this->createGoal(['author_name' => 'Keep', 'saved_amount' => 123]);
        $before = $goal->getAttributes();

        $this->travel(1)->minutes();
        $this->withToken($this->board->api_key)->patchJson('/api/goals/'.$goal->id, [])->assertOk();

        $this->assertSame($before, $goal->fresh()->getAttributes());
    }

    public function test_explicit_nulls_empty_tags_zero_and_false_clear_only_requested_fields(): void
    {
        $goal = $this->createGoal([
            'description' => 'Description',
            'url' => 'https://example.com',
            'author_name' => 'IVAN',
            'target_amount' => 30000,
            'saved_amount' => 5000,
            'is_completed' => true,
        ]);

        $this->withToken($this->board->api_key)->patchJson('/api/goals/'.$goal->id, [
            'description' => null,
            'url' => null,
            'tags' => [],
            'target_amount' => null,
            'saved_amount' => 0,
            'is_completed' => false,
        ])->assertOk()
            ->assertJsonPath('data.description', null)
            ->assertJsonPath('data.url', null)
            ->assertJsonPath('data.tags', [])
            ->assertJsonPath('data.target_amount', 0)
            ->assertJsonPath('data.saved_amount', 0)
            ->assertJsonPath('data.is_completed', false)
            ->assertJsonPath('data.title', $goal->title);

        $this->assertNull($goal->fresh()->author_name);
        $this->withToken($this->board->api_key)->patchJson('/api/goals/'.$goal->id, ['saved_amount' => null])
            ->assertOk()->assertJsonPath('data.saved_amount', 0);
    }

    public function test_changing_priority_appends_only_to_the_selected_boards_column(): void
    {
        $goal = $this->createGoal(['position' => 3]);
        $sourceNeighbour = $this->createGoal(['position' => 4]);
        $destination = $this->createGoal(['priority' => 1, 'position' => 7]);
        $archive = $this->createGoal(['priority' => 4, 'position' => 2]);
        $otherBoard = Board::create(['name' => 'Other board', 'pin_code' => '9876']);
        $otherGoal = $this->createGoal(['board_id' => $otherBoard->id, 'priority' => 1, 'position' => 20]);

        $this->withToken($this->board->api_key)->patchJson('/api/goals/'.$goal->id, ['priority' => 1])
            ->assertOk()->assertJsonPath('data.position', 8)->assertJsonPath('data.priority', 1);
        $this->withToken($this->board->api_key)->patchJson('/api/goals/'.$goal->id, ['priority' => '1'])
            ->assertOk()->assertJsonPath('data.position', 8);
        $this->withToken($this->board->api_key)->patchJson('/api/goals/'.$goal->id, ['priority' => 4])
            ->assertOk()->assertJsonPath('data.position', 3)->assertJsonPath('data.priority', 4);

        $this->assertSame(4, $sourceNeighbour->fresh()->position);
        $this->assertSame(7, $destination->fresh()->position);
        $this->assertSame(2, $archive->fresh()->position);
        $this->assertSame(20, $otherGoal->fresh()->position);
    }

    public function test_missing_invalid_and_revoked_keys_cannot_update_a_goal(): void
    {
        $goal = $this->createGoal();
        $oldKey = $this->board->api_key;
        $this->board->resetApiKey();

        foreach (['', 'Bearer invalid', 'Basic '.$this->board->api_key, 'Bearer lvl_'.str_repeat('x', 64), 'Bearer '.$oldKey] as $authorization) {
            $this->withHeader('Authorization', $authorization)->patchJson('/api/goals/'.$goal->id, ['title' => 'Unauthorized'])
                ->assertUnauthorized()->assertHeader('WWW-Authenticate', 'Bearer');
        }

        $this->assertSame('Existing goal', $goal->fresh()->title);
        $this->withToken($this->board->api_key)->patchJson('/api/goals/'.$goal->id, ['title' => 'New key works'])->assertOk();
    }

    public function test_a_pin_session_and_keys_in_the_query_or_body_do_not_authorize_updates(): void
    {
        $goal = $this->createGoal();

        $this->withSession(['board_id' => $this->board->id, 'admin_ok' => true])
            ->patchJson('/api/goals/'.$goal->id.'?api_key='.$this->board->api_key, [
                'api_key' => $this->board->api_key,
                'title' => 'Unauthorized',
            ])->assertUnauthorized();

        $this->assertSame('Existing goal', $goal->fresh()->title);
    }

    public function test_foreign_and_missing_goals_return_the_same_not_found_response(): void
    {
        $otherBoard = Board::create(['name' => 'Other board', 'pin_code' => '9876']);
        $otherGoal = $this->createGoal(['board_id' => $otherBoard->id, 'is_public' => true]);

        foreach ([$otherGoal->id, $otherGoal->id + 1] as $id) {
            $this->withSession(['board_id' => $otherBoard->id, 'admin_ok' => true])
                ->withToken($this->board->api_key)
                ->patchJson('/api/goals/'.$id, ['title' => 'Forbidden update'])
                ->assertNotFound()->assertJsonPath('message', 'Цель не найдена.');
        }

        $this->assertSame('Existing goal', $otherGoal->fresh()->title);
        $this->assertDatabaseCount('goals', 1);
        $this->withSession(['board_id' => $this->board->id])->withToken($otherBoard->api_key)
            ->patchJson('/api/goals/'.$otherGoal->id, ['title' => 'Allowed update'])
            ->assertOk()->assertJsonPath('data.board_id', $otherBoard->id);
    }

    public function test_internal_fields_cannot_be_changed(): void
    {
        $goal = $this->createGoal(['is_public' => true, 'public_token' => 'keep-public-token']);
        $before = $goal->getAttributes();
        $otherBoard = Board::create(['name' => 'Other board', 'pin_code' => '9876']);

        $this->withToken($this->board->api_key)->patchJson('/api/goals/'.$goal->id, [
            'title' => 'Forged goal',
            'board_id' => $otherBoard->id,
            'position' => 99,
            'is_public' => false,
            'public_token' => 'forged-token',
        ])->assertUnprocessable()->assertJsonValidationErrors(['board_id', 'position', 'is_public', 'public_token']);

        $this->assertSame($before, $goal->fresh()->getAttributes());

        foreach ([null, '', [], 0, false] as $value) {
            $this->withToken($this->board->api_key)->patchJson('/api/goals/'.$goal->id, [
                'board_id' => $value,
                'position' => $value,
                'is_public' => $value,
                'public_token' => $value,
            ])->assertUnprocessable()->assertJsonValidationErrors(['board_id', 'position', 'is_public', 'public_token']);

            $this->assertSame($before, $goal->fresh()->getAttributes());
        }

        $this->withToken($this->board->api_key)->patchJson('/api/goals/'.$goal->id, [
            'title' => 'Allowed title',
            'id' => 123456,
            'author_name' => 'Forged tag',
            'subgoals' => [['title' => 'Forged subgoal']],
        ])->assertOk()->assertJsonPath('data.id', $goal->id)->assertJsonPath('data.tags', []);

        $this->assertNull($goal->fresh()->subgoals);
        $this->assertSame('keep-public-token', $goal->fresh()->public_token);
    }

    public function test_invalid_updates_are_atomic_and_do_not_change_column_order(): void
    {
        $goal = $this->createGoal();
        $before = $goal->getAttributes();
        $destination = $this->createGoal(['priority' => 1, 'position' => 1]);
        $cases = [
            [['title' => '   '], 'title'],
            [['title' => null], 'title'],
            [['title' => str_repeat('a', 256)], 'title'],
            [['description' => []], 'description'],
            [['description_format' => 'bbcode'], 'description_format'],
            [['description_format' => null], 'description_format'],
            [['url' => []], 'url'],
            [['priority' => 5], 'priority'],
            [['priority' => null], 'priority'],
            [['target_amount' => -1], 'target_amount'],
            [['saved_amount' => -1], 'saved_amount'],
            [['target_amount' => 'NaN'], 'target_amount'],
            [['saved_amount' => 10000000000], 'saved_amount'],
            [['target_amount' => 1.001], 'target_amount'],
            [['saved_amount' => 1.001], 'saved_amount'],
            [['is_completed' => 'yes'], 'is_completed'],
            [['is_completed' => null], 'is_completed'],
            [['tags' => null], 'tags'],
            [['tags' => 'IVAN'], 'tags'],
            [['tags' => ['name' => 'IVAN']], 'tags'],
            [['tags' => [[]]], 'tags.0'],
            [['tags' => ['one,two']], 'tags.0'],
            [['tags' => [str_repeat('a', 128), str_repeat('b', 128)]], 'tags'],
        ];

        foreach ($cases as [$data, $field]) {
            $this->withToken($this->board->api_key)->patchJson('/api/goals/'.$goal->id, array_merge(['priority' => 1], $data))
                ->assertUnprocessable()->assertJsonValidationErrors($field);
            $this->assertSame($before, $goal->fresh()->getAttributes());
            $this->assertSame(1, $destination->fresh()->position);
        }
    }

    public function test_tag_updates_replace_the_list_and_check_length_after_deduplication(): void
    {
        $goal = $this->createGoal(['author_name' => 'Old tag']);
        $tag = str_repeat('Я', 200);

        $this->withToken($this->board->api_key)->patchJson('/api/goals/'.$goal->id, ['tags' => [$tag, mb_strtolower($tag)]])
            ->assertOk()->assertJsonPath('data.tags', [$tag]);
    }

    public function test_update_errors_are_json_even_when_html_is_requested(): void
    {
        $goal = $this->createGoal();

        $this->withHeader('Accept', 'text/html')->patch('/api/goals/'.$goal->id, ['title' => 'Unauthorized'])
            ->assertUnauthorized()->assertHeader('Content-Type', 'application/json');
        $this->withToken($this->board->api_key)->patch('/api/goals/'.$goal->id, ['title' => ''])
            ->assertUnprocessable()->assertHeader('Content-Type', 'application/json')->assertJsonValidationErrors('title');
        $this->patch('/api/goals/'.($goal->id + 1), ['title' => 'Missing'])
            ->assertNotFound()->assertHeader('Content-Type', 'application/json')->assertJsonPath('message', 'Цель не найдена.');
        $this->patch('/api/goals/not-an-id', ['title' => 'Missing'])
            ->assertNotFound()->assertHeader('Content-Type', 'application/json');
    }

    private function createGoal(array $attributes = []): Goal
    {
        return Goal::create(array_merge([
            'board_id' => $this->board->id,
            'title' => 'Existing goal',
            'priority' => 2,
            'position' => 1,
        ], $attributes))->refresh();
    }
}
