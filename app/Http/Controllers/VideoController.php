<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    public function show(int $id, string $slug)
    {
        // Special case: /video/0/test
        if ($id === 0 && $slug === 'test') {
            return view('video.show', [
                'video'              => null,
                'randomVideosSide'   => collect(),
                'randomVideosBottom' => collect(),
            ]);
        }

        // Load the video (eager-load relations you actually use)
        $video = Video::query()
            ->with(['tags:id,name,slug']) // add/remove relations as needed
            ->findOrFail($id);

        // Canonical slug redirect
        if (! hash_equals($video->slug, $slug)) {
            return redirect()->route('video.show', [
                'id'   => $video->id,
                'slug' => $video->slug,
            ], 301);
        }

        // Count a view
        $video->increment('views');

        // Helper to fetch related videos by same target_id with backfill
        $fetchRelated = function (int $limit) use ($video) {
            // If your column is actually misspelled (e.g., `taget_id`), change `target_id` below.
            $primary = Video::query()
                ->where('target_id', $video->target_id)
                ->whereKeyNot($video->getKey())
                ->inRandomOrder()
                ->limit($limit)
                ->get();

            if ($primary->count() >= $limit) {
                return $primary;
            }

            // Backfill with randoms (excluding already picked + current)
            $needed = $limit - $primary->count();

            $backfill = Video::query()
                ->whereKeyNot($video->getKey())
                ->whereNotIn('id', $primary->pluck('id'))
                ->inRandomOrder()
                ->limit($needed)
                ->get();

            return $primary->merge($backfill);
        };

        $randomVideosSide   = $fetchRelated(3);
        $randomVideosBottom = $fetchRelated(5);

        return view('video.show', [
            'video'              => $video,
            'randomVideosSide'   => $randomVideosSide,
            'randomVideosBottom' => $randomVideosBottom,
        ]);
    }
}
