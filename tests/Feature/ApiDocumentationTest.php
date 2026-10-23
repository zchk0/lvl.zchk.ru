<?php

namespace Tests\Feature;

use App\Models\Board;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_documentation_is_public_and_does_not_include_board_credentials(): void
    {
        $board = Board::query()->firstOrFail();

        $this->get(route('api.docs'))
            ->assertOk()
            ->assertSee('Документация API')
            ->assertSee('/api/goals')
            ->assertSee('API_KEY')
            ->assertDontSee($board->api_key)
            ->assertDontSee($board->api_key_hash);
    }

    public function test_the_documented_json_request_is_accepted_by_the_api(): void
    {
        $html = $this->get(route('api.docs'))->assertOk()->getContent();
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $code = (new DOMXPath($document))->query('//*[@id="request-body-example"]//code')->item(0);
        $this->assertNotNull($code);
        $payload = json_decode($code->textContent, true, flags: JSON_THROW_ON_ERROR);
        $board = Board::query()->firstOrFail();

        $this->withToken($board->api_key)->postJson('/api/goals', $payload)
            ->assertCreated()
            ->assertJsonPath('data.title', $payload['title'])
            ->assertJsonPath('data.tags', $payload['tags'])
            ->assertJsonPath('data.description_format', $payload['description_format'])
            ->assertJsonPath('data.target_amount', $payload['target_amount']);
    }
}
