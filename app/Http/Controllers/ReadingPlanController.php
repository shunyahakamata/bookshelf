<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * ログインユーザーの読書計画一覧を表示する。
     */
    public function index(Request $request): View
    {
        $currentStatus = $request->query('status', '');

        $readingPlans = ReadingPlan::with('book')
            ->where('user_id', Auth::id())
            ->when(
                in_array($currentStatus, array_column(ReadingPlanStatus::cases(), 'value'), true),
                fn ($query) => $query->where('status', $currentStatus)
            )
            ->orderBy('target_date')
            ->get();

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    /**
     * 読書計画の登録画面を表示する。
     */
    public function create(): View
    {
        $books = Book::orderBy('title')->get();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 進行中の計画の重複を確認し、読書計画を登録する。
     */
    public function store(StoreReadingPlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $exists = ReadingPlan::where('user_id', Auth::id())
            ->where('book_id', $validated['book_id'])
            ->where('status', ReadingPlanStatus::InProgress)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'book_id' => 'この書籍には、すでに進行中の読書計画があります。',
            ]);
        }

        ReadingPlan::create([
            'user_id' => Auth::id(),
            'book_id' => $validated['book_id'],
            'target_date' => $validated['target_date'],
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を登録しました。');
    }

    /**
     * 認可された読書計画の編集画面を表示する。
     */
    public function edit(ReadingPlan $readingPlan): View
    {
        $this->authorize('update', $readingPlan);

        $readingPlan->load('book');

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * 読書計画の目標日を更新する。
     * 期限切れの場合は重複を確認して進行中に戻す。
     */
    public function update(UpdateReadingPlanRequest $request, ReadingPlan $readingPlan): RedirectResponse
    {
        $validated = $request->validated();

        if ($readingPlan->status === ReadingPlanStatus::Expired) {
            $exists = ReadingPlan::where('user_id', Auth::id())
                ->where('book_id', $readingPlan->book_id)
                ->where('status', ReadingPlanStatus::InProgress)
                ->whereKeyNot($readingPlan->id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'target_date' => 'この書籍には、すでに進行中の読書計画があります。',
                ]);
            }

            $readingPlan->status = ReadingPlanStatus::InProgress;
        }

        $readingPlan->target_date = $validated['target_date'];
        $readingPlan->save();

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を更新しました。');
    }

    /**
     * 読書計画を読了済みに変更し、読了日時を記録する。
     */
    public function complete(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('complete', $readingPlan);

        $readingPlan->status = ReadingPlanStatus::Completed;
        $readingPlan->completed_at = now();
        $readingPlan->save();

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を読了済みにしました。');
    }

    /**
     * 認可された読書計画を削除する。
     */
    public function destroy(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('delete', $readingPlan);

        $readingPlan->delete();

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を削除しました。');
    }
}
