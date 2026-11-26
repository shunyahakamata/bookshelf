<?php

namespace App\Http\Requests;

use App\Models\ReadingPlan;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReadingPlanRequest extends FormRequest
{
    /**
     * 読書計画の更新権限を確認する。
     */
    public function authorize(): bool
    {
        $readingPlan = $this->route('reading_plan');

        return $readingPlan instanceof ReadingPlan
            && $this->user() !== null
            && $this->user()->can('update', $readingPlan);
    }

    /**
     * 読書計画の更新時のバリデーションルールを取得する。
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'target_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }
}
