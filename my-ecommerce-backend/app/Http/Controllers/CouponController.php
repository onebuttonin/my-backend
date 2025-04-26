<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{

    // Add a new coupon to the database
public function addCoupon(Request $request)
{
    $request->validate([
        'code' => 'required|string|unique:coupons,code|max:20',
        'description' => 'nullable|string',
        'type' => 'required|in:fixed,percentage',
        'value' => 'required|numeric|min:0',
        'usage_limit' => 'nullable|integer|min:1',
        'expires_at' => 'nullable|date|after_or_equal:today',
        'is_active' => 'required|boolean'
    ]);

    $coupon = Coupon::create([
        'code' => strtoupper($request->code),   // Store code in uppercase
        'description' => $request->description,
        'type' => $request->type,
        'value' => $request->value,
        'usage_limit' => $request->usage_limit ?? null,
        'used_count' => 0,                      // Initialize usage count
        'expires_at' => $request->expires_at,
        'is_active' => $request->is_active
    ]);

    return response()->json([
        'message' => 'Coupon added successfully',
        'coupon' => $coupon
    ], 201);
}

public function applyCoupon(Request $request)
{
    $request->validate([
        'code' => 'required|string',
        'cart_id' => 'required|integer|exists:cart_items,cart_id'
    ]);

    $user = JWTAuth::parseToken()->authenticate();
    $userId = $user->id;

    $coupon = Coupon::where('code', $request->code)
        ->where('is_active', true)
        ->where(function ($query) {
            $query->whereNull('expires_at')
                  ->orWhere('expires_at', '>=', now());
        })
        ->first();

    if (!$coupon) {
        return response()->json(['message' => 'Invalid or expired coupon'], 404);
    }

    // **Check individual user usage**
    $userUsage = DB::table('coupon_user')
        ->where('user_id', $userId)
        ->where('coupon_id', $coupon->id)
        ->count();

    if ($userUsage >= $coupon->usage_limit) {
        return response()->json(['message' => 'You have already used this coupon'], 400);
    }

    // **Calculate cart total**
    $cartTotal = CartItem::where('cart_id', $request->cart_id)
        ->sum(DB::raw('quantity * (SELECT price FROM products WHERE products.id = cart_items.product_id)'));

    if (!$cartTotal) {
        return response()->json(['message' => 'Cart is empty or invalid'], 400);
    }

    // **Apply discount**
    $discount = 0;

    if ($coupon->type === 'fixed') {
        $discount = min($coupon->value, $cartTotal); // Ensure discount doesn't exceed total
    } elseif ($coupon->type === 'percentage') {
        $discount = ($cartTotal * $coupon->value) / 100;
    }

    $newTotal = $cartTotal - $discount;

    // **Insert usage record per user**
    DB::table('coupon_user')->insert([
        'user_id' => $userId,
        'coupon_id' => $coupon->id,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    return response()->json([
        'message' => 'Coupon applied successfully',
        'cart_total' => $cartTotal,
        'discount' => $discount,
        'new_total' => $newTotal
    ]);
}

public function removeCoupon(Request $request)
{
    $request->validate([
        'cart_id' => 'required|integer|exists:cart_items,cart_id',
        'code' => 'required|string|exists:coupons,code',
    ]);

    $user = JWTAuth::parseToken()->authenticate();

    // Find the coupon
    $coupon = Coupon::where('code', $request->code)->first();

    if (!$coupon) {
        return response()->json(['message' => 'Coupon not found'], 404);
    }

    // Check if the user has applied this coupon
    $couponUsage = DB::table('coupon_user')
        ->where('user_id', $user->id)
        ->where('coupon_id', $coupon->id)
        ->first();

    if (!$couponUsage) {
        return response()->json(['message' => 'Coupon not applied by this user'], 400);
    }

    // Remove the coupon usage for this user
    DB::table('coupon_user')
        ->where('user_id', $user->id)
        ->where('coupon_id', $coupon->id)
        ->delete();

    // ✅ Decrement usage count in coupons table
    if ($coupon->used_count > 0) {
        $coupon->decrement('used_count');
    }

    return response()->json([
        'message' => 'Coupon removed successfully',
        'coupon' => $coupon->code,
        'new_used_count' => $coupon->used_count
    ]);
}


    public function getAllCoupons()
{
    try {
        // Fetch all coupons
        $coupons = Coupon::all();

        return response()->json($coupons, 200);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to fetch coupons'], 500);
    }
}

public function getCouponById($id){

    $coupon = Coupon::find($id);

    if (!$coupon) {
        return response()->json(['message' => 'Coupon not found'], 404);
    }

    return response()->json([$coupon]);

}

public function update(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        
        'value' => 'nullable|numeric|min:0',
        'min_order_value' => 'nullable|numeric|min:0',
        'expires_at' => 'nullable|date|after_or_equal:today',
        'usage_limit' => 'nullable|integer|min:1',
        'is_active' => 'nullable|boolean',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Find coupon
    $coupon = Coupon::find($id);

    // Update only provided fields
    $coupon->update($request->only(['value', 'min_order_value', 'expires_at', 'usage_limit', 'is_active']));

    return response()->json(['message' => 'Coupon updated successfully!', 'coupon' => $coupon], 200);
}


    public function deleteCoupon($id)
{
    $coupon = Coupon::find($id);

    if (!$coupon) {
        return response()->json(['message' => 'Coupon not found'], 404);
    }

    $coupon->delete();

    return response()->json(['message' => 'Coupon deleted successfully']);
}

}
