<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Generate derivatives on upload/CLI, never while rendering a product list. */
class ImageVariants
{
    public function productSources(?string $path): array
    {
        $fallback = $this->validProductPath($path) ? '/storage/'.$path : null;
        $source = $this->productFile($path);
        $variants = $source ? $this->available($source, 'products', [384, 768]) : [];

        return [
            'src' => $variants ? end($variants)['url'] : $fallback,
            'srcset' => implode(', ', array_map(fn ($v) => $v['url'].' '.$v['width'].'w', $variants)),
        ];
    }

    public function prepareProduct(?string $path): bool
    {
        $source = $this->productFile($path);

        return $source ? $this->prepare($source, 'products', [384, 768], 82) : false;
    }

    public function deleteProduct(?string $path): void
    {
        $source = $this->productFile($path);
        $meta = $source ? $this->metadata($source, 'products', [384, 768]) : null;
        if ($meta) {
            foreach ($meta['widths'] as $width) {
                foreach (['webp', 'jpg'] as $extension) {
                    Storage::disk('public')->delete($meta['prefix'].'-'.$width.'.'.$extension);
                }
            }
        }
    }

    public function floorUrl(): ?string
    {
        $path = config('floor_plan.background.path');
        if (! is_string($path) || ! is_file(public_path($path))) {
            return null;
        }
        $source = public_path($path);
        $variants = $this->available($source, 'floor', [(int) config('floor_plan.background.width')]);

        return $variants ? asset(end($variants)['url']) : asset($path);
    }

    public function prepareFloor(): bool
    {
        $path = config('floor_plan.background.path');

        return is_string($path) && is_file(public_path($path))
            ? $this->prepare(public_path($path), 'floor', [(int) config('floor_plan.background.width')], 90) : false;
    }

    private function validProductPath(?string $path): bool
    {
        return is_string($path) && str_starts_with($path, 'products/')
            && ! preg_match('~[\\\\\x00]|(?:^|/)\.\.(?:/|$)~', $path)
            && (bool) preg_match('/\.(png|jpe?g|webp)$/i', $path);
    }

    private function productFile(?string $path): ?string
    {
        if (! $this->validProductPath($path)) {
            return null;
        }
        $root = realpath(Storage::disk('public')->path('products'));
        $source = realpath(Storage::disk('public')->path($path));

        return $root && $source && str_starts_with($source, $root.DIRECTORY_SEPARATOR) && is_file($source) ? $source : null;
    }

    private function metadata(string $source, string $group, array $widths): ?array
    {
        clearstatcache(true, $source);
        $size = @getimagesize($source);
        // Bound decoding memory even when a highly compressed upload is enormous.
        if (! $size || $size[0] < 1 || $size[1] < 1 || $size[0] * $size[1] > 12000000) {
            return null;
        }
        $orientation = 1;
        if ($size[2] === IMAGETYPE_JPEG) {
            // Preserve phone camera orientation; an older runtime can use the original.
            if (! function_exists('exif_read_data')) {
                return null;
            }
            $exif = @exif_read_data($source, 'IFD0', true);
            $orientation = (int) ($exif['IFD0']['Orientation'] ?? 1);
        }
        if (in_array($orientation, [5, 6, 7, 8], true)) {
            [$size[0], $size[1]] = [$size[1], $size[0]];
        }
        $key = hash('sha256', $source.'|'.filesize($source).'|'.filemtime($source));
        $widths = array_values(array_unique(array_map(fn ($width) => min($size[0], $width), $widths)));

        return ['width' => $size[0], 'height' => $size[1], 'widths' => $widths, 'orientation' => $orientation, 'prefix' => 'optimized-media/'.$group.'/'.$key];
    }

    private function available(string $source, string $group, array $widths): array
    {
        $meta = $this->metadata($source, $group, $widths);
        if (! $meta) {
            return [];
        }
        $variants = [];
        foreach ($meta['widths'] as $width) {
            foreach (['webp', 'jpg'] as $extension) {
                $path = $meta['prefix'].'-'.$width.'.'.$extension;
                if (Storage::disk('public')->exists($path)) {
                    $variants[] = ['url' => '/storage/'.$path, 'width' => $width];
                    break;
                }
            }
        }

        return $variants;
    }

    private function prepare(string $source, string $group, array $widths, int $quality): bool
    {
        $meta = $this->metadata($source, $group, $widths);
        if (! $meta || ! function_exists('imagecreatefromstring')) {
            return false;
        }
        $extension = function_exists('imagewebp') && (imagetypes() & IMG_WEBP) ? 'webp' : 'jpg';
        $disk = Storage::disk('public');
        $missing = array_filter($meta['widths'], fn ($width) => ! $disk->exists($meta['prefix'].'-'.$width.'.'.$extension));
        if (! $missing) {
            return true;
        }
        if (! $disk->makeDirectory('optimized-media/'.$group)) {
            throw new RuntimeException('Direktori gambar tampilan tidak dapat dibuat.');
        }
        $original = @imagecreatefromstring(file_get_contents($source));
        if (! $original) {
            return false;
        }
        if (in_array($meta['orientation'], [2, 5, 7], true)) {
            imageflip($original, IMG_FLIP_HORIZONTAL);
        } elseif ($meta['orientation'] === 4) {
            imageflip($original, IMG_FLIP_VERTICAL);
        }
        $angle = match ($meta['orientation']) {
            3 => 180, 5, 8 => 90, 6, 7 => -90, default => 0
        };
        if ($angle !== 0) {
            $original = imagerotate($original, $angle, 0);
        }
        try {
            foreach ($missing as $width) {
                $height = max(1, (int) round($meta['height'] * $width / $meta['width']));
                $resized = imagecreatetruecolor($width, $height);
                $temp = false;
                try {
                    if ($extension === 'webp') {
                        imagealphablending($resized, false);
                        imagesavealpha($resized, true);
                    } else {
                        imagefill($resized, 0, 0, imagecolorallocate($resized, 255, 255, 255));
                    }
                    imagecopyresampled($resized, $original, 0, 0, 0, 0, $width, $height, $meta['width'], $meta['height']);
                    $target = $disk->path($meta['prefix'].'-'.$width.'.'.$extension);
                    $temp = tempnam(dirname($target), '.image-');
                    if ($temp === false) {
                        throw new RuntimeException('Gambar sementara tidak dapat dibuat.');
                    }
                    $ok = $extension === 'webp' ? imagewebp($resized, $temp, $quality) : imagejpeg($resized, $temp, $quality);
                    if (! $ok || ! filesize($temp)) {
                        throw new RuntimeException('Gambar tampilan gagal ditulis.');
                    }
                    if (! is_file($target) && ! rename($temp, $target)) {
                        throw new RuntimeException('Gambar tampilan gagal disimpan.');
                    }
                } finally {
                    unset($resized);
                    if ($temp && is_file($temp)) {
                        unlink($temp);
                    }
                }
            }
        } finally {
            unset($original);
        }

        return true;
    }
}
