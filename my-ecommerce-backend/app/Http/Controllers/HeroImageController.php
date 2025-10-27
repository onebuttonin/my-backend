<?php

namespace App\Http\Controllers;

use App\Models\HeroImage;
use Illuminate\Http\Request;

class HeroImageController extends Controller
{
    // ✅ Get all hero images (grouped by screen type)
    public function index()
    {
        $large = HeroImage::where('screen_type', 'large')
            ->where('is_active', true)
            ->orderBy('order')
            ->pluck('image_path');

        $small = HeroImage::where('screen_type', 'small')
            ->where('is_active', true)
            ->orderBy('order')
            ->pluck('image_path');

        return response()->json([
            'large' => $large,
            'small' => $small,
        ]);
    }

    // ✅ Store new hero image
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'screen_type' => 'required|in:large,small',
        ]);

        $path = $request->file('image')->store('uploads/hero', 'public');

        $image = HeroImage::create([
            'image_path' => asset('storage/' . $path),
            'screen_type' => $request->screen_type,
        ]);

        return response()->json(['message' => 'Image uploaded successfully', 'data' => $image]);
    }

    // ✅ Delete hero image
public function destroy(Request $request)
{
    $path = $request->input('path');

    if (!$path) {
        return response()->json(['error' => 'Image path is required'], 400);
    }

    $image = HeroImage::where('image_path', $path)->first();

    if (!$image) {
        return response()->json(['error' => 'Image not found'], 404);
    }

    // Optional: delete the file from storage if it exists
    $fullPath = public_path($path);
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }

    $image->delete();

    return response()->json(['message' => 'Image deleted successfully']);
}


}
