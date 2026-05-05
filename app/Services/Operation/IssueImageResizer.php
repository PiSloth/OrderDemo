<?php

namespace App\Services\Operation;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IssueImageResizer
{
    public function store(UploadedFile $file, int $maxDimension = 1200): string
    {
        $binary = file_get_contents($file->getRealPath());
        $source = imagecreatefromstring($binary ?: '');
        if (!$source) {
            throw new \RuntimeException('Invalid image upload.');
        }

        [$width, $height, $type] = getimagesizefromstring($binary);
        $scale = min(1, $maxDimension / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        $bg = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $bg);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        $path = 'issues/' . now()->format('Y/m') . '/' . Str::uuid() . '.jpg';
        ob_start();
        imagejpeg($canvas, null, 85);
        $encoded = ob_get_clean();

        imagedestroy($source);
        imagedestroy($canvas);
        Storage::disk('public')->put($path, $encoded);

        return $path;
    }
}
