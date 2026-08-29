<?php

namespace App\Console\Commands;

use App\Models\Board;
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
    protected $description = 'Импортирует доски и цели из JSON-файла';

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
            $this->error('Неверный формат JSON.');

            return self::FAILURE;
        }

        $version = $data['version'] ?? null;
        $boards = $data['boards'] ?? null;
        $goals = $data['goals'] ?? null;

        if ($version !== 2 || ! is_array($boards) || ! array_is_list($boards) || ! is_array($goals) || ! array_is_list($goals)) {
            $this->error('Неверный формат экспорта: ожидается версия 2 с массивами boards и goals.');

            return self::FAILURE;
        }

        $truncate = (bool) $this->option('truncate');

        try {
            DB::transaction(function () use ($boards, $goals, $truncate) {
                if ($truncate) {
                    $this->warn('Очищаю таблицу goals...');
                    Goal::query()->delete();
                }

                $boardMap = $this->importBoards($boards);
                $count = 0;

                foreach ($goals as $item) {
                    if (! is_array($item)) {
                        throw new \UnexpectedValueException('Некорректная запись цели в файле экспорта.');
                    }

                    $sourceBoardId = isset($item['board_id']) ? (int) $item['board_id'] : null;
                    $goalBoardId = $this->resolveBoardId($sourceBoardId, $boardMap);

                    $payload = [
                        'board_id' => $goalBoardId,
                        'title' => $item['title'] ?? 'Без названия',
                        'url' => $item['url'] ?? null,
                        'description' => $item['description'] ?? '',
                        'description_format' => isset($item['description_format']) && in_array($item['description_format'], Goal::DESCRIPTION_FORMATS, true)
                            ? $item['description_format']
                            : 'raw',
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

    /**
     * @param  array<int, mixed>  $boards
     * @return array<int, int>
     */
    private function importBoards(array $boards): array
    {
        $boardMap = [];

        foreach ($boards as $item) {
            if (! is_array($item)) {
                throw new \UnexpectedValueException('Некорректная запись доски в файле экспорта.');
            }

            $sourceId = isset($item['id']) ? (int) $item['id'] : 0;
            $name = isset($item['name']) ? trim((string) $item['name']) : '';
            $pinCode = isset($item['pin_code']) ? (string) $item['pin_code'] : '';

            if ($sourceId <= 0 || $name === '' || ! preg_match('/^\d{4}$/', $pinCode)) {
                throw new \UnexpectedValueException("Некорректные данные доски с исходным ID {$sourceId}.");
            }

            $board = Board::query()->firstOrCreate(
                ['pin_code' => $pinCode],
                ['name' => $name],
            );

            $boardMap[$sourceId] = $board->id;
        }

        return $boardMap;
    }

    /**
     * @param  array<int, int>  $boardMap
     */
    private function resolveBoardId(?int $sourceBoardId, array $boardMap): int
    {
        if ($sourceBoardId && isset($boardMap[$sourceBoardId])) {
            return $boardMap[$sourceBoardId];
        }

        throw new \UnexpectedValueException("Для цели не найдена доска с исходным ID {$sourceBoardId}.");
    }
}
