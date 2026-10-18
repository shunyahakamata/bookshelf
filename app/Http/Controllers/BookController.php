<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Genre;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;

class BookController extends Controller
{
    public function index()
    {
        $books = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->latest()
            ->paginate(10);

        return view('books.index', compact('books'));
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

        $genres = $request->validated('genres',[]);

        $book->genres()->sync($genres);

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を登録しました。');
    }

    public function edit(Book $book)
    {
        $this->authorize('update',$book);

        $genres = Genre::all();

        $book->load('genres');

        return view('books.edit',compact('book','genres'));
    }

    public function update(UpdateBookRequest $request, Book $book)
    {
        $this->authorize('update',$book);

        $data = $request->safe()->except('genres');

        $book->update($data);

        $genres = $request->validated('genres',[]);

        $book->genres()->sync($genres);

        return redirect()
            ->route('books.show',$book)
            ->with('success', '書籍を更新しました。');
    }

    public function destroy(Book $book)
    {
        $this->authorize('delete' ,$book);

        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', '書籍を削除しました。');
    }
}
