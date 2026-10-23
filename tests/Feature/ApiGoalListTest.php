<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Goal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiGoalListTest extends TestCase
{
    use RefreshDatabase;

    private Board $board;

    protected function setUp(): void
    {
        parent::setUp();

        $this->board = Board::query()->firstOrFail();
    }

    public function test_only_the_key_boards_goals_are_returned_including_completed_and_archived_goals(): void
    {
        $active = $this->createGoal();
        $completed = $this->createGoal(['is_completed' => true]);
        $archived = $this->createGoal(['priority' => 4, 'is_public' => true]);
        $other = Board::create(['name' => 'Other board', 'pin_code' => '9876']);
        $foreign = $this->createGoal(['board_id' => $other->id, 'is_public' => true]);

        $this->withSession(['board_id' => $other->id, 'admin_ok' => true])->withToken($this->board->api_key)
            ->getJson('/api/goals')->assertOk()->assertJsonPath('meta.total', 3)
            ->assertJsonPath('data.0.id', $active->id)
            ->assertJsonPath('data.1.id', $completed->id)
            ->assertJsonPath('data.2.id', $archived->id);

        $this->getJson($this->url(['id' => $foreign->id]))->assertOk()->assertJsonPath('data', [])->assertJsonPath('meta.total', 0);
        $this->withToken($other->api_key)->getJson('/api/goals')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $foreign->id);
    }

    public function test_pagination_has_stable_order_totals_and_filter_preserving_links(): void
    {
        $first = $this->createGoal(['priority' => 1, 'position' => 2, 'author_name' => 'Работа']);
        $second = $this->createGoal(['priority' => 1, 'position' => 2, 'author_name' => 'Работа']);
        $third = $this->createGoal(['priority' => 1, 'position' => 3, 'author_name' => 'Работа']);
        $this->createGoal(['priority' => 2, 'position' => 1, 'author_name' => 'Работа']);
        $filters = ['priority' => 1, 'tags' => ['Работа'], 'per_page' => 2];

        $response = $this->withToken($this->board->api_key)->getJson($this->url($filters))->assertOk()
            ->assertJsonPath('data.0.id', $first->id)->assertJsonPath('data.1.id', $second->id)
            ->assertJsonPath('meta.current_page', 1)->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3)->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.from', 1)->assertJsonPath('meta.to', 2)
            ->assertJsonPath('links.prev', null);

        $next = $response->json('links.next');
        parse_str(parse_url($next, PHP_URL_QUERY), $nextFilters);
        $this->assertSame('2', $nextFilters['page']);
        $this->assertSame('1', $nextFilters['priority']);
        $this->assertSame(['Работа'], $nextFilters['tags']);
        $this->getJson($next)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $third->id)
            ->assertJsonPath('links.next', null)->assertJsonPath('meta.current_page', 2)->assertJsonPath('meta.total', 3);
        $this->getJson($this->url([...$filters, 'page' => 3]))->assertOk()->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 3)->assertJsonPath('meta.from', null)->assertJsonPath('meta.to', null);
    }

    public function test_default_page_size_and_maximum_page_size(): void
    {
        for ($i = 0; $i < 105; $i++) {
            $this->createGoal();
        }

        $this->withToken($this->board->api_key)->getJson('/api/goals')->assertOk()->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.per_page', 20)->assertJsonPath('meta.total', 105)->assertJsonPath('meta.last_page', 6);
        $this->getJson('/api/goals?per_page=100')->assertOk()->assertJsonCount(100, 'data')->assertJsonPath('meta.last_page', 2);
        $this->getJson('/api/goals?per_page=100&page=2')->assertOk()->assertJsonCount(5, 'data');
    }

    public function test_field_filters_combine_with_and_and_accept_false_and_zero(): void
    {
        $matching = $this->createGoal([
            'title' => 'Купить монитор', 'description' => 'Для работы', 'description_format' => 'markdown',
            'url' => 'https://example.com/monitor', 'priority' => 1, 'position' => 0,
            'target_amount' => 30000, 'saved_amount' => 0, 'author_name' => 'IVAN, Работа',
        ]);
        $this->createGoal(['title' => 'Купить монитор', 'priority' => 1, 'is_completed' => true]);
        $this->createGoal(['title' => 'Купить монитор', 'priority' => 1, 'is_public' => true]);
        $this->createGoal(['title' => 'Купить монитор', 'priority' => 2]);
        $filters = [
            'id' => $matching->id, 'title' => 'МОНИТОР', 'description' => 'РАБОТЫ',
            'description_format' => 'markdown', 'url' => 'EXAMPLE.COM', 'priority' => 1,
            'position' => 0, 'is_completed' => 'false', 'is_public' => '0',
            'target_amount' => '30000.00', 'saved_amount' => 0, 'tags' => ['ivan', 'работа'],
        ];

        $this->withToken($this->board->api_key)->getJson($this->url($filters))->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $matching->id)->assertJsonPath('meta.total', 1);
        $this->getJson($this->url([...$filters, 'priority' => 2]))->assertOk()->assertJsonPath('data', []);
    }

    public function test_boolean_filters_accept_url_booleans_and_numeric_values(): void
    {
        $open = $this->createGoal();
        $done = $this->createGoal(['is_completed' => true, 'is_public' => true]);

        foreach (['0' => $open->id, 'false' => $open->id, '1' => $done->id, 'true' => $done->id] as $value => $id) {
            $this->withToken($this->board->api_key)->getJson($this->url(['is_completed' => $value, 'is_public' => $value]))
                ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $id);
        }
    }

    public function test_search_covers_title_description_and_tags_without_escaping_the_board_scope(): void
    {
        $title = $this->createGoal(['title' => 'РАБОТА над проектом']);
        $description = $this->createGoal(['description' => 'Новая работа']);
        $tag = $this->createGoal(['author_name' => 'Работа']);
        $this->createGoal(['title' => 'Unrelated']);
        $other = Board::create(['name' => 'Other board', 'pin_code' => '9876']);
        $this->createGoal(['board_id' => $other->id, 'author_name' => 'Работа']);

        $this->withToken($this->board->api_key)->getJson($this->url(['q' => 'работа']))
            ->assertOk()->assertJsonPath('meta.total', 3)
            ->assertJsonPath('data.0.id', $title->id)->assertJsonPath('data.1.id', $description->id)->assertJsonPath('data.2.id', $tag->id);
        $this->getJson($this->url(['q' => 'работа', 'title' => 'проект']))->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_text_filters_treat_sql_wildcards_and_quotes_as_literal_text(): void
    {
        $literal = $this->createGoal(['title' => "Save 10%_! O'Reilly"]);
        $this->createGoal(['title' => 'Save 1000']);
        $this->createGoal(['title' => 'Other']);

        foreach ([['title' => '%_!'], ['q' => "O'Reilly"]] as $filters) {
            $this->withToken($this->board->api_key)->getJson($this->url($filters))->assertOk()
                ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $literal->id);
        }

        $this->getJson($this->url(['q' => "' OR 1=1 --"]))->assertOk()->assertJsonPath('data', []);
    }

    public function test_tag_filters_match_whole_normalized_tags_with_all_and_any_modes(): void
    {
        $both = $this->createGoal(['author_name' => 'IVAN, Работа']);
        $one = $this->createGoal(['author_name' => 'ivan']);
        $legacy = $this->createGoal(['author_name' => "работа; Анна   Иванова\nIVAN"]);
        $this->createGoal(['author_name' => 'IVANOV, Работать']);
        $this->createGoal(['author_name' => null]);

        $this->withToken($this->board->api_key)->getJson($this->url(['tags' => ['ivan', 'РАБОТА', 'Ivan']]))
            ->assertOk()->assertJsonPath('meta.total', 2)->assertJsonPath('data.0.id', $both->id)->assertJsonPath('data.1.id', $legacy->id);
        $this->getJson($this->url(['tags' => ['IVAN', 'работа'], 'tags_mode' => 'any']))
            ->assertOk()->assertJsonPath('meta.total', 3)->assertJsonPath('data.1.id', $one->id);
        $this->getJson($this->url(['tags' => [' Анна  Иванова ']]))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $legacy->id);
        $this->getJson($this->url(['tags' => ['Missing']]))->assertOk()->assertJsonPath('data', []);
    }

    public function test_amount_ranges_are_inclusive_and_combine_with_exact_values(): void
    {
        $zero = $this->createGoal(['target_amount' => 0, 'saved_amount' => 0]);
        $middle = $this->createGoal(['target_amount' => 100.25, 'saved_amount' => 25.50]);
        $upper = $this->createGoal(['target_amount' => 200, 'saved_amount' => 50]);
        $this->createGoal(['target_amount' => 201, 'saved_amount' => 51]);

        $this->withToken($this->board->api_key)->getJson($this->url([
            'target_amount_min' => 100.25, 'target_amount_max' => 200, 'saved_amount_min' => 25.50, 'saved_amount_max' => 50,
        ]))->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.id', $middle->id)->assertJsonPath('data.1.id', $upper->id);
        $this->getJson($this->url(['target_amount_max' => 0, 'saved_amount' => 0]))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $zero->id);
        $this->getJson($this->url(['target_amount' => 100.25]))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $middle->id);
    }

    public function test_date_filters_include_the_entire_boundary_days(): void
    {
        $start = $this->createGoal();
        $start->forceFill(['created_at' => '2026-10-01 00:00:00', 'updated_at' => '2026-10-10 00:00:00'])->save();
        $end = $this->createGoal();
        $end->forceFill(['created_at' => '2026-10-02 23:59:59', 'updated_at' => '2026-10-11 23:59:59'])->save();
        $outside = $this->createGoal();
        $outside->forceFill(['created_at' => '2026-10-03 00:00:00', 'updated_at' => '2026-10-12 00:00:00'])->save();

        $this->withToken($this->board->api_key)->getJson($this->url([
            'created_from' => '2026-10-01', 'created_to' => '2026-10-02', 'updated_from' => '2026-10-10', 'updated_to' => '2026-10-11',
        ]))->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.id', $start->id)->assertJsonPath('data.1.id', $end->id);
    }

    public function test_custom_sorting_has_a_stable_id_tiebreaker(): void
    {
        $first = $this->createGoal(['saved_amount' => 100]);
        $second = $this->createGoal(['saved_amount' => 100]);
        $low = $this->createGoal(['saved_amount' => 0]);

        $this->withToken($this->board->api_key)->getJson('/api/goals?sort=saved_amount&direction=desc&per_page=2')
            ->assertOk()->assertJsonPath('data.0.id', $first->id)->assertJsonPath('data.1.id', $second->id);
        $this->getJson('/api/goals?sort=saved_amount&direction=desc&per_page=2&page=2')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $low->id);
        $this->getJson('/api/goals?sort=id&direction=desc')->assertOk()->assertJsonPath('data.0.id', $low->id);
    }

    public function test_missing_invalid_and_revoked_keys_do_not_allow_listing(): void
    {
        $this->createGoal();
        $oldKey = $this->board->api_key;
        $this->board->resetApiKey();

        foreach (['', 'Bearer invalid', 'Basic '.$this->board->api_key, 'Bearer '.$oldKey] as $authorization) {
            $this->withHeader('Authorization', $authorization)->getJson('/api/goals')
                ->assertUnauthorized()->assertHeader('WWW-Authenticate', 'Bearer');
        }

        $this->withHeader('Authorization', '')->withSession(['board_id' => $this->board->id, 'admin_ok' => true])
            ->getJson('/api/goals?api_key='.$this->board->api_key)->assertUnauthorized();
        $this->withToken($this->board->api_key)->getJson('/api/goals')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_empty_board_returns_an_empty_paginated_collection(): void
    {
        $this->withToken($this->board->api_key)->getJson('/api/goals')->assertOk()->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0)->assertJsonPath('meta.last_page', 1)
            ->assertJsonPath('meta.current_page', 1)->assertJsonPath('links.next', null);
    }

    public function test_invalid_filters_are_rejected_as_json_without_changing_goals(): void
    {
        $goal = $this->createGoal();
        $before = $goal->getAttributes();
        $cases = [
            [['page' => 0], 'page'], [['page' => '1.5'], 'page'], [['page' => 2147483648], 'page'],
            [['per_page' => 0], 'per_page'], [['per_page' => 101], 'per_page'], [['per_page' => []], 'per_page'],
            [['id' => -1], 'id'], [['title' => []], 'title'], [['q' => ''], 'q'],
            [['priority' => 5], 'priority'], [['is_completed' => 'yes'], 'is_completed'], [['is_public' => '2'], 'is_public'],
            [['description_format' => 'bbcode'], 'description_format'], [['position' => -1], 'position'],
            [['tags' => 'IVAN'], 'tags'], [['tags' => ['name' => 'IVAN']], 'tags'],
            [['tags' => ['one,two']], 'tags.0'], [['tags' => [str_repeat('Я', 256)]], 'tags.0'],
            [['tags' => array_fill(0, 51, 'tag')], 'tags'], [['tags_mode' => 'none'], 'tags_mode'],
            [['target_amount_min' => -1], 'target_amount_min'], [['saved_amount_max' => 1.001], 'saved_amount_max'],
            [['target_amount' => 10000000000], 'target_amount'],
            [['target_amount_min' => 100, 'target_amount_max' => 99], 'target_amount_max'],
            [['saved_amount_min' => 100, 'saved_amount_max' => 99], 'saved_amount_max'],
            [['created_from' => '2026-02-30'], 'created_from'], [['updated_to' => 'yesterday'], 'updated_to'],
            [['created_from' => '2026-10-03', 'created_to' => '2026-10-02'], 'created_to'],
            [['updated_from' => '2026-10-03', 'updated_to' => '2026-10-02'], 'updated_to'],
            [['sort' => 'title desc; DROP TABLE goals'], 'sort'], [['direction' => 'sideways'], 'direction'],
            [['board_id' => $this->board->id], 'board_id'], [['board_id' => ''], 'board_id'],
        ];

        foreach ($cases as [$filters, $field]) {
            // PHP omits empty arrays from query strings, so provide an invalid nested value instead.
            $filters = array_map(static fn ($value) => $value === [] ? ['invalid'] : $value, $filters);
            $this->withToken($this->board->api_key)->withHeader('Accept', 'text/html')->get($this->url($filters))
                ->assertUnprocessable()->assertHeader('Content-Type', 'application/json')->assertJsonValidationErrors($field);
        }

        $this->assertSame($before, $goal->fresh()->getAttributes());
    }

    public function test_response_and_links_do_not_expose_credentials_or_unvalidated_query_parameters(): void
    {
        $goal = $this->createGoal(['author_name' => 'IVAN', 'is_public' => true, 'public_token' => 'private-public-token']);
        $this->createGoal();
        $before = $goal->getAttributes();
        $response = $this->withToken($this->board->api_key)->getJson($this->url([
            'per_page' => 1, 'api_key' => $this->board->api_key, 'unknown' => 'do-not-echo',
        ]))->assertOk()->assertJsonCount(1, 'data');

        foreach (['api_key', 'api_key_hash', 'pin_code', 'public_token', 'author_name', 'subgoals'] as $field) {
            $this->assertArrayNotHasKey($field, $response->json('data.0'));
        }

        $response->assertDontSee($this->board->api_key)->assertDontSee('private-public-token')->assertDontSee('do-not-echo');
        $this->assertSame($before, $goal->fresh()->getAttributes());
        $this->json('GET', '/api/goals', ['priority' => 4, 'board_id' => 999])
            ->assertOk()->assertJsonPath('meta.total', 2);
    }

    private function url(array $filters): string
    {
        return '/api/goals?'.http_build_query($filters, '', '&', PHP_QUERY_RFC3986);
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
