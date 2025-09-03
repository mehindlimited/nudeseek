<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\Tag;
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
            // Validation rules extracted from your Filament form
            $validated = $request->validate([
                'code' => [
                    'required',
                    'string',
                    'size:16',
                    'regex:/^[a-z0-9]+$/',
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
                    Rule::exists('categories', 'id')->where(function ($query) use ($request) {
                        if ($request->target_id) {
                            $query->where('target_id', $request->target_id);
                        }
                    })
                ],
                'tags' => 'array',
                'tags.*' => 'string|min:2|max:50',
            ]);

            // Force lowercase on code for consistency
            $validated['code'] = Str::lower($validated['code']);

            // Generate slug if not provided
            if (empty($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['title']);
            }

            // Set default status
            $validated['status'] = 'draft';

            // Clean up tags
            if (isset($validated['tags'])) {
                $validated['tags'] = array_map('trim', $validated['tags']);
                $validated['tags'] = array_filter($validated['tags'], function ($tag) {
                    return !empty($tag);
                });
            }

            // Create the video record
            $video = Video::create([
                'title' => $validated['title'],
                'slug' => $validated['slug'],
                'code' => $validated['code'],
                'description' => $validated['description'] ?? null,
                'published_at' => $validated['published_at'],
                'status' => $validated['status'],
                'access_type' => $validated['access_type'],
                'user_id' => $validated['user_id'],
                'target_id' => $validated['target_id'],
                'category_id' => $validated['category_id'] ?? null,
            ]);

            // Handle tags
            if (isset($validated['tags']) && !empty($validated['tags'])) {
                $tagIds = collect($validated['tags'])->map(function ($tagName) {
                    $tagName = trim($tagName);
                    if (empty($tagName)) {
                        return null;
                    }
                    $tag = Tag::firstOrCreate(
                        ['name' => $tagName],
                        [
                            'name' => $tagName,
                            'slug' => (new Tag)->generateUniqueSlug($tagName)
                        ]
                    );
                    return $tag->id;
                })->filter();

                $video->tags()->sync($tagIds);
            }

            // Load relationships for response
            $video->load(['user', 'target', 'category', 'tags']);

            \Log::info('Video created via robot API:', [
                'video_id' => $video->id,
                'video_code' => $video->code,
                'title' => $video->title
            ]);

            return response()->json([
                'success' => true,
                'data' => $video,
                'message' => 'Video created successfully'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error creating video via robot API:', [
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
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create video: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing video (for the robot)
     */
    public function update(Request $request, $videoCode)
    {
        try {
            // Find video by code
            $video = Video::where('code', $videoCode)->first();

            if (!$video) {
                return response()->json([
                    'success' => false,
                    'message' => 'Video not found'
                ], 404);
            }

            // Similar validation but with ignore rule for slug uniqueness
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'slug' => [
                    'nullable',
                    'string',
                    'max:255',
                    Rule::unique('videos', 'slug')->ignore($video->id)
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
                    Rule::exists('categories', 'id')->where(function ($query) use ($request) {
                        if ($request->target_id) {
                            $query->where('target_id', $request->target_id);
                        }
                    })
                ],
                'tags' => 'array',
                'tags.*' => 'string|min:2|max:50',
            ]);

            // Generate slug if not provided
            if (empty($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['title']);
            }

            // Clean up tags
            if (isset($validated['tags'])) {
                $validated['tags'] = array_map('trim', $validated['tags']);
                $validated['tags'] = array_filter($validated['tags'], function ($tag) {
                    return !empty($tag);
                });
            }

            // Update the video record
            $video->update([
                'title' => $validated['title'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? null,
                'published_at' => $validated['published_at'],
                'access_type' => $validated['access_type'],
                'user_id' => $validated['user_id'],
                'target_id' => $validated['target_id'],
                'category_id' => $validated['category_id'] ?? null,
            ]);

            // Handle tags
            if (isset($validated['tags'])) {
                if (empty($validated['tags'])) {
                    $video->tags()->detach();
                } else {
                    $tagIds = collect($validated['tags'])->map(function ($tagName) {
                        $tagName = trim($tagName);
                        if (empty($tagName)) {
                            return null;
                        }
                        $tag = Tag::firstOrCreate(
                            ['name' => $tagName],
                            [
                                'name' => $tagName,
                                'slug' => (new Tag)->generateUniqueSlug($tagName)
                            ]
                        );
                        return $tag->id;
                    })->filter();

                    $video->tags()->sync($tagIds);
                }
            }

            // Load relationships for response
            $video->load(['user', 'target', 'category', 'tags']);

            return response()->json([
                'success' => true,
                'data' => $video,
                'message' => 'Video updated successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating video via robot API:', [
                'video_code' => $videoCode,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update video: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trigger the afterCreate functionality using video code
     */
    public function triggerAfterCreate(Request $request, $videoCode)
    {
        try {
            \Log::info('AfterCreate called with video code:', ['video_code' => $videoCode]);

            // Find video by code
            $video = Video::where('code', $videoCode)->first();

            if (!$video) {
                \Log::error('Video not found for afterCreate:', [
                    'video_code' => $videoCode,
                    'searched_in' => 'videos.code column'
                ]);

                // Debug: Check if video exists with different criteria
                $videoById = Video::latest()->first();
                \Log::info('Latest video in database:', [
                    'id' => $videoById->id ?? 'none',
                    'code' => $videoById->code ?? 'none',
                    'title' => $videoById->title ?? 'none'
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Video not found',
                    'video_code' => $videoCode,
                    'debug' => 'Check logs for video lookup details'
                ], 404);
            }

            \Log::info('API afterCreate triggered for video:', [
                'video_id' => $video->id,
                'video_code' => $video->code,
                'request_data' => $request->all()
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
            'video_model_data' => $video->toArray(),
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

            \Log::info("Video uploaded and queued for encoding! Video code: {$video->code}{$thumbnailMessage}");
        } catch (\Exception $e) {
            \Log::error('Encoding queue failed: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
}
