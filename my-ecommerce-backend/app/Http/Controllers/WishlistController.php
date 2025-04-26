<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class WishlistController extends Controller
{
    

public function store(Request $request) 
{
    try {
        // ✅ Log the incoming token
        // \Log::info('Token:', ['token' => $request->header('Authorization')]);

        // ✅ Authenticate user
        $user = JWTAuth::parseToken()->authenticate();
        
        // ✅ Log the authenticated user
        // \Log::info('Authenticated User:', ['user' => $user]);

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // ✅ Save to wishlist
        $wishlist = Wishlist::firstOrCreate([
            'user_id' => $user->id,              // Use $user->id
            'product_id' => $request->product_id,
        ]);

        // ✅ Log the wishlist entry
        \Log::info('Wishlist entry:', ['wishlist' => $wishlist]);

        return response()->json(['message' => 'Added to wishlist', 'wishlist' => $wishlist]);
         
    } catch (\Exception $e) {
        \Log::error('Error:', ['error' => $e->getMessage()]);
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    // Remove product from wishlist
    public function destroy($id)
    {
        $user = JWTAuth::parseToken()->authenticate();                     // ✅ Get the current user ID
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
        $user = JWTAuth::parseToken()->authenticate(); 

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
