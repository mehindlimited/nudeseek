<?php

namespace App\Services;

class StorageConfigService
{
    /**
     * Get all storage paths from environment variables
     */
    public static function getStoragePaths(): array
    {
        return [
            'videos' => env('STORAGE_VIDEOS', 'videos'),
            'thumbnails' => env('STORAGE_THUMBNAILS', 'thumbnails'),
            'origin' => env('STORAGE_ORIGIN', 'origin'),
            'temp' => env('STORAGE_TEMP', 'temp'),
        ];
    }

    /**
     * Get the final video path (after encoding)
     */
    public static function getVideoPath(string $videoCode): string
    {
        $firstChar = strtolower($videoCode[0]);
        $storagePath = self::getStoragePaths()['videos'];

        return "{$storagePath}/{$firstChar}/{$videoCode}.mp4";
    }

    /**
     * Get a specific thumbnail path
     */
    public static function getThumbnailPath(string $videoCode, int $thumbnailIndex): string
    {
        $firstChar = strtolower($videoCode[0]);
        $storagePath = self::getStoragePaths()['thumbnails'];

        return "{$storagePath}/{$firstChar}/{$videoCode}_thumb_{$thumbnailIndex}.jpg";
    }

    /**
     * Get all thumbnail paths (default 5 thumbnails)
     */
    public static function getThumbnailPaths(string $videoCode, int $count = 5): array
    {
        $paths = [];
        for ($i = 1; $i <= $count; $i++) {
            $paths[] = self::getThumbnailPath($videoCode, $i);
        }

        return $paths;
    }

    /**
     * Get the origin/backup video path
     */
    public static function getOriginPath(string $videoCode): string
    {
        $firstChar = strtolower($videoCode[0]);
        $storagePath = self::getStoragePaths()['origin'];

        return "{$storagePath}/{$firstChar}/{$videoCode}.mp4";
    }

    /**
     * Get temporary video path (before encoding)
     */
    public static function getTempVideoPath(string $videoCode): string
    {
        $storagePath = self::getStoragePaths()['temp'];

        return "{$storagePath}/{$videoCode}.mp4";
    }

    /**
     * Get temporary thumbnail path (uploaded thumbnail)
     */
    public static function getTempThumbnailPath(string $videoCode): string
    {
        $storagePath = self::getStoragePaths()['temp'];

        return "{$storagePath}/{$videoCode}_thumb.jpg";
    }

    /**
     * Get the first character directory for organization
     */
    public static function getFirstCharDirectory(string $videoCode): string
    {
        return strtolower($videoCode[0]);
    }

    /**
     * Get complete storage configuration for a video encoding job
     * This is used by the Python encoder
     */
    public static function getStorageConfigForJob(string $videoCode): array
    {
        $storagePaths = self::getStoragePaths();
        $firstChar = self::getFirstCharDirectory($videoCode);

        return array_merge($storagePaths, [
            'first_char' => $firstChar,
            'video_path' => self::getVideoPath($videoCode),
            'origin_path' => self::getOriginPath($videoCode),
            'thumbnail_paths' => self::getThumbnailPaths($videoCode),
            'temp_video_path' => self::getTempVideoPath($videoCode),
            'temp_thumbnail_path' => self::getTempThumbnailPath($videoCode),
        ]);
    }

    /**
     * Check if a video code has a valid format for directory organization
     */
    public static function isValidVideoCode(string $videoCode): bool
    {
        // Must be at least 1 character and start with alphanumeric
        return !empty($videoCode) && ctype_alnum($videoCode[0]);
    }

    /**
     * Get all possible first character directories that might exist
     * Useful for cleanup or analytics
     */
    public static function getAllPossibleDirectories(): array
    {
        $directories = [];

        // a-z
        for ($i = ord('a'); $i <= ord('z'); $i++) {
            $directories[] = chr($i);
        }

        // 0-9
        for ($i = 0; $i <= 9; $i++) {
            $directories[] = (string)$i;
        }

        return $directories;
    }
}
