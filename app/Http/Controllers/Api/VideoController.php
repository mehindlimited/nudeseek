<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    public function activate(string $videoCode): JsonResponse
    {
        $video = Video::where('code', $videoCode)->first();

        if (!$video) {
            return response()->json(['error' => 'Video not found'], 404);
        }

        if ($video->status === 'active') {
            return response()->json(['message' => 'Video is already active']);
        }

        $video->status = 'active';
        $video->save();

        return response()->json([
            'message' => 'Video activated successfully',
            'video_code' => $video->code,
            'status' => $video->status,
        ]);
    }

    public function updateMetadata(Request $request, string $videoCode): JsonResponse
    {
        $request->validate([
            'duration' => 'nullable|integer|min:0',
            'width' => 'nullable|integer|min:0',
            'height' => 'nullable|integer|min:0',
        ]);

        $video = Video::where('code', $videoCode)->first();

        if (!$video) {
            return response()->json(['error' => 'Video not found'], 404);
        }

        $video->duration = $request->duration ?? $video->duration;
        $video->width = $request->width ?? $video->width;
        $video->height = $request->height ?? $video->height;
        $video->save();

        return response()->json([
            'message' => 'Video metadata updated successfully',
            'video_code' => $video->code,
            'duration' => $video->duration,
            'width' => $video->width,
            'height' => $video->height,
        ]);
    }
}
