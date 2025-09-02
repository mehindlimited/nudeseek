<a href="{{ route('video.show', ['id' => 0, 'slug' => 'test']) }}" class="group cursor-pointer block">
    <div class="relative aspect-video bg-gray-900 rounded-lg overflow-hidden">
        <img src="https://picsum.photos/320/180?random={{ $i }}"
            alt="Video thumbnail"
            class="w-full h-full object-cover">
        <span class="absolute bottom-2 right-2 bg-black/90 text-white text-xs px-1.5 py-0.5 rounded font-medium">
            24:13
        </span>
    </div>
    <div class="mt-2">
        <h3 class="font-medium text-[#222] text-sm line-clamp-2 leading-tight group-hover:text-[#dc251f] transition-colors">
            Another Great Video With Interesting Content
        </h3>
        <div class="text-xs text-[#666] mt-1">
            <div>SarahSmith</div>
            <div>845K views • 1 week ago</div>
        </div>
    </div>
</a>