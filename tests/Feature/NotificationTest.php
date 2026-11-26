<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_notifications(): void
    {
        $response = $this->get(route('notifications.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_only_own_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $user->notify(new ReadingPlanReminder(
            planId: 1,
            bookTitle: '自分の書籍A',
            timing: 'three_days_before',
            title: '自分の未読通知',
            body: '自分の未読通知の本文'
        ));

        $user->notify(new ReadingPlanReminder(
            planId: 2,
            bookTitle: '自分の書籍B',
            timing: 'on_due_date',
            title: '自分の既読通知',
            body: '自分の既読通知の本文'
        ));

        $user->notifications()
            ->where('data->plan_id', 2)
            ->firstOrFail()
            ->markAsRead();

        $otherUser->notify(new ReadingPlanReminder(
            planId: 3,
            bookTitle: '他人の書籍',
            timing: 'three_days_before',
            title: '他人の未読通知',
            body: '他人の未読通知の本文'
        ));

        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response->assertOk();
        $response->assertViewIs('notifications.index');
        $response->assertSee('自分の未読通知');
        $response->assertSee('自分の既読通知');
        $response->assertDontSee('他人の未読通知');

        $this->assertCount(
            2,
            $response->viewData('notifications')
        );
    }

    public function test_notifications_are_sorted_by_newest(): void
    {
        $user = User::factory()->create();

        $user->notify(new ReadingPlanReminder(
            planId: 1,
            bookTitle: '書籍A',
            timing: 'three_days_before',
            title: '古い通知',
            body: '古い通知の本文'
        ));

        $user->notify(new ReadingPlanReminder(
            planId: 2,
            bookTitle: '書籍B',
            timing: 'on_due_date',
            title: '新しい通知',
            body: '新しい通知の本文'
        ));

        $user->notify(new ReadingPlanReminder(
            planId: 3,
            bookTitle: '書籍C',
            timing: 'three_days_after',
            title: '中間の通知',
            body: '中間の通知の本文'
        ));

        $user->notifications()
            ->where('data->plan_id', 1)
            ->update(['created_at' => now()->subDays(3)]);

        $user->notifications()
            ->where('data->plan_id', 2)
            ->update(['created_at' => now()->subDay()]);

        $user->notifications()
            ->where('data->plan_id', 3)
            ->update(['created_at' => now()->subDays(2)]);

        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response->assertOk();

        $response->assertSeeInOrder([
            '新しい通知',
            '中間の通知',
            '古い通知',
        ]);

        $this->assertSame(
            [2, 3, 1],
            $response->viewData('notifications')
                ->pluck('data.plan_id')
                ->all()
        );
    }

    public function test_user_can_view_empty_notification_list(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response->assertOk();
        $response->assertViewIs('notifications.index');
        $response->assertSee('通知はありません。');

        $this->assertCount(
            0,
            $response->viewData('notifications')
        );
    }

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $user = User::factory()->create();

        $user->notify(new ReadingPlanReminder(
            planId: 1,
            bookTitle: 'テスト書籍',
            timing: 'three_days_before',
            title: '読書計画の期限が近づいています',
            body: '「テスト書籍」の目標日まであと3日です。'
        ));

        $notification = $user->notifications()->firstOrFail();

        $this->assertNull($notification->read_at);

        $response = $this->actingAs($user)
            ->post(route('notifications.read', $notification->id));

        $response->assertRedirect(route('notifications.index'));

        $response->assertSessionHas(
            'success',
            '通知を既読にしました。'
        );

        $notification->refresh();

        $this->assertNotNull($notification->read_at);
    }

    public function test_user_cannot_mark_other_users_notification_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherUser->notify(new ReadingPlanReminder(
            planId: 1,
            bookTitle: '他人の書籍',
            timing: 'three_days_before',
            title: '読書計画の期限が近づいています',
            body: '「他人の書籍」の目標日まであと3日です。'
        ));

        $notification = $otherUser->notifications()->firstOrFail();

        $this->assertNull($notification->read_at);

        $response = $this->actingAs($user)
            ->post(route('notifications.read', $notification->id));

        $response->assertNotFound();

        $notification->refresh();

        $this->assertNull($notification->read_at);
    }

    public function test_user_cannot_mark_nonexistent_notification_as_read(): void
    {
        $user = User::factory()->create();

        $notificationId = (string) Str::uuid();

        $this->assertDatabaseMissing('notifications', [
            'id' => $notificationId,
        ]);

        $response = $this->actingAs($user)
            ->post(route('notifications.read', $notificationId));

        $response->assertNotFound();
    }

    public function test_already_read_notification_keeps_original_read_at(): void
    {
        $user = User::factory()->create();

        $user->notify(new ReadingPlanReminder(
            planId: 1,
            bookTitle: 'テスト書籍',
            timing: 'three_days_before',
            title: '読書計画の期限が近づいています',
            body: '「テスト書籍」の目標日まであと3日です。'
        ));

        $notification = $user->notifications()->firstOrFail();

        $originalReadAt = now()
            ->subDay()
            ->setTime(12, 0, 0)
            ->toDateTimeString();

        $notification->update([
            'read_at' => $originalReadAt,
        ]);

        $response = $this->actingAs($user)
            ->post(route('notifications.read', $notification->id));

        $response->assertRedirect(route('notifications.index'));

        $notification->refresh();

        $this->assertSame(
            $originalReadAt,
            $notification->read_at?->toDateTimeString()
        );
    }

    public function test_guest_cannot_mark_notification_as_read(): void
    {
        $user = User::factory()->create();

        $user->notify(new ReadingPlanReminder(
            planId: 1,
            bookTitle: 'テスト書籍',
            timing: 'three_days_before',
            title: '読書計画の期限が近づいています',
            body: '「テスト書籍」の目標日まであと3日です。'
        ));

        $notification = $user->notifications()->firstOrFail();

        $this->assertNull($notification->read_at);

        $response = $this->post(
            route('notifications.read', $notification->id)
        );

        $response->assertRedirect(route('login'));

        $notification->refresh();

        $this->assertNull($notification->read_at);
    }

    public function test_header_displays_only_own_unread_notification_count(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        for ($i = 1; $i <= 3; $i++) {
            $user->notify(new ReadingPlanReminder(
                planId: $i,
                bookTitle: "自分の書籍{$i}",
                timing: 'three_days_before',
                title: "自分の通知{$i}",
                body: "自分の通知{$i}の本文"
            ));
        }

        $user->notifications()
            ->where('data->plan_id', 3)
            ->firstOrFail()
            ->markAsRead();

        for ($i = 4; $i <= 6; $i++) {
            $otherUser->notify(new ReadingPlanReminder(
                planId: $i,
                bookTitle: "他人の書籍{$i}",
                timing: 'three_days_before',
                title: "他人の通知{$i}",
                body: "他人の通知{$i}の本文"
            ));
        }

        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response->assertOk();

        $response->assertSee(
            '<span class="absolute top-0 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full">2</span>',
            false
        );

        $response->assertSee(
            '<span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full">2</span>',
            false
        );
    }

    public function test_header_unread_notification_count_decreases_after_reading(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 2; $i++) {
            $user->notify(new ReadingPlanReminder(
                planId: $i,
                bookTitle: "テスト書籍{$i}",
                timing: 'three_days_before',
                title: "テスト通知{$i}",
                body: "テスト通知{$i}の本文"
            ));
        }

        $firstNotification = $user->notifications()
            ->where('data->plan_id', 1)
            ->firstOrFail();

        $secondNotification = $user->notifications()
            ->where('data->plan_id', 2)
            ->firstOrFail();

        $desktopBadgeClass = 'absolute top-0 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full';

        $mobileBadgeClass = 'ml-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full';

        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee(
            "<span class=\"{$desktopBadgeClass}\">2</span>",
            false
        );
        $response->assertSee(
            "<span class=\"{$mobileBadgeClass}\">2</span>",
            false
        );

        $response = $this->followingRedirects()
            ->post(route('notifications.read', $firstNotification->id));

        $response->assertOk();
        $response->assertSee(
            "<span class=\"{$desktopBadgeClass}\">1</span>",
            false
        );
        $response->assertSee(
            "<span class=\"{$mobileBadgeClass}\">1</span>",
            false
        );

        $response = $this->followingRedirects()
            ->post(route('notifications.read', $secondNotification->id));

        $response->assertOk();
        $response->assertDontSee($desktopBadgeClass, false);
        $response->assertDontSee($mobileBadgeClass, false);

        $this->assertSame(
            0,
            $user->unreadNotifications()->count()
        );
    }

    public function test_three_reminder_types_are_displayed_correctly(): void
    {
        $user = User::factory()->create();

        $reminders = [
            [
                'timing' => 'three_days_before',
                'title' => '読書計画の期限が近づいています',
                'body' => '「テスト書籍」の目標日まであと3日です。',
                'border' => 'bg-blue-500',
                'iconBg' => 'bg-blue-100',
                'iconColor' => 'text-blue-600',
                'iconPath' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            ],
            [
                'timing' => 'on_due_date',
                'title' => '読書計画の目標日になりました',
                'body' => '「テスト書籍」の目標日は今日です。',
                'border' => 'bg-yellow-500',
                'iconBg' => 'bg-yellow-100',
                'iconColor' => 'text-yellow-700',
                'iconPath' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'timing' => 'three_days_after',
                'title' => '読書計画の目標日を過ぎています',
                'body' => '「テスト書籍」の目標日から3日が経過しました。',
                'border' => 'bg-red-500',
                'iconBg' => 'bg-red-100',
                'iconColor' => 'text-red-600',
                'iconPath' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
            ],
        ];

        foreach ($reminders as $reminder) {
            $user->notify(new ReadingPlanReminder(
                planId: 1,
                bookTitle: 'テスト書籍',
                timing: $reminder['timing'],
                title: $reminder['title'],
                body: $reminder['body']
            ));

            $notification = $user->notifications()->firstOrFail();

            $response = $this->actingAs($user)
                ->get(route('notifications.index'));

            $response->assertOk();

            $response->assertSee($reminder['title']);
            $response->assertSee($reminder['body']);

            $response->assertSee(
                "class=\"absolute inset-y-0 left-0 w-1 {$reminder['border']}\"",
                false
            );

            $response->assertSee(
                "class=\"inline-flex h-10 w-10 items-center justify-center rounded-full {$reminder['iconBg']}\"",
                false
            );

            $response->assertSee(
                "class=\"h-5 w-5 {$reminder['iconColor']}\"",
                false
            );

            $response->assertSee(
                "d=\"{$reminder['iconPath']}\"",
                false
            );

            $response->assertSee('未読');

            $response->assertSee(
                'action="'.route('notifications.read', $notification->id).'"',
                false
            );

            $notification->delete();
        }
    }
}
