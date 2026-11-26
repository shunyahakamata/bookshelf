<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * 書籍のお気に入り登録・解除を切り替える。
     */
    public function toggle(Book $book): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $user->favoriteBooks()->toggle($book->id);

        return back();
    }

    /**
     * ログインユーザーのお気に入り一覧を表示する。
     */
    public function index(): View
    {
        /** @var User $user */
        $user = auth()->user();

        $books = $user->favoriteBooks()
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }
}
