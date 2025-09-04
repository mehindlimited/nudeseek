<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NudeSeek - Homemade and Self Created Videos</title>
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
        document.addEventListener('DOMContentLoaded', () => {
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
                    mobileOverlay?.classList.remove('hidden');
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
                    mobileOverlay?.classList.remove('hidden');
                    mobileMenu.classList.remove('-translate-x-full');
                    mobileMenu.classList.add('translate-x-0');
                    document.body.classList.add('overflow-hidden');
                });
            }

            // Close buttons
            if (mobileUserClose) mobileUserClose.addEventListener('click', closeAllMobilePanels);
            if (mobileMenuClose) mobileMenuClose.addEventListener('click', closeAllMobilePanels);

            // Overlay click to close
            if (mobileOverlay) {
                mobileOverlay.addEventListener('click', closeAllMobilePanels);
            }

            // Mobile submenu toggles
            document.querySelectorAll('.mobile-nav-toggle').forEach(button => {
                button.addEventListener('click', () => {
                    const submenu = button.nextElementSibling;
                    const icon = button.querySelector('svg');

                    submenu?.classList.toggle('hidden');
                    icon?.classList.toggle('rotate-180');
                });
            });

            // Gender handler (unchanged)
            document.querySelectorAll('input[name="gender"], input[name="mobile-gender"]').forEach(input => {
                input.addEventListener('change', (e) => {
                    console.log('Gender selected:', e.target.value);
                    // Add AJAX or other functionality here if needed
                });
            });

            // === Likes handler (integrated with URL `target` param) ===
            const likeInputs = document.querySelectorAll('input[name="likes[]"], input[name="mobile-likes[]"]');
            if (likeInputs.length) {
                const url = new URL(window.location.href);
                const params = url.searchParams;
                const valueToTarget = {
                    males: 'gay',
                    females: 'straight'
                };

                const setChecked = (value, checked) => {
                    likeInputs.forEach(inp => {
                        if (inp.value === value) inp.checked = checked;
                    });
                };

                // Initialize checked states based on URL
                const currentTarget = params.get('target'); // 'gay' | 'straight' | null
                if (!currentTarget) {
                    setChecked('males', true);
                    setChecked('females', true);
                } else {
                    setChecked('males', currentTarget === 'gay');
                    setChecked('females', currentTarget === 'straight');
                }

                let navigating = false;
                const navigateWithParams = (nextParams) => {
                    if (navigating) return;
                    navigating = true;
                    const nextUrl = new URL(window.location.href);
                    nextUrl.search = nextParams.toString(); // preserve other params
                    window.location.replace(nextUrl.toString()); // keep history tidy
                };

                likeInputs.forEach(input => {
                    input.addEventListener('change', (e) => {
                        // Mirror state across desktop+mobile for same value
                        setChecked(e.target.value, e.target.checked);

                        const malesChecked = Array.from(likeInputs).some(inp => inp.value === 'males' && inp.checked);
                        const femalesChecked = Array.from(likeInputs).some(inp => inp.value === 'females' && inp.checked);

                        // Decide URL param based on current state
                        if (malesChecked && !femalesChecked) {
                            params.set('target', 'gay');
                        } else if (!malesChecked && femalesChecked) {
                            params.set('target', 'straight');
                        } else {
                            // Both checked or none checked -> remove param (default = both)
                            params.delete('target');
                        }

                        console.log('Preference toggled:', e.target.value, e.target.checked, '→ target:', params.get('target'));
                        navigateWithParams(params);
                    });
                });
            }

            // Extreme toggle handler (unchanged)
            document.querySelectorAll('input[name="extreme"], input[name="mobile-extreme"]').forEach(input => {
                input.addEventListener('change', (e) => {
                    console.log('Extreme mode:', e.target.checked);
                    // Add functionality to filter content based on extreme setting if needed
                });
            });
        });
    </script>
</body>

</html>