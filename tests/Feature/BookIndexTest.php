<?php

namespace Tests\Feature;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_book_index(): void
    {
        $this->get(route('books.index'))
            ->assertOk()
            ->assertViewIs('books.index');
    }

    public function test_books_are_displayed_in_latest_order(): void
    {
        $olderBook = Book::factory()->create([
            'created_at' => '2026-01-01 10:00:00',
        ]);

        $newerBook = Book::factory()->create([
            'created_at' => '2026-01-02 10:00:00',
        ]);

        $this->get(route('books.index'))
            ->assertOk()
            ->assertViewHas('books', function ($books) use ($olderBook, $newerBook) {
                return $books->pluck('id')->all() === [
                    $newerBook->id,
                    $olderBook->id,
                ];
            });
    }

    public function test_book_index_displays_ten_books_per_page(): void
    {
        Book::factory()->count(11)->create();

        $this->get(route('books.index'))
            ->assertOk()
            ->assertViewHas('books', function ($books) {
                return $books->count() === 10
                    && $books->total() === 11
                    && $books->lastPage() === 2;
            });
    }

    public function test_book_without_reviews_displays_no_rating_on_index(): void
    {
        $book = Book::factory()->create([
            'title' => '評価なし確認用の書籍',
        ]);

        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('評価なし確認用の書籍')
            ->assertSee('評価なし');
    }

    public function test_guest_can_see_create_book_link_but_must_login_to_access(): void
    {
        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('書籍を登録')
            ->assertSee(route('books.create'));

        $this->get(route('books.create'))
            ->assertRedirect(route('login'));
    }
}