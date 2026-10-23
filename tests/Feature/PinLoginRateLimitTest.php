<?php

namespace Tests\Feature;

use App\Models\Board;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PinLoginRateLimitTest extends TestCase
{
    use RefreshDatabase;

    private Board $board;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeTime();
        Cache::flush();
        $this->board = Board::query()->firstOrFail();
        $this->board->update(['pin_code' => '1234']);
    }

    public function test_fifth_failure_blocks_even_a_correct_pin_and_disables_the_form(): void
    {
        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->from(route('pin.form'))->post(route('pin.check'), ['pin' => '0000'])
                ->assertSessionHasErrors('pin')
                ->assertSessionMissing('board_id');
        }

        $this->get(route('pin.form'))->assertViewHas('retryAfter', 0);
        $this->failAttempts(1);
        $this->get(route('pin.form'))
            ->assertViewHas('retryAfter', 120)
            ->assertSee('data-pin-cooldown', false)
            ->assertSee('02:00');

        $this->post(route('pin.check'), ['pin' => '1234'])
            ->assertRedirect(route('pin.form'))
            ->assertSessionMissing('board_id');
    }

    public function test_two_minute_pause_starts_at_the_fifth_failure(): void
    {
        $this->failAttempts(4);
        $this->travel(10)->minutes();
        $this->failAttempts(1);
        $this->get(route('pin.form'))->assertViewHas('retryAfter', 120);

        $this->travel(119)->seconds();
        $this->post(route('pin.check'), ['pin' => '1234'])
            ->assertRedirect(route('pin.form'))
            ->assertSessionMissing('board_id');
        $this->get(route('pin.form'))->assertViewHas('retryAfter', 1);

        $this->travel(1)->seconds();
        $this->post(route('pin.check'), ['pin' => '1234'])
            ->assertRedirect('/')
            ->assertSessionHas('board_id', $this->board->id);
    }

    public function test_successful_login_clears_consecutive_failures(): void
    {
        $this->failAttempts(4);
        $this->post(route('pin.check'), ['pin' => '1234'])->assertSessionHas('board_id', $this->board->id);
        $this->post(route('pin.logout'));

        $this->failAttempts(4);
        $this->get(route('pin.form'))->assertViewHas('retryAfter', 0);
        $this->post(route('pin.check'), ['pin' => '1234'])->assertSessionHas('board_id', $this->board->id);
    }

    public function test_requests_during_the_pause_do_not_extend_it(): void
    {
        $this->failAttempts();
        $this->travel(60)->seconds();
        $this->failAttempts(2);
        $this->get(route('pin.form'))->assertViewHas('retryAfter', 60);

        $this->travel(60)->seconds();
        $this->post(route('pin.check'), ['pin' => '1234'])->assertSessionHas('board_id', $this->board->id);
    }

    public function test_attempts_start_over_after_the_pause(): void
    {
        $this->failAttempts();
        $this->travel(120)->seconds();
        $this->failAttempts(4);
        $this->get(route('pin.form'))->assertViewHas('retryAfter', 0);
        $this->failAttempts(1);
        $this->get(route('pin.form'))->assertViewHas('retryAfter', 120);
    }

    public function test_one_ip_does_not_block_another_ip(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10']);
        $this->failAttempts();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.11']);
        $this->get(route('pin.form'))->assertViewHas('retryAfter', 0);
        $this->post(route('pin.check'), ['pin' => '1234'])->assertSessionHas('board_id', $this->board->id);
        $this->post(route('pin.logout'));

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10']);
        $this->get(route('pin.form'))->assertViewHas('retryAfter', 120);
    }

    public function test_clearing_the_session_does_not_bypass_the_pause(): void
    {
        $this->failAttempts();
        $this->session([]);
        session()->flush();

        $this->post(route('pin.check'), ['pin' => '1234'])
            ->assertRedirect(route('pin.form'))
            ->assertSessionMissing('board_id');
        $this->get(route('pin.form'))->assertViewHas('retryAfter', 120);
    }

    public function test_admin_login_is_also_blocked_after_five_failures(): void
    {
        $adminPin = (string) env('ADMIN_PIN_CODE', env('PIN_CODE', '1011'));
        $this->failAttempts(5, 'admin.login.check');

        $this->post(route('admin.login.check'), ['pin' => $adminPin])
            ->assertRedirect(route('admin.login'))
            ->assertSessionMissing('admin_ok');
        $this->get(route('admin.login'))->assertViewHas('retryAfter', 120);

        $this->travel(120)->seconds();
        $this->post(route('admin.login.check'), ['pin' => $adminPin])
            ->assertRedirect(route('admin.boards'))
            ->assertSessionHas('admin_ok', true);
    }

    public function test_admin_success_resets_its_counter_and_board_login_has_a_separate_counter(): void
    {
        $adminPin = (string) env('ADMIN_PIN_CODE', env('PIN_CODE', '1011'));
        $this->failAttempts(4, 'admin.login.check');
        $this->post(route('admin.login.check'), ['pin' => $adminPin])->assertSessionHas('admin_ok', true);
        $this->post(route('admin.logout'));
        $this->failAttempts(4, 'admin.login.check');
        $this->get(route('admin.login'))->assertViewHas('retryAfter', 0);

        $this->failAttempts(1, 'admin.login.check');
        $this->get(route('pin.form'))->assertViewHas('retryAfter', 0);
        $this->post(route('pin.check'), ['pin' => '1234'])->assertSessionHas('board_id', $this->board->id);
    }

    public function test_limiter_works_with_the_database_cache_used_in_production(): void
    {
        config(['cache.default' => 'database']);
        Cache::flush();

        $this->failAttempts();
        $this->get(route('pin.form'))->assertViewHas('retryAfter', 120);
        $this->travel(120)->seconds();
        $this->post(route('pin.check'), ['pin' => '1234'])->assertSessionHas('board_id', $this->board->id);
    }

    private function failAttempts(int $count = 5, string $endpoint = 'pin.check'): void
    {
        $form = $endpoint === 'pin.check' ? 'pin.form' : 'admin.login';

        for ($attempt = 0; $attempt < $count; $attempt++) {
            $this->from(route($form))->post(route($endpoint), ['pin' => '0000'])
                ->assertRedirect(route($form));
        }
    }
}
