<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /** 書籍にレビューを投稿する。 */
    public function store(StoreReviewRequest $request, Book $book): RedirectResponse
    {
        $data = $request->validated();

        $data['user_id'] = $request->user()->id;

        $data['book_id'] = $book->id;

        Review::create($data);

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'レビューを投稿しました。');
    }

    /** レビューの編集画面を表示する。 */
    public function edit(Review $review): View
    {
        $this->authorize('update', $review);

        return view('reviews.edit', compact('review'));
    }

    /** レビューを更新する。 */
    public function update(UpdateReviewRequest $request, Review $review): RedirectResponse
    {
        $this->authorize('update', $review);

        $data = $request->validated();

        $review->update($data);

        return redirect()
            ->route('books.show', $review->book)
            ->with('success', 'レビューを更新しました。');
    }

    /** レビューを削除する。 */
    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $book = $review->book;

        $review->delete();

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'レビューを削除しました。');
    }

    /**
     * レビューへのいいねを切り替える。
     */
    public function like(Review $review): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $user->likedReviews()->toggle($review->id);

        return back();
    }
}
