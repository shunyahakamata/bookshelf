<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();


        $ratingDistribution = collect(range(1, 5))->map(
            fn($rating) => $user->reviews()
                ->where('rating', $rating)
                ->count()
        );

        $topRatedBooks = $user->reviews()
            ->with('book')
            ->where('rating', '>=', 4)
            ->orderByDesc('rating')
            ->orderBy('id')
            ->limit(5)
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->book->id,
                    'title' => $review->book->title,
                    'author' => $review->book->author,
                    'rating' => $review->rating,
                ];
            });

        $genreRatings = $user->reviews()
            ->with('book.genres')
            ->get()
            ->flatMap(function ($review) {
                if (! $review->book) {
                    return [];
                }

                return $review->book->genres->map(function ($genre) use ($review) {
                    return [
                        'id' => $genre->id,
                        'name' => $genre->name,
                        'rating' => $review->rating,
                    ];
                });
            })
            ->groupBy('id')
            ->map(function ($reviews) {
                return [
                    'id' => $reviews->first()['id'],
                    'name' => $reviews->first()['name'],
                    'count' => $reviews->count(),
                    'average_rating' => $reviews->avg('rating'),
                ];
            })
            ->sortByDesc('average_rating')
            ->take(5)
            ->values();

        $stats = [
            'summary' => [
                'total_reviews' => $user->reviews()->count(),
                'books_read' => $user->reviews()
                    ->distinct()
                    ->count('book_id'),
                'average_rating' => $user->reviews()->avg('rating') ?? 0,
            ],
            'rating_distribution' => $ratingDistribution,
            'top_rated_books' => $topRatedBooks,
            'genre_ratings' => $genreRatings,
        ];


        return view('reports.index', compact('stats'));
    }
}
