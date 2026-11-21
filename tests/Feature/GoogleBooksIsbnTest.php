<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleBooksIsbnTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_isbn_length_returns_400(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/books/isbn/123456789012');

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'ISBNは13桁で入力してください。',
            ]);
    }

    public function test_valid_isbn_returns_book_information(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => '吾輩は猫である',
                            'authors' => ['夏目漱石'],
                            'description' => '猫の視点から描かれた小説',
                            'publishedDate' => '1980',
                            'imageLinks' => [
                                'thumbnail' => 'https://example.com/book.jpg',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/books/isbn/9784101010014');

        $response->assertStatus(200)
            ->assertJson([
                'title' => '吾輩は猫である',
                'author' => '夏目漱石',
                'description' => '猫の視点から描かれた小説',
                'published_date' => '1980',
                'image_url' => 'https://example.com/book.jpg',
            ]);
    }

    public function test_book_not_found_returns_404(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 0,
                'items' => [],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/books/isbn/9784101010014');

        $response->assertStatus(404)
            ->assertJson([
                'error' => '書籍が見つかりませんでした。',
            ]);
    }

    public function test_api_rate_limit_returns_429(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'error' => [
                    'code' => 429,
                    'message' => 'Quota exceeded',
                ],
            ], 429),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/books/isbn/9784101010014');

        $response->assertStatus(429)
            ->assertJson([
                'error' => 'APIの利用制限に達しました。',
            ]);
    }

    public function test_api_server_error_returns_500(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'error' => [
                    'code' => 500,
                    'message' => 'Internal Server Error',
                ],
            ], 500),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/books/isbn/9784101010014');

        $response->assertStatus(500)
            ->assertJson([
                'error' => 'Google Books APIとの通信に失敗しました。',
            ]);
    }

    public function test_api_connection_failure_returns_500(): void
    {
        Http::fake(function () {
            throw new ConnectionException(
                'Google Books API connection failed'
            );
        });

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/books/isbn/9784101010014');

        $response->assertStatus(500)
            ->assertJson([
                'error' => 'Google Books APIとの通信に失敗しました。',
            ]);
    }
}
