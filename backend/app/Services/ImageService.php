<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    /**
     * Store and optimize an image
     * 
     * @param UploadedFile $file
     * @param string $directory Directory in storage/app/public
     * @param string $name Base name for the file
     * @param int $maxWidth Maximum width in pixels
     * @param int $quality JPEG quality (1-100)
     * @return string|null Path to stored image
     */
    public function store(UploadedFile $file, string $directory, string $name, int $maxWidth = 800, int $quality = 80): ?string
    {
        try {
            $extension = strtolower($file->getClientOriginalExtension());
            
            // Only process images that can be optimized
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                // Store as-is for other formats
                $filename = Str::slug($name) . '-' . time() . '.' . $extension;
                return $file->storeAs($directory, $filename, 'public');
            }

            // Create image resource based on type
            $sourceImage = match($extension) {
                'jpg', 'jpeg' => @imagecreatefromjpeg($file->getRealPath()),
                'png' => @imagecreatefrompng($file->getRealPath()),
                'webp' => @imagecreatefromwebp($file->getRealPath()),
                default => null,
            };

            if (!$sourceImage) {
                // Fallback to simple storage if GD fails
                $filename = Str::slug($name) . '-' . time() . '.' . $extension;
                return $file->storeAs($directory, $filename, 'public');
            }

            // Get original dimensions
            $originalWidth = imagesx($sourceImage);
            $originalHeight = imagesy($sourceImage);

            // Calculate new dimensions (maintain aspect ratio)
            if ($originalWidth > $maxWidth) {
                $ratio = $maxWidth / $originalWidth;
                $newWidth = $maxWidth;
                $newHeight = (int) ($originalHeight * $ratio);
            } else {
                $newWidth = $originalWidth;
                $newHeight = $originalHeight;
            }

            // Create resized image
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

            // Preserve transparency for PNG
            if ($extension === 'png') {
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
                imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
            }

            // Resize
            imagecopyresampled(
                $resizedImage, 
                $sourceImage, 
                0, 0, 0, 0, 
                $newWidth, $newHeight, 
                $originalWidth, $originalHeight
            );
            
            // Generate filename - always save as WebP for best compression
            $filename = Str::slug($name) . '-' . time() . '.webp';
            $storagePath = storage_path('app/public/' . $directory);
            
            // Ensure directory exists
            if (!is_dir($storagePath)) {
                mkdir($storagePath, 0755, true);
            }

            $fullPath = $storagePath . '/' . $filename;

            // Save optimized image as WebP
            imagewebp($resizedImage, $fullPath, $quality);

            // Clean up
            imagedestroy($sourceImage);
            imagedestroy($resizedImage);

            return $directory . '/' . $filename;

        } catch (\Exception $e) {
            // Fallback to simple storage on any error
            $filename = Str::slug($name) . '-' . time() . '.' . $file->getClientOriginalExtension();
            return $file->storeAs($directory, $filename, 'public');
        }
    }

    /**
     * Delete an image from storage
     */
    public function delete(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
