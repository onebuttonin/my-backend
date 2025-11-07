<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class WishlistController extends Controller
{
    

// public function store(Request $request) 
// {
//     try {
        
//         // ✅ Authenticate user
//         $user = JWTAuth::parseToken()->authenticate();

//         if (!$user) {
//             return response()->json(['error' => 'Unauthorized'], 401);
//         }

//         // ✅ Save to wishlist
//         $wishlist = Wishlist::firstOrCreate([
//             'user_id' => $user->id,              // Use $user->id
//             'product_id' => $request->product_id,
//         ]);

//         // ✅ Log the wishlist entry
//         \Log::info('Wishlist entry:', ['wishlist' => $wishlist]);

//         return response()->json(['message' => 'Added to wishlist', 'wishlist' => $wishlist]);
         
//     } catch (\Exception $e) {
//         \Log::error('Error:', ['error' => $e->getMessage()]);
//         return response()->json(['error' => $e->getMessage()], 500);
//     }
// }


public function store(Request $request)
{
    try {
        // ✅ Automatically authenticated via auth:user middleware
        $user = auth('user')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // ✅ Validate input
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        // ✅ Create or get wishlist entry
        $wishlist = Wishlist::firstOrCreate([
            'user_id' => $user->id,
            'product_id' => $request->product_id,
        ]);

        // ✅ Log the wishlist entry
        \Log::info('Wishlist entry created or found', ['wishlist' => $wishlist]);

        return response()->json([
            'message' => 'Added to wishlist successfully',
            'wishlist' => $wishlist,
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'error' => 'Validation failed',
            'details' => $e->errors(),
        ], 422);

    } catch (\Exception $e) {
        \Log::error('Wishlist store error', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Something went wrong'], 500);
    }
}




public function destroy($id)
    {
         $user = auth('user')->user();                   // ✅ Get the current user ID
        $wishlist = Wishlist::where('product_id', $id)
                            ->where('user_id', $user->id)  // ✅ Ensure only user's wishlist item is deleted
                            ->first();

        if ($wishlist) {
            $wishlist->delete();
            return response()->json(['message' => 'Product removed from wishlist']);
        }

        return response()->json(['message' => 'Product not found in wishlist'], 404);
    }

    // Get all wishlist products for the authenticated user
    public function index()
{
    try {
        // ✅ Authenticate user
         $user = auth('user')->user();

        // ✅ Get the current user's wishlist with products
        $wishlist = Wishlist::with('product')
                            ->where('user_id', $user->id)
                            ->get();

        // ✅ Return wishlist response
        return response()->json($wishlist);

    } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
        return response()->json(['error' => 'Token expired'], 401);
    } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
        return response()->json(['error' => 'Invalid token'], 401);
    } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
        return response()->json(['error' => 'Token missing or invalid'], 401);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
    }
}



    


    

}
