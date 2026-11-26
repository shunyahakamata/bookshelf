<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_store_book(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', [
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn' => '9781234567890',
            'published_date' => '2026-10-01',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('books', [
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn' => '9781234567890',
            'user_id' => $user->id,
        ]);
    }

    public function test_can_store_book_with_genres(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $genres = Genre::factory()->count(2)->create();

        $response = $this->postJson('/api/v1/books', [
            'title' => 'ジャンル付き書籍',
            'author' => '山田太郎',
            'isbn' => '9781234567891',
            'published_date' => '2026-10-01',
            'genres' => $genres->pluck('id')->all(),
        ]);

        $response->assertStatus(201);

        $bookId = $response->json('data.id');

        foreach ($genres as $genre) {
            $this->assertDatabaseHas('book_genre', [
                'book_id' => $bookId,
                'genre_id' => $genre->id,
            ]);
        }
    }

    public function test_unauthenticated_user_cannot_store_book(): void
    {
        $response = $this->postJson('/api/v1/books', [
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn' => '9781234567892',
            'published_date' => '2026-10-01',
        ]);

        $response->assertStatus(401);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_cannot_store_book_as_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', [
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn' => '9781234567893',
            'published_date' => '2026-10-01',
            'user_id' => $otherUser->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('books', [
            'isbn' => '9781234567893',
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => '9781234567893',
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_isbn_must_be_unique(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $isbn = '9781234567894';

        Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => $isbn,
        ]);

        $response = $this->postJson('/api/v1/books', [
            'title' => '別の書籍',
            'author' => '山田太郎',
            'isbn' => $isbn,
            'published_date' => '2026-10-01',
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors(['isbn']);

        $this->assertDatabaseCount('books', 1);
    }

    public function test_required_fields_are_validated(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', []);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'title',
            'author',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_genre_id_must_exist(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', [
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn' => '9781234567895',
            'published_date' => '2026-10-01',
            'genres' => [99999],
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors(['genres.0']);

        $this->assertDatabaseMissing('books', [
            'isbn' => '9781234567895',
        ]);
    }

    public function test_isbn_must_be_13_characters(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', [
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn' => '123456789012',
            'published_date' => '2026-10-01',
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors(['isbn']);

        $this->assertDatabaseMissing('books', [
            'isbn' => '123456789012',
        ]);
    }

    public function test_can_store_book_without_isbn_and_published_date(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', [
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('books', [
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn' => null,
            'published_date' => null,
            'user_id' => $user->id,
        ]);
    }

    public function test_can_store_book_with_null_isbn_and_published_date(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', [
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn' => null,
            'published_date' => null,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('books', [
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn' => null,
            'published_date' => null,
            'user_id' => $user->id,
        ]);
    }

    public function test_published_date_must_be_valid_date(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', [
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'published_date' => 'invalid-date',
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'published_date',
        ]);

        $this->assertDatabaseCount('books', 0);
    }
}
