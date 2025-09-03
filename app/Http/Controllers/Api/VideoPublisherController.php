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
                'main_dir' => 'nullable|string|max:1', // Add validation for main_dir
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

            // Generate main_dir from first character of code
            $validated['main_dir'] = substr($validated['code'], 0, 1);

            \Log::info('Generated main_dir before creation:', [
                'code' => $validated['code'],
                'main_dir' => $validated['main_dir'],
                'main_dir_length' => strlen($validated['main_dir']),
                'validated_array_has_main_dir' => isset($validated['main_dir'])
            ]);

            // Debug: Check what we're actually passing to create()
            $createData = [
                'title' => $validated['title'],
                'slug' => $validated['slug'],
                'code' => $validated['code'],
                'main_dir' => $validated['main_dir'], // Explicitly set
                'description' => $validated['description'] ?? null,
                'published_at' => $validated['published_at'],
                'status' => $validated['status'],
                'access_type' => $validated['access_type'], // Use access_type since you fixed it
                'user_id' => $validated['user_id'],
                'target_id' => $validated['target_id'],
                'category_id' => $validated['category_id'] ?? null,
            ];

            \Log::info('Data being passed to Video::create():', [
                'create_data' => $createData,
                'main_dir_in_create_data' => $createData['main_dir'] ?? 'NOT SET'
            ]);

            // Create the video record - DO NOT generate new code
            $video = Video::create($createData);

            // CRITICAL: Verify code and main_dir weren't changed by model events
            \Log::info('Video created - checking all fields:', [
                'video_id' => $video->id,
                'expected_code' => $validated['code'],
                'actual_code' => $video->code,
                'expected_main_dir' => $validated['main_dir'],
                'actual_main_dir' => $video->main_dir,
                'main_dir_is_null' => is_null($video->main_dir),
                'main_dir_is_empty_string' => $video->main_dir === '',
                'code_matches' => $video->code === $validated['code'],
                'main_dir_matches' => $video->main_dir === $validated['main_dir']
            ]);

            // Check database directly
            $dbVideo = \DB::table('videos')->where('id', $video->id)->first();
            \Log::info('Direct database check:', [
                'db_code' => $dbVideo->code,
                'db_main_dir' => $dbVideo->main_dir,
                'db_main_dir_is_null' => is_null($dbVideo->main_dir),
                'db_access_type' => $dbVideo->access_type ?? 'FIELD_NOT_EXISTS'
            ]);

            // If code or main_dir was changed by model events, force it back
            if ($video->code !== $validated['code'] || $video->main_dir !== $validated['main_dir']) {
                \Log::error('CODE OR MAIN_DIR WAS OVERRIDDEN BY MODEL! Forcing correction...', [
                    'expected_code' => $validated['code'],
                    'got_code' => $video->code,
                    'expected_main_dir' => $validated['main_dir'],
                    'got_main_dir' => $video->main_dir
                ]);

                // Force update without triggering events
                \DB::table('videos')->where('id', $video->id)->update([
                    'code' => $validated['code'],
                    'main_dir' => $validated['main_dir']
                ]);
                $video->refresh();

                \Log::info('Code and main_dir forcefully corrected:', [
                    'final_code' => $video->code,
                    'final_main_dir' => $video->main_dir
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

            \Log::info('FINAL VIDEO STATE WITH DEBUG:', [
                'video_id' => $video->id,
                'video_code' => $video->code,
                'main_dir' => $video->main_dir,
                'main_dir_debug' => [
                    'is_null' => is_null($video->main_dir),
                    'is_empty_string' => $video->main_dir === '',
                    'length' => $video->main_dir ? strlen($video->main_dir) : 0,
                    'expected' => substr($video->code, 0, 1)
                ],
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
