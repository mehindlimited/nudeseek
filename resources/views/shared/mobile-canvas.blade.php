<!-- Mobile Off-Canvas Overlay -->
<div id="mobile-overlay" class="fixed inset-0 bg-black/70 z-40 hidden lg:hidden"></div>

<!-- Mobile Menu Off-Canvas -->
<div id="mobile-menu" class="fixed top-0 left-0 h-full w-80 bg-white z-50 transform -translate-x-full transition-transform duration-300 ease-in-out lg:hidden shadow-lg">
    <div class="p-4">
        <!-- Close button -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-bold text-[#222]">Menu</h2>
            <button id="mobile-menu-close" class="p-2 text-[#222] hover:text-[#dc251f] transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Menu content -->
        <nav>
            <ul class="space-y-2">
                <!-- Videos -->
                <li>
                    <button class="mobile-nav-toggle flex items-center justify-between w-full px-3 py-3 text-left font-medium text-[#222] hover:text-[#dc251f] hover:bg-gray-50 rounded">
                        Videos
                        <svg class="h-5 w-5 transform transition-transform" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M5.23 7.21a.75.75 0 011.06.02L10 10.17l3.71-2.94a.75.75 0 111.04 1.08l-4.24 3.36a.75.75 0 01-.94 0L5.21 8.31a.75.75 0 01.02-1.1z" />
                        </svg>
                    </button>
                    <div class="mobile-submenu hidden pl-4 py-2 space-y-1">
                        <a href="/videos/trending" class="block px-3 py-2 text-sm text-[#666] hover:text-[#dc251f] hover:bg-gray-50 rounded">Trending</a>
                        <a href="/videos/latest" class="block px-3 py-2 text-sm text-[#666] hover:text-[#dc251f] hover:bg-gray-50 rounded">Latest</a>
                        <a href="/videos/hd" class="block px-3 py-2 text-sm text-[#666] hover:text-[#dc251f] hover:bg-gray-50 rounded">HD</a>
                        <a href="/videos/long" class="block px-3 py-2 text-sm text-[#666] hover:text-[#dc251f] hover:bg-gray-50 rounded">Long videos</a>
                    </div>
                </li>

                <!-- Categories -->
                <li>
                    <button class="mobile-nav-toggle flex items-center justify-between w-full px-3 py-3 text-left font-medium text-[#222] hover:text-[#dc251f] hover:bg-gray-50 rounded">
                        Categories
                        <svg class="h-5 w-5 transform transition-transform" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M5.23 7.21a.75.75 0 011.06.02L10 10.17l3.71-2.94a.75.75 0 111.04 1.08l-4.24 3.36a.75.75 0 01-.94 0L5.21 8.31a.75.75 0 01.02-1.1z" />
                        </svg>
                    </button>
                    <div class="mobile-submenu hidden pl-4 py-2 space-y-1">
                        <a href="/categories/most-popular" class="block px-3 py-2 text-sm text-[#666] hover:text-[#dc251f] hover:bg-gray-50 rounded">Most Popular</a>
                        <a href="/categories/new" class="block px-3 py-2 text-sm text-[#666] hover:text-[#dc251f] hover:bg-gray-50 rounded">New</a>
                        <a href="/categories/verified" class="block px-3 py-2 text-sm text-[#666] hover:text-[#dc251f] hover:bg-gray-50 rounded">Verified</a>
                        <a href="/categories/compilations" class="block px-3 py-2 text-sm text-[#666] hover:text-[#dc251f] hover:bg-gray-50 rounded">Compilations</a>
                        <a href="/categories/shorts" class="block px-3 py-2 text-sm text-[#666] hover:text-[#dc251f] hover:bg-gray-50 rounded">Shorts</a>
                        <a href="/categories/other" class="block px-3 py-2 text-sm text-[#666] hover:text-[#dc251f] hover:bg-gray-50 rounded">Other</a>
                    </div>
                </li>

                <!-- Community -->
                <li>
                    <button class="mobile-nav-toggle flex items-center justify-between w-full px-3 py-3 text-left font-medium text-[#222] hover:text-[#dc251f] hover:bg-gray-50 rounded">
                        Community
                        <svg class="h-5 w-5 transform transition-transform" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M5.23 7.21a.75.75 0 011.06.02L10 10.17l3.71-2.94a.75.75 0 111.04 1.08l-4.24 3.36a.75.75 0 01-.94 0L5.21 8.31a.75.75 0 01.02-1.1z" />
                        </svg>
                    </button>
                    <div class="mobile-submenu hidden pl-4 py-2 space-y-1">
                        <a href="/community/feed" class="block px-3 py-2 text-sm text-[#666] hover:text-[#dc251f] hover:bg-gray-50 rounded">Feed</a>
                        <a href="/community/groups" class="block px-3 py-2 text-sm text-[#666] hover:text-[#dc251f] hover:bg-gray-50 rounded">Groups</a>
                        <a href="/community/challenges" class="block px-3 py-2 text-sm text-[#666] hover:text-[#dc251f] hover:bg-gray-50 rounded">Challenges</a>
                        <a href="/community/help" class="block px-3 py-2 text-sm text-[#666] hover:text-[#dc251f] hover:bg-gray-50 rounded">Help Center</a>
                    </div>
                </li>

                <!-- Amateurs -->
                <li>
                    <button class="mobile-nav-toggle flex items-center justify-between w-full px-3 py-3 text-left font-medium text-[#222] hover:text-[#dc251f] hover:bg-gray-50 rounded">
                        Amateurs
                        <svg class="h-5 w-5 transform transition-transform" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M5.23 7.21a.75.75 0 011.06.02L10 10.17l3.71-2.94a.75.75 0 111.04 1.08l-4.24 3.36a.75.75 0 01-.94 0L5.21 8.31a.75.75 0 01.02-1.1z" />
                        </svg>
                    </button>
                    <div class="mobile-submenu hidden pl-4 py-2 space-y-1">
                        <a href="/amateurs/creators" class="block px-3 py-2 text-sm text-[#666] hover:text-[#dc251f] hover:bg-gray-50 rounded">Creators</a>
                        <a href="/amateurs/upload" class="block px-3 py-2 text-sm text-[#666] hover:text-[#dc251f] hover:bg-gray-50 rounded">Upload</a>
                        <a href="/amateurs/top" class="block px-3 py-2 text-sm text-[#666] hover:text-[#dc251f] hover:bg-gray-50 rounded">Top this week</a>
                        <a href="/amateurs/new" class="block px-3 py-2 text-sm text-[#666] hover:text-[#dc251f] hover:bg-gray-50 rounded">Newcomers</a>
                    </div>
                </li>
            </ul>
        </nav>
    </div>
</div>

<!-- Mobile User Menu Off-Canvas -->
<div id="mobile-user-menu" class="fixed top-0 right-0 h-full w-80 bg-white z-50 transform translate-x-full transition-transform duration-300 ease-in-out lg:hidden shadow-lg">
    <div class="p-4">
        <!-- Close button -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-bold text-[#222]">Preferences</h2>
            <button id="mobile-user-close" class="p-2 text-[#222] hover:text-[#dc251f] transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- User preferences content -->
        <div class="space-y-6">
            <!-- I am section -->
            <div class="space-y-3">
                <span class="text-sm font-medium text-[#666]">I am</span>
                <div class="flex gap-2">
                    <label class="relative">
                        <input type="radio" name="mobile-gender" value="male" class="peer sr-only">
                        <div class="px-4 py-2 text-sm border-2 border-[#dfdfdf] bg-[#ffffff] rounded-full cursor-pointer transition-all peer-checked:border-[#dc251f] peer-checked:bg-[#dc251f] peer-checked:text-white hover:border-[#dc251f]/50">
                            Male
                        </div>
                    </label>
                    <label class="relative">
                        <input type="radio" name="mobile-gender" value="female" class="peer sr-only">
                        <div class="px-4 py-2 text-sm border-2 border-[#dfdfdf] bg-[#ffffff] rounded-full cursor-pointer transition-all peer-checked:border-[#dc251f] peer-checked:bg-[#dc251f] peer-checked:text-white hover:border-[#dc251f]/50">
                            Female
                        </div>
                    </label>
                </div>
            </div>

            <!-- I like section -->
            <div class="space-y-3">
                <span class="text-sm font-medium text-[#666]">I like</span>
                <div class="flex gap-2">
                    <label class="relative">
                        <input type="checkbox" name="mobile-likes[]" value="males" class="peer sr-only">
                        <div class="px-4 py-2 text-sm border-2 border-[#dfdfdf] bg-[#ffffff] rounded-full cursor-pointer transition-all peer-checked:border-[#dc251f] peer-checked:bg-[#dc251f] peer-checked:text-white hover:border-[#dc251f]/50">
                            Males
                        </div>
                    </label>
                    <label class="relative">
                        <input type="checkbox" name="mobile-likes[]" value="females" class="peer sr-only">
                        <div class="px-4 py-2 text-sm border-2 border-[#dfdfdf] bg-[#ffffff] rounded-full cursor-pointer transition-all peer-checked:border-[#dc251f] peer-checked:bg-[#dc251f] peer-checked:text-white hover:border-[#dc251f]/50">
                            Females
                        </div>
                    </label>
                </div>
            </div>

            <!-- Extreme toggle -->
            <div class="space-y-3">
                <span class="text-sm font-medium text-[#666]">Content</span>
                <label class="relative flex items-start cursor-pointer">
                    <input type="checkbox" name="mobile-extreme" value="extreme" class="peer sr-only">
                    <div class="px-4 py-2 text-sm border-2 border-[#dfdfdf] bg-[#ffffff] rounded-full transition-all peer-checked:border-[#dc251f] peer-checked:bg-[#dc251f] peer-checked:text-white hover:border-[#dc251f]/50">
                        Extreme
                    </div>
                </label>
            </div>
        </div>
    </div>
</div>