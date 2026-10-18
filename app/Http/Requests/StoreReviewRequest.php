<?php

namespace App\Http\Requests;

use App\Models\Review;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
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
            'rating' => [
                'required',
                'integer',
                'min:1',
                'max:5',
            ],

            'comment' => [
                'required',
                'string',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {

                $reviewExists = Review::where(
                    'user_id',
                    $this->user()->id
                )
                    ->where(
                        'book_id',
                        $this->route('book')->id
                    )
                    ->exists();

                if ($reviewExists) {
                    $validator->errors()->add(
                        'review',
                        'この書籍には既にレビューを投稿しています。'
                    );
                }
            },
        ];
    }
}
