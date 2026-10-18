<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Review;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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
