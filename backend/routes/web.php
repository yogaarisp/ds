
use Illuminate\Support\Facades\Route;

// Simple test route
Route::get('/test-web', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'Web route working',
        'time' => now()
    ]);
});

// Fallback storage route for environments where symlink is broken or missing
Route::get('/storage/{path}', function ($path) {
    // 1. Look in storage/app/public/
    $storageFile = storage_path('app/public/' . $path);
    if (file_exists($storageFile) && is_file($storageFile)) {
        return response()->file($storageFile, [
            'Cache-Control' => 'public, max-age=2592000'
        ]);
    }

    // 2. Look in public/ directly
    $publicFile = public_path($path);
    if (file_exists($publicFile) && is_file($publicFile)) {
        return response()->file($publicFile, [
            'Cache-Control' => 'public, max-age=2592000'
        ]);
    }

    abort(404);
})->where('path', '.*');
