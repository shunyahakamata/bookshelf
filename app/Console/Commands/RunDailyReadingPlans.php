<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Console\Command;

class RunDailyReadingPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reading-plans:run-daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '読書計画の期限切れ更新とリマインダー通知を実行する';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $updatedCount = ReadingPlan::where(
            'status',
            ReadingPlanStatus::InProgress
        )
            ->whereDate('target_date', '<', today())
            ->update([
                'status' => ReadingPlanStatus::Expired,
            ]);

        $this->info("期限切れに更新した読書計画：{$updatedCount}件");

        $beforeThreeDaysPlans = ReadingPlan::with(['user', 'book'])
            ->where('status', ReadingPlanStatus::InProgress)
            ->whereDate('target_date', today()->addDays(3))
            ->get();

        foreach ($beforeThreeDaysPlans as $plan) {
            $plan->user->notify(new ReadingPlanReminder(
                planId: $plan->id,
                bookTitle: $plan->book->title,
                timing: 'three_days_before',
                title: '読書計画の期限が近づいています',
                body: "「{$plan->book->title}」の目標日まであと3日です。"
            ));
        }

        $todayPlans = ReadingPlan::with(['user', 'book'])
            ->where('status', ReadingPlanStatus::InProgress)
            ->whereDate('target_date', today())
            ->get();

        foreach ($todayPlans as $plan) {
            $plan->user->notify(new ReadingPlanReminder(
                planId: $plan->id,
                bookTitle: $plan->book->title,
                timing: 'on_due_date',
                title: '読書計画の目標日になりました',
                body: "「{$plan->book->title}」の目標日は今日です。"
            ));
        }

        $afterThreeDaysPlans = ReadingPlan::with(['user', 'book'])
            ->where('status', ReadingPlanStatus::Expired)
            ->whereDate('target_date', today()->subDays(3))
            ->get();

        foreach ($afterThreeDaysPlans as $plan) {
            $plan->user->notify(new ReadingPlanReminder(
                planId: $plan->id,
                bookTitle: $plan->book->title,
                timing: 'three_days_after',
                title: '読書計画の目標日を過ぎています',
                body: "「{$plan->book->title}」の目標日から3日が経過しました。"
            ));
        }

        return self::SUCCESS;
    }
}
