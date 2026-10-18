<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_without_reviews_displays_no_rating(): void
    {
        $book = Book::factory()->create();

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('評価なし');
    }

    public function test_reviews_are_ordered_by_rating_then_date_then_id(): void
    {
        $book = Book::factory()->create();

        $first = Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 5,
            'created_at' => '2026-01-01 10:00:00',
        ]);

        $second = Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 5,
            'created_at' => '2026-01-02 10:00:00',
        ]);

        $third = Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 4,
            'created_at' => '2026-01-03 10:00:00',
        ]);

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertViewHas('book', function ($book) use ($first, $second, $third) {
                return $book->reviews->pluck('id')->all() === [
                    $second->id,
                    $first->id,
                    $third->id,
                ];
            });
    }

    public function test_reviews_with_same_rating_and_date_are_ordered_by_id_ascending(): void
    {
        $book = Book::factory()->create();

        $first = Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 5,
            'created_at' => '2026-01-01 10:00:00',
        ]);

        $second = Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 5,
            'created_at' => '2026-01-01 10:00:00',
        ]);

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertViewHas('book', function ($book) use ($first, $second) {
                return $book->reviews->pluck('id')->all() === [
                    $first->id,
                    $second->id,
                ];
            });
    }

    public function test_user_without_review_can_see_review_form(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->actingAs($user)
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('レビューを投稿')
            ->assertSee('投稿する');
    }

    public function test_user_with_review_cannot_see_review_form(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $this->actingAs($user)
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertDontSee('action="'.route('reviews.store', $book).'"', false)
            ->assertSee('この書籍にはすでにレビューを投稿しています。');
    }
}
