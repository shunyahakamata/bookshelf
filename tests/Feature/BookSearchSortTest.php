<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookSearchSortTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_can_be_searched_by_title(): void
    {
        Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        Book::factory()->create([
            'title' => 'PHP入門',
        ]);

        $response = $this->get('/books?keyword=Laravel');

        $response->assertOk();

        $response->assertSee('Laravel入門');
        $response->assertDontSee('PHP入門');
    }

    public function test_books_can_be_searched_by_author(): void
    {
        Book::factory()->create([
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
        ]);

        Book::factory()->create([
            'title' => '羅生門',
            'author' => '芥川龍之介',
        ]);

        $response = $this->get('/books?keyword=夏目');

        $response->assertOk();
        $response->assertSee('吾輩は猫である');
        $response->assertDontSee('羅生門');
    }

    public function test_books_can_be_searched_by_genre(): void
    {
        $novel = Genre::factory()->create([
            'name' => '小説',
        ]);

        $programming = Genre::factory()->create([
            'name' => 'プログラミング',
        ]);

        $novelBook = Book::factory()->create([
            'title' => '吾輩は猫である',
        ]);

        $programmingBook = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $novelBook->genres()->attach($novel->id);
        $programmingBook->genres()->attach($programming->id);

        $response = $this->get('/books?genre='.$novel->id);

        $response->assertOk();
        $response->assertSee('吾輩は猫である');
        $response->assertDontSee('Laravel入門');
    }

    public function test_books_can_be_sorted_by_latest(): void
    {
        Book::factory()->create([
            'title' => '古い書籍',
            'created_at' => '2026-01-01 10:00:00',
        ]);

        Book::factory()->create([
            'title' => '新しい書籍',
            'created_at' => '2026-02-01 10:00:00',
        ]);

        $response = $this->get('/books?sort=latest');

        $response->assertOk();

        $response->assertSeeInOrder([
            '新しい書籍',
            '古い書籍',
        ]);
    }

    public function test_books_can_be_sorted_by_oldest(): void
    {
        Book::factory()->create([
            'title' => '古い書籍',
            'created_at' => '2026-01-01 10:00:00',
        ]);

        Book::factory()->create([
            'title' => '新しい書籍',
            'created_at' => '2026-02-01 10:00:00',
        ]);

        $response = $this->get('/books?sort=oldest');

        $response->assertOk();

        $response->assertSeeInOrder([
            '古い書籍',
            '新しい書籍',
        ]);
    }

    public function test_books_can_be_sorted_by_rating(): void
    {
        $lowRatedBook = Book::factory()->create([
            'title' => '評価が低い書籍',
        ]);

        $highRatedBook = Book::factory()->create([
            'title' => '評価が高い書籍',
        ]);

        // 低評価の書籍にレビューを登録（平均2.0）
        Review::factory()->create([
            'book_id' => $lowRatedBook->id,
            'rating' => 2,
        ]);

        // 高評価の書籍にレビューを2件登録（平均4.5）
        Review::factory()->create([
            'book_id' => $highRatedBook->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'book_id' => $highRatedBook->id,
            'rating' => 5,
        ]);

        $response = $this->get('/books?sort=rating');

        $response->assertOk();

        $response->assertSeeInOrder([
            '評価が高い書籍',
            '評価が低い書籍',
        ]);
    }

    public function test_books_can_be_sorted_by_title(): void
    {
        Book::factory()->create([
            'title' => 'Zebra',
        ]);

        Book::factory()->create([
            'title' => 'Apple',
        ]);

        $response = $this->get('/books?sort=title');

        $response->assertOk();

        $response->assertSeeInOrder([
            'Apple',
            'Zebra',
        ]);
    }

    public function test_books_can_be_searched_by_keyword_and_genre(): void
    {
        $novel = Genre::factory()->create([
            'name' => '小説',
        ]);

        $programming = Genre::factory()->create([
            'name' => 'プログラミング',
        ]);

        $matchingBook = Book::factory()->create([
            'title' => 'Laravel小説',
        ]);
        $matchingBook->genres()->attach($novel->id);

        $keywordOnlyBook = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);
        $keywordOnlyBook->genres()->attach($programming->id);

        $genreOnlyBook = Book::factory()->create([
            'title' => '吾輩は猫である',
        ]);
        $genreOnlyBook->genres()->attach($novel->id);

        $response = $this->get(
            '/books?keyword=Laravel&genre='.$novel->id
        );

        $response->assertOk();
        $response->assertSee('Laravel小説');
        $response->assertDontSee('Laravel入門');
        $response->assertDontSee('吾輩は猫である');
    }

    public function test_books_can_be_searched_by_keyword_and_sorted_by_rating(): void
    {
        // キーワードに一致する書籍（平均評価2.0）
        $lowRatedBook = Book::factory()->create([
            'title' => 'Laravel基礎',
        ]);

        // キーワードに一致する書籍（平均評価4.5）
        $highRatedBook = Book::factory()->create([
            'title' => 'Laravel応用',
        ]);

        // キーワードに一致しない書籍（平均評価5.0）
        $unrelatedBook = Book::factory()->create([
            'title' => 'PHP入門',
        ]);

        Review::factory()->create([
            'book_id' => $lowRatedBook->id,
            'rating' => 2,
        ]);

        Review::factory()->create([
            'book_id' => $highRatedBook->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'book_id' => $highRatedBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $unrelatedBook->id,
            'rating' => 5,
        ]);

        $response = $this->get('/books?keyword=Laravel&sort=rating');

        $response->assertOk();

        $response->assertSeeInOrder([
            'Laravel応用',
            'Laravel基礎',
        ]);

        $response->assertDontSee('PHP入門');
    }

    public function test_books_can_be_searched_by_genre_and_sorted_by_title(): void
    {
        $novel = Genre::factory()->create([
            'name' => '小説',
        ]);

        $programming = Genre::factory()->create([
            'name' => 'プログラミング',
        ]);

        $zebraBook = Book::factory()->create([
            'title' => 'Zebra',
        ]);

        $appleBook = Book::factory()->create([
            'title' => 'Apple',
        ]);

        $bananaBook = Book::factory()->create([
            'title' => 'Banana',
        ]);

        $zebraBook->genres()->attach($novel->id);
        $appleBook->genres()->attach($novel->id);
        $bananaBook->genres()->attach($programming->id);

        $response = $this->get(
            '/books?genre='.$novel->id.'&sort=title'
        );

        $response->assertOk();

        $response->assertSeeInOrder([
            'Apple',
            'Zebra',
        ]);

        $response->assertDontSee('Banana');
    }

    public function test_books_can_be_searched_by_keyword_and_genre_and_sorted_by_rating(): void
    {
        $novel = Genre::factory()->create([
            'name' => '小説',
        ]);

        $programming = Genre::factory()->create([
            'name' => 'プログラミング',
        ]);

        // 3条件に一致する書籍（平均評価2.0）
        $lowRatedBook = Book::factory()->create([
            'title' => 'Laravel小説 基礎編',
        ]);
        $lowRatedBook->genres()->attach($novel->id);

        // 3条件に一致する書籍（平均評価4.5）
        $highRatedBook = Book::factory()->create([
            'title' => 'Laravel小説 応用編',
        ]);
        $highRatedBook->genres()->attach($novel->id);

        // キーワードは一致するが、ジャンルが異なる書籍
        $differentGenreBook = Book::factory()->create([
            'title' => 'Laravelプログラミング入門',
        ]);
        $differentGenreBook->genres()->attach($programming->id);

        // ジャンルは一致するが、キーワードが異なる書籍
        $differentKeywordBook = Book::factory()->create([
            'title' => '吾輩は猫である',
        ]);
        $differentKeywordBook->genres()->attach($novel->id);

        Review::factory()->create([
            'book_id' => $lowRatedBook->id,
            'rating' => 2,
        ]);

        Review::factory()->create([
            'book_id' => $highRatedBook->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'book_id' => $highRatedBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $differentGenreBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $differentKeywordBook->id,
            'rating' => 5,
        ]);

        $response = $this->get(
            '/books?keyword=Laravel&genre='.$novel->id.'&sort=rating'
        );

        $response->assertOk();

        $response->assertSeeInOrder([
            'Laravel小説 応用編',
            'Laravel小説 基礎編',
        ]);

        $response->assertDontSee('Laravelプログラミング入門');
        $response->assertDontSee('吾輩は猫である');
    }

    public function test_pagination_preserves_search_and_sort_conditions(): void
    {
        $genre = Genre::factory()->create([
            'name' => 'プログラミング',
        ]);

        $otherGenre = Genre::factory()->create([
            'name' => 'その他',
        ]);

        for ($i = 1; $i <= 11; $i++) {
            $book = Book::factory()->create([
                'title' => sprintf('Laravel Book %02d', $i),
            ]);

            $book->genres()->attach($genre->id);
        }

        $book = Book::factory()->create([
            'title' => 'PHP入門',
            'author' => '著者A',
        ]);

        $book->genres()->attach($genre->id);

        $book = Book::factory()->create([
            'title' => 'Laravel実践ガイド',
        ]);

        $book->genres()->attach($otherGenre->id);

        $response = $this->get(
            '/books?keyword=Laravel&genre='.$genre->id.'&sort=title'
        );

        $response->assertOk();
        $response->assertSee('Laravel Book 01');
        $response->assertSee('Laravel Book 10');
        $response->assertDontSee('Laravel Book 11');
        $response->assertDontSee('PHP入門');
        $response->assertDontSee('Laravel実践ガイド');

        $paginator = $response->viewData('books');
        $nextPageUrl = $paginator->nextPageUrl();

        $this->assertStringContainsString('keyword=Laravel', $nextPageUrl);
        $this->assertStringContainsString('genre='.$genre->id, $nextPageUrl);
        $this->assertStringContainsString('sort=title', $nextPageUrl);
        $this->assertStringContainsString('page=2', $nextPageUrl);

        $response = $this->get($nextPageUrl);

        $response->assertOk();
        $response->assertSee('Laravel Book 11');
        $response->assertDontSee('Laravel Book 01');
        $response->assertDontSee('PHP入門');
        $response->assertDontSee('Laravel実践ガイド');
    }

    public function test_books_are_sorted_by_latest_by_default(): void
    {
        Book::factory()->create([
            'title' => '古い書籍',
            'created_at' => '2026-01-01 10:00:00',
        ]);

        Book::factory()->create([
            'title' => '新しい書籍',
            'created_at' => '2026-02-01 10:00:00',
        ]);

        $response = $this->get('/books');

        $response->assertOk();

        $response->assertSeeInOrder([
            '新しい書籍',
            '古い書籍',
        ]);
    }

    public function test_reset_link_clears_search_conditions(): void
    {
        Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        Book::factory()->create([
            'title' => 'PHP入門',
        ]);

        $response = $this->get('/books?keyword=Laravel');

        $response->assertOk();
        $response->assertSee('Laravel入門');
        $response->assertDontSee('PHP入門');

        $resetUrl = route('books.index');

        $response->assertSee(
            'href="'.$resetUrl.'"',
            false
        );

        $response = $this->get($resetUrl);

        $response->assertOk();
        $response->assertSee('Laravel入門');
        $response->assertSee('PHP入門');
    }

    public function test_search_returns_no_books_when_keyword_does_not_match(): void
    {
        Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $response = $this->get('/books?keyword=Python');

        $response->assertOk();

        $paginator = $response->viewData('books');

        $this->assertSame(0, $paginator->total());

        $response->assertDontSee('Laravel入門');
    }

    public function test_books_without_reviews_are_sorted_last_by_rating(): void
    {
        // レビューがない書籍
        Book::factory()->create([
            'title' => '未評価の書籍',
        ]);

        $lowRatedBook = Book::factory()->create([
            'title' => '低評価の書籍',
        ]);

        $highRatedBook = Book::factory()->create([
            'title' => '高評価の書籍',
        ]);

        Review::factory()->create([
            'book_id' => $lowRatedBook->id,
            'rating' => 2,
        ]);

        Review::factory()->create([
            'book_id' => $highRatedBook->id,
            'rating' => 5,
        ]);

        $response = $this->get('/books?sort=rating');

        $response->assertOk();

        $response->assertSeeInOrder([
            '高評価の書籍',
            '低評価の書籍',
            '未評価の書籍',
        ]);
    }

    public function test_books_with_same_rating_are_sorted_by_id(): void
    {
        // IDが小さい書籍（平均評価4.0）
        $firstBook = Book::factory()->create([
            'title' => 'Laravel基礎',
        ]);

        // 平均評価5.0の書籍
        $highRatedBook = Book::factory()->create([
            'title' => 'PHP入門',
        ]);

        // IDが大きい書籍（平均評価4.0）
        $lastBook = Book::factory()->create([
            'title' => 'Laravel応用',
        ]);

        Review::factory()->create([
            'book_id' => $firstBook->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'book_id' => $highRatedBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $lastBook->id,
            'rating' => 4,
        ]);

        $response = $this->get('/books?sort=rating');

        $response->assertOk();

        $response->assertSeeInOrder([
            'PHP入門',
            'Laravel基礎',
            'Laravel応用',
        ]);
    }

    public function test_books_with_same_created_at_are_sorted_by_id(): void
    {
        $createdAt = '2026-01-01 10:00:00';

        $firstBook = Book::factory()->create([
            'title' => '最初の書籍',
            'created_at' => $createdAt,
        ]);

        $secondBook = Book::factory()->create([
            'title' => '次の書籍',
            'created_at' => $createdAt,
        ]);

        $this->assertTrue($firstBook->id < $secondBook->id);

        $response = $this->get('/books?sort=latest');

        $response->assertOk();

        // 登録日時が同じ場合はID昇順
        $response->assertSeeInOrder([
            '最初の書籍',
            '次の書籍',
        ]);

        $response = $this->get('/books?sort=oldest');

        $response->assertOk();

        $response->assertSeeInOrder([
            '最初の書籍',
            '次の書籍',
        ]);
    }

    public function test_books_can_be_searched_by_zero_keyword(): void
    {
        Book::factory()->create([
            'title' => 'PHP 8.0 入門',
            'author' => '著者A',
        ]);

        Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '著者B',
        ]);

        $response = $this->get('/books?keyword=0');

        $response->assertOk();
        $response->assertSee('PHP 8.0 入門');
        $response->assertDontSee('Laravel入門');
        $response->assertSee('検索結果: 1件');
    }
}
