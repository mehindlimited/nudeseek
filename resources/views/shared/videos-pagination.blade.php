<!-- Pagination Block -->
<div class="mt-8 flex justify-center items-center gap-2">
    <!-- Previous Button -->
    <a href="?page=1" class="px-4 py-2 text-sm font-medium text-[#222] bg-[#ffffff] border-2 border-[#dfdfdf] rounded-full hover:border-[#dc251f] hover:text-[#dc251f] transition-colors disabled:opacity-50 disabled:cursor-not-allowed" aria-label="Previous page">
        <svg class="w-5 h-5 inline-block -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
    </a>

    <!-- Page Numbers -->
    <div class="flex gap-1">
        <a href="?page=1" class="px-4 py-2 text-sm font-medium text-[#222] bg-[#ffffff] border-2 border-[#dfdfdf] rounded-full hover:border-[#dc251f] hover:text-[#dc251f] transition-colors">1</a>
        <a href="?page=2" class="px-4 py-2 text-sm font-medium text-[#222] bg-[#ffffff] border-2 border-[#dfdfdf] rounded-full hover:border-[#dc251f] hover:text-[#dc251f] transition-colors">2</a>
        <a href="?page=3" class="px-4 py-2 text-sm font-medium text-[#222] bg-[#ffffff] border-2 border-[#dfdfdf] rounded-full hover:border-[#dc251f] hover:text-[#dc251f] transition-colors">3</a>
        <span class="px-4 py-2 text-sm font-medium text-[#666]">...</span>
        <a href="?page=10" class="px-4 py-2 text-sm font-medium text-[#222] bg-[#ffffff] border-2 border-[#dfdfdf] rounded-full hover:border-[#dc251f] hover:text-[#dc251f] transition-colors">10</a>
    </div>

    <!-- Next Button -->
    <a href="?page=3" class="px-4 py-2 text-sm font-medium text-[#222] bg-[#ffffff] border-2 border-[#dfdfdf] rounded-full hover:border-[#dc251f] hover:text-[#dc251f] transition-colors disabled:opacity-50 disabled:cursor-not-allowed" aria-label="Next page">
        <svg class="w-5 h-5 inline-block -mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
    </a>
</div>