<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\CartItem;
use App\Models\Product;



class CartController extends Controller
{
    
    

    // public function addToCart(Request $request)
    // {
    //     try {
    //         // ✅ Authenticate the user using JWT token
    //         $user = JWTAuth::parseToken()->authenticate();
    
    //         // ✅ Validate the request
    //         $validator = Validator::make($request->all(), [
    //             'cart_id' => 'nullable|integer',  // cart_id can be null (new cart will be created)
    //             'product_id' => 'required|exists:products,id',
    //             'size' => 'required|string',
    //             'quantity' => 'required|integer|min:1',
    //             'status' => 'required|string'
    //         ]);
    
    //         if ($validator->fails()) {
    //             return response()->json(['error' => $validator->errors()], 400);
    //         }
    
    //         // ✅ Check if cart_id is provided
    //         $existingCart = null;
    
    //         if ($request->has('cart_id')) {
    //             $existingCart = Cart::where('cart_id', $request->cart_id)
    //                 ->where('user_id', $user->id)  // Ensure cart belongs to the user
    //                 ->first();
    //         }
    
    //         // ✅ Use the existing cart or generate a new one
    //         if ($existingCart && !in_array($existingCart->status, ['ordered', 'cancelled'])) {
    //             $cart_id = $existingCart->cart_id;  // Use the existing cart_id
    //         } else {
    //             $cart_id = random_int(10000, 99999);  // Generate a new unique cart_id
    //         }
    
    //         // ✅ Add the product to the cart with user_id
    //         $cart = Cart::create([
    //             'cart_id' => $cart_id,
    //             'user_id' => $user->id,            // Associate with authenticated user
    //             'product_id' => $request->product_id,
    //             'size' => $request->size,
    //             'quantity' => $request->quantity,
    //             'status' => $request->status
    //         ]);
    
    //         return response()->json([
    //             'message' => 'Item added to cart successfully',
    //             'cart_id' => $cart_id,
    //             'cart' => $cart
    //         ], 201);
    
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }
    
    // {new}
    public function addToCart(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        // Validate request
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'size' => 'required|string|max:10',
        ]);

        // Check if the user already has an active cart
        $cart = Cart::where('user_id', $user->id)
                      ->where('status', 'pending')
                      ->first();

        if (!$cart) {
            // Create new cart if none exists
            $cart = Cart::create([
                'user_id' => $user->id,
                'status' => 'pending',
            ]);
        }

        // Check if the product is already in the cart
        $cartItem = CartItem::where('cart_id', $cart->id)
                            ->where('product_id', $request->product_id)
                            ->where('size', $request->size)
                            ->first();

        if ($cartItem) {
            // Update quantity if product already exists
            $cartItem->quantity += $request->quantity;
            $cartItem->save();
        } else {
            // Add new item to the cart
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $request->product_id,
                'size' => $request->size,
                'quantity' => $request->quantity,
                // 'price' => Product::find($request->product_id)->price,
            ]);
        }

        return response()->json(['message' => 'Item added to cart successfully']);
    }



// {new}
public function getCart()
    {
        $user = JWTAuth::parseToken()->authenticate();

        $cart = Cart::with('cartItems.product')
                      ->where('user_id', $user->id)
                      ->where('status', 'pending')
                      ->first();

        if (!$cart) {
            return response()->json(['message' => 'Cart is empty'], 200);
        }

        return response()->json($cart);
    }
    
    // Get All Cart Items
// public function getCart(Request $request) 
// {
//     // ✅ Get authenticated user ID
//     $user = JWTAuth::parseToken()->authenticate();

//     if (!$user) {
//         return response()->json(["message" => "Unauthorized"], 401);
//     }

//     // ✅ Fetch only the current user's cart items
//     $cartItems = Cart::with('product')
//         ->where('user_id', $user->id)
//         ->where('status', 'pending') 
//         ->get(); 

//     if ($cartItems->isEmpty()) {
//         return response()->json(["message" => "No cart found"], 404);
//     }

//     // ✅ Extract common cart status and cart_id
//     $status = $cartItems->first()->status;
//     $cartId = $cartItems->first()->cart_id;

//     // ✅ Transform cart items into desired structure
//     $items = $cartItems->map(function ($cartItem) {
//         return [
//             'product_id' => $cartItem->product_id,
//             'size' => $cartItem->size,
//             'quantity' => $cartItem->quantity,
//             'created_at' => $cartItem->created_at,
//             'updated_at' => $cartItem->updated_at,
//             'product' => [
//                 'id' => $cartItem->product->id,
//                 'name' => $cartItem->product->name,
//                 'price' => $cartItem->product->price,
//                 'image' => $cartItem->product->image,
//                 'thumbnail_images' => is_string($cartItem->product->thumbnail_images) 
//                     ? json_decode($cartItem->product->thumbnail_images, true) 
//                     : $cartItem->product->thumbnail_images,
//                 'hover_image' => $cartItem->product->hover_image,
//                 'description' => $cartItem->product->description,
//                 'created_at' => $cartItem->product->created_at,
//                 'updated_at' => $cartItem->product->updated_at,
//                 'availableSizes' => is_string($cartItem->product->available_sizes) 
//                     ? json_decode($cartItem->product->available_sizes, true) 
//                     : $cartItem->product->available_sizes,
//                 'availableColors' => is_string($cartItem->product->available_colors) 
//                     ? json_decode($cartItem->product->available_colors, true) 
//                     : $cartItem->product->available_colors,
//                 'category' => $cartItem->product->category,
//                 'stock' => $cartItem->product->stock,
//             ]
//         ];
//     });

//     // ✅ Return structured response
//     return response()->json([
//         'cart_id' => $cartId,
//         'status' => $status,
//         'items' => $items
//     ]);
// }





    

public function getCartById($cart_id)
{
    try {
        // ✅ Authenticate the user using JWT token
        $user = JWTAuth::parseToken()->authenticate();

        // ✅ Fetch the cart with items, ensuring it belongs to the authenticated user
        $cart = Cart::with(['cartItems.product'])
                    ->where('id', $cart_id)
                    ->where('user_id', $user->id)
                    ->first();

        if (!$cart) {
            return response()->json(['message' => 'Cart not found or unauthorized'], 404);
        }

        // ✅ Transform cart items into desired structure
        $items = $cart->cartItems->map(function ($cartItem) {
            return [
                'product_id' => $cartItem->product_id,
                'size' => $cartItem->size,
                'quantity' => $cartItem->quantity,
                'created_at' => $cartItem->created_at,
                'updated_at' => $cartItem->updated_at,
                'product' => [
                    'id' => $cartItem->product->id,
                    'name' => $cartItem->product->name,
                    'price' => $cartItem->product->price,
                    'image' => $cartItem->product->image,
                    'thumbnail_images' => is_string($cartItem->product->thumbnail_images) ? json_decode($cartItem->product->thumbnail_images, true) : $cartItem->product->thumbnail_images,
                    'hover_image' => $cartItem->product->hover_image,
                    'description' => $cartItem->product->description,
                    'created_at' => $cartItem->product->created_at,
                    'updated_at' => $cartItem->product->updated_at,
                    'availableSizes' => is_string($cartItem->product->available_sizes) ? json_decode($cartItem->product->available_sizes, true) : $cartItem->product->available_sizes,
                    'availableColors' => is_string($cartItem->product->available_colors) ? json_decode($cartItem->product->available_colors, true) : $cartItem->product->available_colors,
                    'category' => $cartItem->product->category,
                    'stock' => $cartItem->product->stock,
                ]
            ];
        });

        // ✅ Return structured response
        return response()->json([
            'cart_id' => $cart->id,
            'status' => $cart->status,
            'items' => $items
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}



public function getCartByIds($cart_id)
{
    
       

        // ✅ Fetch the cart with items, ensuring it belongs to the authenticated user
        $cart = Cart::with(['cartItems.product'])
                    ->where('id', $cart_id)
                    ->first();

        if (!$cart) {
            return response()->json(['message' => 'Cart not found or unauthorized'], 404);
        }

        // ✅ Transform cart items into desired structure
        $items = $cart->cartItems->map(function ($cartItem) {
            return [
                'product_id' => $cartItem->product_id,
                'size' => $cartItem->size,
                'quantity' => $cartItem->quantity,
                'created_at' => $cartItem->created_at,
                'updated_at' => $cartItem->updated_at,
                'product' => [
                    'id' => $cartItem->product->id,
                    'name' => $cartItem->product->name,
                    'price' => $cartItem->product->price,
                    'image' => $cartItem->product->image,
                    'thumbnail_images' => is_string($cartItem->product->thumbnail_images) ? json_decode($cartItem->product->thumbnail_images, true) : $cartItem->product->thumbnail_images,
                    'hover_image' => $cartItem->product->hover_image,
                    'description' => $cartItem->product->description,
                    'created_at' => $cartItem->product->created_at,
                    'updated_at' => $cartItem->product->updated_at,
                    'availableSizes' => is_string($cartItem->product->available_sizes) ? json_decode($cartItem->product->available_sizes, true) : $cartItem->product->available_sizes,
                    'availableColors' => is_string($cartItem->product->available_colors) ? json_decode($cartItem->product->available_colors, true) : $cartItem->product->available_colors,
                    'category' => $cartItem->product->category,
                    'stock' => $cartItem->product->stock,
                ]
            ];
        });

        // ✅ Return structured response
        return response()->json([
            'cart_id' => $cart->id,
            'status' => $cart->status,
            'items' => $items
        ]);

    
}

    
     // {new}
public function updateCart(Request $request, $cart_id)
{
    // Validate request
    $request->validate([
        'quantity' => 'required|integer|min:1|max:10',
    ]);

    // Get the cart_id from the request (make sure it's passed from frontend)
    $product_id = $request->input('product_id');

    if (!$cart_id) {
        return response()->json(['message' => 'Cart ID is required'], 400);
    }

    // Find the cart item using both cart_id and product_id
    $cartItem = CartItem::where('cart_id', $cart_id)
                        ->where('product_id', $product_id)
                        ->first();

    if (!$cartItem) {
        return response()->json(['message' => 'Cart item not found'], 404);
    }

    // Update quantity
    $cartItem->quantity = $request->quantity;
    $cartItem->save();

    return response()->json([
        'message' => 'Cart updated successfully',
        'cart_item' => $cartItem
    ], 200);
}



public function removeFromCart(Request $request, $cart_id)
{
   

    $product_id = $request->input('product_id');


    if (!$cart_id) {
        return response()->json(['message' => 'Cart ID is required'], 400);
    }

    // Find the cart item using both cart_id and product_id
    $cartItem = CartItem::where('cart_id', $cart_id)
                        ->where('product_id', $product_id)
                        ->first();

    if (!$cartItem) {
        return response()->json(['message' => 'Cart item not found'], 404);
    }

    // Delete the cart item
    $cartItem->delete();

    // Optionally, fetch remaining cart items for updated cart summary
    $remainingItems = CartItem::where('cart_id', $cart_id)
                              ->with('product')
                              ->get();

    return response()->json([
        'message' => 'Product removed from cart',
        'remaining_items' => $remainingItems
    ], 200);
}





public function getCartByStatus($status)
{
    // Fetch all carts with the given status and load products
    $carts = Cart::where('status', $status)
        ->with(['product'])
        ->get();

    if ($carts->isEmpty()) {
        return response()->json(['message' => 'No carts found with status: ' . $status], 404);
    }

    // Grouping carts by cart_id
    $cartData = [];

    foreach ($carts as $cart) {
        $cartId = $cart->cart_id;

        if (!isset($cartData[$cartId])) {
            $cartData[$cartId] = [
                'cart_id' => $cartId,
                'status' => $status,
                'items' => []
            ];
        }

        $cartData[$cartId]['items'][] = [
            'product_id' => $cart->product_id,
            'size' => $cart->size,
            'quantity' => $cart->quantity,
            'created_at' => $cart->created_at,
            'updated_at' => $cart->updated_at,
            'product' => [
                'id' => $cart->product->id,
                'name' => $cart->product->name,
                'price' => $cart->product->price,
                'image' => $cart->product->image,
                'thumbnail_images' => is_string($cart->product->thumbnail_images) ? json_decode($cart->product->thumbnail_images, true) : $cart->product->thumbnail_images,
                'hover_image' => $cart->product->hover_image,
                'description' => $cart->product->description,
                'created_at' => $cart->product->created_at,
                'updated_at' => $cart->product->updated_at,
                'availableSizes' => is_string($cart->product->available_sizes) ? json_decode($cart->product->available_sizes, true) : $cart->product->available_sizes,
                'availableColors' => is_string($cart->product->available_colors) ? json_decode($cart->product->available_colors, true) : $cart->product->available_colors,
                'category' => $cart->product->category,
                'stock' => $cart->product->stock,
            ]
        ];
    }

    // Return structured response
    return response()->json(array_values($cartData));
}





   

   


    
    public function updateStatus(Request $request) 
    {
        // Validate the request
        $request->validate([
            'cart_id' => 'required|integer',     // Ensure cart_id is provided and is an integer
            'status' => 'required|string'        // Ensure status is provided
        ]);
    
        // Search by cart_id, not id
        $cart = Cart::where('id', $request->cart_id)->first();
    
        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }
    
        // Update the status
        $cart->status = $request->status;
        $cart->save();
    
        return response()->json(['message' => 'Cart status updated successfully']);
    }
    

    

}


