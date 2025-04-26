<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use Illuminate\Http\Request;
use App\Http\Controllers\WishlistController;
use App\Models\Product;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Models\Order;
use App\Models\Cart;
use App\Http\Controllers\PlaceOrderController;
use App\Http\Controllers\CouponController;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;   
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\ProductRatingController;


Route::middleware('api')->group(function () {

// Route::middleware(['jwt.auth'])->post('/cart', [CartController::class, 'addToCart']);
Route::middleware(['jwt.auth'])->get('/cart', [CartController::class, 'getCart']);
Route::middleware(['jwt.auth'])->get('/cart/{id}', [CartController::class, 'getCartById']);
Route::get('/carts/{id}',[CartController::class, 'getCartByIds']);

Route::delete('/cart/{id}', [CartController::class, 'removeFromCart']);
Route::put('/cart/{id}', [CartController::class, 'updateCart']);
Route::get('/cartItem/{id}', [CartController::class, 'getCartItemsById']);
Route::post('/cart/update-status', [CartController::class, 'updateStatus']);
Route::get('/cart/status/{status}', [CartController::class, 'getCartByStatus']);

    
    Route::post('/add-products', [ProductController::class, 'store']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::delete('/products/{id}', [ProductController::class, 'removeFromProduct']);
    Route::put('/products/{id}',[ProductController::class, 'update']);
    
    Route::put('/products/{id}/update-size', [ProductController::class, 'updateSize']);
    Route::delete('/products/{id}/delete-size', [ProductController::class, 'deleteSize']);
    Route::put('/products/{id}/add-size', [ProductController::class, 'addSize']);

    Route::put('/products/{id}/update-color', [ProductController::class, 'updateColor']);
    Route::delete('/products/{id}/delete-color', [ProductController::class, 'deleteColor']);
    Route::put('/products/{id}/add-color', [ProductController::class, 'addColor']);
    Route::get('/product-sizes/{id}', [ProductController::class, 'getSizes']);
    Route::post('/products/{id}/update-popularity', [ProductController::class, 'updatePopularity']);
    Route::get('/products', function () {
        return response()->json(Product::all());
    });


//  {Products Ratings} 

Route::post('/products/ratings', [ProductRatingController::class, 'store']);     // Add rating
Route::put('/ratings/{id}', [ProductRatingController::class, 'update']); // Update rating
Route::get('/check-rating', [ProductRatingController::class, 'check']);
Route::delete('/ratings/{id}', [ProductRatingController::class, 'destroy']); // Delete rating
Route::get('/products/ratings/{id}', [ProductRatingController::class, 'getRatingsByProduct']);

// { PlacedOrders }
Route::post('/orders', [PlaceOrderController::class, 'store']); // Place Order
Route::get('/orders/{id}', [PlaceOrderController::class, 'show']); // Get Single Order
Route::put('/orders/{id}', [PlaceOrderController::class, 'update']); // Update Order
Route::delete('/orders/{id}', [PlaceOrderController::class, 'destroy']); // Delete Order
Route::get('/allorders', [PlaceOrderController::class, 'index']); // Get All Orders
Route::middleware(['auth:admin'])->get('adminallorders',[PlaceOrderController::class,'AllOrders']);
Route::post('/update-order-status', [PlaceOrderController::class, 'updateStatus']); // update order status by admin
Route::post('/update-status', [PlaceOrderController::class, 'updateStatusByAdmin']);
Route::get('/get-previous-address', [PlaceOrderController::class, 'getPreviousAddress']);

// {this is for copupons}

Route::post('/add-coupon', [CouponController::class, 'addCoupon']);
Route::delete('/coupons/{id}', [CouponController::class, 'deleteCoupon']);
Route::post('/apply-coupon', [CouponController::class, 'applyCoupon']);
Route::post('/remove-coupon', [CouponController::class, 'removeCoupon']);
Route::get('/coupons', [CouponController::class, 'getAllCoupons']);
Route::get('/coupons/{id}', [CouponController::class, 'getCouponById']);
Route::post('/update-coupon/{id}', [CouponController::class, 'update']);


Route::get('/Allusers', [AuthController::class, 'getAllUsers']);



    // Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orderss/{id}', [OrderController::class, 'show']);
    // Route::put('/orders/{id}', [OrderController::class, 'update']);
    // Route::delete('/orders/{id}', [OrderController::class, 'destroy']);
    // Route::get('/allorders', function(){
    //     return response() ->json(Order::all());
    // });




// {wishlist Api's}

Route::middleware(['jwt.auth'])->post('/wishlist', [WishlistController::class, 'store']);
Route::middleware(['jwt.auth'])->delete('/wishlist/{id}', [WishlistController::class, 'destroy']);
Route::middleware(['jwt.auth'])->get('/wishlist', [WishlistController::class, 'index']);


Route::get('/search', function (Request $request) {
    $query = $request->query('query');

    // Fetch products where name matches the search query
    $products = Product::where('name', 'LIKE', "%{$query}%")->get();

    return response()->json($products);
});

});


Route::middleware(['jwt.auth'])->post('/add-cart', [CartController::class, 'addToCart']);
Route::middleware(['jwt.auth'])->get('/cart', [CartController::class, 'getCart']);

// {Auth Apis}

Route::post('/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/check-user', [AuthController::class, 'checkUser']);
Route::delete('/user-delete/{id}',[AuthController::class, 'destroy']);

Route::middleware(['jwt.auth'])->get('/user-token',[AuthController::class, 'getUserToken']);
Route::middleware(['jwt.auth'])->get('/user-profile', [AuthController::class, 'getUserProfile']);


Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login']); // Step 1: Request OTP
    Route::post('/verify-otp', [AdminAuthController::class, 'verifyOTP']); // Step 2: Verify OTP
    Route::middleware(['auth:admin'])->get('/profile',[AdminAuthController::class, 'getAdminProfile']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout']);
    });
});

// Route::middleware(['admin.auth'])->group(function () {
//     Route::get('/admin/dashboard', function () {
//         return response()->json(['message' => 'Welcome to Admin Dashboard']);
//     });
// });




