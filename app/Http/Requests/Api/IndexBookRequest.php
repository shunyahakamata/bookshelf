<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class IndexBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => [
                'nullable',
                'string',
            ],
            'genre_id' => [
                'nullable',
                'integer',
                'exists:genres,id',
            ],
            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}
