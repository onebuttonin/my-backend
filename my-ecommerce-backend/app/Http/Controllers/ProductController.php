<?php 

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller {



// public function store(Request $request)
// {
//     $validator = Validator::make($request->all(), [
//         'name' => 'required|string|max:255',
//         'category' => 'required|string|max:255',
//         'price' => 'required|numeric',
//         'old_price' => 'nullable|numeric',
//         'stock' => 'required|integer',
//         'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
//         'hover_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
//         'thumbnail_images' => 'nullable|array',
//         'thumbnail_images.*' => 'image|mimes:jpg,jpeg,png|max:5120',
//         'availableSizes' => 'required|array',
//         'availableSizes.*' => 'integer',
//         'availableColors' => 'required|array',
//         // description optional
//         'description' => 'nullable|array', // accept as array (you can change to json/text if you prefer)
//     ]);

//     if ($validator->fails()) {
//         return response()->json(['errors' => $validator->errors()], 422);
//     }

//     // convert sizes to integers (already validated)
//     $availableSizes = array_map('intval', $request->input('availableSizes', []));

//     // Save main image
//     $imagePath = null;
//     if ($request->hasFile('image')) {
//         $imageFile = $request->file('image');
//         $imageName = time() . '-' . $imageFile->getClientOriginalName();
//         $imagePath = $imageFile->storeAs('products', $imageName, 'public');
//     }

//     // Save hover image
//     $hoverImagePath = null;
//     if ($request->hasFile('hover_image')) {
//         $hoverImageFile = $request->file('hover_image');
//         $hoverImageName = time() . '-' . $hoverImageFile->getClientOriginalName();
//         $hoverImagePath = $hoverImageFile->storeAs('products', $hoverImageName, 'public');
//     }

//     // Save multiple thumbnails (optional)
//     $thumbnailImagePaths = [];
//     if ($request->hasFile('thumbnail_images')) {
//         foreach ($request->file('thumbnail_images') as $file) {
//             $thumbImageName = time() . '-' . $file->getClientOriginalName();
//             $thumbnailImagePaths[] = $file->storeAs('products', $thumbImageName, 'public');
//             // slight delay between names not required; timestamps can repeat, but you could append uniqid() if collisions occur
//         }
//     }

//     // available colors
//     $availableColors = $request->input('availableColors', []);

//     // description: keep null if not provided (DB must allow null)
//     $description = $request->has('description') ? $request->input('description') : null;

//     $product = Product::create([
//         'name' => $request->input('name'),
//         'category' => $request->input('category'),
//         'price' => $request->input('price'),
//         'old_price' => $request->input('old_price'),
//         'stock' => $request->input('stock'),
//         'image' => $imagePath,
//         'hover_image' => $hoverImagePath,
//         // store as JSON string if your DB column is json or text; if you have $casts in model you can pass array
//         'thumbnail_images' => $thumbnailImagePaths, // if Product model casts to array/json, this is fine
//         'availableSizes' => $availableSizes,
//         'availableColors' => $availableColors,
//         'description' => $description,
//     ]);

//     return response()->json(['message' => 'Product added successfully!', 'product' => $product], 201);
// }

public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'category' => 'required|string|max:255',
        'price' => 'required|numeric',
        'cost_price' => 'nullable|numeric',         // <-- new: accept cost price
        'old_price' => 'nullable|numeric',
        'stock' => 'required|integer',
        'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        'hover_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        'thumbnail_images' => 'nullable|array',
        'thumbnail_images.*' => 'image|mimes:jpg,jpeg,png|max:5120',
        'availableSizes' => 'required|array',
        'availableSizes.*' => 'integer',
        'availableColors' => 'required|array',
        // description optional
        'description' => 'nullable|array', // accept as array (you can change to json/text if you prefer)
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // convert sizes to integers (already validated)
    $availableSizes = array_map('intval', $request->input('availableSizes', []));

    // Save main image (use uniqid to reduce filename collisions)
    $imagePath = null;
    if ($request->hasFile('image')) {
        $imageFile = $request->file('image');
        $imageName = time() . '-' . uniqid() . '-' . $imageFile->getClientOriginalName();
        $imagePath = $imageFile->storeAs('products', $imageName, 'public');
    }

    // Save hover image
    $hoverImagePath = null;
    if ($request->hasFile('hover_image')) {
        $hoverImageFile = $request->file('hover_image');
        $hoverImageName = time() . '-' . uniqid() . '-' . $hoverImageFile->getClientOriginalName();
        $hoverImagePath = $hoverImageFile->storeAs('products', $hoverImageName, 'public');
    }

    // Save multiple thumbnails (optional)
    $thumbnailImagePaths = [];
    if ($request->hasFile('thumbnail_images')) {
        foreach ($request->file('thumbnail_images') as $file) {
            $thumbImageName = time() . '-' . uniqid() . '-' . $file->getClientOriginalName();
            $thumbnailImagePaths[] = $file->storeAs('products', $thumbImageName, 'public');
        }
    }

    // available colors
    $availableColors = $request->input('availableColors', []);

    // description: keep null if not provided (DB must allow null)
    $description = $request->has('description') ? $request->input('description') : null;

    // Prepare cost_price (use provided or default 0.00)
    $costPrice = $request->filled('cost_price') ? (float) $request->input('cost_price') : 0.00;

    $product = Product::create([
        'name' => $request->input('name'),
        'category' => $request->input('category'),
        'price' => $request->input('price'),
        'cost_price' => $costPrice,               // <-- saved here
        'old_price' => $request->input('old_price'),
        'stock' => $request->input('stock'),
        'image' => $imagePath,
        'hover_image' => $hoverImagePath,
        // store as array if Product model casts to array/json
        'thumbnail_images' => $thumbnailImagePaths,
        'availableSizes' => $availableSizes,
        'availableColors' => $availableColors,
        'description' => $description,
    ]);

    return response()->json(['message' => 'Product added successfully!', 'product' => $product], 201);
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

// public function update(Request $request, $id)
// {
//     $product = Product::findOrFail($id);

//     // Validate only the fields that are present in the request
//     $request->validate([
//         'name' => 'sometimes|string|max:255',
//         'category' => 'sometimes|string|max:255',
//         'price' => 'sometimes|numeric|min:0',
//         'stock' => 'sometimes|integer|min:0',
//         'description' => 'sometimes|string',
//     ]);

//     // Update only the fields that exist in the request
//     if ($request->has('name')) $product->name = $request->name;
//     if ($request->has('category')) $product->category = $request->category;
//     if ($request->has('price')) $product->price = $request->price;
//     if ($request->has('stock')) $product->stock = $request->stock;
//     if ($request->has('description')) $product->description = $request->description;
    
//     $product->save();

//     return response()->json([
//         'message' => 'Product updated successfully',
//         'product' => $product
//     ], 200);
// }


// public function update(Request $request, $id)
// {
//     $product = Product::findOrFail($id);

//     // ✅ Validate only fields that are present in the request
//     $request->validate([
//         'name' => 'sometimes|string|max:255',
//         'category' => 'sometimes|string|max:255',
//         'price' => 'sometimes|numeric|min:0',
//         'old_price' => 'sometimes|numeric|min:0', // ✅ added validation
//         'stock' => 'sometimes|integer|min:0',
//         'description' => 'sometimes|string',
//     ]);

//     // ✅ Update only fields that exist in the request
//     if ($request->has('name')) $product->name = $request->name;
//     if ($request->has('category')) $product->category = $request->category;
//     if ($request->has('price')) $product->price = $request->price;
//     if ($request->has('old_price')) $product->old_price = $request->old_price; // ✅ added update
//     if ($request->has('stock')) $product->stock = $request->stock;
//     if ($request->has('description')) $product->description = $request->description;

//     $product->save();

//     return response()->json([
//         'message' => 'Product updated successfully',
//         'product' => $product
//     ], 200);
// }


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

}

