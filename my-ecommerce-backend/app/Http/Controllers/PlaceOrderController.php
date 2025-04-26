<?php

namespace App\Http\Controllers;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\PlacedOrder;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class PlaceOrderController extends Controller {


    

    public function store(Request $request)
    {
        try {
            // ✅ Authenticate the user using JWT token
            $user = JWTAuth::parseToken()->authenticate();
    
            // ✅ Validate the request
            $request->validate([
                'name' => 'required|string|max:255',
                'street1' => 'required|string|max:255',
                'street2' => 'nullable|string|max:255',
                'city' => 'required|string|max:255',
                'state' => 'required|string|max:255',
                'pincode' => 'required|string|max:20',
                'mobile' => 'required|string|max:20',
                'payment_method' => 'required|string|max:50',
                'cart_id' => 'required|integer', // Removed 'exists' validation
                'cart_total' => 'required|numeric',
                'order_status' => 'nullable|string|max:50'
            ]);
    
            // ✅ Check if an order already exists for this cart_id
            $existingOrder = PlacedOrder::where('cart_id', $request->cart_id)->first();
            if ($existingOrder) {
                return response()->json([
                    'message' => 'Order already placed for this cart',
                    'order' => $existingOrder
                ], 409); // 409 Conflict
            }
    
            // ✅ Create new order with user_id
            $order = PlacedOrder::create([
                'user_id' => $user->id,   // ✅ Associate order with the authenticated user
                'name' => $request->name,
                'street1' => $request->street1,
                'street2' => $request->street2,
                'city' => $request->city,
                'state' => $request->state,
                'pincode' => $request->pincode,
                'mobile' => $request->mobile,
                'payment_method' => $request->payment_method,
                'cart_id' => $request->cart_id,
                'cart_total' => $request->cart_total,
                'order_status' => $request->order_status ?? 'Order Placed'  // Default to 'pending' if not provided
            ]);
    
            return response()->json([
                'message' => 'Order placed successfully',
                'order' => $order
            ], 201); // 201 Created
    
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    

    public function getPreviousAddress()
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        // Fetch latest order address for this user
        $latestOrder = PlacedOrder::where('user_id', $user->id)
                        ->latest()
                        ->first();

        if ($latestOrder) {
            return response()->json([
                
                    'name' => $latestOrder->name,
                    'street1' => $latestOrder->street1,
                    'street2' => $latestOrder->street2,
                    'city' => $latestOrder->city,
                    'state' => $latestOrder->state,
                    'pincode' => $latestOrder->pincode,
                    'state' => $latestOrder->state,
                    'mobile' => $latestOrder->mobile,
                
            ]);
        } else {
            return response()->json([
                'address' => null
            ]);
        }
    }



    // Get a single order
    public function show($id) {
        $order = PlacedOrder::findOrFail($id);
        return response()->json($order);
    }

    // Update an order
    public function update(Request $request, $id) {
        $order = PlacedOrder::findOrFail($id);
        $order->update($request->all());
        return response()->json($order);
    }

    // Delete an order
    public function destroy($id) {
        $order = PlacedOrder::findOrFail($id);
        $order->delete();
        return response()->json(['message' => 'Order deleted']);
    }

    // Get all orders
    public function index()
    {
        try {
            // ✅ Authenticate the user using JWT token
            $user = JWTAuth::parseToken()->authenticate();
    
            // ✅ Fetch only the authenticated user's orders
            $orders = PlacedOrder::where('user_id', $user->id)->get();
    
            if ($orders->isEmpty()) {
                return response()->json(['message' => 'No orders found'], 404);
            }
    
            return response()->json($orders, 200);
    
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function AllOrders()
    {
        
        
    
            // ✅ Fetch only the authenticated user's orders
            $orders = PlacedOrder::all();
    
            if ($orders->isEmpty()) {
                return response()->json(['message' => 'No orders found'], 404);
            }
    
            return response()->json($orders, 200);
    
         
        
    }
    

    public function updateStatus(Request $request) {
        try {
            // ✅ Authenticate the user using JWT token
            $user = JWTAuth::parseToken()->authenticate();
    
            // ✅ Validate the request
            $request->validate([
                'id' => 'required|integer|exists:placedorders,id',
                'order_status' => 'required|string'
            ]);
    
            // ✅ Find the order and ensure it belongs to the authenticated user
            $order = PlacedOrder::where('id', $request->id)
                                ->where('user_id', $user->id)   // Ensure ownership
                                ->first();
    
            if (!$order) {
                return response()->json(['message' => 'Order not found or unauthorized'], 404);
            }
    
            // ✅ Update the order status
            $order->order_status = $request->order_status;
            $order->save();
    
            return response()->json(['message' => 'Order status updated successfully'], 200);
    
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateStatusByAdmin(Request $request) {
       
          
    
            // ✅ Validate the request
            $request->validate([
                'id' => 'required|integer|exists:placedorders,id',
                'order_status' => 'required|string'
            ]);
    
            // ✅ Find the order
            $order = PlacedOrder::where('id', $request->id)->first();
    
            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }
    
            // ✅ Update the order status
            $order->order_status = $request->order_status;
            $order->save();
    
            return response()->json(['message' => 'Order status updated successfully'], 200);
    
        
    }
    
    

    
}




