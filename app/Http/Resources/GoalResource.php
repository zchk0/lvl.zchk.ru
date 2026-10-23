<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'board_id' => (int) $this->board_id,
            'title' => $this->title,
            'description' => $this->description,
            'description_format' => $this->description_format,
            'url' => $this->url,
            'tags' => $this->author_tags,
            'target_amount' => (float) $this->target_amount,
            'saved_amount' => (float) $this->saved_amount,
            'priority' => $this->priority,
            'is_completed' => $this->is_completed,
            'is_public' => $this->is_public,
            'position' => $this->position,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
