<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_review(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $data = [
            'rating' => 5,
            'comment' => 'とても面白かったです。',
        ];

        $response = $this->actingAs($user)
            ->post(route('reviews.store', $book), $data);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても面白かったです。',
        ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            'レビューを投稿しました。'
        );
    }

    public function test_user_cannot_review_the_same_book_twice(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $data = [
            'rating' => 4,
            'comment' => '2回目のレビューです。',
        ];

        $response = $this->actingAs($user)
            ->post(route('reviews.store', $book), $data);

        $response->assertSessionHasErrors('review');

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_review_author_can_update_a_review(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $data = [
            'rating' => 3,
            'comment' => 'レビューを更新しました。',
        ];

        $response = $this->actingAs($user)
            ->put(route('reviews.update', $review), $data);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => 'レビューを更新しました。',
        ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            'レビューを更新しました。'
        );
    }

    public function test_other_user_cannot_update_a_review(): void
    {
        $userA = User::factory()->create();

        $userB = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $userA->id,
        ]);

        $data = [
            'rating' => 3,
            'comment' => 'レビューを更新しました。',
        ];

        $originalreview = [
            'comment' => $review->comment,
            'rating' => $review->rating,
            'book_id' => $review->book_id,
        ];

        $response = $this->actingAs($userB)
            ->put(route('reviews.update', $review), $data);

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $userA->id,
            'book_id' => $originalreview['book_id'],
            'rating' => $originalreview['rating'],
            'comment' => $originalreview['comment'],
        ]);
    }

    public function test_review_author_can_delete_a_review(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('reviews.destroy', $review));

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
            'user_id' => $user->id,
        ]);

        $response->assertRedirect(
            route('books.show', $review->book_id)
        );

        $response->assertSessionHas(
            'success',
            'レビューを削除しました。',
        );
    }

    public function test_other_user_cannot_delete_a_review(): void
    {
        $userA = User::factory()->create();

        $userB = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $userA->id,
        ]);

        $response = $this->actingAs($userB)
            ->delete(route('reviews.destroy', $review));

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_guest_cannot_create_a_review(): void
    {
        $book = Book::factory()->create();

        $data = [
            'rating' => 5,
            'comment' => 'ゲストからの投稿です。',
        ];

        $response = $this->post(route('reviews.store', $book), $data);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_guest_cannot_update_a_review(): void
    {
        $review = Review::factory()->create();

        $data = [
            'rating' => 3,
            'comment' => 'レビューを更新しました。',
        ];

        $originalReview = [
            'comment' => $review->comment,
            'rating' => $review->rating,
            'book_id' => $review->book_id,
            'user_id' => $review->user_id,
        ];

        $response = $this->put(route('reviews.update', $review), $data);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $originalReview['user_id'],
            'book_id' => $originalReview['book_id'],
            'rating' => $originalReview['rating'],
            'comment' => $originalReview['comment'],
        ]);
    }

    public function test_guest_cannot_delete_a_review(): void
    {
        $review = Review::factory()->create();

        $response = $this->delete(route('reviews.destroy', $review));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_user_cannot_create_review_with_invalid_rating(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $data = [
            'rating' => 6,
            'comment' => '評価が範囲外のレビューです。',
        ];

        $response = $this->actingAs($user)
            ->post(route('reviews.store', $book), $data);

        $response->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_user_cannot_create_review_without_comment(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $data = [
            'rating' => 5,
            'comment' => '',
        ];

        $response = $this->actingAs($user)
            ->post(route('reviews.store', $book), $data);

        $response->assertSessionHasErrors('comment');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_user_cannot_update_review_with_invalid_rating(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        $originalReview = [
            'user_id' => $review->user_id,
            'book_id' => $review->book_id,
            'comment' => $review->comment,
            'rating' => $review->rating,
        ];

        $data = [
            'rating' => 6,
            'comment' => '評価が範囲外の更新レビューです。',
        ];

        $response = $this->actingAs($user)
            ->put(route('reviews.update', $review), $data);

        $response->assertSessionHasErrors('rating');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $originalReview['user_id'],
            'book_id' => $originalReview['book_id'],
            'rating' => $originalReview['rating'],
            'comment' => $originalReview['comment'],
        ]);
    }

    public function test_user_cannot_update_review_without_comment(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        $originalReview = [
            'user_id' => $review->user_id,
            'book_id' => $review->book_id,
            'comment' => $review->comment,
            'rating' => $review->rating,
        ];

        $data = [
            'rating' => 5,
            'comment' => '',
        ];

        $response = $this->actingAs($user)
            ->put(route('reviews.update', $review), $data);

        $response->assertSessionHasErrors('comment');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $originalReview['user_id'],
            'book_id' => $originalReview['book_id'],
            'rating' => $originalReview['rating'],
            'comment' => $originalReview['comment'],
        ]);
    }
}
