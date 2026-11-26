<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_reading_plans_index(): void
    {
        $response = $this->get(route('reading-plans.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_only_see_their_own_reading_plans(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $otherPlan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.index'));

        $response->assertOk();

        $response->assertViewHas('readingPlans', function ($readingPlans) use ($ownPlan, $otherPlan) {
            return $readingPlans->contains('id', $ownPlan->id)
                && ! $readingPlans->contains('id', $otherPlan->id);
        });
    }

    public function test_user_can_create_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $targetDate = now()->addDays(7)->toDateString();

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => $targetDate,
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $targetDate,
            'status' => ReadingPlanStatus::InProgress->value,
            'completed_at' => null,
        ]);
    }

    public function test_user_cannot_create_duplicate_in_progress_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)
            ->from(route('reading-plans.create'))
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => now()->addDays(14)->toDateString(),
            ]);

        $response->assertRedirect(route('reading-plans.create'));
        $response->assertSessionHasErrors('book_id');

        $this->assertDatabaseCount('reading_plans', 1);
    }

    public function test_user_cannot_create_reading_plan_with_past_target_date(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('reading-plans.create'))
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => now()->subDay()->toDateString(),
            ]);

        $response->assertRedirect(route('reading-plans.create'));
        $response->assertSessionHasErrors('target_date');

        $this->assertDatabaseCount('reading_plans', 0);
    }

    public function test_user_can_update_reading_plan_target_date(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $newTargetDate = now()->addDays(14)->toDateString();

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => $newTargetDate,
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => $newTargetDate,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_expired_reading_plan_returns_to_in_progress_when_updated(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Expired,
        ]);

        $newTargetDate = now()->addDays(14)->toDateString();

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => $newTargetDate,
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => $newTargetDate,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_user_cannot_update_completed_reading_plan(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $originalTargetDate = $readingPlan->target_date->toDateString();

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => now()->addDays(14)->toDateString(),
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => $originalTargetDate,
            'status' => ReadingPlanStatus::Completed->value,
        ]);
    }

    public function test_user_cannot_edit_other_users_reading_plan(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.edit', $readingPlan));

        $response->assertForbidden();
    }

    public function test_user_cannot_update_other_users_reading_plan(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $originalTargetDate = $readingPlan->target_date->toDateString();

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => now()->addDays(14)->toDateString(),
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $otherUser->id,
            'target_date' => $originalTargetDate,
        ]);
    }

    public function test_user_can_complete_reading_plan(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.complete', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $readingPlan->status
        );

        $this->assertNotNull($readingPlan->completed_at);
    }

    public function test_user_can_complete_expired_reading_plan(): void
    {
        $user = User::factory()->create();

        $targetDate = today()->subDays(3)->toDateString();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => $targetDate,
            'status' => ReadingPlanStatus::Expired,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.complete', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $readingPlan->status
        );

        $this->assertNotNull($readingPlan->completed_at);

        $this->assertSame(
            $targetDate,
            $readingPlan->target_date->toDateString()
        );
    }

    public function test_user_cannot_complete_other_users_reading_plan(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.complete', $readingPlan));

        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $otherUser->id,
            'status' => ReadingPlanStatus::InProgress->value,
            'completed_at' => null,
        ]);
    }

    public function test_user_cannot_complete_already_completed_reading_plan(): void
    {
        $user = User::factory()->create();

        $completedAt = now()->subDay()->startOfSecond();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => $completedAt,
        ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.complete', $readingPlan));

        $response->assertForbidden();

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $readingPlan->status
        );

        $this->assertTrue(
            $readingPlan->completed_at->equalTo($completedAt)
        );
    }

    public function test_user_can_delete_own_reading_plan(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('reading-plans.destroy', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $readingPlan->id,
        ]);
    }

    public function test_user_cannot_delete_other_users_reading_plan(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('reading-plans.destroy', $readingPlan));

        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_user_cannot_reactivate_expired_plan_when_in_progress_plan_exists(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $expiredPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Expired,
        ]);

        $activePlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $originalTargetDate = $expiredPlan->target_date->toDateString();

        $response = $this->actingAs($user)
            ->from(route('reading-plans.edit', $expiredPlan))
            ->put(route('reading-plans.update', $expiredPlan), [
                'target_date' => now()->addDays(14)->toDateString(),
            ]);

        $response->assertRedirect(route('reading-plans.edit', $expiredPlan));
        $response->assertSessionHasErrors('target_date');

        $this->assertDatabaseHas('reading_plans', [
            'id' => $expiredPlan->id,
            'target_date' => $originalTargetDate,
            'status' => ReadingPlanStatus::Expired->value,
        ]);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $activePlan->id,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }
}
