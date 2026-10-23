<?php

namespace Tests\Feature;

use App\Livewire\GoalsBoard;
use App\Models\Board;
use App\Models\Goal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ApiGoalCreationTest extends TestCase
{
    use RefreshDatabase;

    private Board $board;

    protected function setUp(): void
    {
        parent::setUp();

        $this->board = Board::query()->firstOrFail();
    }

    public function test_existing_board_key_creates_a_goal_without_a_pin_session(): void
    {
        $response = $this->withToken($this->board->api_key)->postJson('/api/goals', ['title' => 'API goal']);

        $response->assertCreated()
            ->assertJsonPath('data.board_id', $this->board->id)
            ->assertJsonPath('data.title', 'API goal')
            ->assertJsonPath('data.priority', 2)
            ->assertJsonPath('data.position', 1)
            ->assertJsonPath('data.description', null)
            ->assertJsonPath('data.description_format', 'raw')
            ->assertJsonPath('data.url', null)
            ->assertJsonPath('data.tags', [])
            ->assertJsonPath('data.target_amount', 0)
            ->assertJsonPath('data.saved_amount', 0)
            ->assertJsonPath('data.is_completed', false)
            ->assertJsonPath('data.is_public', false)
            ->assertJsonStructure(['data' => ['id', 'created_at', 'updated_at']]);

        $this->assertDatabaseHas('goals', ['id' => $response->json('data.id'), 'board_id' => $this->board->id]);
    }

    public function test_missing_invalid_and_non_bearer_keys_are_rejected(): void
    {
        foreach (['', 'Bearer invalid', 'Basic '.$this->board->api_key, 'Bearer lvl_'.str_repeat('x', 64)] as $authorization) {
            $this->withHeader('Authorization', $authorization)->postJson('/api/goals', ['title' => 'Unauthorized'])
                ->assertUnauthorized()
                ->assertHeader('WWW-Authenticate', 'Bearer');
        }

        $this->assertDatabaseCount('goals', 0);
    }

    public function test_session_cookies_and_keys_in_the_query_or_body_do_not_authorize_api_requests(): void
    {
        $this->withSession(['board_id' => $this->board->id, 'admin_ok' => true])
            ->postJson('/api/goals?api_key='.$this->board->api_key, [
                'title' => 'Unauthorized',
                'api_key' => $this->board->api_key,
            ])->assertUnauthorized();

        $this->assertDatabaseCount('goals', 0);
    }

    public function test_all_supported_fields_and_tags_are_saved_and_returned(): void
    {
        $url = 'https://example.com/'.str_repeat('long-segment/', 30);

        $response = $this->withToken($this->board->api_key)->postJson('/api/goals', [
            'title' => 'Complete API goal',
            'description' => '**Описание**',
            'description_format' => 'markdown',
            'url' => $url,
            'tags' => ['IVAN', 'ivan', 'Срочно', ' Анна   Иванова '],
            'target_amount' => 1234.50,
            'saved_amount' => 250.25,
            'priority' => 1,
            'is_completed' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.description', '**Описание**')
            ->assertJsonPath('data.description_format', 'markdown')
            ->assertJsonPath('data.url', $url)
            ->assertJsonPath('data.tags', ['IVAN', 'Срочно', 'Анна Иванова'])
            ->assertJsonPath('data.target_amount', 1234.5)
            ->assertJsonPath('data.saved_amount', 250.25)
            ->assertJsonPath('data.priority', 1)
            ->assertJsonPath('data.is_completed', true);

        $this->assertDatabaseHas('goals', [
            'id' => $response->json('data.id'),
            'author_name' => 'IVAN, Срочно, Анна Иванова',
            'url' => $url,
        ]);
    }

    public function test_new_goal_is_inserted_first_only_in_its_board_and_priority(): void
    {
        $first = $this->createGoal(['position' => 1]);
        $second = $this->createGoal(['position' => 2]);
        $otherPriority = $this->createGoal(['priority' => 1]);
        $otherBoard = Board::create(['name' => 'Other board', 'pin_code' => '9876']);
        $otherGoal = $this->createGoal(['board_id' => $otherBoard->id]);

        $this->withToken($this->board->api_key)->postJson('/api/goals', ['title' => 'Inserted first'])
            ->assertCreated()->assertJsonPath('data.position', 1);

        $this->assertSame(2, $first->fresh()->position);
        $this->assertSame(3, $second->fresh()->position);
        $this->assertSame(1, $otherPriority->fresh()->position);
        $this->assertSame(1, $otherGoal->fresh()->position);
    }

    public function test_api_key_selects_the_board_even_when_the_web_session_selects_another_board(): void
    {
        $other = Board::create(['name' => 'Other board', 'pin_code' => '9876']);

        $this->withSession(['board_id' => $this->board->id])
            ->withToken($other->api_key)
            ->postJson('/api/goals', ['title' => 'Other board goal'])
            ->assertCreated()
            ->assertJsonPath('data.board_id', $other->id);

        $this->assertSame(0, $this->board->goals()->count());
        $this->assertSame(1, $other->goals()->count());
    }

    public function test_board_position_and_public_access_cannot_be_set_by_the_client(): void
    {
        $other = Board::create(['name' => 'Other board', 'pin_code' => '9876']);

        $this->withToken($this->board->api_key)->postJson('/api/goals', [
            'title' => 'Forged attributes',
            'board_id' => $other->id,
            'position' => 100,
            'is_public' => true,
            'public_token' => 'forged-token',
        ])->assertUnprocessable()->assertJsonValidationErrors(['board_id', 'position', 'is_public', 'public_token']);

        $this->assertDatabaseCount('goals', 0);
    }

    public function test_invalid_data_is_rejected_without_creating_goals_or_changing_order(): void
    {
        $existing = $this->createGoal();
        $cases = [
            [[], 'title'],
            [['title' => '   '], 'title'],
            [['title' => str_repeat('a', 256)], 'title'],
            [['description' => []], 'description'],
            [['description_format' => 'bbcode'], 'description_format'],
            [['priority' => 5], 'priority'],
            [['target_amount' => -1], 'target_amount'],
            [['saved_amount' => -1], 'saved_amount'],
            [['target_amount' => 'NaN'], 'target_amount'],
            [['saved_amount' => 10000000000], 'saved_amount'],
            [['target_amount' => 1.001], 'target_amount'],
            [['saved_amount' => 1.001], 'saved_amount'],
            [['is_completed' => 'yes'], 'is_completed'],
            [['tags' => 'IVAN'], 'tags'],
            [['tags' => ['name' => 'IVAN']], 'tags'],
            [['tags' => [[]]], 'tags.0'],
            [['tags' => ['one,two']], 'tags.0'],
            [['tags' => [str_repeat('a', 128), str_repeat('b', 128)]], 'tags'],
        ];

        foreach ($cases as [$data, $field]) {
            $payload = $data === [] ? [] : array_merge(['title' => 'Invalid goal'], $data);
            $this->withToken($this->board->api_key)->postJson('/api/goals', $payload)
                ->assertUnprocessable()->assertJsonValidationErrors($field);
        }

        $this->assertDatabaseCount('goals', 1);
        $this->assertSame(1, $existing->fresh()->position);
    }

    public function test_tag_length_is_checked_after_removing_case_insensitive_duplicates(): void
    {
        $tag = str_repeat('Я', 200);

        $this->withToken($this->board->api_key)->postJson('/api/goals', [
            'title' => 'Deduplicated tags',
            'tags' => [$tag, mb_strtolower($tag)],
        ])->assertCreated()->assertJsonPath('data.tags', [$tag]);
    }

    public function test_reset_immediately_revokes_the_previous_key(): void
    {
        $oldKey = $this->board->api_key;
        $this->board->resetApiKey();

        $this->withToken($oldKey)->postJson('/api/goals', ['title' => 'Revoked key'])->assertUnauthorized();
        $this->withToken($this->board->api_key)->postJson('/api/goals', ['title' => 'New key'])
            ->assertCreated()->assertJsonPath('data.board_id', $this->board->id);

        $this->assertDatabaseCount('goals', 1);
    }

    public function test_api_errors_are_json_even_when_the_client_requests_html(): void
    {
        $this->withHeader('Accept', 'text/html')->post('/api/goals', [])
            ->assertUnauthorized()->assertHeader('Content-Type', 'application/json');

        $this->withToken($this->board->api_key)->post('/api/goals', [])
            ->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonValidationErrors('title');
    }

    public function test_response_does_not_expose_credentials_or_internal_tag_storage(): void
    {
        $response = $this->withToken($this->board->api_key)->postJson('/api/goals', [
            'title' => 'Safe response',
            'tags' => ['IVAN'],
            'id' => 123456,
        ])->assertCreated();

        foreach (['api_key', 'api_key_hash', 'pin_code', 'public_token', 'author_name'] as $field) {
            $this->assertArrayNotHasKey($field, $response->json('data'));
        }

        $response->assertDontSee($this->board->api_key);
        $this->assertNotSame(123456, $response->json('data.id'));
        $this->assertArrayNotHasKey('api_key_hash', $this->board->toArray());
    }

    public function test_web_form_keeps_the_same_insertion_behavior_as_the_api(): void
    {
        $existing = $this->createGoal();
        $this->withSession(['board_id' => $this->board->id]);

        Livewire::test(GoalsBoard::class)
            ->call('openCreate', 2)
            ->set('title', 'Web goal')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Goal::where('title', 'Web goal')->firstOrFail()->position);
        $this->assertSame(2, $existing->fresh()->position);
    }

    private function createGoal(array $attributes = []): Goal
    {
        return Goal::create(array_merge([
            'board_id' => $this->board->id,
            'title' => 'Existing goal',
            'priority' => 2,
            'position' => 1,
        ], $attributes));
    }
}
