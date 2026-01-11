<?php

namespace App\Http\Controllers\Document;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyDocumentImageController
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'max:1024'], // 5MB
        ]);

        $path = $validated['image']->store('company-documents/images', 'public');

        $publicUrl = Storage::disk('public')->url($path);
        $relativeUrl = parse_url($publicUrl, PHP_URL_PATH) ?: $publicUrl;

        if (is_string($relativeUrl) && $relativeUrl !== '' && $relativeUrl[0] !== '/') {
            $relativeUrl = '/' . $relativeUrl;
        }

        // Generate an absolute URL using Laravel's URL generator (respects base paths).
        $absoluteUrl = url($relativeUrl);

        $debugEnabled =
            $request->boolean('debug') ||
            $request->headers->get('X-Quill-Debug') === '1';

        return response()->json(array_filter([
            'url' => $absoluteUrl,
            // Keep debug output minimal (no server filesystem paths).
            'debug' => $debugEnabled ? [
                'publicUrl' => $publicUrl,
                'relativeUrl' => $relativeUrl,
            ] : null,
        ]));
    }
}
