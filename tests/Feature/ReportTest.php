<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_page(): void
    {
        $response = $this->get('/reports');

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_report(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/reports');

        $response->assertOk();
        $response->assertViewIs('reports.index');
    }

    public function test_report_only_includes_authenticated_users_reviews(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'rating' => 5,
        ]);

        Review::factory()->count(3)->create([
            'user_id' => $otherUser->id,
            'rating' => 2,
        ]);

        $response = $this->actingAs($user)->get('/reports');

        $response->assertOk();
        $response->assertViewHas('stats', function ($stats) {
            return $stats['summary']['total_reviews'] === 1
                && (float) $stats['summary']['average_rating'] === 5.0
                && $stats['summary']['books_read'] === 1;
        });
    }

    public function test_report_displays_correctly_when_user_has_no_reviews(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/reports');

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            return $stats['summary']['total_reviews'] === 0
                && $stats['summary']['books_read'] === 0
                && (float) $stats['summary']['average_rating'] === 0.0
                && $stats['rating_distribution']->toArray() === [0, 0, 0, 0, 0]
                && $stats['top_rated_books']->isEmpty()
                && $stats['genre_ratings']->isEmpty();
        });
    }

    public function test_report_calculates_rating_distribution_correctly(): void
    {
        $user = User::factory()->create();

        foreach ([2, 4, 4, 5] as $rating) {
            Review::factory()->create([
                'user_id' => $user->id,
                'rating' => $rating,
            ]);
        }

        $response = $this->actingAs($user)->get('/reports');

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            return $stats['rating_distribution']->toArray() === [0, 1, 0, 2, 1];
        });
    }

    public function test_report_displays_top_five_highly_rated_books(): void
    {
        $user = User::factory()->create();

        foreach ([3, 4, 5, 4, 5, 5, 4] as $rating) {
            Review::factory()->create([
                'user_id' => $user->id,
                'rating' => $rating,
            ]);
        }

        $response = $this->actingAs($user)->get('/reports');

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            $topRatedBooks = $stats['top_rated_books'];

            return $topRatedBooks->count() === 5
                && $topRatedBooks->pluck('rating')->toArray() === [5, 5, 5, 4, 4];
        });
    }

    public function test_report_counts_reviews_in_each_associated_genre(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $genreA = Genre::factory()->create([
            'name' => 'テストジャンルA',
        ]);

        $genreB = Genre::factory()->create([
            'name' => 'テストジャンルB',
        ]);

        $book->genres()->attach([$genreA->id, $genreB->id]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
        ]);

        $response = $this->actingAs($user)->get('/reports');

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) use ($genreA, $genreB) {
            $genreRatings = $stats['genre_ratings']->keyBy('id');

            return $genreRatings->count() === 2
                && $genreRatings[$genreA->id]['count'] === 1
                && (float) $genreRatings[$genreA->id]['average_rating'] === 5.0
                && $genreRatings[$genreB->id]['count'] === 1
                && (float) $genreRatings[$genreB->id]['average_rating'] === 5.0;
        });
    }

    public function test_report_sorts_genres_by_average_rating(): void
    {
        $user = User::factory()->create();

        $genreA = Genre::factory()->create([
            'name' => 'テストジャンルA',
        ]);

        $genreB = Genre::factory()->create([
            'name' => 'テストジャンルB',
        ]);

        foreach ([3, 5] as $rating) {
            $book = Book::factory()->create();
            $book->genres()->attach($genreA->id);

            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => $rating,
            ]);
        }

        $book = Book::factory()->create();
        $book->genres()->attach($genreB->id);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
        ]);

        $response = $this->actingAs($user)->get('/reports');

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) use ($genreA, $genreB) {
            $genreRatings = $stats['genre_ratings'];

            return $genreRatings->pluck('id')->toArray() === [
                $genreB->id,
                $genreA->id,
            ]
                && $genreRatings[0]['count'] === 1
                && (float) $genreRatings[0]['average_rating'] === 5.0
                && $genreRatings[1]['count'] === 2
                && (float) $genreRatings[1]['average_rating'] === 4.0;
        });
    }

    public function test_report_displays_only_top_five_genres(): void
    {
        $user = User::factory()->create();

        $expectedGenreIds = [];

        foreach (range(1, 6) as $rating) {
            $genre = Genre::factory()->create();
            $book = Book::factory()->create();

            $book->genres()->attach($genre->id);

            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => min($rating, 5),
            ]);

            $expectedGenreIds[] = $genre->id;
        }

        $response = $this->actingAs($user)->get('/reports');

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) use ($expectedGenreIds) {
            $genreRatings = $stats['genre_ratings'];

            return $genreRatings->count() === 5
                && ! $genreRatings->pluck('id')->contains($expectedGenreIds[0]);
        });
    }

    public function test_report_counts_books_read_correctly(): void
    {
        $user = User::factory()->create();

        $bookA = Book::factory()->create();
        $bookB = Book::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $bookA->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $bookB->id,
            'rating' => 5,
        ]);

        $response = $this->actingAs($user)->get('/reports');

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            return $stats['summary']['total_reviews'] === 2
                && $stats['summary']['books_read'] === 2;
        });
    }
}
