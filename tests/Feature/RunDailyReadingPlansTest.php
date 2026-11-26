<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RunDailyReadingPlansTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_command_expires_only_overdue_in_progress_plans(): void
    {
        $overduePlan = ReadingPlan::factory()->create([
            'target_date' => today()->subDay()->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $todayPlan = ReadingPlan::factory()->create([
            'target_date' => today()->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $futurePlan = ReadingPlan::factory()->create([
            'target_date' => today()->addDay()->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $completedPlan = ReadingPlan::factory()->create([
            'target_date' => today()->subDay()->toDateString(),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $this->artisan('reading-plans:run-daily')
            ->assertExitCode(0);

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $overduePlan->fresh()->status
        );

        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $todayPlan->fresh()->status
        );

        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $futurePlan->fresh()->status
        );

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $completedPlan->fresh()->status
        );
    }

    public function test_daily_command_sends_reminder_three_days_before_target_date(): void
    {
        Notification::fake();

        $targetPlan = ReadingPlan::factory()->create([
            'target_date' => today()->addDays(3)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $completedPlan = ReadingPlan::factory()->create([
            'target_date' => today()->addDays(3)->toDateString(),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $this->artisan('reading-plans:run-daily')
            ->assertExitCode(0);

        Notification::assertSentTo(
            $targetPlan->user,
            ReadingPlanReminder::class,
            function (ReadingPlanReminder $notification) use ($targetPlan) {
                return $notification->planId === $targetPlan->id
                    && $notification->bookTitle === $targetPlan->book->title
                    && $notification->timing === 'three_days_before'
                    && $notification->title === '読書計画の期限が近づいています'
                    && $notification->body === "「{$targetPlan->book->title}」の目標日まであと3日です。";
            }
        );

        Notification::assertNotSentTo(
            $completedPlan->user,
            ReadingPlanReminder::class
        );

        Notification::assertCount(1);
    }

    public function test_daily_command_sends_reminder_on_target_date(): void
    {
        Notification::fake();

        $todayPlan = ReadingPlan::factory()->create([
            'target_date' => today()->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $completedPlan = ReadingPlan::factory()->create([
            'target_date' => today()->toDateString(),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $futurePlan = ReadingPlan::factory()->create([
            'target_date' => today()->addDay()->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('reading-plans:run-daily')
            ->assertExitCode(0);

        Notification::assertSentTo(
            $todayPlan->user,
            ReadingPlanReminder::class,
            function (ReadingPlanReminder $notification) use ($todayPlan) {
                return $notification->planId === $todayPlan->id
                    && $notification->bookTitle === $todayPlan->book->title
                    && $notification->timing === 'on_due_date'
                    && $notification->title === '読書計画の目標日になりました'
                    && $notification->body === "「{$todayPlan->book->title}」の目標日は今日です。";
            }
        );

        Notification::assertNotSentTo(
            $completedPlan->user,
            ReadingPlanReminder::class
        );

        Notification::assertNotSentTo(
            $futurePlan->user,
            ReadingPlanReminder::class
        );

        Notification::assertCount(1);
    }

    public function test_daily_command_sends_reminder_three_days_after_target_date(): void
    {
        Notification::fake();

        $expiredPlan = ReadingPlan::factory()->create([
            'target_date' => today()->subDays(3)->toDateString(),
            'status' => ReadingPlanStatus::Expired,
        ]);

        $overduePlan = ReadingPlan::factory()->create([
            'target_date' => today()->subDays(3)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $completedPlan = ReadingPlan::factory()->create([
            'target_date' => today()->subDays(3)->toDateString(),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $otherExpiredPlan = ReadingPlan::factory()->create([
            'target_date' => today()->subDays(2)->toDateString(),
            'status' => ReadingPlanStatus::Expired,
        ]);

        $this->artisan('reading-plans:run-daily')
            ->assertExitCode(0);

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $overduePlan->fresh()->status
        );

        foreach ([$expiredPlan, $overduePlan] as $plan) {
            Notification::assertSentTo(
                $plan->user,
                ReadingPlanReminder::class,
                function (ReadingPlanReminder $notification) use ($plan) {
                    return $notification->planId === $plan->id
                        && $notification->bookTitle === $plan->book->title
                        && $notification->timing === 'three_days_after'
                        && $notification->title === '読書計画の目標日を過ぎています'
                        && $notification->body === "「{$plan->book->title}」の目標日から3日が経過しました。";
                }
            );
        }

        Notification::assertNotSentTo(
            $completedPlan->user,
            ReadingPlanReminder::class
        );

        Notification::assertNotSentTo(
            $otherExpiredPlan->user,
            ReadingPlanReminder::class
        );

        Notification::assertCount(2);
    }

    public function test_daily_command_stores_reminder_in_database(): void
    {
        $plan = ReadingPlan::factory()->create([
            'target_date' => today()->addDays(3)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('reading-plans:run-daily')
            ->assertExitCode(0);

        $this->assertDatabaseHas('notifications', [
            'type' => ReadingPlanReminder::class,
            'notifiable_type' => $plan->user->getMorphClass(),
            'notifiable_id' => $plan->user_id,
            'read_at' => null,
        ]);

        $notification = $plan->user->notifications()->first();

        $this->assertNotNull($notification);

        $this->assertSame($plan->id, $notification->data['plan_id']);
        $this->assertSame($plan->book->title, $notification->data['book_title']);
        $this->assertSame('three_days_before', $notification->data['timing']);
        $this->assertSame('読書計画の期限が近づいています', $notification->data['title']);
        $this->assertSame(
            "「{$plan->book->title}」の目標日まであと3日です。",
            $notification->data['body']
        );

        $this->assertSame(1, $plan->user->notifications()->count());
    }
}
