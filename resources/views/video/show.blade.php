<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $video->title }}- NudeSeek</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" integrity="sha512-DxV+EoADOkOygM4IR9yXP8Sb2qwgidEmeqAEmDKIOfPRQZOWbXCzLC6vjbZyy0vPisbH2SyW27+ddLVCN+OMzQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.fluidplayer.com/v3/current/fluidplayer.min.js"></script>
</head>

<body class="font-sans bg-gray-50">
    <div class="max-w-[1536px] mx-auto px-[9px]">
        @include('shared.header')
        @include('shared.navbar')
    </div>
    @include('shared.mobile-canvas')

    <!-- Main Content -->
    <main class="max-w-[1536px] mx-auto px-[9px] py-6">
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Video Player and Info -->
            <div class="lg:w-3/4">
                <!-- Video Player -->
                <div class="relative aspect-video rounded-lg overflow-hidden">
                    <video id="nudeseek-player">
                        <source src="https://videos-nudeseek.b-cdn.net/{{ $video->main_dir }}/{{ $video->code }}.mp4" type="video/mp4" />
                    </video>
                </div>

                <!-- Video Info -->
                <div class="mt-4">
                    <h1 class="text-2xl font-bold text-[#222] line-clamp-2">{{ $video->title }}</h1>
                    <div class="flex items-center justify-between mt-2">
                        <div class="text-sm text-[#666]">
                            <span>{{ number_format($video->views) }} views</span> • <span>{{ $video->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="flex gap-2">
                            <button class="flex items-center gap-1 px-3 py-1 text-sm border-2 border-[#dfdfdf] bg-[#ffffff] rounded-full hover:border-[#dc251f] hover:text-[#dc251f] transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                </svg>
                                Like
                            </button>
                            <button class="flex items-center gap-1 px-3 py-1 text-sm border-2 border-[#dfdfdf] bg-[#ffffff] rounded-full hover:border-[#dc251f] hover:text-[#dc251f] transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                                Dislike
                            </button>
                            <button class="flex items-center gap-1 px-3 py-1 text-sm border-2 border-[#dfdfdf] bg-[#ffffff] rounded-full hover:border-[#dc251f] hover:text-[#dc251f] transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C9.886 12.938 10.5 11.908 10.5 10.5c0-1.657-1.343-3-3-3S4.5 8.843 4.5 10.5c0 1.408.614 2.438 1.816 2.842M15.316 13.342C14.114 12.938 13.5 11.908 13.5 10.5c0-1.657 1.343-3 3-3s3 1.343 3 3c0 1.408-.614 2.438-1.816 2.842M12 15v3m-3-3h6"></path>
                                </svg>
                                Share
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Creator Info -->
                <div class="mt-4 border-t border-[#efefef] pt-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <img src="https://picsum.photos/40/40?random=1" alt="Creator avatar" class="w-10 h-10 rounded-full">
                            <div>
                                <a href="/creator/sarahsmith" class="text-sm font-medium text-[#222] hover:text-[#dc251f]">SarahSmith</a>
                                <p class="text-xs text-[#666]">10K subscribers</p>
                            </div>
                        </div>
                        <button class="px-4 py-2 text-sm font-medium text-[#222] bg-[#ffffff] border-2 border-[#dfdfdf] rounded-full hover:border-[#dc251f] hover:text-[#dc251f] transition-colors">
                            Subscribe
                        </button>
                    </div>
                </div>

                <!-- Video Description -->
                <div class="mt-4">
                    <p class="text-sm text-[#666] leading-relaxed">
                        This is a sample video description. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                    </p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <a href="/categories/sample" class="px-3 py-1 text-sm border-2 border-[#dfdfdf] bg-[#ffffff] rounded-full hover:border-[#dc251f] hover:text-[#dc251f] transition-colors">Sample Tag</a>
                        <a href="/categories/example" class="px-3 py-1 text-sm border-2 border-[#dfdfdf] bg-[#ffffff] rounded-full hover:border-[#dc251f] hover:text-[#dc251f] transition-colors">Example</a>
                    </div>
                </div>

                <!-- Comments Section -->
                <div class="mt-6">
                    <h2 class="text-lg font-semibold text-[#222] mb-4">Comments</h2>
                    <div class="space-y-4">
                        <div class="flex gap-3">
                            <img src="https://picsum.photos/32/32?random=2" alt="User avatar" class="w-8 h-8 rounded-full">
                            <div>
                                <p class="text-sm font-medium text-[#222]">User123</p>
                                <p class="text-sm text-[#666]">Great video! Really enjoyed the content.</p>
                                <div class="flex gap-2 mt-1">
                                    <button class="text-xs text-[#666] hover:text-[#dc251f]">Like</button>
                                    <button class="text-xs text-[#666] hover:text-[#dc251f]">Reply</button>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <img src="https://picsum.photos/32/32?random=3" alt="User avatar" class="w-8 h-8 rounded-full">
                            <div>
                                <p class="text-sm font-medium text-[#222]">JaneDoe</p>
                                <p class="text-sm text-[#666]">Thanks for sharing, very informative!</p>
                                <div class="flex gap-2 mt-1">
                                    <button class="text-xs text-[#666] hover:text-[#dc251f]">Like</button>
                                    <button class="text-xs text-[#666] hover:text-[#dc251f]">Reply</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <textarea class="w-full px-4 py-2 border-2 border-[#dfdfdf] bg-[#ffffff] rounded-lg focus:outline-none focus:border-[#dc251f] transition-colors" placeholder="Add a comment..."></textarea>
                        <button class="mt-2 px-4 py-2 text-sm font-medium text-[#222] bg-[#ffffff] border-2 border-[#dfdfdf] rounded-full hover:border-[#dc251f] hover:text-[#dc251f] transition-colors">
                            Post Comment
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Column: Ads and Related Videos -->
            <div class="lg:w-1/4 hidden lg:block">
                <div class="space-y-6">
                    <!-- YouTube-inspired Ad Block -->
                    <div class="bg-white border border-[#dfdfdf] rounded-lg p-4 shadow-sm">
                        <a href="https://example.com/ad1" target="_blank">
                            <img src="https://picsum.photos/300/150?random=7" alt="Ad image" class="w-full rounded-lg mb-3">
                            <h3 class="text-sm font-semibold text-[#222] line-clamp-1">Discover Amazing Products</h3>
                            <p class="text-xs text-[#666] line-clamp-2">Explore our latest collection and elevate your experience with top-quality items designed for you.</p>
                            <div class="flex justify-between items-center mt-3">
                                <span class="text-xs text-[#666] italic">Ad</span>
                                <button class="px-3 py-1 text-xs font-medium text-white bg-[#dc251f] rounded-full hover:bg-[#b91c1c] transition-colors">
                                    Shop Now
                                </button>
                            </div>
                        </a>
                    </div>
                    <!-- Simple Ad Block -->
                    <div class="bg-white border border-[#dfdfdf] rounded-lg p-4 shadow-sm">
                        <a href="https://example.com/ad2" target="_blank">
                            <h3 class="text-sm font-semibold text-[#222] line-clamp-1">Join Our Community</h3>
                            <p class="text-xs text-[#666] line-clamp-2">Connect with like-minded individuals and share your passions today!</p>
                            <div class="flex justify-between items-center mt-3">
                                <span class="text-xs text-[#666] italic">Ad</span>
                                <button class="px-3 py-1 text-xs font-medium text-white bg-[#dc251f] rounded-full hover:bg-[#b91c1c] transition-colors">
                                    Learn More
                                </button>
                            </div>
                        </a>
                    </div>
                    <!-- Related Videos -->
                    <div class="mt-6">
                        <h2 class="text-lg font-semibold text-[#222] mb-4">Related Videos</h2>
                        <div class="space-y-4">
                            <div class="group cursor-pointer">
                                <div class="relative aspect-video bg-gray-900 rounded-lg overflow-hidden">
                                    <img src="https://picsum.photos/320/180?random=4" alt="Related video thumbnail" class="w-full h-full object-cover">
                                    <span class="absolute bottom-2 right-2 bg-black/90 text-white text-xs px-1.5 py-0.5 rounded font-medium">
                                        12:45
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <h3 class="font-medium text-[#222] text-sm line-clamp-2 leading-tight group-hover:text-[#dc251f] transition-colors">
                                        Related Video Title 1
                                    </h3>
                                    <div class="text-xs text-[#666] mt-1">
                                        <div>CreatorName</div>
                                        <div>500K views • 2 days ago</div>
                                    </div>
                                </div>
                            </div>
                            <div class="group cursor-pointer">
                                <div class="relative aspect-video bg-gray-900 rounded-lg overflow-hidden">
                                    <img src="https://picsum.photos/320/180?random=5" alt="Related video thumbnail" class="w-full h-full object-cover">
                                    <span class="absolute bottom-2 right-2 bg-black/90 text-white text-xs px-1.5 py-0.5 rounded font-medium">
                                        8:30
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <h3 class="font-medium text-[#222] text-sm line-clamp-2 leading-tight group-hover:text-[#dc251f] transition-colors">
                                        Related Video Title 2
                                    </h3>
                                    <div class="text-xs text-[#666] mt-1">
                                        <div>AnotherCreator</div>
                                        <div>300K views • 3 days ago</div>
                                    </div>
                                </div>
                            </div>
                            <div class="group cursor-pointer">
                                <div class="relative aspect-video bg-gray-900 rounded-lg overflow-hidden">
                                    <img src="https://picsum.photos/320/180?random=6" alt="Related video thumbnail" class="w-full h-full object-cover">
                                    <span class="absolute bottom-2 right-2 bg-black/90 text-white text-xs px-1.5 py-0.5 rounded font-medium">
                                        15:20
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <h3 class="font-medium text-[#222] text-sm line-clamp-2 leading-tight group-hover:text-[#dc251f] transition-colors">
                                        Related Video Title 3
                                    </h3>
                                    <div class="text-xs text-[#666] mt-1">
                                        <div>ThirdCreator</div>
                                        <div>200K views • 5 days ago</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-6">
            <h2 class="text-lg font-semibold text-[#222] mb-4">Related Videos</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-x-2 gap-y-6">
                <div class="group cursor-pointer">
                    <div class="relative aspect-video bg-gray-900 rounded-lg overflow-hidden">
                        <img src="https://picsum.photos/320/180?random=4" alt="Related video thumbnail" class="w-full h-full object-cover">
                        <span class="absolute bottom-2 right-2 bg-black/90 text-white text-xs px-1.5 py-0.5 rounded font-medium">
                            12:45
                        </span>
                    </div>
                    <div class="mt-2">
                        <h3 class="font-medium text-[#222] text-sm line-clamp-2 leading-tight group-hover:text-[#dc251f] transition-colors">
                            Related Video Title 1
                        </h3>
                        <div class="text-xs text-[#666] mt-1">
                            <div>CreatorName</div>
                            <div>500K views • 2 days ago</div>
                        </div>
                    </div>
                </div>
                <div class="group cursor-pointer">
                    <div class="relative aspect-video bg-gray-900 rounded-lg overflow-hidden">
                        <img src="https://picsum.photos/320/180?random=5" alt="Related video thumbnail" class="w-full h-full object-cover">
                        <span class="absolute bottom-2 right-2 bg-black/90 text-white text-xs px-1.5 py-0.5 rounded font-medium">
                            8:30
                        </span>
                    </div>
                    <div class="mt-2">
                        <h3 class="font-medium text-[#222] text-sm line-clamp-2 leading-tight group-hover:text-[#dc251f] transition-colors">
                            Related Video Title 2
                        </h3>
                        <div class="text-xs text-[#666] mt-1">
                            <div>AnotherCreator</div>
                            <div>300K views • 3 days ago</div>
                        </div>
                    </div>
                </div>
                <div class="group cursor-pointer">
                    <div class="relative aspect-video bg-gray-900 rounded-lg overflow-hidden">
                        <img src="https://picsum.photos/320/180?random=6" alt="Related video thumbnail" class="w-full h-full object-cover">
                        <span class="absolute bottom-2 right-2 bg-black/90 text-white text-xs px-1.5 py-0.5 rounded font-medium">
                            15:20
                        </span>
                    </div>
                    <div class="mt-2">
                        <h3 class="font-medium text-[#222] text-sm line-clamp-2 leading-tight group-hover:text-[#dc251f] transition-colors">
                            Related Video Title 3
                        </h3>
                        <div class="text-xs text-[#666] mt-1">
                            <div>ThirdCreator</div>
                            <div>200K views • 5 days ago</div>
                        </div>
                    </div>
                </div>
                <div class="group cursor-pointer">
                    <div class="relative aspect-video bg-gray-900 rounded-lg overflow-hidden">
                        <img src="https://picsum.photos/320/180?random=6" alt="Related video thumbnail" class="w-full h-full object-cover">
                        <span class="absolute bottom-2 right-2 bg-black/90 text-white text-xs px-1.5 py-0.5 rounded font-medium">
                            15:20
                        </span>
                    </div>
                    <div class="mt-2">
                        <h3 class="font-medium text-[#222] text-sm line-clamp-2 leading-tight group-hover:text-[#dc251f] transition-colors">
                            Related Video Title 3
                        </h3>
                        <div class="text-xs text-[#666] mt-1">
                            <div>ThirdCreator</div>
                            <div>200K views • 5 days ago</div>
                        </div>
                    </div>
                </div>
                <div class="group cursor-pointer">
                    <div class="relative aspect-video bg-gray-900 rounded-lg overflow-hidden">
                        <img src="https://picsum.photos/320/180?random=6" alt="Related video thumbnail" class="w-full h-full object-cover">
                        <span class="absolute bottom-2 right-2 bg-black/90 text-white text-xs px-1.5 py-0.5 rounded font-medium">
                            15:20
                        </span>
                    </div>
                    <div class="mt-2">
                        <h3 class="font-medium text-[#222] text-sm line-clamp-2 leading-tight group-hover:text-[#dc251f] transition-colors">
                            Related Video Title 3
                        </h3>
                        <div class="text-xs text-[#666] mt-1">
                            <div>ThirdCreator</div>
                            <div>200K views • 5 days ago</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @include('shared.footer')

    <script>
        // Mobile functionality
        const mobileOverlay = document.getElementById('mobile-overlay');
        const mobileUserBtn = document.getElementById('mobile-user-btn');
        const mobileUserMenu = document.getElementById('mobile-user-menu');
        const mobileUserClose = document.getElementById('mobile-user-close');
        const mobileSearchBtn = document.getElementById('mobile-search-btn');
        const mobileSearch = document.getElementById('mobile-search');
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileMenuClose = document.getElementById('mobile-menu-close');

        function closeAllMobilePanels() {
            if (mobileSearch) mobileSearch.classList.add('hidden');
            if (mobileOverlay) mobileOverlay.classList.add('hidden');
            if (mobileMenu) {
                mobileMenu.classList.remove('translate-x-0');
                mobileMenu.classList.add('-translate-x-full');
            }
            if (mobileUserMenu) {
                mobileUserMenu.classList.remove('translate-x-0');
                mobileUserMenu.classList.add('translate-x-full');
            }
            document.body.classList.remove('overflow-hidden');
        }

        if (mobileUserBtn && mobileUserMenu) {
            mobileUserBtn.addEventListener('click', () => {
                closeAllMobilePanels();
                mobileOverlay.classList.remove('hidden');
                mobileUserMenu.classList.remove('translate-x-full');
                mobileUserMenu.classList.add('translate-x-0');
                document.body.classList.add('overflow-hidden');
            });
        }

        if (mobileSearchBtn && mobileSearch) {
            mobileSearchBtn.addEventListener('click', () => {
                closeAllMobilePanels();
                mobileSearch.classList.toggle('hidden');
                if (!mobileSearch.classList.contains('hidden')) {
                    const searchInput = mobileSearch.querySelector('input[name="q"]');
                    if (searchInput) searchInput.focus();
                }
            });
        }

        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', () => {
                closeAllMobilePanels();
                mobileOverlay.classList.remove('hidden');
                mobileMenu.classList.remove('-translate-x-full');
                mobileMenu.classList.add('translate-x-0');
                document.body.classList.add('overflow-hidden');
            });
        }

        if (mobileUserClose) {
            mobileUserClose.addEventListener('click', closeAllMobilePanels);
        }

        if (mobileMenuClose) {
            mobileMenuClose.addEventListener('click', closeAllMobilePanels);
        }

        if (mobileOverlay) {
            mobileOverlay.addEventListener('click', closeAllMobilePanels);
        }

        document.querySelectorAll('.mobile-nav-toggle').forEach(button => {
            button.addEventListener('click', () => {
                const submenu = button.nextElementSibling;
                const icon = button.querySelector('svg');
                submenu.classList.toggle('hidden');
                icon.classList.toggle('rotate-180');
            });
        });

        document.querySelectorAll('input[name="gender"], input[name="mobile-gender"]').forEach(input => {
            input.addEventListener('change', (e) => {
                console.log('Gender selected:', e.target.value);
            });
        });
        document.querySelectorAll('input[name="likes[]"], input[name="mobile-likes[]"]').forEach(input => {
            input.addEventListener('change', (e) => {
                console.log('Preference toggled:', e.target.value, e.target.checked);
            });
        });
        document.querySelectorAll('input[name="extreme"], input[name="mobile-extreme"]').forEach(input => {
            input.addEventListener('change', (e) => {
                console.log('Extreme mode:', e.target.checked);
            });
        });
    </script>
    <script src="https://cdn.fluidplayer.com/v3/current/fluidplayer.min.js"></script>
    <script>
        let player = fluidPlayer(
            'nudeseek-player', {
                "layoutControls": {
                    "controlBar": {
                        "autoHide": true,
                        "playbackRates": [
                            "x2",
                            "x1.5",
                            "x1",
                            "x0.5",
                            "x0.25"
                        ],
                    },
                    "persistentSettings": {
                        "volume": true,
                        "quality": false,
                        "speed": false,
                        "theatre": false,
                    },
                    "contextMenu": {
                        "controls": true,
                        "links": [{
                            "href": 'https://fucker.com',
                            "label": 'Fucker'
                        }]
                    },
                    "controlForwardBackward": {
                        "show": false,
                        "doubleTapMobile": true,
                    },
                    "miniPlayer": {
                        "enabled": false,
                    },
                    "roundedCorners": 0,
                    "preload": "auto",
                    "autoPlay": false,
                    "mute": false,
                    "allowTheatre": false,
                    "playPauseAnimation": false,
                    "playbackRateEnabled": true,
                    "autoFullScreenLandscape": true,
                    "allowDownload": false,
                    "autoRotateFullScreen": true,
                    "playButtonShowing": true,
                    "fillToContainer": true,
                    "posterImage": "https://thumbnails-nudeseek.b-cdn.net/{{ $video->main_dir }}/{{ $video->code }}_thumb_1.jpg",
                },
            });
    </script>
</body>

</html>