<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        return response()->json(Setting::getAll());
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'store_name' => 'nullable|string|max:255',
            'store_address' => 'nullable|string|max:500',
            'store_phone' => 'nullable|string|max:20',
        ]);

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                Setting::set($key, $value);
            }
        }

        return response()->json([
            'message' => 'Settings updated successfully',
            'data' => Setting::getAll()
        ]);
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Delete old logo if exists
        $oldLogo = Setting::get('store_logo');
        if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        // Store new logo
        $path = $request->file('logo')->store('logos', 'public');
        Setting::set('store_logo', $path);

        return response()->json([
            'message' => 'Logo uploaded successfully',
            'path' => $path,
            'url' => Storage::url($path)
        ]);
    }

    public function deleteLogo()
    {
        $logo = Setting::get('store_logo');
        if ($logo && Storage::disk('public')->exists($logo)) {
            Storage::disk('public')->delete($logo);
        }
        Setting::set('store_logo', null);

        return response()->json(['message' => 'Logo deleted successfully']);
    }

    public function uploadFavicon(Request $request)
    {
        $request->validate([
            'favicon' => 'required|image|mimes:jpeg,png,gif,ico|max:1024',
        ]);

        // Delete old favicon if exists
        $oldFavicon = Setting::get('store_favicon');
        if ($oldFavicon && Storage::disk('public')->exists($oldFavicon)) {
            Storage::disk('public')->delete($oldFavicon);
        }

        // Store new favicon
        $path = $request->file('favicon')->store('favicons', 'public');
        Setting::set('store_favicon', $path);

        return response()->json([
            'message' => 'Favicon uploaded successfully',
            'path' => $path,
            'url' => Storage::url($path)
        ]);
    }

}
