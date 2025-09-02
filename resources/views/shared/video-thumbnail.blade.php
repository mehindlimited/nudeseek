<a href="{{ route('video.show', ['id' => $video->id, 'slug' => Str::slug($video->title)]) }}"
    class="group cursor-pointer block">

    <div class="relative aspect-video bg-gray-900 rounded-lg overflow-hidden">
        <img src="https://thumbnails-nudeseek.b-cdn.net/{{ $video->main_dir }}/{{ $video->code }}_thumb_1.jpg"
            alt="{{ $video->title }}"
            class="w-full h-full object-cover">

        @if(!empty($video->duration))
        <span class="absolute bottom-2 right-2 bg-black/90 text-white text-xs px-1.5 py-0.5 rounded font-medium">
            {{ $video->duration }}
        </span>
        @endif
    </div>

    <div class="mt-2">
        <h3 class="font-medium text-[#222] text-sm line-clamp-2 leading-tight group-hover:text-[#dc251f] transition-colors">
            {{ $video->title }}
        </h3>

        <div class="text-xs text-[#666] mt-1">
            @if(!empty($video->creator?->name))
            <div>{{ $video->creator->name }}</div>
            @endif

            <div>
                {{ number_format($video->views) }} views •
                {{ $video->created_at->diffForHumans() }}
            </div>
        </div>
    </div>
</a>