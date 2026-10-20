<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_show_returns_successful_response(): void
    {
        $book = Book::factory()->create();

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertOk();

        $response->assertJsonPath('data.id', $book->id);
        $response->assertJsonPath('data.title', $book->title);
        $response->assertJsonPath('data.author', $book->author);
    }

    public function test_books_show_returns_404_when_book_does_not_exist(): void
    {
        $response = $this->getJson('/api/v1/books/999999');

        $response->assertNotFound();

        $this->assertStringContainsString(
            'No query results for model',
            $response->json('message')
        );
    }

    public function test_books_show_returns_book_genres(): void
    {
        $book = Book::factory()->create();

        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertOk();

        $response->assertJsonPath('data.genres.0.id', $genre->id);
        $response->assertJsonPath('data.genres.0.name', $genre->name);
    }

    public function test_books_show_returns_review_details(): void
    {
        $book = Book::factory()->create();
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => 'とても面白かったです。',
        ]);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertOk();

        $response->assertJsonPath('data.reviews.0.id', $review->id);
        $response->assertJsonPath('data.reviews.0.user_name', $user->name);
        $response->assertJsonPath('data.reviews.0.rating', 5);
        $response->assertJsonPath('data.reviews.0.comment', 'とても面白かったです。');
        $response->assertJsonPath(
            'data.reviews.0.created_at',
            $review->created_at->toIso8601String()
        );
    }

    public function test_books_show_returns_empty_reviews_when_no_reviews(): void
    {
        $book = Book::factory()->create();

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertOk();

        $response->assertJsonPath('data.id', $book->id);
        $response->assertJsonPath('data.reviews', []);
    }

    public function test_books_show_returns_expected_json_structure(): void
    {
        $book = Book::factory()->create();

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertOk();

        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'author',
                'isbn',
                'published_date',
                'description',
                'image_url',
                'genres',
                'reviews',
            ],
        ]);
    }
}
