<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_authenticated_user_can_create_a_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $data = [
            'title' => 'テスト本',
            'author' => '作者',
            'isbn' => '1234567890123',
            'published_date' => '2026-10-05',
            'description' => '感想',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->post('/books', $data);

        $this->assertDatabaseHas('books', [
            'title' => 'テスト本',
            'author' => '作者',
            'isbn' => '1234567890123',
            'user_id' => $user->id,
        ]);

        $book = Book::where('isbn', $data['isbn'])->first();

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            '書籍を登録しました。'
        );
    }

    public function test_book_cannot_be_created_when_isbn_is_not_13_characters(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $data = [
            'title' => 'テスト本',
            'author' => '作者',
            'isbn' => '123456789012',
            'published_date' => '2026-10-05',
            'description' => '感想',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->post('/books', $data);

        $response->assertSessionHasErrors('isbn');

        $this->assertDatabaseMissing('books', [
            'isbn' => '123456789012',
        ]);
    }

    public function test_book_cannot_be_created_when_isbn_is_duplicated(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $existingBook = Book::factory()->create();

        $data = [
            'title' => 'テスト本',
            'author' => '作者',
            'isbn' => $existingBook->isbn,
            'published_date' => '2026-10-05',
            'description' => '感想',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->post('/books', $data);

        $response->assertSessionHasErrors('isbn');

        $this->assertDatabaseCount('books', 1);
    }

    public function test_unauthenticated_user_cannot_create_a_book(): void
    {
        $genre = Genre::factory()->create();
        $data = [
            'title' => 'テスト本',
            'author' => '作者',
            'isbn' => '1234567890123',
            'published_date' => '2026-10-05',
            'description' => '感想',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        $response = $this->post('/books', $data);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('books', [
            'isbn' => $data['isbn'],
        ]);
    }

    public function test_book_owner_can_update_a_book(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::factory()->create();

        $data = [
            'title' => '更新された本',
            'author' => '作者',
            'isbn' => '1234567890123',
            'published_date' => '2026-10-05',
            'description' => '感想',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->put(route('books.update', $book), $data);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新された本',
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'genre_id' => $genre->id,
            'book_id' => $book->id,
        ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            '書籍を更新しました。'
        );
    }

    public function test_user_cannot_update_another_users_book(): void
    {

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $originalTitle = $book->title;

        $genre = Genre::factory()->create();

        $data = [
            'title' => '更新された本',
            'author' => '作者',
            'isbn' => '1234567890123',
            'published_date' => '2026-10-05',
            'description' => '感想',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->put(route('books.update', $book), $data);

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $originalTitle,
            'user_id' => $otherUser->id,
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '更新された本',
        ]);
    }

    public function test_book_can_be_updated_without_changing_its_isbn(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::factory()->create();

        $data = [
            'title' => 'ISBNはそのままで更新',
            'author' => '作者',
            'isbn' => $book->isbn,
            'published_date' => '2026-10-05',
            'description' => '感想',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->put(route('books.update', $book), $data);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            '書籍を更新しました。'
        );

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'ISBNはそのままで更新',
            'isbn' => $book->isbn,
        ]);
    }

    public function test_book_cannot_be_updated_to_another_books_isbn(): void
    {

        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $otherBook = Book::factory()->create();

        $genre = Genre::factory()->create();

        $originalTitle = $book->title;

        $data = [
            'title' => '更新された本',
            'author' => '作者',
            'isbn' => $otherBook->isbn,
            'published_date' => '2026-10-05',
            'description' => '感想',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->put(route('books.update', $book), $data);

        $response->assertSessionHasErrors('isbn');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $originalTitle,
            'isbn' => $book->isbn,
        ]);
    }

    public function test_unauthenticated_user_cannot_update_a_book(): void
    {
        $book = Book::factory()->create();

        $originalTitle = $book->title;

        $genre = Genre::factory()->create();

        $data = [
            'title' => '更新された本',
            'author' => '作者',
            'isbn' => $book->isbn,
            'published_date' => '2026-10-05',
            'description' => '感想',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        $response = $this->put(route('books.update', $book), $data);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $originalTitle,
            'isbn' => $book->isbn,
        ]);
    }

    public function test_book_owner_can_delete_a_book(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('books.destroy', $book));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $response->assertRedirect(route('books.index'));

        $response->assertSessionHas(
            'success',
            '書籍を削除しました。'
        );
    }

    public function test_user_cannot_delete_another_users_book(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('books.destroy', $book));

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_delete_a_book(): void
    {
        $book = Book::factory()->create();

        $response = $this->delete(route('books.destroy', $book));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }
}
