<?php

namespace Tests\Feature;

use App\Models\Goal;
use Tests\TestCase;

class GoalCardTest extends TestCase
{
    public function test_zero_decimal_strings_do_not_render_financial_fields(): void
    {
        $goal = new Goal([
            'title' => 'Goal without amounts',
            'target_amount' => '0.00',
            'saved_amount' => '0.00',
            'priority' => 1,
            'position' => 1,
            'is_completed' => false,
            'is_public' => false,
        ]);
        $goal->id = 1;

        $html = view('partials.goal-card', [
            'goal' => $goal,
            'dragEnabled' => true,
        ])->render();

        $this->assertStringNotContainsString('Цель:', $html);
        $this->assertStringNotContainsString('Достигнуто:', $html);
        $this->assertStringNotContainsString('0%', $html);
    }
}
