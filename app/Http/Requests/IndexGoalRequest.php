<?php

namespace App\Http\Requests;

use App\Models\Board;
use App\Models\Goal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class IndexGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->get('api_board') instanceof Board;
    }

    protected function prepareForValidation(): void
    {
        foreach (['is_completed', 'is_public'] as $field) {
            $value = $this->query($field);

            if (is_string($value) && in_array(strtolower($value), ['true', 'false'], true)) {
                $this->query->set($field, strtolower($value) === 'true' ? '1' : '0');
            }
        }
    }

    public function validationData(): array
    {
        return $this->query->all();
    }

    public function rules(): array
    {
        $rules = [
            'page' => ['sometimes', 'required', 'integer', 'min:1', 'max:2147483647'],
            'per_page' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
            'q' => ['sometimes', 'required', 'string', 'max:255'],
            'id' => ['sometimes', 'required', 'integer', 'min:1', 'max:'.PHP_INT_MAX],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:2000'],
            'description_format' => ['sometimes', 'required', Rule::in(Goal::DESCRIPTION_FORMATS)],
            'url' => ['sometimes', 'required', 'string', 'max:2048'],
            'priority' => ['sometimes', 'required', 'integer', Rule::in([1, 2, 3, 4])],
            'is_completed' => ['sometimes', 'required', 'boolean'],
            'is_public' => ['sometimes', 'required', 'boolean'],
            'position' => ['sometimes', 'required', 'integer', 'min:0', 'max:'.PHP_INT_MAX],
            'tags' => ['sometimes', 'array', 'list', 'max:50'],
            'tags.*' => ['required', 'string', 'max:255', 'not_regex:/[,;\r\n]/u'],
            'tags_mode' => ['sometimes', 'required', Rule::in(['all', 'any'])],
            'sort' => ['sometimes', 'required', Rule::in([
                'id', 'title', 'priority', 'position', 'target_amount', 'saved_amount',
                'is_completed', 'is_public', 'created_at', 'updated_at',
            ])],
            'direction' => ['sometimes', 'required', Rule::in(['asc', 'desc'])],
            'board_id' => ['missing'],
        ];

        foreach (['target_amount', 'saved_amount'] as $field) {
            foreach (['', '_min', '_max'] as $suffix) {
                $rules[$field.$suffix] = ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'];
            }
        }

        foreach (['created', 'updated'] as $field) {
            foreach (['from', 'to'] as $suffix) {
                $rules[$field.'_'.$suffix] = ['sometimes', 'required', 'date_format:Y-m-d'];
            }
        }

        return $rules;
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $data = $validator->validated();

            foreach (['target_amount', 'saved_amount'] as $field) {
                if (isset($data[$field.'_min'], $data[$field.'_max']) && $data[$field.'_min'] > $data[$field.'_max']) {
                    $validator->errors()->add($field.'_max', 'Максимальная сумма должна быть не меньше минимальной.');
                }
            }

            foreach (['created', 'updated'] as $field) {
                if (isset($data[$field.'_from'], $data[$field.'_to']) && $data[$field.'_from'] > $data[$field.'_to']) {
                    $validator->errors()->add($field.'_to', 'Конечная дата должна быть не раньше начальной.');
                }
            }
        }];
    }
}
