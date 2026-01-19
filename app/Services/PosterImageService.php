<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class PosterImageService
{
    protected const SIZES = [185, 342, 500, 780];

    protected const POSTER_WIDTH = 500; // Standard poster aspect ratio width

    /**
     * Optimize a poster image by generating multiple sizes and formats.
     *
     * @param  string  $sourcePath  Full storage path (e.g., 'posters/filename.jpg')
     * @return array<string> Array of generated file paths
     */
    public function optimizePoster(string $sourcePath): array
    {
        $disk = Storage::disk('public');
        $fullPath = $disk->path($sourcePath);

        if (! file_exists($fullPath)) {
            return [];
        }

        // Extract base filename without extension
        $pathInfo = pathinfo($sourcePath);
        $baseFilename = $pathInfo['filename'];
        $extension = $pathInfo['extension'] ?? 'jpg';
        // Keep the subdirectory structure (e.g., posters/jo/)
        $directory = $pathInfo['dirname'];

        $generatedFiles = [];

        // Get original image dimensions
        $imageInfo = getimagesize($fullPath);
        if ($imageInfo === false) {
            return [];
        }

        [$originalWidth, $originalHeight] = $imageInfo;
        $mimeType = $imageInfo['mime'];

        // Load source image based on type
        $sourceImage = $this->loadImage($fullPath, $mimeType);
        if ($sourceImage === null) {
            return [];
        }

        // Generate each size
        foreach (self::SIZES as $width) {
            // Calculate height maintaining aspect ratio
            $height = (int) round(($originalHeight / $originalWidth) * $width);

            // Resize image
            $resizedImage = $this->resizeImage($sourceImage, $width, $height, $originalWidth, $originalHeight);
            if ($resizedImage === null) {
                continue;
            }

            // Generate formats for this size
            $sizeFiles = $this->generateFormats($resizedImage, $directory, $baseFilename, $width, $extension);
            $generatedFiles = array_merge($generatedFiles, $sizeFiles);

            // Free memory
            imagedestroy($resizedImage);
        }

        // Free source image memory
        imagedestroy($sourceImage);

        return $generatedFiles;
    }

    /**
     * Load an image from file based on MIME type.
     */
    protected function loadImage(string $path, string $mimeType): ?\GdImage
    {
        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            'image/gif' => imagecreatefromgif($path),
            default => null,
        };
    }

    /**
     * Resize an image to the specified dimensions.
     */
    protected function resizeImage(\GdImage $source, int $width, int $height, int $sourceWidth, int $sourceHeight): ?\GdImage
    {
        $destination = imagecreatetruecolor($width, $height);
        if ($destination === false) {
            return null;
        }

        // Preserve transparency for PNG
        imagealphablending($destination, false);
        imagesavealpha($destination, true);

        // Resize with high quality
        imagecopyresampled(
            $destination,
            $source,
            0,
            0,
            0,
            0,
            $width,
            $height,
            $sourceWidth,
            $sourceHeight
        );

        return $destination;
    }

    /**
     * Generate multiple formats (AVIF, WebP, original) for a resized image.
     *
     * @return array<string> Array of generated file paths
     */
    protected function generateFormats(\GdImage $image, string $directory, string $baseFilename, int $width, string $originalExtension): array
    {
        $generatedFiles = [];

        // Generate AVIF if ImageMagick is available
        if (extension_loaded('imagick') && class_exists('Imagick')) {
            $avifPath = $this->generateAvif($image, $directory, $baseFilename, $width);
            if ($avifPath !== null) {
                $generatedFiles[] = $avifPath;
            }
        }

        // Generate WebP (GD supports this in PHP 8.1+)
        if (function_exists('imagewebp')) {
            $webpPath = "{$directory}/{$baseFilename}-{$width}.webp";
            $fullPath = Storage::disk('public')->path($webpPath);
            if (imagewebp($image, $fullPath, 85)) {
                $generatedFiles[] = $webpPath;
            }
        }

        // Generate original format (JPEG/PNG)
        $originalPath = "{$directory}/{$baseFilename}-{$width}.{$originalExtension}";
        $fullPath = Storage::disk('public')->path($originalPath);

        $saved = match ($originalExtension) {
            'jpg', 'jpeg' => imagejpeg($image, $fullPath, 85),
            'png' => imagepng($image, $fullPath, 6),
            'webp' => function_exists('imagewebp') ? imagewebp($image, $fullPath, 85) : false,
            default => false,
        };

        if ($saved) {
            $generatedFiles[] = $originalPath;
        }

        return $generatedFiles;
    }

    /**
     * Generate AVIF format using ImageMagick.
     */
    protected function generateAvif(\GdImage $image, string $directory, string $baseFilename, int $width): ?string
    {
        try {
            // Save image to temporary file first
            $tempPath = sys_get_temp_dir().'/'.uniqid('poster_', true).'.png';
            imagepng($image, $tempPath);

            // Use ImageMagick to convert to AVIF
            $imagick = new \Imagick($tempPath);
            $imagick->setImageFormat('avif');
            $imagick->setImageCompressionQuality(85);

            $avifPath = "{$directory}/{$baseFilename}-{$width}.avif";
            $fullPath = Storage::disk('public')->path($avifPath);

            $imagick->writeImage($fullPath);
            $imagick->clear();
            $imagick->destroy();

            // Clean up temp file
            @unlink($tempPath);

            return $avifPath;
        } catch (\Exception $e) {
            // If AVIF generation fails, return null (not critical)
            @unlink($tempPath ?? '');

            return null;
        }
    }
}
