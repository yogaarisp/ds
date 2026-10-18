<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CarouselSlide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CarouselSlideController extends Controller
{
    /**
     * Get all carousel slides (admin)
     */
    public function index()
    {
        $slides = CarouselSlide::ordered()->get();
        return response()->json($slides);
    }

    /**
     * Get active carousel slides (public/customer)
     */
    public function active()
    {
        $slides = \Illuminate\Support\Facades\Cache::remember('carousel_slides_active', 600, function() {
            return CarouselSlide::active()->ordered()->get();
        });
        return response()->json($slides);
    }

    /**
     * Store a new carousel slide
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'period_text' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:2048',
            'image_url' => 'nullable|string|max:500',
            'badge_label' => 'nullable|string|max:100',
            'badge_value' => 'nullable|string|max:100',
            'badge_label_2' => 'nullable|string|max:100',
            'badge_value_2' => 'nullable|string|max:100',
            'link_url' => 'nullable|string|max:500',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('carousel', 'public');
        } elseif ($request->filled('image_url')) {
            $validated['image'] = $request->image_url;
        }

        // Set default sort order
        if (!isset($validated['sort_order'])) {
            $validated['sort_order'] = CarouselSlide::max('sort_order') + 1;
        }

        unset($validated['image_url']);
        $slide = CarouselSlide::create($validated);

        return response()->json($slide, 201);
    }

    /**
     * Show a specific carousel slide
     */
    public function show(CarouselSlide $carouselSlide)
    {
        return response()->json($carouselSlide);
    }

    /**
     * Update a carousel slide
     */
    public function update(Request $request, CarouselSlide $carouselSlide)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'period_text' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:2048',
            'image_url' => 'nullable|string|max:500',
            'badge_label' => 'nullable|string|max:100',
            'badge_value' => 'nullable|string|max:100',
            'badge_label_2' => 'nullable|string|max:100',
            'badge_value_2' => 'nullable|string|max:100',
            'link_url' => 'nullable|string|max:500',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if it's a stored file
            if ($carouselSlide->image && !str_starts_with($carouselSlide->image, 'http')) {
                Storage::disk('public')->delete($carouselSlide->image);
            }
            $validated['image'] = $request->file('image')->store('carousel', 'public');
        } elseif ($request->filled('image_url')) {
            // If using external URL
            if ($carouselSlide->image && !str_starts_with($carouselSlide->image, 'http')) {
                Storage::disk('public')->delete($carouselSlide->image);
            }
            $validated['image'] = $request->image_url;
        }

        unset($validated['image_url']);
        $carouselSlide->update($validated);

        return response()->json($carouselSlide);
    }

    /**
     * Delete a carousel slide
     */
    public function destroy(CarouselSlide $carouselSlide)
    {
        // Delete image if it's a stored file
        if ($carouselSlide->image && !str_starts_with($carouselSlide->image, 'http')) {
            Storage::disk('public')->delete($carouselSlide->image);
        }

        $carouselSlide->delete();

        return response()->json(['message' => 'Slide deleted successfully']);
    }

    /**
     * Reorder carousel slides
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'slides' => 'required|array',
            'slides.*.id' => 'required|exists:carousel_slides,id',
            'slides.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($request->slides as $slideData) {
            CarouselSlide::where('id', $slideData['id'])->update([
                'sort_order' => $slideData['sort_order']
            ]);
        }

        return response()->json(['message' => 'Slides reordered successfully']);
    }

    /**
     * Toggle slide active status
     */
    public function toggleActive(CarouselSlide $carouselSlide)
    {
        $carouselSlide->update(['is_active' => !$carouselSlide->is_active]);
        return response()->json($carouselSlide);
    }
}
