<?php

namespace App\Services;

use App\Models\EncodingQueue;
use App\Services\StorageConfigService;
use Illuminate\Support\Facades\Storage;

class VideoEncodingService
{
    private string $disk = 's3';

    /**
     * Create an encoding job for an already uploaded video
     * 
     * @param string $videoCode The unique video identifier
     * @param string $videoFilePath S3 path to the uploaded video file
     * @param string|null $thumbnailFilePath S3 path to uploaded thumbnail (optional)
     * @param array $encodingOptions Encoding configuration options
     * @return EncodingQueue
     */
    public function queueForEncoding(
        string $videoCode,
        string $videoFilePath,
        ?string $thumbnailFilePath = null,
        array $encodingOptions = []
    ): EncodingQueue {
        // Get storage configuration for this video code
        $storageConfig = StorageConfigService::getStorageConfigForJob($videoCode);

        // Handle thumbnail paths - always check if the expected thumbnail exists
        $thumbnailPaths = [];
        $uploadedThumbnailPath = null;

        // If no thumbnail path was provided, check if the expected one exists
        if (!$thumbnailFilePath) {
            $expectedThumbnailPath = StorageConfigService::getTempThumbnailPath($videoCode);
            if ($this->fileExists($expectedThumbnailPath)) {
                $thumbnailFilePath = $expectedThumbnailPath;
                \Log::info("Found expected thumbnail at: {$expectedThumbnailPath}");
            }
        }

        // Now check if we have a thumbnail path and if the file exists
        if ($thumbnailFilePath) {
            if ($this->fileExists($thumbnailFilePath)) {
                $thumbnailPaths = [$thumbnailFilePath];
                $uploadedThumbnailPath = $thumbnailFilePath;
                \Log::info("Thumbnail verified and added: {$thumbnailFilePath}");
            } else {
                \Log::warning("Thumbnail path provided but file doesn't exist: {$thumbnailFilePath}");
            }
        }

        // Log the final state for debugging
        \Log::info('VideoEncodingService - Final thumbnail state:', [
            'video_code' => $videoCode,
            'provided_thumbnail_path' => $thumbnailFilePath,
            'thumbnail_paths_array' => $thumbnailPaths,
            'uploaded_thumbnail_path' => $uploadedThumbnailPath,
            'thumbnail_paths_count' => count($thumbnailPaths),
        ]);

        // Create encoding queue entry
        $encodingQueue = EncodingQueue::create([
            'video_code' => $videoCode,
            'status' => EncodingQueue::STATUS_PENDING,
            'input_file_path' => $videoFilePath,
            'thumbnail_paths' => $thumbnailPaths, // This should now contain the thumbnail if it exists
            'encoding_options' => array_merge([
                'resolution' => '1080p',
                'bitrate' => '2000k',
                'format' => 'mp4',
                'codec' => 'h264',
                'generate_thumbnails' => true,
                'thumbnail_count' => 5,
                'uploaded_thumbnail' => $uploadedThumbnailPath,
                'storage_config' => $storageConfig
            ], $encodingOptions),
        ]);

        // Log what was actually saved to the database
        \Log::info('EncodingQueue created:', [
            'id' => $encodingQueue->id,
            'video_code' => $encodingQueue->video_code,
            'thumbnail_paths' => $encodingQueue->thumbnail_paths,
        ]);

        return $encodingQueue;
    }

    /**
     * Get the current encoding status for a video
     */
    public function getEncodingStatus(string $videoCode): ?EncodingQueue
    {
        return EncodingQueue::where('video_code', $videoCode)->first();
    }

    /**
     * Check if a video is currently being processed or queued
     */
    public function isVideoProcessing(string $videoCode): bool
    {
        return EncodingQueue::where('video_code', $videoCode)
            ->whereIn('status', [EncodingQueue::STATUS_PENDING, EncodingQueue::STATUS_PROCESSING])
            ->exists();
    }

    /**
     * Get the final encoded video URL if encoding is complete
     */
    public function getEncodedVideoUrl(string $videoCode): ?string
    {
        $queue = EncodingQueue::where('video_code', $videoCode)
            ->where('status', EncodingQueue::STATUS_COMPLETED)
            ->first();

        return $queue ? $this->getFileUrl($queue->output_file_path) : null;
    }

    /**
     * Get all thumbnail URLs for a completed encoding job
     */
    public function getThumbnailUrls(string $videoCode): array
    {
        $queue = EncodingQueue::where('video_code', $videoCode)
            ->where('status', EncodingQueue::STATUS_COMPLETED)
            ->first();

        if (!$queue || !$queue->thumbnail_paths) {
            return [];
        }

        return array_map([$this, 'getFileUrl'], $queue->thumbnail_paths);
    }

    /**
     * Get the public URL for a file stored in S3
     */
    public function getFileUrl(string $path): string
    {
        return Storage::disk($this->disk)->url($path);
    }

    /**
     * Check if a file exists in S3
     */
    public function fileExists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }

    /**
     * Retry a failed encoding job
     */
    public function retryEncoding(string $videoCode): bool
    {
        $queue = EncodingQueue::where('video_code', $videoCode)
            ->where('status', EncodingQueue::STATUS_FAILED)
            ->first();

        if (!$queue || !$queue->canRetry()) {
            return false;
        }

        $queue->incrementRetry();
        return true;
    }
}
