<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_ranking(): void
    {
        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertViewIs('ranking.index');
    }

    public function test_books_are_ranked_by_average_rating_descending(): void
    {
        $lowBook = Book::factory()->create();
        $highBook = Book::factory()->create();

        Review::factory()->create([
            'book_id' => $lowBook->id,
            'rating' => 3,
        ]);

        Review::factory()->create([
            'book_id' => $highBook->id,
            'rating' => 5,
        ]);

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertViewHas('rankedBooks', function ($books) use ($highBook, $lowBook) {
                return $books->pluck('id')->all() === [
                    $highBook->id,
                    $lowBook->id,
                ];
            });
    }

    public function test_books_with_same_average_are_ranked_by_review_count(): void
    {
        $fewerBook = Book::factory()->create();
        $moreBook = Book::factory()->create();

        Review::factory()->create([
            'book_id' => $fewerBook->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'book_id' => $moreBook->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'book_id' => $moreBook->id,
            'rating' => 4,
        ]);

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertViewHas('rankedBooks', function ($books) use ($moreBook, $fewerBook) {
                return $books->pluck('id')->all() === [
                    $moreBook->id,
                    $fewerBook->id,
                ];
            });
    }

    public function test_books_with_same_average_and_count_are_ranked_by_id(): void
    {
        $firstBook = Book::factory()->create();
        $secondBook = Book::factory()->create();

        Review::factory()->create([
            'book_id' => $firstBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $secondBook->id,
            'rating' => 5,
        ]);

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertViewHas('rankedBooks', function ($books) use ($firstBook, $secondBook) {
                return $books->pluck('id')->all() === [
                    $firstBook->id,
                    $secondBook->id,
                ];
            });
    }

    public function test_ranking_excludes_unreviewed_books_and_limits_to_ten(): void
    {
        Book::factory()->create();

        $reviewedBooks = Book::factory()->count(11)->create();

        foreach ($reviewedBooks as $book) {
            Review::factory()->create([
                'book_id' => $book->id,
                'rating' => 5,
            ]);
        }

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertViewHas('rankedBooks', function ($books) use ($reviewedBooks) {
                return $books->count() === 10
                    && $books->pluck('id')->all()
                        === $reviewedBooks->take(10)->pluck('id')->all();
            });
    }
}
