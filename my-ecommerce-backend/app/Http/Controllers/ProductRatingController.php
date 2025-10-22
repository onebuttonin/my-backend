<?php

namespace App\Http\Controllers;

use App\Models\ProductRating;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;

class ProductRatingController extends Controller
{

//     public function store(Request $request)
// {
//     $user = JWTAuth::parseToken()->authenticate();
//     $userId = $user->id;

//     $request->validate([
//         'product_id' => 'required|exists:products,id',
//         'rating' => 'required|integer|min:1|max:5',
//         'review' => 'nullable|string',
//         'review_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // ✅ add validation for image
//     ]);

//     // ✅ Handle image upload
//     $imagePath = null;
//     if ($request->hasFile('review_image')) {
//         $file = $request->file('review_image');
//         $imagePath = $file->store('review_images', 'public'); // stored in storage/app/public/review_images
//     }

//     // ✅ Save rating + review + image
//     $rating = ProductRating::create([
//         'product_id' => $request->product_id,
//         'user_id' => $userId,
//         'rating' => $request->rating,
//         'review' => $request->review,
//         'review_image' => $imagePath, // ✅ save path to DB
//     ]);

//     // ✅ Return full image URL for frontend
//     $rating->review_image = $imagePath ? asset('storage/' . $imagePath) : null;

//     return response()->json($rating, 201);
// }

public function store(Request $request)
{
    $user = JWTAuth::parseToken()->authenticate();
    $userId = $user->id;

    $request->validate([
        'product_id' => 'required|exists:products,id',
        'rating' => 'required|integer|min:1|max:5',
        'review' => 'nullable|string',
        'review_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
    ]);

    $imagePath = null;
    if ($request->hasFile('review_image')) {
        $file = $request->file('review_image');
        $imagePath = $file->store('review_images', 'public');
    }

    $rating = ProductRating::create([
        'product_id' => $request->product_id,
        'user_id' => $userId,
        'rating' => $request->rating,
        'review' => $request->review,
        'review_image' => $imagePath,
    ]);

    // ✅ Convert stored path to public URL
    $rating->review_image = $imagePath ? asset('storage/' . $imagePath) : null;

    return response()->json($rating, 201);
}


public function check(Request $request)
{
    $user = JWTAuth::parseToken()->authenticate();
    $userId = $user->id;

    $request->validate([
        'product_id' => 'required|exists:products,id',
    ]);

    $alreadyRated = ProductRating::where('product_id', $request->product_id)
                    ->where('user_id', $userId)
                    ->exists();

    return response()->json([
        'alreadyRated' => $alreadyRated,
    ]);
}



    // Update a rating
    public function update(Request $request, $id)
    {
        $rating = ProductRating::findOrFail($id);

        $validated = $request->validate([
            'rating' => 'integer|min:1|max:5',
            'review' => 'nullable|string',
        ]);

        $rating->update($validated);

        return response()->json($rating);
    }

    // Delete a rating
    public function destroy($id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        
        if(!$user){
            return response()->json(['message' => 'invalid user'], 404);
        }

        $rating = ProductRating::findOrFail($id);
        $rating->delete();

        return response()->json(['message' => 'Rating deleted successfully']);
    }



public function getRatingsByProduct($productId)
{
    $ratings = ProductRating::with('user')
        ->where('product_id', $productId)
        ->latest()
        ->get();

    return response()->json($ratings);
}



}
