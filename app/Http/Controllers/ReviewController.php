<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;

use App\Models\Review;
use App\Models\Book;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request, Book $book)
    {
        $data = $request->validated();

        $data['user_id'] = $request->user()->id;

        $data['book_id'] = $book->id;

        Review::create($data);

        return redirect()
        ->route('books.show',$book)
        ->with('success','レビューを投稿しました。');
    }

    public function edit(Review $review)
    {
        $this->authorize('update',$review);

        return view('reviews.edit', compact('review'));
    }

    public function update(UpdateReviewRequest $request, Review $review)
    {
        $this->authorize('update',$review);

        $data = $request->validated();

        $review->update($data);

        return redirect()
            ->route('books.show',$review->book)
            ->with('success','レビューを更新しました。');
    }

    public function destroy(Review $review)
    {
        $this->authorize('delete' ,$review);

        $book = $review->book;

        $review->delete();

        return redirect()
            ->route('books.show',$book)
            ->with('success','レビューを削除しました。');
    }

    public function like(Review $review)
    {
        auth()->user()
            ->likedReviews()
            ->toggle($review->id);

        return back();
    }
}
