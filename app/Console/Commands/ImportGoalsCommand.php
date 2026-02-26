<?php

namespace App\Console\Commands;

use App\Models\Goal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportGoalsCommand extends Command
{
    /**
     * Имя и сигнатура команды
     *
     * Примеры:
     *  php artisan goals:import
     *  php artisan goals:import storage/app/goals_export.json
     *  php artisan goals:import --truncate
     *
     * @var string
     */
    protected $signature = 'goals:import
        {path? : Путь к JSON-файлу (по умолчанию storage/app/goals_export.json)}
        {--truncate : Очистить таблицу goals перед импортом}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Импортирует данные в модель Goal из JSON-файла';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pathArg = $this->argument('path');
        $defaultPath = storage_path('app/goals_export.json');
        $path = $pathArg ? base_path($pathArg) : $defaultPath;
        if (! is_file($path)) {
            $this->error("Файл не найден: {$path}");

            return self::FAILURE;
        }

        $this->info("Читаю файл: {$path}");
        $contents = file_get_contents($path);
        if ($contents === false) {
            $this->error("Не удалось прочитать файл: {$path}");

            return self::FAILURE;
        }

        $data = json_decode($contents, true);
        if (! is_array($data)) {
            $this->error('Неверный формат JSON: ожидается массив объектов.');

            return self::FAILURE;
        }

        $truncate = (bool) $this->option('truncate');

        try {
            DB::transaction(function () use ($data, $truncate) {
                if ($truncate) {
                    $this->warn('Очищаю таблицу goals (TRUNCATE)...');
                    Goal::truncate();
                }

                $count = 0;

                foreach ($data as $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $payload = [
                        'title' => $item['title'] ?? 'Без названия',
                        'url' => $item['url'] ?? null,
                        'description' => $item['description'] ?? '',
                        'subgoals' => isset($item['subgoals']) && is_array($item['subgoals']) ? $item['subgoals'] : [],
                        'author_name' => $item['author_name'] ?? null,
                        'target_amount' => isset($item['target_amount']) ? (float) $item['target_amount'] : 0,
                        'saved_amount' => isset($item['saved_amount']) ? (float) $item['saved_amount'] : 0,
                        'priority' => isset($item['priority']) ? (int) $item['priority'] : 2,
                        'is_completed' => isset($item['is_completed']) ? (bool) $item['is_completed'] : false,
                        'is_public' => isset($item['is_public']) ? (bool) $item['is_public'] : false,
                        'public_token' => isset($item['public_token']) && $item['public_token'] !== '' ? $item['public_token'] : null,
                        'position' => isset($item['position']) ? (int) $item['position'] : 0,
                        'created_at' => isset($item['created_at']) ? $item['created_at'] : null,
                    ];

                    Goal::create($payload);
                    $count++;
                }

                $this->info("Импортировано записей: {$count}");
            });
        } catch (\Throwable $e) {
            $this->error('Ошибка при импорте: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
