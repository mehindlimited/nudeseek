<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\Tag;
use App\Models\Category;
use App\Services\StorageConfigService;
use App\Services\VideoEncodingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VideoPublisherController extends Controller
{
    /**
     * Store a new video (for the content publisher robot)
     */
    public function store(Request $request)
    {
        try {
            // Log the incoming request to debug
            \Log::info('API Request received:', [
                'code_from_request' => $request->input('code'),
                'title' => $request->input('title'),
                'category_id' => $request->input('category_id'),
                'tags' => $request->input('tags'),
                'all_request_data' => $request->all()
            ]);

            // Validation rules - code is REQUIRED from Python
            $validated = $request->validate([
                'code' => [
                    'required', // Code MUST be provided by Python
                    'string',
                    'max:255',
                    'unique:videos,code'
                ],
                'title' => 'required|string|max:255',
                'slug' => [
                    'nullable',
                    'string',
                    'max:255',
                    Rule::unique('videos', 'slug')
                ],
                'description' => 'nullable|string|max:65535',
                'published_at' => 'required|date',
                'access_type' => 'required|in:public,private,unlisted',
                'user_id' => [
                    'required',
                    'integer',
                    Rule::exists('users', 'id')
                ],
                'target_id' => [
                    'required',
                    'integer',
                    Rule::exists('targets', 'id')
                ],
                'category_id' => [
                    'nullable',
                    'integer',
                    function ($attribute, $value, $fail) use ($request) {
                        if ($value !== null) {
                            if (!Category::where('id', $value)->exists()) {
                                $fail('The selected category does not exist.');
                                return;
                            }

                            if ($request->target_id) {
                                if (!Category::where('id', $value)->where('target_id', $request->target_id)->exists()) {
                                    $fail('The selected category does not belong to the specified target.');
                                }
                            }
                        }
                    }
                ],
                'tags' => 'array',
                'tags.*' => 'string|min:2|max:50',
            ]);

            // Generate slug if not provided
            if (empty($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['title']);
            }

            // Set default status
            $validated['status'] = 'draft';

            // Log what we're about to save
            \Log::info('About to create video with data:', [
                'code' => $validated['code'], // This should be the Python code
                'title' => $validated['title'],
                'target_id' => $validated['target_id'],
                'category_id' => $validated['category_id'] ?? null,
                'user_id' => $validated['user_id'],
                'tags_count' => isset($validated['tags']) ? count($validated['tags']) : 0
            ]);

            // Create the video record - DO NOT generate new code
            $video = Video::create([
                'title' => $validated['title'],
                'slug' => $validated['slug'],
                'code' => $validated['code'], // Use EXACT code from Python
                'description' => $validated['description'] ?? null,
                'published_at' => $validated['published_at'],
                'status' => $validated['status'],
                'access_type' => $validated['access_type'],
                'user_id' => $validated['user_id'],
                'target_id' => $validated['target_id'],
                'category_id' => $validated['category_id'] ?? null,
            ]);

            // CRITICAL: Verify code wasn't changed by model events
            \Log::info('Video created - checking code:', [
                'video_id' => $video->id,
                'expected_code' => $validated['code'],
                'actual_code' => $video->code,
                'code_matches' => $video->code === $validated['code']
            ]);

            // If code was changed by model events, force it back
            if ($video->code !== $validated['code']) {
                \Log::error('CODE WAS OVERRIDDEN BY MODEL! Forcing correction...', [
                    'expected' => $validated['code'],
                    'got' => $video->code
                ]);

                // Force update without triggering events
                \DB::table('videos')->where('id', $video->id)->update(['code' => $validated['code']]);
                $video->refresh();

                \Log::info('Code forcefully corrected:', [
                    'final_code' => $video->code
                ]);
            }

            // Handle tags AFTER video creation
            if (isset($validated['tags']) && !empty($validated['tags'])) {
                \Log::info('Processing tags for video:', [
                    'video_id' => $video->id,
                    'video_code' => $video->code,
                    'tags' => $validated['tags']
                ]);

                $tagIds = [];
                foreach ($validated['tags'] as $tagName) {
                    $tagName = trim($tagName);
                    if (empty($tagName)) {
                        continue;
                    }

                    try {
                        $tag = Tag::firstOrCreate(
                            ['name' => $tagName],
                            [
                                'name' => $tagName,
                                'slug' => Str::slug($tagName)
                            ]
                        );

                        $tagIds[] = $tag->id;

                        \Log::info('Tag processed:', [
                            'tag_name' => $tagName,
                            'tag_id' => $tag->id
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Error creating tag:', [
                            'tag_name' => $tagName,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                // Attach tags to video
                if (!empty($tagIds)) {
                    try {
                        $video->tags()->sync($tagIds);

                        \Log::info('Tags synced to video:', [
                            'video_id' => $video->id,
                            'video_code' => $video->code,
                            'tag_ids' => $tagIds,
                            'tag_count' => count($tagIds)
                        ]);

                        // Verify tags were attached
                        $attachedCount = $video->tags()->count();
                        \Log::info('Tags attachment verified:', [
                            'video_id' => $video->id,
                            'attached_count' => $attachedCount
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Error syncing tags to video:', [
                            'video_id' => $video->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            // Load relationships and verify final state
            $video->load(['user', 'target', 'category', 'tags']);

            \Log::info('FINAL VIDEO STATE:', [
                'video_id' => $video->id,
                'video_code' => $video->code,
                'main_dir' => $video->main_dir,
                'title' => $video->title,
                'category_id' => $video->category_id,
                'category_name' => $video->category?->name,
                'target_id' => $video->target_id,
                'target_name' => $video->target?->name,
                'tags_count' => $video->tags->count(),
                'tag_names' => $video->tags->pluck('name')->toArray(),
                'user_id' => $video->user_id
            ]);

            return response()->json([
                'success' => true,
                'data' => $video,
                'message' => 'Video created successfully'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error creating video:', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error creating video via robot API:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create video: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trigger the afterCreate functionality using video code
     */
    public function triggerAfterCreate(Request $request, $videoCode)
    {
        try {
            \Log::info('AfterCreate called:', [
                'video_code' => $videoCode,
                'request_data' => $request->all()
            ]);

            // Find video by code
            $video = Video::where('code', $videoCode)->first();

            if (!$video) {
                \Log::error('Video not found for afterCreate:', [
                    'searched_code' => $videoCode
                ]);

                // Debug: Show recent videos
                $recentVideos = Video::latest()->limit(5)->get(['id', 'code', 'title']);
                \Log::info('Recent videos in database:', [
                    'videos' => $recentVideos->toArray()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Video not found',
                    'searched_code' => $videoCode
                ], 404);
            }

            \Log::info('Video found, executing afterCreate:', [
                'video_id' => $video->id,
                'video_code' => $video->code,
                'title' => $video->title
            ]);

            // Execute the afterCreate logic
            $this->executeAfterCreateLogic($video);

            return response()->json([
                'success' => true,
                'message' => 'AfterCreate triggered successfully',
                'video_id' => $video->id,
                'video_code' => $video->code
            ], 200);
        } catch (\Exception $e) {
            \Log::error('API afterCreate failed:', [
                'video_code' => $videoCode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'AfterCreate failed: ' . $e->getMessage(),
                'video_code' => $videoCode,
            ], 500);
        }
    }

    /**
     * Execute the afterCreate logic (from your Filament form)
     */
    private function executeAfterCreateLogic(Video $video): void
    {
        $tempVideoPath = StorageConfigService::getTempVideoPath($video->code);
        $expectedThumbnailPath = StorageConfigService::getTempThumbnailPath($video->code);

        \Log::info('Video creation - checking thumbnail:', [
            'video_code' => $video->code,
            'expected_thumbnail_path' => $expectedThumbnailPath,
            'temp_video_path' => $tempVideoPath
        ]);

        $videoEncodingService = new VideoEncodingService();

        try {
            $encodingQueue = $videoEncodingService->queueForEncoding(
                videoCode: $video->code,
                videoFilePath: $tempVideoPath,
                thumbnailFilePath: null,
                encodingOptions: [
                    'resolution' => $video->quality ?? '1080p',
                    'bitrate' => '2000k',
                    'codec' => 'h264',
                ]
            );

            $video->update(['encoding_queue_id' => $encodingQueue->id]);

            $hasThumbnail = !empty($encodingQueue->thumbnail_paths);
            $thumbnailMessage = $hasThumbnail ? ' (with thumbnail)' : ' (no thumbnail)';

            \Log::info('Video encoding queued successfully:', [
                'video_code' => $video->code,
                'encoding_queue_id' => $encodingQueue->id,
                'has_thumbnail' => $hasThumbnail
            ]);
        } catch (\Exception $e) {
            \Log::error('Encoding queue failed:', [
                'video_code' => $video->code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
