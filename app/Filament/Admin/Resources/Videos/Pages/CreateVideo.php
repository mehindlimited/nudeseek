<?php

namespace App\Filament\Admin\Resources\Videos\Pages;

use App\Filament\Admin\Resources\Videos\VideoResource;
use Filament\Resources\Pages\CreateRecord;
use App\Services\VideoEncodingService;
use Filament\Notifications\Notification;
use App\Services\StorageConfigService;

class CreateVideo extends CreateRecord
{
    protected static string $resource = VideoResource::class;
    protected function afterCreate(): void
    {
        $video = $this->record;

        // Since Filament saves directly to S3 temp directory, construct the paths
        $tempVideoPath = StorageConfigService::getTempVideoPath($video->code);

        // Always check for the expected thumbnail path since files are uploaded correctly
        $expectedThumbnailPath = StorageConfigService::getTempThumbnailPath($video->code);

        \Log::info('Video creation - checking thumbnail:', [
            'video_code' => $video->code,
            'expected_thumbnail_path' => $expectedThumbnailPath,
            'video_model_data' => $video->toArray(),
        ]);

        // Queue for encoding (the service will check if thumbnail exists)
        $videoEncodingService = new VideoEncodingService();

        try {
            $encodingQueue = $videoEncodingService->queueForEncoding(
                videoCode: $video->code,
                videoFilePath: $tempVideoPath,
                thumbnailFilePath: null, // Let the service auto-detect the thumbnail
                encodingOptions: [
                    'resolution' => $video->quality ?? '1080p',
                    'bitrate' => '2000k',
                    'codec' => 'h264',
                ]
            );

            // Store the encoding queue ID
            $video->update(['encoding_queue_id' => $encodingQueue->id]);

            // Check final result
            $hasThumbnail = !empty($encodingQueue->thumbnail_paths);
            $thumbnailMessage = $hasThumbnail ? ' (with thumbnail)' : ' (no thumbnail)';

            Notification::make()
                ->title('Video uploaded and queued for encoding!')
                ->body("Video code: {$video->code}{$thumbnailMessage}")
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Log::error('Encoding queue failed: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            Notification::make()
                ->title('Video uploaded but encoding queue failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
