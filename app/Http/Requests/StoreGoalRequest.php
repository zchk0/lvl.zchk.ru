<?php

namespace App\Http\Requests;

use App\Models\Board;
use App\Models\Goal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->get('api_board') instanceof Board;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'description_format' => ['sometimes', 'required', Rule::in(Goal::DESCRIPTION_FORMATS)],
            'url' => ['nullable', 'string'],
            'tags' => ['sometimes', 'array', 'list'],
            'tags.*' => ['required', 'string', 'max:255', 'not_regex:/[,;\r\n]/u'],
            'target_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'saved_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'priority' => ['sometimes', 'required', 'integer', Rule::in([1, 2, 3, 4])],
            'is_completed' => ['sometimes', 'required', 'boolean'],
            'board_id' => ['prohibited'],
            'position' => ['prohibited'],
            'is_public' => ['prohibited'],
            'public_token' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $tags = Goal::parseAuthorTags(implode(', ', $this->input('tags', [])));

            if (mb_strlen(implode(', ', $tags)) > 255) {
                $validator->errors()->add('tags', 'Общая длина тегов не должна превышать 255 символов.');
            }
        }];
    }
}
