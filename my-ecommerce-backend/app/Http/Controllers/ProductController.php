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
//         'stock' => 'required|integer',
//         'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
//         'hover_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
//         'thumbnail_images.*' => 'required|image|mimes:jpg,jpeg,png|max:5120',
//         'availableSizes' => 'required|array',  // ✅ Ensure array validation
//         'availableColors' => 'required|array', // ✅ Ensure array validation
//     ]);

//     if ($validator->fails()) {
//         return response()->json(['errors' => $validator->errors()], 422);
//     }

//     // ✅ Convert availableSizes values to integers
//     $availableSizes = array_map('intval', $request->availableSizes);

//     // ✅ Save main image with original name
//     $imagePath = null;
//     if ($request->hasFile('image')) {
//         $imageFile = $request->file('image');
//         $imageName = time() . '-' . $imageFile->getClientOriginalName();
//         $imagePath = $imageFile->storeAs('products', $imageName, 'public');
//     }

//     // ✅ Save hover image with original name
//     $hoverImagePath = null;
//     if ($request->hasFile('hover_image')) {
//         $hoverImageFile = $request->file('hover_image');
//         $hoverImageName = time() . '-' . $hoverImageFile->getClientOriginalName();
//         $hoverImagePath = $hoverImageFile->storeAs('products', $hoverImageName, 'public');
//     }

//     // ✅ Save multiple thumbnail images
//     $thumbnailImagePaths = [];
//     if ($request->hasFile('thumbnail_images')) {
//         foreach ($request->file('thumbnail_images') as $file) {
//             $thumbImageName = time() . '-' . $file->getClientOriginalName();
//             $thumbnailImagePaths[] = $file->storeAs('products', $thumbImageName, 'public');
//         }
//     }

//     // ✅ Create the product
//     $product = Product::create([
//         'name' => $request->name,
//         'category' => $request->category,
//         'price' => $request->price,
//         'stock' => $request->stock,
//         'image' => $imagePath,
//         'hover_image' => $hoverImagePath,
//         'thumbnail_images' => $thumbnailImagePaths, // ✅ No need to encode, will be cast as JSON
//         'availableSizes' => $availableSizes,  // ✅ Now values are integers
//         'availableColors' => $request->availableColors, // ✅ Automatically converted to JSON in the model
//     ]);

//     return response()->json(['message' => 'Product added successfully!', 'product' => $product], 201);
// }

public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'category' => 'required|string|max:255',
        'price' => 'required|numeric',
        'old_price' => 'nullable|numeric', // ✅ optional old price
        'stock' => 'required|integer',
        'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        'hover_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        'thumbnail_images.*' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        'availableSizes' => 'required|array',  
        'availableColors' => 'required|array', 
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // ✅ Convert availableSizes values to integers
    $availableSizes = array_map('intval', $request->availableSizes);

    // ✅ Save main image with original name
    $imagePath = null;
    if ($request->hasFile('image')) {
        $imageFile = $request->file('image');
        $imageName = time() . '-' . $imageFile->getClientOriginalName();
        $imagePath = $imageFile->storeAs('products', $imageName, 'public');
    }

    // ✅ Save hover image with original name
    $hoverImagePath = null;
    if ($request->hasFile('hover_image')) {
        $hoverImageFile = $request->file('hover_image');
        $hoverImageName = time() . '-' . $hoverImageFile->getClientOriginalName();
        $hoverImagePath = $hoverImageFile->storeAs('products', $hoverImageName, 'public');
    }

    // ✅ Save multiple thumbnail images
    $thumbnailImagePaths = [];
    if ($request->hasFile('thumbnail_images')) {
        foreach ($request->file('thumbnail_images') as $file) {
            $thumbImageName = time() . '-' . $file->getClientOriginalName();
            $thumbnailImagePaths[] = $file->storeAs('products', $thumbImageName, 'public');
        }
    }

    // ✅ Create the product
    $product = Product::create([
        'name' => $request->name,
        'category' => $request->category,
        'price' => $request->price,
        'old_price' => $request->old_price, // ✅ added here
        'stock' => $request->stock,
        'image' => $imagePath,
        'hover_image' => $hoverImagePath,
        'thumbnail_images' => $thumbnailImagePaths,
        'availableSizes' => $availableSizes,
        'availableColors' => $request->availableColors,
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


public function update(Request $request, $id)
{
    $product = Product::findOrFail($id);

    // ✅ Validate only fields that are present in the request
    $request->validate([
        'name' => 'sometimes|string|max:255',
        'category' => 'sometimes|string|max:255',
        'price' => 'sometimes|numeric|min:0',
        'old_price' => 'sometimes|numeric|min:0', // ✅ added validation
        'stock' => 'sometimes|integer|min:0',
        'description' => 'sometimes|string',
    ]);

    // ✅ Update only fields that exist in the request
    if ($request->has('name')) $product->name = $request->name;
    if ($request->has('category')) $product->category = $request->category;
    if ($request->has('price')) $product->price = $request->price;
    if ($request->has('old_price')) $product->old_price = $request->old_price; // ✅ added update
    if ($request->has('stock')) $product->stock = $request->stock;
    if ($request->has('description')) $product->description = $request->description;

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

