<?php
declare(strict_types=1);

define('BASE_URL', getenv('VERCEL') === '1' ? '' : '/renthome');
const MAX_UPLOAD_SIZE = 5 * 1024 * 1024; // 5 MB
const MAX_PROPERTY_IMAGES = 8;

function base_url(string $path = ''): string
{
    $base = rtrim(BASE_URL, '/');

    if ($path !== '') {
        return $base . '/' . ltrim($path, '/');
    }

    return $base !== '' ? $base . '/' : '/';
}

/**
 * Return a valid URL for a property image. Normalizes older stored paths and
 * falls back when a referenced file is missing instead of rendering a broken image.
 */
function property_image_url(?string $storedPath): string
{
    $path = trim((string)$storedPath);
    if ($path === '') {
        return base_url('assets/img/placeholder.svg');
    }
    $path = str_replace('\\', '/', $path);
    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }
    $path = ltrim($path, '/');
    if (str_starts_with($path, trim(BASE_URL, '/') . '/')) {
        $path = substr($path, strlen(trim(BASE_URL, '/')) + 1);
    }
    $path = preg_replace('~^(?:\./)+~', '', $path) ?? $path;
    // Refuse traversal paths and use only files inside the project root.
    if (str_contains($path, '..')) {
        return base_url('assets/img/placeholder.svg');
    }
    $absolute = dirname(__DIR__) . '/' . $path;
    if (!is_file($absolute)) {
        return base_url('assets/img/placeholder.svg');
    }
    return base_url($path);
}
