@php
$current = $videos->currentPage();
$last = $videos->lastPage();
$qs = request()->getQueryString(); // Get query string (e.g., "sort=desc")

// Function to generate URLs with trailing slash and query string
$urlFor = function (int $n) use ($qs) {
// Base URL: use index route for page 1, index.page for others
$baseUrl = $n === 1 ? route('index') : route('index.page', ['page' => $n]);
// Append query string only if it exists
return $qs ? $baseUrl . '?' . $qs : $baseUrl;
};

$prevUrl = $current > 1 ? $urlFor($current - 1) : null;
$nextUrl = $current < $last ? $urlFor($current + 1) : null;

    $start=max(1, $current - 3);
    $end=min($last, $current + 3);
    @endphp

    <!-- Canonical Tag for SEO -->
    <link rel="canonical" href="{{ $urlFor($current) }}">

    <!-- Pagination Block -->
    <div class="mt-8 flex justify-center items-center gap-2">
        <!-- Previous -->
        <a
            @if($prevUrl) href="{{ $prevUrl }}" @endif
            class="px-4 py-2 text-sm font-medium text-[#222] bg-[#ffffff] border-2 border-[#dfdfdf] rounded-full hover:border-[#dc251f] hover:text-[#dc251f] transition-colors {{ $prevUrl ? '' : 'opacity-50 pointer-events-none' }}"
            aria-label="Previous page">
            <svg class="w-5 h-5 inline-block -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>

        <!-- Page Numbers -->
        <div class="flex gap-1">
            @if($start > 1)
            <a href="{{ $urlFor(1) }}" class="px-4 py-2 text-sm font-medium text-[#222] bg-[#ffffff] border-2 border-[#dfdfdf] rounded-full hover:border-[#dc251f] hover:text-[#dc251f] transition-colors">1</a>
            @if($start > 2)
            <span class="px-2 py-2 text-sm font-medium text-[#666]">…</span>
            @endif
            @endif

            @for($i = $start; $i <= $end; $i++)
                <a href="{{ $urlFor($i) }}"
                class="px-4 py-2 text-sm font-medium rounded-full border-2 transition-colors
                      {{ $i === $current
                          ? 'bg-[#dc251f] border-[#dc251f] text-white'
                          : 'text-[#222] bg-[#ffffff] border-[#dfdfdf] hover:border-[#dc251f] hover:text-[#dc251f]' }}">
                {{ $i }}
                </a>
                @endfor

                @if($end < $last)
                    @if($end < $last - 1)
                    <span class="px-2 py-2 text-sm font-medium text-[#666]">…</span>
                    @endif
                    <a href="{{ $urlFor($last) }}" class="px-4 py-2 text-sm font-medium text-[#222] bg-[#ffffff] border-2 border-[#dfdfdf] rounded-full hover:border-[#dc251f] hover:text-[#dc251f] transition-colors">{{ $last }}</a>
                    @endif
        </div>

        <!-- Next -->
        <a
            @if($nextUrl) href="{{ $nextUrl }}" @endif
            class="px-4 py-2 text-sm font-medium text-[#222] bg-[#ffffff] border-2 border-[#dfdfdf] rounded-full hover:border-[#dc251f] hover:text-[#dc251f] transition-colors {{ $nextUrl ? '' : 'opacity-50 pointer-events-none' }}"
            aria-label="Next page">
            <svg class="w-5 h-5 inline-block -mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </a>
    </div>