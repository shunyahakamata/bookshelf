<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_book(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '9781234567890',
        ]);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '9781234567891',
            'published_date' => '2026-10-01',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '9781234567891',
            'user_id' => $user->id,
        ]);
    }

    public function test_can_update_book_without_changing_isbn(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '9781234567890',
        ]);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => 'タイトルだけ変更',
            'author' => $book->author,
            'isbn' => $book->isbn,
            'published_date' => $book->published_date,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'タイトルだけ変更',
            'isbn' => '9781234567890',
        ]);
    }

    public function test_can_update_book_genres(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genres = Genre::factory()->count(3)->create();

        $book->genres()->sync([
            $genres[0]->id,
            $genres[1]->id,
        ]);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn,
            'published_date' => $book->published_date,
            'genres' => [
                $genres[1]->id,
                $genres[2]->id,
            ],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genres[0]->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genres[1]->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genres[2]->id,
        ]);
    }

    public function test_cannot_update_nonexistent_book(): void
    {
        $response = $this->putJson('/api/v1/books/99999', [
            'title' => '更新後のタイトル',
            'author' => '山田太郎',
            'isbn' => '9781234567892',
            'published_date' => '2026-10-01',
        ]);

        $response->assertStatus(404);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_cannot_update_book_with_duplicate_isbn(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '9781234567890',
        ]);

        $otherBook = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '9781234567891',
        ]);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => '更新後のタイトル',
            'author' => $book->author,
            'isbn' => $otherBook->isbn,
            'published_date' => $book->published_date,
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors(['isbn']);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => '9781234567890',
        ]);
    }

    public function test_required_fields_are_validated(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->putJson("/api/v1/books/{$book->id}", []);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'title',
            'author',
            'isbn',
            'published_date',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn,
        ]);
    }

    public function test_genre_id_must_exist(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => '更新後のタイトル',
            'author' => $book->author,
            'isbn' => $book->isbn,
            'published_date' => $book->published_date,
            'genres' => [99999],
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors(['genres.0']);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $book->title,
        ]);
    }
}
