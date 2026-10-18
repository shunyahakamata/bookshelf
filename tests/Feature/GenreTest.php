<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_genre(): void
    {
        $user = User::factory()->create();

        $data = ['name' => 'テストジャンル'];

        $response = $this->actingAs($user)
            ->post(route('genres.store'), $data);

        $this->assertDatabaseHas('genres', [
            'name' => 'テストジャンル',
        ]);

        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas(
            'success',
            'ジャンルを登録しました。'
        );
    }

    public function test_genre_cannot_be_created_when_name_is_duplicated(): void
    {
        $user = User::factory()->create();

        $existingGenre = Genre::factory()->create();

        $data = [
            'name' => $existingGenre->name,
        ];

        $response = $this->actingAs($user)
            ->post(route('genres.store'), $data);

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseCount('genres', 1);
    }

    public function test_authenticated_user_can_update_a_genre(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $data = ['name' => '更新したジャンル'];

        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), $data);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '更新したジャンル',
        ]);

        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas(
            'success',
            'ジャンルを更新しました。'
        );
    }

    public function test_genre_can_be_updated_without_changing_its_name(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $data = [
            'name' => $genre->name,
        ];

        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), $data);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => $genre->name,
        ]);

        $response->assertRedirect(route('genres.index'));
    }

    public function test_genre_cannot_be_updated_to_another_genres_name(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $otherGenre = Genre::factory()->create();

        $originalName = $genre->name;

        $data = [
            'name' => $otherGenre->name,
        ];

        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), $data);

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => $originalName,
        ]);
    }

    public function test_authenticated_user_can_delete_an_unattached_genre(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);

        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas(
            'success',
            'ジャンルを削除しました。'
        );
    }

    public function test_attached_genre_cannot_be_deleted(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $book = Book::factory()->create();

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);

        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas(
            'error',
            'ジャンルを削除できませんでした。'
        );
    }
}
