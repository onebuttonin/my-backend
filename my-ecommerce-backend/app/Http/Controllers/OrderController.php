<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    // Get all orders
    public function index()
    {
        return response()->json(Order::all(), 200);
    }

    // Create a new order
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'total_price' => 'required|numeric',
            'shipping_address' => 'required|string',
            'billing_address' => 'nullable|string',
            'payment_method' => 'nullable|string',
        ]);

        $validated['order_number'] = strtoupper(Str::random(10)); // Generate unique order number
        $validated['status'] = 'pending'; // Default status
        $validated['payment_status'] = 'pending'; // Default payment status

        $order = Order::create($validated);

        return response()->json(['message' => 'Order placed successfully', 'order' => $order], 201);
    }

    // Get a single order
    public function show($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order, 200);
    }

    // Update order status or payment status
    public function update(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order->update($request->only(['status', 'payment_status']));

        return response()->json(['message' => 'Order updated successfully', 'order' => $order], 200);
    }

    // Delete an order
    public function destroy($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order->delete();

        return response()->json(['message' => 'Order deleted successfully'], 200);
    }
}
