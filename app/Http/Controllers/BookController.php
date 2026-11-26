<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = $request->query('keyword');

        $genre = $request->query('genre');

        $sort = $request->query('sort', 'latest');

        $books = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->when($request->filled('keyword'), function ($query) use ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('title', 'like', '%'.$keyword.'%')
                        ->orWhere('author', 'like', '%'.$keyword.'%');
                });
            })
            ->when($genre, function ($query) use ($genre) {
                $query->whereHas('genres', function ($query) use ($genre) {
                    $query->where('genres.id', $genre);
                });
            });
        switch ($sort) {
            case 'oldest':
                $books->orderBy('created_at', 'asc');
                break;

            case 'rating':
                $books->orderByDesc('reviews_avg_rating');
                break;

            case 'title':
                $books->orderBy('title', 'asc');
                break;

            case 'latest':
            default:
                $books->latest();
                break;
        }

        $books = $books
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        $genres = Genre::all();

        return view('books.index', compact('books', 'genres'));
    }

    public function show(Book $book): View
    {
        $book->load([
            'genres',
            'reviews' => function ($query) {
                $query->orderBy('rating', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'asc');
            },
            'reviews.user',
            'reviews.likedByUsers',
        ]);

        $book->loadAvg('reviews', 'rating');

        $hasReviewed = auth()->check()
            && $book->reviews->contains('user_id', auth()->id());

        return view('books.show', compact('book', 'hasReviewed'));
    }

    public function fetchByIsbn(string $isbn): JsonResponse
    {
        if (mb_strlen($isbn) !== 13) {
            return response()->json([
                'error' => 'ISBNは13桁で入力してください。',
            ], 400);
        }

        try {
            $response = Http::timeout(10)->get(
                'https://www.googleapis.com/books/v1/volumes',
                [
                    'q' => 'isbn:'.$isbn,
                    'key' => config('services.google_books.api_key'),
                ]
            );
        } catch (ConnectionException $e) {
            return response()->json([
                'error' => 'Google Books APIとの通信に失敗しました。',
            ], 500);
        }

        if ($response->status() === 429) {
            return response()->json([
                'error' => 'APIの利用制限に達しました。',
            ], 429);
        }

        if ($response->failed()) {
            return response()->json([
                'error' => 'Google Books APIとの通信に失敗しました。',
            ], 500);
        }

        $data = $response->json();

        if (empty($data['items'])) {
            return response()->json([
                'error' => '書籍が見つかりませんでした。',
            ], 404);
        }

        $book = $data['items'][0]['volumeInfo'] ?? [];

        return response()->json([
            'title' => $book['title'] ?? '',
            'author' => implode(', ', $book['authors'] ?? []),
            'description' => $book['description'] ?? '',
            'published_date' => $book['publishedDate'] ?? null,
            'image_url' => $book['imageLinks']['thumbnail'] ?? '',
        ]);
    }

    public function create(): View
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    public function store(StoreBookRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('genres');

        $data['user_id'] = $request->user()->id;

        $genres = $request->validated('genres', []);

        $book = DB::transaction(function () use ($data, $genres) {
            $book = Book::create($data);

            $book->genres()->sync($genres);

            return $book;
        });

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を登録しました。');
    }

    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        $genres = Genre::all();

        $book->load('genres');

        return view('books.edit', compact('book', 'genres'));
    }

    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        $data = $request->safe()->except('genres');

        $genres = $request->validated('genres', []);

        DB::transaction(function () use ($book, $data, $genres) {
            $book->update($data);

            $book->genres()->sync($genres);
        });

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を更新しました。');
    }

    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', '書籍を削除しました。');
    }
}
