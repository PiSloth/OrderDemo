<?php

namespace App\Services\Operation;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class OperationNoteImageResizer
{
    public function store(UploadedFile $file, int $maxDimension = 600): string
    {
        if (!function_exists('imagecreatefromstring')) {
            throw new RuntimeException('GD image extension is not available.');
        }

        $realPath = $file->getRealPath();
        $binary = $realPath ? file_get_contents($realPath) : false;

        if ($binary === false) {
            throw new RuntimeException('Unable to read uploaded image.');
        }

        $source = imagecreatefromstring($binary);

        if (!$source) {
            throw new RuntimeException('Unable to process uploaded image.');
        }

        [$width, $height, $imageType] = getimagesizefromstring($binary);
        $scale = min(1, $maxDimension / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        if (in_array($imageType, [IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefill($canvas, 0, 0, $transparent);
        } else {
            $background = imagecolorallocate($canvas, 255, 255, 255);
            imagefill($canvas, 0, 0, $background);
        }

        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        $extension = $this->extensionForType($imageType);
        $path = 'notes/' . now()->format('Y/m') . '/' . Str::uuid() . '.' . $extension;

        ob_start();
        $saved = $this->saveImageToOutputBuffer($canvas, $imageType);
        $contents = ob_get_clean();

        imagedestroy($source);
        imagedestroy($canvas);

        if (!$saved || $contents === false) {
            throw new RuntimeException('Unable to encode resized image.');
        }

        Storage::disk('public')->put($path, $contents);

        return $path;
    }

    protected function extensionForType(int $imageType): string
    {
        return match ($imageType) {
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            default => 'jpg',
        };
    }

    protected function saveImageToOutputBuffer($canvas, int $imageType): bool
    {
        return match ($imageType) {
            IMAGETYPE_PNG => imagepng($canvas, null, 6),
            IMAGETYPE_WEBP => function_exists('imagewebp') ? imagewebp($canvas, null, 80) : imagejpeg($canvas, null, 85),
            default => imagejpeg($canvas, null, 85),
        };
    }
}
