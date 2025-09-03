<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Video;

class IndexController extends Controller
{
    public function index(Request $request)
    {
        // Map query values to target_ids
        $map = ['gay' => 1, 'straight' => 2];

        $targetSlug = strtolower($request->query('target', ''));  // 'gay' | 'straight' | ''
        $targetId   = $map[$targetSlug] ?? null;

        $videos = Video::query()
            ->where('status', 'active') // ✅ only active videos
            ->when($targetId, fn($q) => $q->where('target_id', $targetId))
            ->latest()
            ->paginate(60)
            ->withQueryString();

        return view('index', [
            'videos' => $videos,
            'target' => $targetSlug ?: null,
        ]);
    }
}
