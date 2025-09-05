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


    

    // public function store(Request $request)
    // {
    //     try {
    //         // ✅ Authenticate the user using JWT token
    //         $user = JWTAuth::parseToken()->authenticate();
    
    //         // ✅ Validate the request
    //         $request->validate([
    //             'name' => 'required|string|max:255',
    //             'street1' => 'required|string|max:255',
    //             'street2' => 'nullable|string|max:255',
    //             'city' => 'required|string|max:255',
    //             'state' => 'required|string|max:255',
    //             'pincode' => 'required|string|max:20',
    //             'mobile' => 'required|string|max:20',
    //             'payment_method' => 'required|string|max:50',
    //             'cart_id' => 'required|integer', // Removed 'exists' validation
    //             'cart_total' => 'required|numeric',
    //             'order_status' => 'nullable|string|max:50'
    //         ]);
    
    //         // ✅ Check if an order already exists for this cart_id
    //         $existingOrder = PlacedOrder::where('cart_id', $request->cart_id)->first();
    //         if ($existingOrder) {
    //             return response()->json([
    //                 'message' => 'Order already placed for this cart',
    //                 'order' => $existingOrder
    //             ], 409); // 409 Conflict
    //         }
    
    //         // ✅ Create new order with user_id
    //         $order = PlacedOrder::create([
    //             'user_id' => $user->id,   // ✅ Associate order with the authenticated user
    //             'name' => $request->name,
    //             'street1' => $request->street1,
    //             'street2' => $request->street2,
    //             'city' => $request->city,
    //             'state' => $request->state,
    //             'pincode' => $request->pincode,
    //             'mobile' => $request->mobile,
    //             'payment_method' => $request->payment_method,
    //             'cart_id' => $request->cart_id,
    //             'cart_total' => $request->cart_total,
    //             'order_status' => $request->order_status ?? 'Order Placed'  // Default to 'pending' if not provided
    //         ]);
    
    //         return response()->json([
    //             'message' => 'Order placed successfully',
    //             'order' => $order
    //         ], 201); // 201 Created
    
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }
    

  public function store(\Illuminate\Http\Request $request)
{
    try {
        // Authenticate the user using JWT token
        $user = \Tymon\JWTAuth\Facades\JWTAuth::parseToken()->authenticate();

        // Validate the request
        $request->validate([
            'name' => 'required|string|max:255',
            'street1' => 'required|string|max:255',
            'street2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'pincode' => 'required|string|max:20',
            'mobile' => 'required|string|max:20',
            'payment_method' => 'required|string|max:50',
            'cart_id' => 'required|integer',
            'cart_total' => 'required|numeric',
            'order_status' => 'nullable|string|max:50'
        ]);

        // Prevent duplicate orders for same cart
        $existingOrder = \App\Models\PlacedOrder::where('cart_id', $request->cart_id)->first();
        if ($existingOrder) {
            return response()->json([
                'message' => 'Order already placed for this cart',
                'order' => $existingOrder
            ], 409);
        }

        // Create the order
        $order = \App\Models\PlacedOrder::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'street1' => $request->street1,
            'street2' => $request->street2 ?? null,
            'city' => $request->city,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'mobile' => $request->mobile,
            'payment_method' => $request->payment_method,
            'cart_id' => $request->cart_id,
            'cart_total' => $request->cart_total,
            'order_status' => $request->order_status ?? 'Order Confirmed',
        ]);

        // Optionally mark cart completed here if desired (you already call from frontend)
        // try {
        //     \App\Models\Cart::where('id', $request->cart_id)->update(['status' => 'completed']);
        // } catch (\Throwable $e) {
        //     \Log::warning('Failed to mark cart completed', ['cart_id'=>$request->cart_id, 'err'=>$e->getMessage()]);
        // }

        // --- Attempt to send "Order Confirmed" email (non-blocking) ---
        $emailAttempted = false;
        $emailResult = null;

        try {
            // Determine recipient email & name. Prefer authenticated user's email/name.
            $userEmail = $user->email ?? null;
            $userName  = $user->name ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

            // If frontend passed an email/name explicitly, prefer those
            if ($request->filled('email')) {
                $userEmail = $request->input('email');
            }
            if ($request->filled('name')) {
                $userName = $request->input('name');
            }

            if (!empty($userEmail)) {
                $emailPayload = [
                    'email' => $userEmail,
                    'name' => $userName ?: ($request->name ?? 'Customer'),
                    'orderId' => $order->id,
                    'trackingId' => null,
                    'orderStatus' => 'Order Confirmed',
                    // 'templateId' => <int> // optional: force a template ID
                ];

                // Create a Request instance and call the mail controller on an instance
                $fakeRequest = new \Illuminate\Http\Request();
                $fakeRequest->replace($emailPayload);

                // Make an instance of your mail controller (container resolves dependencies)
                $mailController = app()->make(\App\Http\Controllers\TransactionalMailController::class);

                // Call the instance method (not statically)
                $response = $mailController->sendOrderMail($fakeRequest);

                $emailAttempted = true;
                $emailResult = $response instanceof \Illuminate\Http\JsonResponse ? $response->getData(true) : $response;
                \Log::info('Order confirmation email attempted', ['order_id' => $order->id, 'mail_result' => $emailResult]);
            } else {
                \Log::warning('Order placed but no email found to send confirmation', ['order_id' => $order->id, 'user_id' => $user->id]);
            }
        } catch (\Throwable $e) {
            // Log but do not fail the order creation
            \Log::error('Failed to auto-send order confirmation email', ['order_id' => $order->id, 'err' => $e->getMessage()]);
            $emailAttempted = true;
            $emailResult = ['error' => $e->getMessage()];
        }

        // Return the created order and email attempt info
        return response()->json([
            'message' => 'Order placed successfully',
            'order' => $order,
            'email_attempted' => $emailAttempted,
            'email_result' => $emailResult
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $ve) {
        return response()->json(['errors' => $ve->errors()], 422);
    } catch (\Throwable $e) {
        \Log::error('Place order failed', ['err' => $e->getMessage(), 'payload' => $request->all()]);
        return response()->json(['error' => 'Failed to place order', 'details' => $e->getMessage()], 500);
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

    // public function AllOrders()
    // {
        
        
    
    //         // ✅ Fetch only the authenticated user's orders
    //         $orders = PlacedOrder::all();
    
    //         if ($orders->isEmpty()) {
    //             return response()->json(['message' => 'No orders found'], 404);
    //         }
    
    //         return response()->json($orders, 200);
    
         
        
    // }
    

    // public function updateStatus(Request $request) {
    //     try {
    //         // ✅ Authenticate the user using JWT token
    //         $user = JWTAuth::parseToken()->authenticate();
    
    //         // ✅ Validate the request
    //         $request->validate([
    //             'id' => 'required|integer|exists:placedorders,id',
    //             'order_status' => 'required|string'
    //         ]);
    
    //         // ✅ Find the order and ensure it belongs to the authenticated user
    //         $order = PlacedOrder::where('id', $request->id)
    //                             ->where('user_id', $user->id)   // Ensure ownership
    //                             ->first();
    
    //         if (!$order) {
    //             return response()->json(['message' => 'Order not found or unauthorized'], 404);
    //         }
    
    //         // ✅ Update the order status
    //         $order->order_status = $request->order_status;
    //         $order->save();
    
    //         return response()->json(['message' => 'Order status updated successfully'], 200);
    
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }


 public function updateStatus(\Illuminate\Http\Request $request)
{
    try {
        // Authenticate the user using JWT token
        $user = \Tymon\JWTAuth\Facades\JWTAuth::parseToken()->authenticate();

        // Validate input
        $request->validate([
            'id' => 'required|integer|exists:placedorders,id',
            'order_status' => 'required|string'
        ]);

        // Find the order and ensure it belongs to the authenticated user
        $order = \App\Models\PlacedOrder::where('id', $request->id)
                    ->where('user_id', $user->id)
                    ->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found or unauthorized'], 404);
        }

        // Update the order status and save
        $order->order_status = $request->order_status;
        $order->save();

        // Decide whether to send an email: send when status indicates cancellation
        $statusNormalized = strtolower(trim($request->order_status));
        $cancelValues = ['cancelled', 'canceled', 'cancel', 'returned']; // include variants you use

        $emailAttempted = false;
        $emailResult = null;

        if (in_array($statusNormalized, $cancelValues, true)) {
            // Prepare recipient email & name. Prefer authenticated user info.
            $recipientEmail = $user->email ?? null;
            $recipientName  = $user->name ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

            // Fall back to order-level email/name if available
            if (empty($recipientEmail) && !empty($order->email)) {
                $recipientEmail = $order->email;
            }
            if (empty($recipientName) && !empty($order->name)) {
                $recipientName = $order->name;
            }

            // If order has user relation, prefer that
            if (empty($recipientEmail) && method_exists($order, 'user') && $order->user) {
                $recipientEmail = $order->user->email ?? $recipientEmail;
                $recipientName  = $order->user->name ?? $recipientName;
            }

            // If still missing and user_id exists, try to fetch user
            if (empty($recipientEmail) && isset($order->user_id)) {
                try {
                    $u = \App\Models\User::select('email', 'name', 'first_name', 'last_name')->find($order->user_id);
                    if ($u) {
                        $recipientEmail = $u->email ?? $recipientEmail;
                        $recipientName  = $u->name ?? trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
                    }
                } catch (\Throwable $e) {
                    \Log::warning('updateStatus: failed to fetch user by user_id for cancellation email', [
                        'order_id' => $order->id,
                        'user_id' => $order->user_id,
                        'err' => $e->getMessage()
                    ]);
                }
            }

            // Build template map (must match the map used in sendOrderMail)
            $templateMap = [
                'Order Confirmed'    => env('BREVO_TEMPLATE_ORDER_CONFIRMED', 1),
                'Shipped'            => env('BREVO_TEMPLATE_SHIPPED', 2),
                'In Transit'         => env('BREVO_TEMPLATE_IN_TRANSIT', 3),
                'Out For Delivery'   => env('BREVO_TEMPLATE_OUT_FOR_DELIVERY', 4),
                'Delivered'          => env('BREVO_TEMPLATE_DELIVERED', 5),
                'Cancelled'          => env('BREVO_TEMPLATE_CANCELLED', 6),
            ];

            // Only attempt send if we have an email
            if (!empty($recipientEmail)) {
                try {
                    // Use explicit 'Cancelled' key to find templateId
                    $explicitStatusKey = 'Cancelled';
                    $templateId = isset($templateMap[$explicitStatusKey]) ? (int)$templateMap[$explicitStatusKey] : null;

                    // If templateId is null-ish, log warning and still send with orderStatus fall-back (defensive)
                    if (empty($templateId)) {
                        \Log::warning('updateStatus: no templateId configured for Cancelled, sendOrderMail will fallback to default', [
                            'order_id' => $order->id,
                            'env_val' => env('BREVO_TEMPLATE_CANCELLED')
                        ]);
                    }

                    $emailPayload = [
                        'email' => $recipientEmail,
                        'name' => $recipientName ?: ($order->name ?? 'Customer'),
                        'orderId' => $order->id,
                        'trackingId' => $order->tracking_id ?? $order->trackingId ?? null,
                        'orderStatus' => 'Cancelled', // keep this for readability and for sendOrderMail map
                    ];

                    // Crucial: explicitly include templateId so sendOrderMail cannot pick the wrong template
                    if (!empty($templateId)) {
                        $emailPayload['templateId'] = $templateId;
                    }

                    // Add debugging log before calling mailing function
                    \Log::info('updateStatus: sending cancellation email', [
                        'order_id' => $order->id,
                        'to' => $recipientEmail,
                        'orderStatus' => $emailPayload['orderStatus'],
                        'templateId' => $emailPayload['templateId'] ?? null
                    ]);

                    $fakeRequest = new \Illuminate\Http\Request();
                    $fakeRequest->replace($emailPayload);

                    // Make an instance of the mail controller (container resolves dependencies)
                    $mailController = app()->make(\App\Http\Controllers\TransactionalMailController::class);

                    // Call the instance method (non-static)
                    $response = $mailController->sendOrderMail($fakeRequest);

                    $emailAttempted = true;
                    $emailResult = $response instanceof \Illuminate\Http\JsonResponse ? $response->getData(true) : $response;

                    \Log::info('Cancellation email attempted', [
                        'order_id' => $order->id,
                        'to' => $recipientEmail,
                        'mail_result' => $emailResult
                    ]);
                } catch (\Throwable $e) {
                    \Log::error('Failed to send cancellation email', [
                        'order_id' => $order->id,
                        'to' => $recipientEmail,
                        'err' => $e->getMessage()
                    ]);
                    $emailAttempted = true;
                    $emailResult = ['error' => $e->getMessage()];
                }
            } else {
                \Log::warning('Cancellation email skipped: no recipient email found', ['order_id' => $order->id]);
            }
        }

        // Return success (include email attempt info when applicable)
        return response()->json([
            'message' => 'Order status updated successfully',
            'order_id' => $order->id,
            'order_status' => $order->order_status,
            'email_attempted' => $emailAttempted,
            'email_result' => $emailResult
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $ve) {
        return response()->json(['errors' => $ve->errors()], 422);
    } catch (\Throwable $e) {
        \Log::error('updateStatus failed', ['err' => $e->getMessage(), 'payload' => $request->all()]);
        return response()->json(['error' => 'Failed to update order status', 'details' => $e->getMessage()], 500);
    }
}




    public function AllOrders(Request $request)
{
    // Optional: ensure only admins can call this (adjust to your auth system)
    // $this->authorize('viewAny', PlacedOrder::class);

    // Eager load the user relationship but only select the fields we need
    // Use pagination in production if the orders table is large
    $perPage = (int) $request->query('per_page', 0);

    if ($perPage > 0) {
        $orders = PlacedOrder::with(['user:id,email,name'])->paginate($perPage);
    } else {
        $orders = PlacedOrder::with(['user:id,email,name'])->get();
    }

    if ($orders->isEmpty()) {
        return response()->json(['message' => 'No orders found'], 404);
    }

    // If using pagination, return the paginator directly (it includes meta)
    return response()->json($orders, 200);
}

    // public function updateStatusByAdmin(Request $request) {
       
          
    
    //         // ✅ Validate the request
    //         $request->validate([
    //             'id' => 'required|integer|exists:placedorders,id',
    //             'order_status' => 'required|string'
    //         ]);
    
    //         // ✅ Find the order
    //         $order = PlacedOrder::where('id', $request->id)->first();
    
    //         if (!$order) {
    //             return response()->json(['message' => 'Order not found'], 404);
    //         }
    
    //         // ✅ Update the order status
    //         $order->order_status = $request->order_status;
    //         $order->save();
    
    //         return response()->json(['message' => 'Order status updated successfully'], 200);
        
    // }
    

public function updateStatusByAdmin(\Illuminate\Http\Request $request)
{
    // Validate request
    $request->validate([
        'id' => 'required|integer|exists:placedorders,id',
        'order_status' => 'required|string'
    ]);

    // Find the order, eager-load user relation if available
    $order = \App\Models\PlacedOrder::with('user')->find($request->id);

    if (! $order) {
        return response()->json(['message' => 'Order not found'], 404);
    }

    // Update the order status and save
    $order->order_status = $request->order_status;
    $order->save();

    // --- Prepare email payload (defensive) ---
    $email = $order->email ?? null;
    $name  = $order->name ?? null;

    // Prefer user relation if present
    if (empty($email) && isset($order->user) && $order->user) {
        $email = $order->user->email ?? $email;
        $name  = $order->user->name ?? $name;
    }

    // If still missing and user_id exists, try to fetch user
    if (empty($email) && isset($order->user_id)) {
        try {
            $user = \App\Models\User::select('email', 'name', 'first_name', 'last_name')->find($order->user_id);
            if ($user) {
                $email = $user->email ?? $email;
                $name  = $user->name ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            }
        } catch (\Throwable $e) {
            \Log::warning('updateStatusByAdmin: failed to load user by user_id', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'err' => $e->getMessage()
            ]);
        }
    }

    // Build payload for sendOrderMail
    $emailPayload = [
        'email' => $email,
        'name' => $name ?? 'Customer',
        'orderId' => $order->id,
        'trackingId' => $order->tracking_id ?? $order->trackingId ?? null,
        'orderStatus' => $order->order_status,
        // optionally: 'templateId' => <int> 
    ];

    $emailAttempted = false;
    $emailResult = null;

    // Attempt to call TransactionalMailController::sendOrderMail through container
    if (! empty($emailPayload['email'])) {
        try {
            // Create a Request instance for the controller method
            $fakeRequest = new \Illuminate\Http\Request();
            $fakeRequest->replace($emailPayload);

            // Call the sendOrderMail method on your TransactionalMailController (adjust namespace if needed)
            $response = app()->call([\App\Http\Controllers\TransactionalMailController::class, 'sendOrderMail'], [
                'request' => $fakeRequest
            ]);

            $emailAttempted = true;
            // If the controller returned a JsonResponse, capture its data
            if ($response instanceof \Illuminate\Http\JsonResponse) {
                $emailResult = $response->getData(true);
            } else {
                // Could be other return types; capture string cast
                $emailResult = is_object($response) ? (array) $response : $response;
            }
        } catch (\Throwable $e) {
            \Log::error('updateStatusByAdmin: failed to call sendOrderMail', [
                'order_id' => $order->id,
                'err' => $e->getMessage()
            ]);
            $emailAttempted = true;
            $emailResult = ['error' => $e->getMessage()];
        }
    } else {
        \Log::warning('updateStatusByAdmin: no recipient email found; skipping email send', [
            'order_id' => $order->id
        ]);
    }

    // Return success and minimal email attempt info
    return response()->json([
        'message' => 'Order status updated successfully',
        'order_id' => $order->id,
        'order_status' => $order->order_status,
        'email_attempted' => $emailAttempted,
        'email_result' => $emailResult
    ], 200);
}   
}




