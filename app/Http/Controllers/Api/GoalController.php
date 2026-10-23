<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateGoal;
use App\Actions\ListGoals;
use App\Actions\UpdateGoal;
use App\Http\Controllers\Controller;
use App\Http\Requests\IndexGoalRequest;
use App\Http\Requests\StoreGoalRequest;
use App\Http\Requests\UpdateGoalRequest;
use App\Http\Resources\GoalResource;
use App\Models\Goal;
use Illuminate\Http\JsonResponse;

class GoalController extends Controller
{
    public function index(IndexGoalRequest $request, ListGoals $listGoals): JsonResponse
    {
        $goals = $listGoals->handle(
            $request->attributes->get('api_board')->id,
            $request->validated()
        );

        return GoalResource::collection($goals)->response();
    }

    public function store(StoreGoalRequest $request, CreateGoal $createGoal): JsonResponse
    {
        $data = $request->validated();
        $tags = Goal::parseAuthorTags(implode(', ', $data['tags'] ?? []));
        $goal = $createGoal->handle($request->attributes->get('api_board')->id, [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'description_format' => $data['description_format'] ?? 'raw',
            'url' => $data['url'] ?? null,
            'author_name' => $tags ? implode(', ', $tags) : null,
            'target_amount' => $data['target_amount'] ?? 0,
            'saved_amount' => $data['saved_amount'] ?? 0,
            'priority' => $data['priority'] ?? 2,
            'is_completed' => $data['is_completed'] ?? false,
        ]);

        return (new GoalResource($goal))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateGoalRequest $request, string $goal, UpdateGoal $updateGoal): JsonResponse
    {
        $data = $request->validated();
        if (array_key_exists('tags', $data)) {
            $tags = Goal::parseAuthorTags(implode(', ', $data['tags']));
            $data['author_name'] = $tags ? implode(', ', $tags) : null;
            unset($data['tags']);
        }

        foreach (['target_amount', 'saved_amount'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] ??= 0;
            }
        }

        $updated = $updateGoal->handle(
            $request->attributes->get('api_board')->id,
            $goal,
            $data
        );

        return (new GoalResource($updated))->response();
    }
}
