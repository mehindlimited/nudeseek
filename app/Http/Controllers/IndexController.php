<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Video;

class IndexController extends Controller
{
    public function index(Request $request, ?int $page = 1)
    {
        $map = ['gay' => 1, 'straight' => 2];

        $targetSlug = strtolower($request->query('target', ''));  // 'gay' | 'straight' | ''
        $targetId   = $map[$targetSlug] ?? null;

        $page = max(1, (int) $page); // ensure >= 1

        $videos = Video::query()
            ->where('status', 'active')
            ->when($targetId, fn($q) => $q->where('target_id', $targetId))
            ->latest()
            ->paginate(
                perPage: 60,
                columns: ['*'],
                pageName: 'page',
                page: $page, // <-- key bit for path-based page
            )
            ->withQueryString(); // preserve ?target=...

        return view('index', [
            'videos' => $videos,
            'target' => $targetSlug ?: null,
        ]);
    }
}
