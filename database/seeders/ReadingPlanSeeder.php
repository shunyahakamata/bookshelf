<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReadingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $yamada = User::where('email', 'yamada@example.com')->firstOrFail();
        $suzuki = User::where('email', 'suzuki@example.com')->firstOrFail();

        $books = Book::orderBy('id')->take(6)->get();

        if ($books->count() < 6) {
            throw new \RuntimeException('読書計画の登録には6冊以上の書籍が必要です。');
        }

        $today = Carbon::today();

        $plans = [
            [
                'user_id' => $yamada->id,
                'book_id' => $books[0]->id,
                'target_date' => $today->copy()->addDays(3),
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,
            ],
            [
                'user_id' => $yamada->id,
                'book_id' => $books[1]->id,
                'target_date' => $today->copy(),
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,
            ],
            [
                'user_id' => $yamada->id,
                'book_id' => $books[2]->id,
                'target_date' => $today->copy()->subDays(3),
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,
            ],
            [
                'user_id' => $yamada->id,
                'book_id' => $books[3]->id,
                'target_date' => $today->copy()->addDays(7),
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,
            ],
            [
                'user_id' => $yamada->id,
                'book_id' => $books[4]->id,
                'target_date' => $today->copy()->subDays(10),
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => $today->copy()->subDays(5),
            ],
            [
                'user_id' => $suzuki->id,
                'book_id' => $books[5]->id,
                'target_date' => $today->copy()->addDays(5),
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,
            ],
        ];

        foreach ($plans as $planData) {
            ReadingPlan::create($planData);
        }
    }
}

