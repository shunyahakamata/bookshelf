<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexBookRequest;
use App\Http\Requests\Api\StoreBookRequest;
use App\Http\Requests\Api\UpdateBookRequest;
use App\Http\Resources\Api\V1\BookDetailResource;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    public function index(IndexBookRequest $request): AnonymousResourceCollection
    {
        $keyword = $request->validated('keyword');
        $genreId = $request->validated('genre_id');
        $perPage = $request->validated('per_page', 20);

        /** @var LengthAwarePaginator $books */
        $books = Book::query()
            ->with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when($request->filled('keyword'), function ($query) use ($keyword) {
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
            ->paginate($perPage);

        $books->withQueryString();

        return BookResource::collection($books);
    }

    public function show(Book $book): BookDetailResource
    {
        $book->load([
            'genres',
            'reviews.user',
        ]);

        return new BookDetailResource($book);
    }

    public function store(StoreBookRequest $request): JsonResponse
    {
        $data = $request->safe()->except('genres');
        $data['user_id'] = $request->user()->id;

        $genres = $request->validated('genres', []);

        $book = DB::transaction(function () use ($data, $genres) {
            $book = Book::create($data);

            $book->genres()->sync($genres);

            return $book;
        });

        $book->load('genres');

        return (new BookDetailResource($book))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateBookRequest $request, Book $book): BookDetailResource
    {
        $data = $request->safe()->except('genres');

        $genres = $request->validated('genres', []);

        DB::transaction(function () use ($book, $data, $genres) {
            $book->update($data);

            $book->genres()->sync($genres);
        });

        $book->load(['genres', 'reviews.user']);

        return new BookDetailResource($book);
    }

    public function destroy(Book $book): Response
    {
        $this->authorize('delete', $book);

        $book->delete();

        return response()->noContent();
    }
}
