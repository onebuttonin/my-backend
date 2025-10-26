<?php

namespace App\Http\Controllers;

use App\Models\HeroImage;
use Illuminate\Http\Request;

class HeroImageController extends Controller
{
    // ✅ Get all hero images (grouped by screen type)
   public function index()
{
    return response()->json([
        'large' => HeroImage::where('screen_type', 'large')
                    ->get(['id', 'image_path as path']),
        'small' => HeroImage::where('screen_type', 'small')
                    ->get(['id', 'image_path as path']),
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
    public function destroy($id)
    {
        $image = HeroImage::findOrFail($id);
        $image->delete();

        return response()->json(['message' => 'Image deleted successfully']);
    }
}
