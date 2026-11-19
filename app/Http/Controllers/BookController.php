<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->query('keyword');

        $genre = $request->query('genre');

        $sort = $request->query('sort', 'newest');

        $books = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->when($keyword, function ($query) use ($keyword) {
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

    public function show(Book $book)
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

    public function create()
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    public function store(StoreBookRequest $request)
    {
        $data = $request->safe()->except('genres');

        $data['user_id'] = $request->user()->id;

        $book = Book::create($data);

        $genres = $request->validated('genres', []);

        $book->genres()->sync($genres);

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を登録しました。');
    }

    public function edit(Book $book)
    {
        $this->authorize('update', $book);

        $genres = Genre::all();

        $book->load('genres');

        return view('books.edit', compact('book', 'genres'));
    }

    public function update(UpdateBookRequest $request, Book $book)
    {
        $this->authorize('update', $book);

        $data = $request->safe()->except('genres');

        $book->update($data);

        $genres = $request->validated('genres', []);

        $book->genres()->sync($genres);

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を更新しました。');
    }

    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', '書籍を削除しました。');
    }
}
