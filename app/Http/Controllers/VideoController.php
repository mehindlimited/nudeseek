<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    public function show($id, $slug)
    {
        // Special case: /video/0/test
        if ((int) $id === 0 && $slug === 'test') {
            return view('video.show', [
                'video' => null, // you can pass dummy data if needed
            ]);
        }

        // Normal case: look up real video
        $video = Video::findOrFail($id);

        // Redirect if slug doesn’t match canonical slug
        if ($video->slug !== $slug) {
            return redirect()->route('video.show', [
                'id'   => $video->id,
                'slug' => $video->slug,
            ], 301);
        }

        $video->increment('views');

        return view('video.show', compact('video'));
    }
}
