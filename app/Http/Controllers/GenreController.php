<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GenreController extends Controller
{
    /** ジャンル一覧を表示する。 */
    public function index(): View
    {
        $genres = Genre::withCount('books')->get();

        return view('genres.index', compact('genres'));
    }

    /** ジャンルに属する書籍一覧を表示する。 */
    public function show(Genre $genre): View
    {
        $books = $genre->books()
            ->with('genres')
            ->paginate(10);

        return view('genres.show', compact('genre', 'books'));
    }

    /** ジャンルの登録画面を表示する。 */
    public function create(): View
    {
        return view('genres.create');
    }

    /** ジャンルを登録する。 */
    public function store(StoreGenreRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Genre::create($data);

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを登録しました。');
    }

    /** ジャンルの編集画面を表示する。 */
    public function edit(Genre $genre): View
    {
        return view('genres.edit', compact('genre'));
    }

    /** ジャンルを更新する。 */
    public function update(UpdateGenreRequest $request, Genre $genre): RedirectResponse
    {
        $data = $request->validated();

        $genre->update($data);

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを更新しました。');
    }

    /** 書籍が紐づいていないジャンルを削除する。 */
    public function destroy(Genre $genre): RedirectResponse
    {
        if ($genre->books()->exists()) {
            return redirect()
                ->route('genres.index')
                ->with('error', 'ジャンルを削除できませんでした。');
        }

        $genre->delete();

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを削除しました。');
    }
}
