<?php 

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;



class ProductController extends Controller {


public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'category' => 'required|string|max:255',
        'price' => 'required|numeric',
        'cost_price' => 'nullable|numeric',
        'old_price' => 'nullable|numeric',
        'stock' => 'required|integer',
        'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        'hover_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        'thumbnail_images' => 'nullable|array',
        'thumbnail_images.*' => 'image|mimes:jpg,jpeg,png|max:5120',
        'availableSizes' => 'required|array',
        'availableSizes.*' => 'integer',
        'availableColors' => 'required|array',
        'description' => 'nullable|array',
        'sku' => 'nullable|string|max:255|unique:products,sku', // ✅ new validation
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Handle image uploads
    $imagePath = $hoverImagePath = null;
    $thumbnailImagePaths = [];

    if ($request->hasFile('image')) {
        $file = $request->file('image');
        $imageName = time() . '-' . uniqid() . '-' . $file->getClientOriginalName();
        $imagePath = $file->storeAs('products', $imageName, 'public');
    }

    if ($request->hasFile('hover_image')) {
        $file = $request->file('hover_image');
        $hoverName = time() . '-' . uniqid() . '-' . $file->getClientOriginalName();
        $hoverImagePath = $file->storeAs('products', $hoverName, 'public');
    }

    if ($request->hasFile('thumbnail_images')) {
        foreach ($request->file('thumbnail_images') as $file) {
            $thumbName = time() . '-' . uniqid() . '-' . $file->getClientOriginalName();
            $thumbnailImagePaths[] = $file->storeAs('products', $thumbName, 'public');
        }
    }

    // Prepare data
    $availableSizes = array_map('intval', $request->input('availableSizes', []));
    $availableColors = $request->input('availableColors', []);
    $description = $request->has('description') ? $request->input('description') : null;
    $costPrice = $request->filled('cost_price') ? (float)$request->input('cost_price') : 0.00;

    // ✅ Auto-generate SKU if not provided
    $sku = $request->filled('sku') 
        ? $request->input('sku') 
        : 'OB-' . strtoupper(uniqid());

    // Create product
    $product = Product::create([
        'name' => $request->input('name'),
        'category' => $request->input('category'),
        'price' => $request->input('price'),
        'cost_price' => $costPrice,
        'old_price' => $request->input('old_price'),
        'stock' => $request->input('stock'),
        'image' => $imagePath,
        'hover_image' => $hoverImagePath,
        'thumbnail_images' => $thumbnailImagePaths,
        'availableSizes' => $availableSizes,
        'availableColors' => $availableColors,
        'description' => $description,
        'sku' => $sku, // ✅ saved here
    ]);

    return response()->json([
        'message' => 'Product added successfully!',
        'product' => $product
    ], 201);
}
    

public function show($id) {
    $product = Product::find($id);
    if (!$product) {
        return response()->json(['message' => 'Product not found'], 404);
    }
    return response()->json($product);
}

public function removeFromProduct($id) {
    $product = Product::find($id);

    if (!$product) {
        return response()->json(['message' => 'product not found'], 404);
    }

    $product->delete();

    return response()->json(['message' => 'product removed']);
}


public function update(Request $request, $id)
{
    $product = Product::findOrFail($id);

    // ✅ Validate only fields that are present in the request
    $request->validate([
        'name' => 'sometimes|string|max:255',
        'category' => 'sometimes|string|max:255',
        'price' => 'sometimes|numeric|min:0',
        'old_price' => 'sometimes|numeric|min:0',
        'stock' => 'sometimes|integer|min:0',
        'description' => 'sometimes|array', // ✅ description must be an array (JSON)
        'description.details' => 'sometimes|string|nullable',
        'description.size_fit' => 'sometimes|string|nullable',
        'description.wash_care' => 'sometimes|string|nullable',
        'description.specification' => 'sometimes|array|nullable',
        'description.sku' => 'sometimes|string|nullable',
    ]);

    // ✅ Update only fields that exist in the request
    if ($request->has('name')) $product->name = $request->name;
    if ($request->has('category')) $product->category = $request->category;
    if ($request->has('price')) $product->price = $request->price;
    if ($request->has('old_price')) $product->old_price = $request->old_price;
    if ($request->has('stock')) $product->stock = $request->stock;
    if ($request->has('description')) $product->description = $request->description; // ✅ stored as JSON

    $product->save();

    return response()->json([
        'message' => 'Product updated successfully',
        'product' => $product
    ], 200);
}


public function replaceImage(Request $request, $id)
{
    $request->validate([
        'type' => 'required|string|in:image,hover_image,thumbnail_images',
        'new_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        'old_path' => 'nullable|string'
    ]);

    $product = Product::findOrFail($id);

    $type = $request->input('type');
    $file = $request->file('new_image');
    $path = $file->store('products', 'public'); // save new image

    if ($type === 'image') {
        // Delete old file
        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }
        $product->image = $path;
    }

    if ($type === 'hover_image') {
        if ($product->hover_image && Storage::disk('public')->exists($product->hover_image)) {
            Storage::disk('public')->delete($product->hover_image);
        }
        $product->hover_image = $path;
    }

    if ($type === 'thumbnail_images') {
        $oldPath = $request->input('old_path');

        $thumbs = $product->thumbnail_images ?? [];
        // Replace old image path with new one
        $thumbs = array_map(function ($thumb) use ($oldPath, $path) {
            return $thumb === $oldPath ? $path : $thumb;
        }, $thumbs);

        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $product->thumbnail_images = $thumbs;
    }

    $product->save();

    return response()->json([
        'message' => '✅ Image replaced successfully.',
        'product' => $product
    ]);
}


public function deleteImage(Request $request, $id)
{
    $product = Product::findOrFail($id);

    $type = $request->type;
    $path = $request->path;

    if ($type === 'image' && $product->image) {
        \Storage::delete($product->image);
        $product->image = null;
    } elseif ($type === 'hover_image' && $product->hover_image) {
        \Storage::delete($product->hover_image);
        $product->hover_image = null;
    } elseif ($type === 'thumbnail_images' && $path) {
        $thumbs = $product->thumbnail_images; // assume array
        $thumbs = array_filter($thumbs, fn($t) => $t !== $path);
        \Storage::delete($path);
        $product->thumbnail_images = array_values($thumbs);
    }

    $product->save();

    return response()->json(['message' => 'Image deleted', 'product' => $product]);
}












public function updatePrice(Request $request, $id)
{
    $request->validate([
        'price' => 'required|numeric|min:0',
    ]);

    // Find the product
    $product = Product::findOrFail($id);

    // Update only the price
    $product->price = $request->price;
    $product->save();

    return response()->json([
        'message' => 'Product price updated successfully',
        'product' => $product
    ], 200);
}

public function updateSize(Request $request, $id)
{
    $product = Product::findOrFail($id);
    
    $request->validate([
        'size' => 'required|string',
        'stock' => 'required|integer|min:0'
    ]);

    $sizes = $product->availableSizes ?? [];

    if (!array_key_exists($request->size, $sizes)) {
        return response()->json(['message' => 'Size not found.'], 404);
    }

    $sizes[$request->size] = $request->stock;
    $product->availableSizes = $sizes;
    $product->save();

    return response()->json(['message' => 'Size updated successfully', 'availableSizes' => $sizes]);
}


public function addSize(Request $request, $id)
{
    $product = Product::findOrFail($id);
    
    $request->validate([
        'size' => 'required|string',
        'stock' => 'required|integer|min:0'
    ]);

    $sizes = $product->availableSizes ?? [];

    if (array_key_exists($request->size, $sizes)) {
        return response()->json(['message' => 'Size already exists.'], 400);
    }

    $sizes[$request->size] = $request->stock;
    $product->availableSizes = $sizes;
    $product->save();

    return response()->json(['message' => 'Size added successfully', 'availableSizes' => $sizes]);
}


public function deleteSize(Request $request, $id)
{
    $product = Product::findOrFail($id);
    
    $request->validate([
        'size' => 'required|string',
    ]);

    $sizes = $product->availableSizes ?? [];

    if (!array_key_exists($request->size, $sizes)) {
        return response()->json(['message' => 'Size not found.'], 404);
    }

    unset($sizes[$request->size]);
    $product->availableSizes = $sizes;
    $product->save();

    return response()->json(['message' => 'Size deleted successfully', 'availableSizes' => $sizes]);
}

public function removeSize(Request $request, $id)
{
    $product = Product::findOrFail($id);

    $request->validate([
        'size' => 'required|string',
        'quantity' => 'nullable|integer|min:1', // optional quantity to decrement
    ]);

    $sizes = $product->availableSizes ?? [];

    $sizeKey = $request->size;

    if (!array_key_exists($sizeKey, $sizes)) {
        return response()->json(['message' => 'Size not found.'], 404);
    }

    // If quantity provided -> decrement; otherwise remove the size (old behaviour)
    if ($request->filled('quantity')) {
        $decrement = intval($request->quantity);
        $current = intval($sizes[$sizeKey] ?? 0);
        $remaining = $current - $decrement;

        if ($remaining <= 0) {
            // remove the size entirely if no stock left
            unset($sizes[$sizeKey]);
            $product->availableSizes = $sizes;
            $product->save();

            return response()->json([
                'message' => 'Size removed as remaining stock reached zero.',
                'availableSizes' => $sizes,
            ]);
        }

        // otherwise update with remaining quantity
        $sizes[$sizeKey] = $remaining;
        $product->availableSizes = $sizes;
        $product->save();

        return response()->json([
            'message' => 'Size quantity decremented successfully.',
            'remaining' => $remaining,
            'availableSizes' => $sizes,
        ]);
    }

    // No quantity provided — keep original delete behaviour
    unset($sizes[$sizeKey]);
    $product->availableSizes = $sizes;
    $product->save();

    return response()->json(['message' => 'Size deleted successfully', 'availableSizes' => $sizes]);
}


public function updateColor(Request $request, $id)
{
    $product = Product::findOrFail($id);
    
    $request->validate([
        'oldColor' => 'required|string',
        'newColor' => 'required|string'
    ]);

    $colors = $product->availableColors ?? [];

    if (!in_array($request->oldColor, $colors)) {
        return response()->json(['message' => 'Color not found.'], 404);
    }

    $colors[array_search($request->oldColor, $colors)] = $request->newColor;
    $product->availableColors = $colors;
    $product->save();

    return response()->json(['message' => 'Color updated successfully', 'availableColors' => $colors]);
}


public function addColor(Request $request, $id)
{
    $product = Product::findOrFail($id);
    
    $request->validate([
        'color' => 'required|string'
    ]);

    $colors = $product->availableColors ?? [];

    if (in_array($request->color, $colors)) {
        return response()->json(['message' => 'Color already exists.'], 400);
    }

    $colors[] = $request->color;
    $product->availableColors = $colors;
    $product->save();

    return response()->json(['message' => 'Color added successfully', 'availableColors' => $colors]);
}


public function deleteColor(Request $request, $id)
{
    $product = Product::findOrFail($id);
    
    $request->validate([
        'color' => 'required|string',
    ]);

    $colors = $product->availableColors ?? [];

    if (!in_array($request->color, $colors)) {
        return response()->json(['message' => 'Color not found.'], 404);
    }

    $colors = array_values(array_diff($colors, [$request->color])); // Remove color
    $product->availableColors = $colors;
    $product->save();

    return response()->json(['message' => 'Color deleted successfully', 'availableColors' => $colors]);
}

public function updatePopularity(Request $request, $id)
{
    $request->validate([
        'popularity' => 'required|integer|min:1|max:10',
    ]);

    $product = Product::find($id);

    if (!$product) {
        return response()->json([
            'status' => false,
            'message' => 'Product not found'
        ], 404);
    }

    $product->popularity = $request->popularity;
    $product->save();

    return response()->json([
        'status' => true,
        'message' => 'Popularity updated successfully',
        'product' => $product
    ], 200);
}



// ProductController.php
public function decrementSize(Request $request, $id)
{
    $request->validate([
        'size' => 'required|string',
        'quantity' => 'required|integer|min:1',
        'order_id' => 'nullable|integer',
    ]);

    $product = Product::findOrFail($id);
    $sizes = $product->availableSizes ?? [];

    // check size exists
    if (!isset($sizes[$request->size])) {
        return response()->json(['message' => 'Size not found.'], 404);
    }

    // decrement
    $sizes[$request->size] = intval($sizes[$request->size]) - $request->quantity;

    // if <= 0 then remove the size entirely
    if ($sizes[$request->size] <= 0) {
        unset($sizes[$request->size]);
    }

    $product->availableSizes = $sizes;
    $product->save();

    // mark order as processed so you don't reduce stock twice (optional but recommended)
    if ($request->order_id) {
        $order = Order::find($request->order_id);
        if ($order) {
            $order->stock_updated = true; // add this column to orders table if not exist
            $order->save();
        }
    }

    return response()->json(['message' => 'Stock updated', 'availableSizes' => $sizes]);
}


}

