<?php

if (!function_exists('media_base')) {
    /**
     * Base d'URL publique des médias (sans slash final).
     */
    function media_base(): string
    {
        return rtrim(config('media.url') ?: '/uploads', '/');
    }
}

if (!function_exists('media_url')) {
    /**
     * URL publique d'un média à partir de son chemin relatif stocké en base
     * (ex : "photos/uuid.jpg" → ".../photos/uuid.jpg").
     */
    function media_url(?string $path): string
    {
        $path = ltrim((string) $path, '/');

        return $path === '' ? media_base() : media_base() . '/' . $path;
    }
}

if (!function_exists('media_disk')) {
    /**
     * Nom du disque Laravel où écrire les médias.
     */
    function media_disk(): string
    {
        return config('media.disk') ?: 'uploads_local';
    }
}
