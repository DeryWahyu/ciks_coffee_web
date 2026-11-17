<?php

namespace App\Http\Controllers;

use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicAssetController extends Controller
{
    public function __invoke(Request $request, string $path): BinaryFileResponse
    {
        abort_if(str_starts_with($path, 'payment_proofs/') || preg_match('~[\\\\\x00]|(?:^|/)\.\.(?:/|$)~', $path), 404);
        $root = realpath(Storage::disk('public')->path(''));
        $source = realpath(Storage::disk('public')->path($path));
        abort_unless($root && $source && str_starts_with($source, $root.DIRECTORY_SEPARATOR) && is_file($source), 404);
        $relative = str_replace('\\', '/', substr($source, strlen($root) + 1));
        abort_if(str_starts_with(strtolower($relative), 'payment_proofs/'), 404);
        $immutable = (bool) preg_match('~^optimized-media/(products|floor)/[a-f0-9]{64}-[0-9]+\.(webp|jpg)$~', $path);
        $response = response()->file($source, [
            'Cache-Control' => $immutable ? 'public, max-age=31536000, immutable' : 'public, max-age=86400',
            'Access-Control-Allow-Origin' => config('app.url'),
            'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setEtag(hash('sha256', filesize($source).'|'.filemtime($source)), true);
        $response->setLastModified(new DateTimeImmutable('@'.filemtime($source)));
        $response->isNotModified($request);

        return $response;
    }
}
