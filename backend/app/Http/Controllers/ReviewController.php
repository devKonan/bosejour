<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Accommodation;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request, $accommodationId)
    {
        $reviews = Review::with('user')
            ->where('accommodation_id', $accommodationId)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($reviews);
    }

    public function store(Request $request)
    {
        // Catégories principales
        $mainCategories = [
            'cleanliness' => 'Propreté',
            'equipment' => 'Equipements',
            'staff' => 'Personnel',
            'value_for_money' => 'Rapport qualité/prix',
            'location' => 'Situation géographique',
            'comfort' => 'Confort',
        ];

        // Catégories supplémentaires
        $additionalCategories = [
            'wifi' => 'Wi-Fi',
            'bed' => 'Evaluation du lit',
            'breakfast' => 'Petit déjeuner',
        ];

        $allCategories = array_merge($mainCategories, $additionalCategories);

        $rules = [
            'accommodation_id' => 'required|exists:accommodations,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
            'comment_en' => 'nullable|string|max:1000',
        ];

        // Validation des catégories principales (requises)
        foreach (array_keys($mainCategories) as $category) {
            $rules["category_ratings.{$category}"] = 'required|integer|min:1|max:5';
        }

        // Validation des catégories supplémentaires (optionnelles)
        foreach (array_keys($additionalCategories) as $category) {
            $rules["category_ratings.{$category}"] = 'nullable|integer|min:1|max:5';
        }

        $request->validate($rules);

        // Check if user has a confirmed booking
        $hasBooking = \App\Models\Booking::where('user_id', $request->user()->id)
            ->where('accommodation_id', $request->accommodation_id)
            ->where('status', 'confirmed')
            ->where('check_out', '<=', now())
            ->exists();

        if (!$hasBooking) {
            return response()->json(['message' => 'You must have a completed booking to review'], 400);
        }

        // Check if user already reviewed
        $existingReview = Review::where('user_id', $request->user()->id)
            ->where('accommodation_id', $request->accommodation_id)
            ->first();

        if ($existingReview) {
            return response()->json(['message' => 'You have already reviewed this accommodation'], 400);
        }

        // Préparer les catégories d'évaluation
        $categoryRatings = [];
        foreach ($allCategories as $key => $label) {
            if ($request->has("category_ratings.{$key}")) {
                $categoryRatings[$key] = (int) $request->input("category_ratings.{$key}");
            }
        }

        $review = Review::create([
            'user_id' => $request->user()->id,
            'accommodation_id' => $request->accommodation_id,
            'rating' => $request->rating,
            'category_ratings' => $categoryRatings,
            'comment' => $request->comment,
            'comment_en' => $request->comment_en,
        ]);

        // Update accommodation rating
        $accommodation = Accommodation::findOrFail($request->accommodation_id);
        $averageRating = Review::where('accommodation_id', $request->accommodation_id)->avg('rating');
        $totalReviews = Review::where('accommodation_id', $request->accommodation_id)->count();
        
        $accommodation->update([
            'rating' => round($averageRating, 2),
            'total_reviews' => $totalReviews,
        ]);

        return response()->json($review->load('user'), 201);
    }
}

