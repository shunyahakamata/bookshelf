<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\BookResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexBookRequest;
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
            ->paginate($perPage);

        return BookResource::collection($books);
    }
}