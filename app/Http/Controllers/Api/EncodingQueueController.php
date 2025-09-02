<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EncodingQueue;
use App\Services\VideoEncodingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EncodingQueueController extends Controller
{
    public function __construct(
        private VideoEncodingService $videoEncodingService
    ) {}

    public function getNextPending(): JsonResponse
    {
        // First try to get retryable failed jobs
        $queue = EncodingQueue::retryable()
            ->orderBy('last_retry_at', 'asc')
            ->first();

        // If no retryable jobs, get regular pending jobs
        if (!$queue) {
            $queue = EncodingQueue::pending()
                ->orderBy('created_at')
                ->first();
        }

        // Also check for stuck processing jobs and reset them
        $this->resetStuckJobs();

        if (!$queue) {
            return response()->json(['message' => 'No pending jobs'], 404);
        }

        return response()->json([
            'video_code' => $queue->video_code,
            'input_file_url' => $this->videoEncodingService->getFileUrl($queue->input_file_path),
            'uploaded_thumbnail_url' => $queue->encoding_options['uploaded_thumbnail'] ?? null ?
                $this->videoEncodingService->getFileUrl($queue->encoding_options['uploaded_thumbnail']) : null,
            'encoding_options' => $queue->encoding_options,
            'retry_count' => $queue->retry_count,
            'max_retries' => $queue->max_retries,
        ]);
    }

    public function markAsProcessing(string $videoCode): JsonResponse
    {
        $queue = EncodingQueue::where('video_code', $videoCode)->first();

        if (!$queue) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        $queue->markAsProcessing();

        return response()->json(['message' => 'Job marked as processing']);
    }

    public function markAsCompleted(Request $request, string $videoCode): JsonResponse
    {
        $request->validate([
            'output_file_path' => 'required|string',
            'thumbnail_paths' => 'nullable|array',
            'thumbnail_paths.*' => 'string',
        ]);

        $queue = EncodingQueue::where('video_code', $videoCode)->first();

        if (!$queue) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        $queue->markAsCompleted(
            $request->output_file_path,
            $request->thumbnail_paths ?? []
        );

        return response()->json(['message' => 'Job marked as completed']);
    }

    public function markAsFailed(Request $request, string $videoCode): JsonResponse
    {
        $request->validate([
            'error_message' => 'required|string',
        ]);

        $queue = EncodingQueue::where('video_code', $videoCode)->first();

        if (!$queue) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        $queue->markAsFailed($request->error_message);

        return response()->json(['message' => 'Job marked as failed']);
    }

    public function getStatus(string $videoCode): JsonResponse
    {
        $queue = EncodingQueue::where('video_code', $videoCode)->first();

        if (!$queue) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        return response()->json([
            'video_code' => $queue->video_code,
            'status' => $queue->status,
            'input_file_path' => $queue->input_file_path,
            'output_file_path' => $queue->output_file_path,
            'thumbnail_paths' => $queue->thumbnail_paths,
            'encoding_options' => $queue->encoding_options,
            'error_message' => $queue->error_message,
            'retry_count' => $queue->retry_count,
            'max_retries' => $queue->max_retries,
            'last_retry_at' => $queue->last_retry_at,
            'started_at' => $queue->started_at,
            'completed_at' => $queue->completed_at,
        ]);
    }

    public function retryJob(string $videoCode): JsonResponse
    {
        $queue = EncodingQueue::where('video_code', $videoCode)->first();

        if (!$queue) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        if (!$queue->canRetry()) {
            return response()->json(['error' => 'Job cannot be retried (max retries reached)'], 400);
        }

        $queue->incrementRetry();

        return response()->json(['message' => 'Job queued for retry']);
    }

    public function resetStuckJobs(): JsonResponse
    {
        $stuckJobs = EncodingQueue::stuck(30)->get();

        foreach ($stuckJobs as $job) {
            if ($job->canRetry()) {
                $job->incrementRetry('Job was stuck in processing state');
            } else {
                $job->markAsFailed('Job was stuck in processing state and exceeded max retries');
            }
        }

        return response()->json([
            'message' => "Reset {$stuckJobs->count()} stuck jobs"
        ]);
    }
}
