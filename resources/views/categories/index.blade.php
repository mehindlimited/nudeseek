<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - NudeSeek</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" integrity="sha512-DxV+EoADOkOygM4IR9yXP8Sb2qwgidEmeqAEmDKIOfPRQZOWbXCzLC6vjbZyy0vPisbH2SyW27+ddLVCN+OMzQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans bg-gray-50">
    <div class="max-w-[1536px] mx-auto px-[9px]">
        @include('shared.header')
        @include('shared.navbar')
    </div>
    @include('shared.mobile-canvas')


    <!-- Main Content -->
    <main class="max-w-[1536px] mx-auto px-[9px] py-6">

        <div class="mt-6">
            <h2 class="text-lg font-semibold text-[#222] mb-4">All Categories</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="group cursor-pointer">
                    <div class="relative aspect-video bg-gray-900 rounded-lg overflow-hidden">
                        <img src="" alt="Category thumbnail" class="w-full h-full object-cover">
                        <span class="absolute bottom-2 right-2 bg-black/90 text-white text-xs px-1.5 py-0.5 rounded font-medium">
                            Explore
                        </span>
                    </div>
                    <div class="mt-2">
                        <h3 class="font-medium text-[#222] text-sm line-clamp-2 leading-tight group-hover:text-[#dc251f] transition-colors">
                            Most Popular
                        </h3>
                        <div class="text-xs text-[#666] mt-1">
                            <div>Trending Categories</div>
                            <div>1.2M videos</div>
                        </div>
                    </div>
                </div>
                <div class="group cursor-pointer">
                    <div class="relative aspect-video bg-gray-900 rounded-lg overflow-hidden">
                        <img src="" alt="Category thumbnail" class="w-full h-full object-cover">
                        <span class="absolute bottom-2 right-2 bg-black/90 text-white text-xs px-1.5 py-0.5 rounded font-medium">
                            Explore
                        </span>
                    </div>
                    <div class="mt-2">
                        <h3 class="font-medium text-[#222] text-sm line-clamp-2 leading-tight group-hover:text-[#dc251f] transition-colors">
                            New
                        </h3>
                        <div class="text-xs text-[#666] mt-1">
                            <div>Latest Additions</div>
                            <div>450K videos</div>
                        </div>
                    </div>
                </div>
                <div class="group cursor-pointer">
                    <div class="relative aspect-video bg-gray-900 rounded-lg overflow-hidden">
                        <img src="" alt="Category thumbnail" class="w-full h-full object-cover">
                        <span class="absolute bottom-2 right-2 bg-black/90 text-white text-xs px-1.5 py-0.5 rounded font-medium">
                            Explore
                        </span>
                    </div>
                    <div class="mt-2">
                        <h3 class="font-medium text-[#222] text-sm line-clamp-2 leading-tight group-hover:text-[#dc251f] transition-colors">
                            Verified
                        </h3>
                        <div class="text-xs text-[#666] mt-1">
                            <div>Certified Content</div>
                            <div>320K videos</div>
                        </div>
                    </div>
                </div>
                <div class="group cursor-pointer">
                    <div class="relative aspect-video bg-gray-900 rounded-lg overflow-hidden">
                        <img src="" alt="Category thumbnail" class="w-full h-full object-cover">
                        <span class="absolute bottom-2 right-2 bg-black/90 text-white text-xs px-1.5 py-0.5 rounded font-medium">
                            Explore
                        </span>
                    </div>
                    <div class="mt-2">
                        <h3 class="font-medium text-[#222] text-sm line-clamp-2 leading-tight group-hover:text-[#dc251f] transition-colors">
                            Compilations
                        </h3>
                        <div class="text-xs text-[#666] mt-1">
                            <div>Best Collections</div>
                            <div>780K videos</div>
                        </div>
                    </div>
                </div>
                <div class="group cursor-pointer">
                    <div class="relative aspect-video bg-gray-900 rounded-lg overflow-hidden">
                        <img src="" alt="Category thumbnail" class="w-full h-full object-cover">
                        <span class="absolute bottom-2 right-2 bg-black/90 text-white text-xs px-1.5 py-0.5 rounded font-medium">
                            Explore
                        </span>
                    </div>
                    <div class="mt-2">
                        <h3 class="font-medium text-[#222] text-sm line-clamp-2 leading-tight group-hover:text-[#dc251f] transition-colors">
                            Shorts
                        </h3>
                        <div class="text-xs text-[#666] mt-1">
                            <div>Quick Clips</div>
                            <div>1.5M videos</div>
                        </div>
                    </div>
                </div>
                <div class="group cursor-pointer">
                    <div class="relative aspect-video bg-gray-900 rounded-lg overflow-hidden">
                        <img src="" alt="Category thumbnail" class="w-full h-full object-cover">
                        <span class="absolute bottom-2 right-2 bg-black/90 text-white text-xs px-1.5 py-0.5 rounded font-medium">
                            Explore
                        </span>
                    </div>
                    <div class="mt-2">
                        <h3 class="font-medium text-[#222] text-sm line-clamp-2 leading-tight group-hover:text-[#dc251f] transition-colors">
                            Other
                        </h3>
                        <div class="text-xs text-[#666] mt-1">
                            <div>Miscellaneous</div>
                            <div>620K videos</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-gray-50 text-[#222] py-8">
        <div class="max-w-[1536px] mx-auto px-[9px]">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <a href="/" class="flex items-center mb-4">
                        <i class="fa-solid fa-magnifying-glass text-[#dc251f] mr-2"></i>
                        <span class="text-2xl font-bold uppercase">NudeSeek</span>
                    </a>
                    <p class="text-sm text-[#666]">
                        Discover and explore a wide range of content tailored to your preferences. Join our community and stay connected!
                    </p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/videos" class="hover:text-[#dc251f] transition-colors">Videos</a></li>
                        <li><a href="/categories" class="hover:text-[#dc251f] transition-colors">Categories</a></li>
                        <li><a href="/community" class="hover:text-[#dc251f] transition-colors">Community</a></li>
                        <li><a href="/amateurs" class="hover:text-[#dc251f] transition-colors">Amateurs</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Help</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/faq" class="hover:text-[#dc251f] transition-colors">FAQ</a></li>
                        <li><a href="/contact" class="hover:text-[#dc251f] transition-colors">Contact Us</a></li>
                        <li><a href="/content-removal" class="hover:text-[#dc251f] transition-colors">Content Removal</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Legal</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/terms" class="hover:text-[#dc251f] transition-colors">Terms of Use</a></li>
                        <li><a href="/privacy" class="hover:text-[#dc251f] transition-colors">Privacy Policy</a></li>
                        <li><a href="/cookies" class="hover:text-[#dc251f] transition-colors">Cookies Policy</a></li>
                        <li><a href="/dmca" class="hover:text-[#dc251f] transition-colors">DMCA/Copyright</a></li>
                        <li><a href="/parental-controls" class="hover:text-[#dc251f] transition-colors">Parental Controls</a></li>
                        <li><a href="/eu-dsa" class="hover:text-[#dc251f] transition-colors">EU DSA</a></li>
                        <li><a href="/trust-safety" class="hover:text-[#dc251f] transition-colors">Trust and Safety</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-[#efefef] mt-8 pt-6 text-center text-sm text-[#666]">
                &copy; 2025 NudeSeek. All rights reserved.
            </div>
        </div>
    </footer>

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
</body>

</html>