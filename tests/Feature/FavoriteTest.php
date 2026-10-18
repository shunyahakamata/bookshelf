<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_add_and_remove_favorite(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('favorites.toggle', $book))
            ->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('favorites.toggle', $book))
            ->assertRedirect(route('books.show', $book));

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_guest_cannot_toggle_favorite(): void
    {
        $book = Book::factory()->create();

        $this->post(route('favorites.toggle', $book))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_authenticated_user_can_view_favorite_list(): void
    {
        $user = User::factory()->create();
        $favoriteBook = Book::factory()->create();
        $otherBook = Book::factory()->create();

        $user->favoriteBooks()->attach($favoriteBook->id);

        $this->actingAs($user)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertViewIs('favorites.index')
            ->assertViewHas('books', function ($books) use ($favoriteBook, $otherBook) {
                return $books->contains('id', $favoriteBook->id)
                    && ! $books->contains('id', $otherBook->id);
            });
    }

    public function test_guest_cannot_view_favorite_list(): void
    {
        $this->get(route('favorites.index'))
            ->assertRedirect(route('login'));
    }

    public function test_favorite_list_displays_ten_books_per_page(): void
{
    $user = User::factory()->create();

    $books = Book::factory()->count(11)->create();

    $user->favoriteBooks()->attach($books->pluck('id')->all());

    $this->actingAs($user)
        ->get(route('favorites.index'))
        ->assertOk()
        ->assertViewHas('books', function ($favoriteBooks) {
            return $favoriteBooks->count() === 10
                && $favoriteBooks->total() === 11
                && $favoriteBooks->lastPage() === 2;
        });
}
}