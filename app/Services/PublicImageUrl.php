<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class PublicImageUrl
{
    public static function resolve(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (preg_match('~^https?://~i', $path)) {
            return $path;
        }

        $path = str_replace('\\', '/', $path);
        $encodedPath = implode('/', array_map(fn ($segment) => rawurlencode(rawurldecode($segment)), explode('/', ltrim($path, '/'))));

        if (str_starts_with($encodedPath, 'storage/')) {
            return url('/'.$encodedPath);
        }

        return Storage::disk('images')->url($encodedPath);
    }
}
