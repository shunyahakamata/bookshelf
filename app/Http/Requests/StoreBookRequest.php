<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
            ],
            'author' => [
                'required',
                'string',
            ],
            'isbn' => [
                'required',
                'string',
                'size:13',
                'unique:books,isbn',
            ],
            'published_date' => [
                'required',
                'date',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'image_url' => [
                'nullable',
                'url',
            ],
            'genres' => [
                'nullable',
                'array',
            ],
            'genres.*' => [
                'integer',
                'exists:genres,id',
            ],
        ];
    }
}
