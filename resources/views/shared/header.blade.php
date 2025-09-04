 <!-- Mobile Header -->
 <header class="block lg:hidden">
     <div class="flex justify-between items-center py-[15px]">
         <!-- Menu button -->
         <button id="mobile-menu-btn" class="p-2 text-[#222] hover:text-[#dc251f] transition-colors">
             <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
             </svg>
         </button>

         <!-- Logo -->
         <a href="/" class="text-2xl font-bold text-[#222] uppercase">
             <i class="fa-solid fa-magnifying-glass text-[#dc251f] mr-[2px]"></i>NudeSeek
         </a>

         <!-- Right side icons -->
         <div class="flex items-center gap-2">
             <!-- Search button -->
             <button id="mobile-search-btn" class="p-2 text-[#222] hover:text-[#dc251f] transition-colors">
                 <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                 </svg>
             </button>

             <!-- User menu button -->
             <button id="mobile-user-btn" class="p-2 text-[#222] hover:text-[#dc251f] transition-colors">
                 <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                 </svg>
             </button>
         </div>
     </div>

     <!-- Mobile Search Bar (Hidden by default) -->
     <div id="mobile-search" class="hidden pb-3 border-t border-[#efefef] pt-3">
         <form action="#" method="GET" class="relative">
             <input
                 type="text"
                 name="q"
                 placeholder="Search..."
                 class="w-full px-4 py-2 pr-12 border-2 border-[#dfdfdf] bg-[#ffffff] rounded-full focus:outline-none focus:border-[#dc251f] transition-colors hover:border-[#dc251f]/50">
             <button
                 type="submit"
                 class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-[#222] hover:text-[#dc251f] transition-colors">
                 <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                 </svg>
             </button>
         </form>
     </div>
 </header>

 <!-- Desktop Header -->
 <header class="hidden lg:flex justify-between items-center py-[15px] w-full">
     <!-- Logo -->
     <a href="/" class="text-2xl font-bold text-[#222] uppercase">
         <i class="fa-solid fa-magnifying-glass text-[#dc251f] mr-[2px]"></i>NudeSeek
     </a>
     <!-- Search Bar -->
     <div class="flex-1 max-w-2xl mx-8">
         <form action="#" method="GET" class="relative">
             <input
                 type="text"
                 name="q"
                 placeholder="Search..."
                 class="w-full px-4 py-2 pr-12 border-2 border-[#dfdfdf] bg-[#ffffff] rounded-full focus:outline-none focus:border-[#dc251f] transition-colors hover:border-[#dc251f]/50">
             <button
                 type="submit"
                 class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-[#222] hover:text-[#dc251f] transition-colors">
                 <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                 </svg>
             </button>
         </form>
     </div>
     <!-- User Preferences Block -->
     <div class="flex items-center space-x-4">
         <!-- Separator -->
         <div class="h-8 w-[1px] bg-[#dfdfdf]"></div>
         <!-- I like section -->
         <div class="flex items-center gap-2">
             <span class="text-sm text-[#666]">I like</span>
             <div class="flex gap-2">
                 <label class="relative">
                     <input type="checkbox" name="likes[]" value="males" class="peer sr-only">
                     <div class="px-3 py-1 text-sm border-2 border-[#dfdfdf] bg-[#ffffff] rounded-full cursor-pointer transition-all peer-checked:border-[#dc251f] peer-checked:bg-[#dc251f] peer-checked:text-white hover:border-[#dc251f]/50">
                         Males
                     </div>
                 </label>
                 <label class="relative">
                     <input type="checkbox" name="likes[]" value="females" class="peer sr-only">
                     <div class="px-3 py-1 text-sm border-2 border-[#dfdfdf] bg-[#ffffff] rounded-full cursor-pointer transition-all peer-checked:border-[#dc251f] peer-checked:bg-[#dc251f] peer-checked:text-white hover:border-[#dc251f]/50">
                         Females
                     </div>
                 </label>
             </div>
         </div>
         <!-- Separator -->
         <div class="h-8 w-[1px] bg-[#dfdfdf]"></div>
         <!-- Extreme toggle -->
         <label class="relative flex items-center gap-2 cursor-pointer">
             <input type="checkbox" name="extreme" value="extreme" class="peer sr-only">
             <div class="px-3 py-1 text-sm border-2 border-[#dfdfdf] bg-[#ffffff] rounded-full transition-all peer-checked:border-[#dc251f] peer-checked:bg-[#dc251f] peer-checked:text-white hover:border-[#dc251f]/50">
                 Extreme
             </div>
         </label>
         <!-- Separator -->
         <div class="h-8 w-[1px] bg-[#dfdfdf]"></div>
         <!-- User menu button -->
         <button class="p-2 text-[#222] hover:text-[#dc251f] transition-colors">
             <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
             </svg>
         </button>
     </div>
 </header>