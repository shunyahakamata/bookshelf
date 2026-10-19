<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_index_returns_successful_response(): void
    {
        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);
    }

    public function test_per_page_cannot_exceed_100(): void
    {
        $response = $this->getJson('/api/v1/books?per_page=101');

        $response->assertStatus(422);

        $response->assertJsonValidationErrors(['per_page']);
    }

    public function test_genre_id_must_exist(): void
    {
        $response = $this->getJson('/api/v1/books?genre_id=999999');

        $response->assertStatus(422);

        $response->assertJsonValidationErrors(['genre_id']);
    }

    public function test_books_can_be_searched_by_title_or_author(): void
    {
        $titleMatchedBook = Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        $authorMatchedBook = Book::factory()->create([
            'title' => 'PHP基礎',
            'author' => 'Laravel研究会',
        ]);

        $unmatchedBook = Book::factory()->create([
            'title' => 'JavaScript入門',
            'author' => '佐藤花子',
        ]);

        $response = $this->getJson('/api/v1/books?keyword=Laravel');

        $response->assertOk();

        $response->assertJsonCount(2, 'data');

        $response->assertJsonFragment(['id' => $titleMatchedBook->id]);

        $response->assertJsonFragment(['id' => $authorMatchedBook->id]);

        $response->assertJsonMissing(['id' => $unmatchedBook->id]);
    }

    public function test_books_can_be_filtered_by_genre(): void
    {
        $technicalGenre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $novelGenre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $technicalBook = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $novelBook = Book::factory()->create([
            'title' => '小説入門',
        ]);

        $technicalBook->genres()->attach($technicalGenre->id);
        $novelBook->genres()->attach($novelGenre->id);

        $response = $this->getJson(
            '/api/v1/books?genre_id='.$technicalGenre->id
        );

        $response->assertOk();

        $response->assertJsonCount(1, 'data');

        $response->assertJsonFragment([
            'id' => $technicalBook->id,
        ]);

        $response->assertJsonMissing([
            'id' => $novelBook->id,
        ]);
    }

    public function test_books_can_be_filtered_by_keyword_and_genre(): void
    {
        $technicalGenre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $novelGenre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $matchedBook = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $wrongGenreBook = Book::factory()->create([
            'title' => 'Laravel小説',
        ]);

        $wrongKeywordBook = Book::factory()->create([
            'title' => 'PHP入門',
            'author' => '山田太郎',
        ]);

        $matchedBook->genres()->attach($technicalGenre->id);
        $wrongGenreBook->genres()->attach($novelGenre->id);
        $wrongKeywordBook->genres()->attach($technicalGenre->id);

        $response = $this->getJson(
            '/api/v1/books?keyword=Laravel&genre_id='.$technicalGenre->id
        );

        $response->assertOk();

        $response->assertJsonCount(1, 'data');

        $response->assertJsonPath('data.0.id', $matchedBook->id);
    }

    public function test_books_can_be_paginated(): void
    {
        Book::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/books?per_page=2');

        $response->assertOk();

        $response->assertJsonCount(2, 'data');

        $response->assertJsonPath('meta.total', 5);

        $response->assertJsonPath('meta.per_page', 2);

        $response->assertJsonPath('meta.last_page', 3);
    }

    public function test_books_can_access_second_page(): void
    {
        Book::factory()->count(5)->create();

        $firstResponse = $this->getJson('/api/v1/books?per_page=2&page=1');

        $secondResponse = $this->getJson('/api/v1/books?per_page=2&page=2');

        $firstResponse->assertOk();
        $secondResponse->assertOk();

        $firstResponse->assertJsonCount(2, 'data');
        $secondResponse->assertJsonCount(2, 'data');

        $secondResponse->assertJsonPath('meta.current_page', 2);
        $secondResponse->assertJsonPath('meta.total', 5);

        $firstPageIds = collect($firstResponse->json('data'))->pluck('id');

        $secondPageIds = collect($secondResponse->json('data'))->pluck('id');

        $this->assertEmpty(
            $firstPageIds->intersect($secondPageIds)
        );
    }

    public function test_books_are_paginated_by_20_by_default(): void
    {
        Book::factory()->count(21)->create();

        $response = $this->getJson('/api/v1/books');

        $response->assertOk();

        $response->assertJsonCount(20, 'data');

        $response->assertJsonPath('meta.per_page', 20);

        $response->assertJsonPath('meta.total', 21);

        $response->assertJsonPath('meta.last_page', 2);
    }

    public function test_books_index_returns_expected_json_structure(): void
    {
        Book::factory()->create();

        $response = $this->getJson('/api/v1/books');

        $response->assertOk();

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'author',
                    'isbn',
                    'published_date',
                    'genres',
                    'average_rating',
                    'review_count',
                ],
            ],
            'links',
            'meta',
        ]);

        $response->assertJsonMissingPath('data.0.description');

        $response->assertJsonMissingPath('data.0.image_url');
    }

    public function test_books_index_returns_book_genres(): void
    {
        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $book = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->getJson('/api/v1/books');

        $response->assertOk();

        $response->assertJsonPath('data.0.id', $book->id);

        $response->assertJsonPath('data.0.genres.0.id', $genre->id);

        $response->assertJsonPath('data.0.genres.0.name', '技術書');
    }

    public function test_books_index_returns_review_count_and_average_rating(): void
    {
        $book = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 5,
        ]);

        $response = $this->getJson('/api/v1/books');

        $response->assertOk();

        $response->assertJsonPath('data.0.id', $book->id);

        $response->assertJsonPath('data.0.review_count', 2);

        $this->assertSame(
            4.5,
            $response->json('data.0.average_rating')
        );
    }

    public function test_books_index_returns_null_average_when_no_reviews(): void
    {
        $book = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $response = $this->getJson('/api/v1/books');

        $response->assertOk();

        $response->assertJsonPath('data.0.id', $book->id);

        $response->assertJsonPath('data.0.review_count', 0);

        $response->assertJsonPath('data.0.average_rating', null);
    }

    public function test_per_page_must_be_at_least_1(): void
    {
        $response = $this->getJson('/api/v1/books?per_page=0');

        $response->assertStatus(422);

        $response->assertJsonValidationErrors(['per_page']);
    }

    public function test_page_must_be_at_least_1(): void
    {
        $response = $this->getJson('/api/v1/books?page=0');

        $response->assertStatus(422);

        $response->assertJsonValidationErrors(['page']);
    }

    public function test_pagination_links_preserve_query_parameters(): void
    {
        Book::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/books?per_page=2&page=1');

        $response->assertOk();

        $nextUrl = $response->json('links.next');

        $this->assertNotNull($nextUrl);

        $query = [];

        parse_str(parse_url($nextUrl, PHP_URL_QUERY), $query);

        $this->assertSame('2', $query['per_page']);
        $this->assertSame('2', $query['page']);
    }

    public function test_pagination_links_preserve_keyword_and_genre_filters(): void
    {
        $technicalGenre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $books = Book::factory()->count(3)->create([
            'title' => 'Laravel入門',
        ]);

        foreach ($books as $book) {
            $book->genres()->attach($technicalGenre->id);
        }

        $response = $this->getJson(
            '/api/v1/books?keyword=Laravel&genre_id='.$technicalGenre->id.'&per_page=2'
        );

        $response->assertOk();

        $response->assertJsonCount(2, 'data');

        $nextUrl = $response->json('links.next');

        $this->assertNotNull($nextUrl);

        $query = [];

        parse_str(parse_url($nextUrl, PHP_URL_QUERY), $query);

        $this->assertSame('Laravel', $query['keyword']);
        $this->assertSame((string) $technicalGenre->id, $query['genre_id']);
        $this->assertSame('2', $query['per_page']);
        $this->assertSame('2', $query['page']);
    }
}
