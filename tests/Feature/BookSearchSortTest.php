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
        // 検索に一致する書籍
        Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        // 検索に一致しない書籍
        Book::factory()->create([
            'title' => 'PHP入門',
        ]);

        // キーワード検索を実行
        $response = $this->get('/books?keyword=Laravel');

        // レスポンスを確認
        $response->assertOk();

        // 検索結果に表示される書籍を確認
        $response->assertSee('Laravel入門');
        $response->assertDontSee('PHP入門');
    }

    public function test_books_can_be_searched_by_author(): void
    {
        // 著者名が検索キーワードに一致する書籍
        Book::factory()->create([
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
        ]);

        // 著者名が一致しない書籍
        Book::factory()->create([
            'title' => '羅生門',
            'author' => '芥川龍之介',
        ]);

        // 著者名でキーワード検索
        $response = $this->get('/books?keyword=夏目');

        // 検索結果を検証
        $response->assertOk();
        $response->assertSee('吾輩は猫である');
        $response->assertDontSee('羅生門');
    }

    public function test_books_can_be_searched_by_genre(): void
    {
        // ジャンルを2つ作成
        $novel = Genre::factory()->create([
            'name' => '小説',
        ]);

        $programming = Genre::factory()->create([
            'name' => 'プログラミング',
        ]);

        // 書籍を2冊作成
        $novelBook = Book::factory()->create([
            'title' => '吾輩は猫である',
        ]);

        $programmingBook = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        // 書籍とジャンルを関連付ける
        $novelBook->genres()->attach($novel->id);
        $programmingBook->genres()->attach($programming->id);

        // 「小説」ジャンルで検索
        $response = $this->get('/books?genre='.$novel->id);

        // 検索結果を検証
        $response->assertOk();
        $response->assertSee('吾輩は猫である');
        $response->assertDontSee('Laravel入門');
    }

    public function test_books_can_be_sorted_by_newest(): void
    {
        // 登録日時が古い書籍
        Book::factory()->create([
            'title' => '古い書籍',
            'created_at' => '2026-01-01 10:00:00',
        ]);

        // 登録日時が新しい書籍
        Book::factory()->create([
            'title' => '新しい書籍',
            'created_at' => '2026-02-01 10:00:00',
        ]);

        // 新しい順で並び替え
        $response = $this->get('/books?sort=newest');

        // レスポンスが正常であることを確認
        $response->assertOk();

        // 「新しい書籍」が「古い書籍」より先に表示される
        $response->assertSeeInOrder([
            '新しい書籍',
            '古い書籍',
        ]);
    }

    public function test_books_can_be_sorted_by_oldest(): void
    {
        // 登録日時が古い書籍
        Book::factory()->create([
            'title' => '古い書籍',
            'created_at' => '2026-01-01 10:00:00',
        ]);

        // 登録日時が新しい書籍
        Book::factory()->create([
            'title' => '新しい書籍',
            'created_at' => '2026-02-01 10:00:00',
        ]);

        // 古い順で並び替え
        $response = $this->get('/books?sort=oldest');

        // レスポンスが正常であることを確認
        $response->assertOk();

        // 古い書籍が先、新しい書籍が後
        $response->assertSeeInOrder([
            '古い書籍',
            '新しい書籍',
        ]);
    }

    public function test_books_can_be_sorted_by_rating(): void
    {
        // 評価が低い書籍
        $lowRatedBook = Book::factory()->create([
            'title' => '評価が低い書籍',
        ]);

        // 評価が高い書籍
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

        // 評価が高い順で並び替え
        $response = $this->get('/books?sort=rating');

        // レスポンスが正常であることを確認
        $response->assertOk();

        // 平均評価4.5の書籍が平均評価2.0の書籍より先に表示される
        $response->assertSeeInOrder([
            '評価が高い書籍',
            '評価が低い書籍',
        ]);
    }

    public function test_books_can_be_sorted_by_title(): void
    {
        // タイトルが後ろに並ぶ書籍
        Book::factory()->create([
            'title' => 'Zebra',
        ]);

        // タイトルが先に並ぶ書籍
        Book::factory()->create([
            'title' => 'Apple',
        ]);

        // タイトル昇順で並び替え
        $response = $this->get('/books?sort=title');

        // レスポンスが正常であることを確認
        $response->assertOk();

        // Apple → Zebra の順番で表示される
        $response->assertSeeInOrder([
            'Apple',
            'Zebra',
        ]);
    }

    public function test_books_can_be_searched_by_keyword_and_genre(): void
    {
        // ジャンルを作成
        $novel = Genre::factory()->create([
            'name' => '小説',
        ]);

        $programming = Genre::factory()->create([
            'name' => 'プログラミング',
        ]);

        // キーワードとジャンルの両方に一致する書籍
        $matchingBook = Book::factory()->create([
            'title' => 'Laravel小説',
        ]);
        $matchingBook->genres()->attach($novel->id);

        // キーワードのみ一致する書籍
        $keywordOnlyBook = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);
        $keywordOnlyBook->genres()->attach($programming->id);

        // ジャンルのみ一致する書籍
        $genreOnlyBook = Book::factory()->create([
            'title' => '吾輩は猫である',
        ]);
        $genreOnlyBook->genres()->attach($novel->id);

        // キーワードとジャンルを組み合わせて検索
        $response = $this->get(
            '/books?keyword=Laravel&genre='.$novel->id
        );

        // 検索結果を検証
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

        // レビューを登録
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

        // キーワード検索＋評価順
        $response = $this->get('/books?keyword=Laravel&sort=rating');

        // レスポンスを確認
        $response->assertOk();

        // 検索に一致した書籍が評価の高い順に表示される
        $response->assertSeeInOrder([
            'Laravel応用',
            'Laravel基礎',
        ]);

        // キーワードに一致しない書籍は表示されない
        $response->assertDontSee('PHP入門');
    }

    public function test_books_can_be_searched_by_genre_and_sorted_by_title(): void
    {
        // ジャンルを作成
        $novel = Genre::factory()->create([
            'name' => '小説',
        ]);

        $programming = Genre::factory()->create([
            'name' => 'プログラミング',
        ]);

        // 小説ジャンルの書籍を作成
        $zebraBook = Book::factory()->create([
            'title' => 'Zebra',
        ]);

        $appleBook = Book::factory()->create([
            'title' => 'Apple',
        ]);

        // プログラミングジャンルの書籍を作成
        $bananaBook = Book::factory()->create([
            'title' => 'Banana',
        ]);

        // 書籍とジャンルを関連付ける
        $zebraBook->genres()->attach($novel->id);
        $appleBook->genres()->attach($novel->id);
        $bananaBook->genres()->attach($programming->id);

        // 小説ジャンルで検索し、タイトル昇順で並び替え
        $response = $this->get(
            '/books?genre='.$novel->id.'&sort=title'
        );

        // レスポンスが正常であることを確認
        $response->assertOk();

        // 小説ジャンルの書籍がタイトル昇順で表示される
        $response->assertSeeInOrder([
            'Apple',
            'Zebra',
        ]);

        // 別ジャンルの書籍は表示されない
        $response->assertDontSee('Banana');
    }

    public function test_books_can_be_searched_by_keyword_and_genre_and_sorted_by_rating(): void
    {
        // ジャンルを作成
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

        // レビューを登録
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

        // キーワード＋ジャンル＋評価順
        $response = $this->get(
            '/books?keyword=Laravel&genre='.$novel->id.'&sort=rating'
        );

        // レスポンスを検証
        $response->assertOk();

        // 3条件に一致した書籍が平均評価の高い順に表示される
        $response->assertSeeInOrder([
            'Laravel小説 応用編',
            'Laravel小説 基礎編',
        ]);

        // 条件に一致しない書籍は表示されない
        $response->assertDontSee('Laravelプログラミング入門');
        $response->assertDontSee('吾輩は猫である');
    }

    public function test_pagination_preserves_search_and_sort_conditions(): void
    {
        // 検索対象の書籍を11冊作成
        for ($i = 1; $i <= 11; $i++) {
            Book::factory()->create([
                'title' => sprintf('Laravel Book %02d', $i),
            ]);
        }

        // 検索対象外の書籍
        Book::factory()->create([
            'title' => 'PHP入門',
        ]);

        // 1ページ目を取得
        $response = $this->get('/books?keyword=Laravel&sort=title');

        $response->assertOk();
        $response->assertSee('Laravel Book 01');
        $response->assertSee('Laravel Book 10');
        $response->assertDontSee('Laravel Book 11');
        $response->assertDontSee('PHP入門');

        // Laravelが生成した2ページ目のURLを取得
        $paginator = $response->viewData('books');
        $nextPageUrl = $paginator->nextPageUrl();

        // 2ページ目のURLに検索条件が保持されているか確認
        $this->assertStringContainsString('keyword=Laravel', $nextPageUrl);
        $this->assertStringContainsString('sort=title', $nextPageUrl);
        $this->assertStringContainsString('page=2', $nextPageUrl);

        // 実際に生成されたURLで2ページ目を取得
        $response = $this->get($nextPageUrl);

        $response->assertOk();
        $response->assertSee('Laravel Book 11');
        $response->assertDontSee('Laravel Book 01');
        $response->assertDontSee('PHP入門');
    }

    public function test_books_are_sorted_by_newest_by_default(): void
    {
        // 登録日時が古い書籍
        Book::factory()->create([
            'title' => '古い書籍',
            'created_at' => '2026-01-01 10:00:00',
        ]);

        // 登録日時が新しい書籍
        Book::factory()->create([
            'title' => '新しい書籍',
            'created_at' => '2026-02-01 10:00:00',
        ]);

        // sortを指定せずに一覧画面へアクセス
        $response = $this->get('/books');

        // 正常に表示されることを確認
        $response->assertOk();

        // 新しい書籍が先に表示される
        $response->assertSeeInOrder([
            '新しい書籍',
            '古い書籍',
        ]);
    }

    public function test_reset_link_clears_search_conditions(): void
    {
        // 書籍を2冊作成
        Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        Book::factory()->create([
            'title' => 'PHP入門',
        ]);

        // キーワード検索を実行
        $response = $this->get('/books?keyword=Laravel');

        $response->assertOk();
        $response->assertSee('Laravel入門');
        $response->assertDontSee('PHP入門');

        // リセットリンクのURLを確認
        $resetUrl = route('books.index');

        $response->assertSee(
            'href="'.$resetUrl.'"',
            false
        );

        // リセット先のURLにアクセス
        $response = $this->get($resetUrl);

        // 検索条件なしですべての書籍が表示される
        $response->assertOk();
        $response->assertSee('Laravel入門');
        $response->assertSee('PHP入門');
    }

    public function test_search_returns_no_books_when_keyword_does_not_match(): void
    {
        // 書籍を作成
        Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        // 一致しないキーワードで検索
        $response = $this->get('/books?keyword=Python');

        // エラーにならないことを確認
        $response->assertOk();

        // 検索結果が0件であることを確認
        $paginator = $response->viewData('books');

        $this->assertSame(0, $paginator->total());

        // 一致しない書籍が表示されないことを確認
        $response->assertDontSee('Laravel入門');
    }

    public function test_books_without_reviews_are_sorted_last_by_rating(): void
    {
        // レビューがない書籍
        Book::factory()->create([
            'title' => '未評価の書籍',
        ]);

        // 平均評価2.0の書籍
        $lowRatedBook = Book::factory()->create([
            'title' => '低評価の書籍',
        ]);

        // 平均評価5.0の書籍
        $highRatedBook = Book::factory()->create([
            'title' => '高評価の書籍',
        ]);

        // レビューを登録
        Review::factory()->create([
            'book_id' => $lowRatedBook->id,
            'rating' => 2,
        ]);

        Review::factory()->create([
            'book_id' => $highRatedBook->id,
            'rating' => 5,
        ]);

        // 評価順で書籍一覧を取得
        $response = $this->get('/books?sort=rating');

        // 正常に表示されることを確認
        $response->assertOk();

        // 評価が高い順、未評価の書籍は最後
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

        // それぞれの書籍にレビューを登録
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

        // 評価順で書籍一覧を取得
        $response = $this->get('/books?sort=rating');

        // 正常に表示されることを確認
        $response->assertOk();

        // 評価の高い順、同じ評価ならID昇順
        $response->assertSeeInOrder([
            'PHP入門',
            'Laravel基礎',
            'Laravel応用',
        ]);
    }

    public function test_books_with_same_created_at_are_sorted_by_id(): void
    {
        // 登録日時を統一
        $createdAt = '2026-01-01 10:00:00';

        // IDが小さい書籍
        $firstBook = Book::factory()->create([
            'title' => '最初の書籍',
            'created_at' => $createdAt,
        ]);

        // IDが大きい書籍
        $secondBook = Book::factory()->create([
            'title' => '次の書籍',
            'created_at' => $createdAt,
        ]);

        // IDの大小関係を確認
        $this->assertTrue($firstBook->id < $secondBook->id);

        // 新しい順で書籍一覧を取得
        $response = $this->get('/books?sort=newest');

        $response->assertOk();

        // 登録日時が同じ場合はID昇順
        $response->assertSeeInOrder([
            '最初の書籍',
            '次の書籍',
        ]);

        // 古い順でも確認
        $response = $this->get('/books?sort=oldest');

        $response->assertOk();

        $response->assertSeeInOrder([
            '最初の書籍',
            '次の書籍',
        ]);
    }
}
