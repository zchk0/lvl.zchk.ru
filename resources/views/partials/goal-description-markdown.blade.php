{!! Illuminate\Support\Str::markdown($description, [
    'renderer' => ['soft_break' => "<br>\n"],
    'table' => [
        'wrap' => [
            'enabled' => true,
            'attributes' => [
                'class' => 'goal-description-table',
                'tabindex' => '0',
                'role' => 'region',
                'aria-label' => 'Таблица в описании',
            ],
        ],
    ],
]) !!}
