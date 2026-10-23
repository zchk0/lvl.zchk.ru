<?php

namespace App\Http\Requests;

class UpdateGoalRequest extends StoreGoalRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['title'] = ['sometimes', ...$rules['title']];

        foreach (['board_id', 'position', 'is_public', 'public_token'] as $field) {
            $rules[$field] = ['missing'];
        }

        return $rules;
    }
}
