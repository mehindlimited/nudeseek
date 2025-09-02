<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Header with Search and Preferences</title>
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
        <!-- Video Thumbnails Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-x-2 gap-y-6">
            @foreach ($videos as $video)
            @include('shared.video-thumbnail', ['video' => $video])
            @endforeach
        </div>
    </main>

    @include('shared.videos-pagination')
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

        // Close all mobile panels
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

        // Mobile user menu toggle
        if (mobileUserBtn && mobileUserMenu) {
            mobileUserBtn.addEventListener('click', () => {
                closeAllMobilePanels();
                mobileOverlay.classList.remove('hidden');
                mobileUserMenu.classList.remove('translate-x-full');
                mobileUserMenu.classList.add('translate-x-0');
                document.body.classList.add('overflow-hidden');
            });
        }

        // Mobile search toggle
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

        // Mobile menu toggle
        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', () => {
                closeAllMobilePanels();
                mobileOverlay.classList.remove('hidden');
                mobileMenu.classList.remove('-translate-x-full');
                mobileMenu.classList.add('translate-x-0');
                document.body.classList.add('overflow-hidden');
            });
        }

        // Close buttons
        if (mobileUserClose) {
            mobileUserClose.addEventListener('click', closeAllMobilePanels);
        }

        if (mobileMenuClose) {
            mobileMenuClose.addEventListener('click', closeAllMobilePanels);
        }

        // Overlay click to close
        if (mobileOverlay) {
            mobileOverlay.addEventListener('click', closeAllMobilePanels);
        }

        // Mobile submenu toggles
        document.querySelectorAll('.mobile-nav-toggle').forEach(button => {
            button.addEventListener('click', () => {
                const submenu = button.nextElementSibling;
                const icon = button.querySelector('svg');

                submenu.classList.toggle('hidden');
                icon.classList.toggle('rotate-180');
            });
        });

        // Optional: Add any JavaScript functionality for handling preference changes
        document.querySelectorAll('input[name="gender"], input[name="mobile-gender"]').forEach(input => {
            input.addEventListener('change', (e) => {
                console.log('Gender selected:', e.target.value);
                // You can add AJAX calls or other functionality here
            });
        });
        document.querySelectorAll('input[name="likes[]"], input[name="mobile-likes[]"]').forEach(input => {
            input.addEventListener('change', (e) => {
                console.log('Preference toggled:', e.target.value, e.target.checked);
                // You can add AJAX calls or other functionality here
            });
        });
        // Extreme toggle handler
        document.querySelectorAll('input[name="extreme"], input[name="mobile-extreme"]').forEach(input => {
            input.addEventListener('change', (e) => {
                console.log('Extreme mode:', e.target.checked);
                // You can add functionality to filter content based on extreme setting
            });
        });
    </script>
</body>

</html>