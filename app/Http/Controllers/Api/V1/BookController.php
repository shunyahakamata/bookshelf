<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexBookRequest;
use App\Http\Requests\Api\StoreBookRequest;
use App\Http\Requests\Api\UpdateBookRequest;
use App\Http\Resources\Api\V1\BookDetailResource;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Book;

class BookController extends Controller
{
    public function index(IndexBookRequest $request)
    {
        $keyword = $request->validated('keyword');
        $genreId = $request->validated('genre_id');
        $perPage = $request->validated('per_page', 20);

        $books = Book::query()
            ->with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('title', 'like', "%{$keyword}%")
                        ->orWhere('author', 'like', "%{$keyword}%");
                });
            })
            ->when($genreId, function ($query) use ($genreId) {
                $query->whereHas('genres', function ($query) use ($genreId) {
                    $query->where('genres.id', $genreId);
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return BookResource::collection($books);
    }

    public function show(Book $book)
    {
        $book->load([
            'genres',
            'reviews.user',
        ]);

        return new BookDetailResource($book);
    }

    public function store(StoreBookRequest $request)
    {
        $data = $request->safe()->except('genres');

        $book = Book::create($data);

        $genres = $request->validated('genres', []);

        $book->genres()->sync($genres);

        $book->load('genres');

        return (new BookDetailResource($book))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateBookRequest $request, Book $book)
    {
        $data = $request->safe()->except('genres');

        $book->update($data);

        $genres = $request->validated('genres', []);

        $book->genres()->sync($genres);

        $book->load(['genres', 'reviews.user']);

        return new BookDetailResource($book);
    }

    public function destroy(Book $book)
    {
        $book->delete();

        return response()->noContent();
    }
}
