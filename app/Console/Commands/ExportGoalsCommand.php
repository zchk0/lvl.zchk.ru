<?php

namespace App\Console\Commands;

use App\Models\Goal;
use Illuminate\Console\Command;

class ExportGoalsCommand extends Command
{
    /**
     * Имя и сигнатура консольной команды.
     *
     * Пример:
     *  php artisan goals:export
     *  php artisan goals:export storage/app/custom_goals.json
     *
     * @var string
     */
    protected $signature = 'goals:export
        {path? : Путь к JSON-файлу (по умолчанию storage/app/goals_export.json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Экспортирует все записи модели Goal в JSON-файл';

    /**
     * Выполнение команды.
     */
    public function handle(): int
    {
        $pathArg = $this->argument('path');
        $defaultPath = storage_path('app/goals_export.json');

        // Если передан относительный путь — считаем его от корня проекта
        $path = $pathArg ? base_path($pathArg) : $defaultPath;

        $this->info('Экспорт целей...');
        $goals = Goal::orderBy('priority')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        // Явно выбираем поля, чтобы не тащить всякий мусор
        $data = $goals->map(function (Goal $goal) {
            return [
                'title' => $goal->title,
                'url' => $goal->url,
                'description' => $goal->description ?? '',
                'subgoals' => is_array($goal->subgoals) ? $goal->subgoals : [],
                'author_name' => $goal->author_name,
                'target_amount' => (float) $goal->target_amount,
                'saved_amount' => (float) $goal->saved_amount,
                'priority' => (int) $goal->priority,
                'is_completed' => (bool) $goal->is_completed,
                'is_public' => (bool) $goal->is_public,
                'public_token' => $goal->public_token,
                'position' => (int) $goal->position,
                'created_at' => $goal->created_at,
            ];
        })->all();

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            $this->error('Не удалось закодировать данные в JSON: '.json_last_error_msg());

            return self::FAILURE;
        }

        $dir = dirname($path);
        if (! is_dir($dir)) {
            if (! mkdir($dir, 0775, true) && ! is_dir($dir)) {
                $this->error("Не удалось создать директорию: {$dir}");

                return self::FAILURE;
            }
        }

        if (file_put_contents($path, $json) === false) {
            $this->error("Не удалось записать файл: {$path}");

            return self::FAILURE;
        }

        $this->info('Экспортировано '.count($data).' целей.');
        $this->info("Файл: {$path}");

        return self::SUCCESS;
    }
}
