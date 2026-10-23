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
            ->assertSee('Изменение цели')
            ->assertSee('PATCH')
            ->assertSee('Получение целей')
            ->assertSee('GET')
            ->assertSee('API_KEY')
            ->assertDontSee($board->api_key)
            ->assertDontSee($board->api_key_hash);
    }

    public function test_the_documented_json_request_is_accepted_by_the_api(): void
    {
        $html = $this->get(route('api.docs'))->assertOk()->getContent();
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
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

    public function test_the_documented_partial_update_preserves_other_fields(): void
    {
        $html = $this->get(route('api.docs'))->assertOk()->getContent();
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $createCode = $xpath->query('//*[@id="request-body-example"]//code')->item(0);
        $updateCode = $xpath->query('//*[@id="update-request-body-example"]//code')->item(0);
        $this->assertNotNull($createCode);
        $this->assertNotNull($updateCode);
        $createPayload = json_decode($createCode->textContent, true, flags: JSON_THROW_ON_ERROR);
        $updatePayload = json_decode($updateCode->textContent, true, flags: JSON_THROW_ON_ERROR);
        $board = Board::query()->firstOrFail();
        $id = $this->withToken($board->api_key)->postJson('/api/goals', $createPayload)->assertCreated()->json('data.id');

        $this->patchJson('/api/goals/' . $id, $updatePayload)
            ->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.title', $updatePayload['title'])
            ->assertJsonPath('data.tags', $updatePayload['tags'])
            ->assertJsonPath('data.saved_amount', $updatePayload['saved_amount'])
            ->assertJsonPath('data.description', $createPayload['description'])
            ->assertJsonPath('data.target_amount', $createPayload['target_amount'])
            ->assertJsonPath('data.priority', $createPayload['priority']);
    }

    public function test_the_documented_list_url_filters_the_created_goal(): void
    {
        $html = $this->get(route('api.docs'))->assertOk()->getContent();
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $createCode = $xpath->query('//*[@id="request-body-example"]//code')->item(0);
        $listCode = $xpath->query('//*[@id="list-javascript-panel"]//code')->item(0);
        $this->assertNotNull($createCode);
        $this->assertNotNull($listCode);
        $payload = json_decode($createCode->textContent, true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(1, preg_match('/fetch\("([^"]+)"/', $listCode->textContent, $matches));
        $board = Board::query()->firstOrFail();
        $id = $this->withToken($board->api_key)->postJson('/api/goals', $payload)->assertCreated()->json('data.id');
        $this->postJson('/api/goals', [...$payload, 'is_completed' => true])->assertCreated();
        $this->postJson('/api/goals', [...$payload, 'tags' => ['Other']])->assertCreated();

        $this->getJson($matches[1])->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.per_page', 20);
    }
}
