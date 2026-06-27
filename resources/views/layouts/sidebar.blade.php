@php
    $role = auth()->user() ? auth()->user()->role : 'user';
    $currentRoute = Route::currentRouteName() ?? 'home';
    
    // Tự động kiểm tra để giữ trạng thái mở cho Dropdown khi load trang
    $isFlashcardActive = Str::startsWith($currentRoute, 'user.flashcards');
    $isCommunityActive = Str::startsWith($currentRoute, 'user.community');
@endphp

<aside :class="sidebarOpen ? 'w-64' : 'w-24'"
       class="fixed left-0 top-0 h-screen bg-white border-r border-slate-200 transition-all duration-300 z-50">
    <div class="flex flex-col h-full py-8">
        <!-- Logo Header -->
        <div class="px-8 mb-12 flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-indigo-200 cursor-pointer hover:shadow-indigo-300 transition-shadow"
                 @click="sidebarOpen = !sidebarOpen">
                Σ
            </div>
            <span x-show="sidebarOpen"
                  x-transition:enter="transition ease-out duration-200"
                  x-transition:enter-start="opacity-0 -translate-x-2"
                  x-transition:enter-end="opacity-100 translate-x-0"
                  class="text-xl font-display font-bold text-slate-900 tracking-tight whitespace-nowrap">
                EduNova <span class="text-indigo-600">AI</span>
            </span>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 px-4 py-4 space-y-2 overflow-y-auto"
             x-data="{ 
                expandedMenus: [
                    '{{ $isFlashcardActive ? 'flashcard' : '' }}',
                    '{{ $isCommunityActive ? 'community' : '' }}'
                ].filter(Boolean) 
             }">

            @if($role === 'user')
                <!-- 1. Trang Chủ -->
                <a href="{{ route('user.dashboard') }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all group font-semibold {{ $currentRoute === 'user.dashboard' ? 'bg-indigo-50 text-indigo-600 shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">
                    <i data-lucide="home" class="w-5 h-5 flex-shrink-0 {{ $currentRoute === 'user.dashboard' ? 'text-indigo-600' : 'text-slate-500 group-hover:text-indigo-600' }}"></i>
                    <span x-show="sidebarOpen" class="whitespace-nowrap">Trang chủ</span>
                </a>

                <!-- 2. Khóa học của tôi -->
                <a href="{{ route('user.knowledge') }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all group font-semibold {{ $currentRoute === 'user.knowledge' ? 'bg-indigo-50 text-indigo-600 shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">
                    <i data-lucide="book-open" class="w-5 h-5 flex-shrink-0 {{ $currentRoute === 'user.knowledge' ? 'text-indigo-600' : 'text-slate-500 group-hover:text-indigo-600' }}"></i>
                    <span x-show="sidebarOpen" class="whitespace-nowrap">Lộ trình học</span>
                </a>

                <!-- 3. Tài liệu (MỚI) -->
                <a href="{{ route('user.documents') }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all group font-semibold {{ $currentRoute === 'user.documents' ? 'bg-indigo-50 text-indigo-600 shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">
                    <i data-lucide="file-text" class="w-5 h-5 flex-shrink-0 {{ $currentRoute === 'user.documents' ? 'text-indigo-600' : 'text-slate-500 group-hover:text-indigo-600' }}"></i>
                    <span x-show="sidebarOpen" class="whitespace-nowrap">Tài liệu</span>
                </a>

                <!-- 4. Lịch học (MỚI) -->
                <a href="{{ route('user.calendars') }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all group font-semibold {{ $currentRoute === 'user.calendars' ? 'bg-indigo-50 text-indigo-600 shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">
                    <i data-lucide="calendar" class="w-5 h-5 flex-shrink-0 {{ $currentRoute === 'user.calendars' ? 'text-indigo-600' : 'text-slate-500 group-hover:text-indigo-600' }}"></i>
                    <span x-show="sidebarOpen" class="whitespace-nowrap">Lịch học</span>
                </a>

                <!-- 6. Kho bài thi (MỚI) -->
                <a href="{{ route('user.exams') }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all group font-semibold {{ $currentRoute === 'user.exams' ? 'bg-indigo-50 text-indigo-600 shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">
                    <i data-lucide="graduation-cap" class="w-5 h-5 flex-shrink-0 {{ $currentRoute === 'user.exams' ? 'text-indigo-600' : 'text-slate-500 group-hover:text-indigo-600' }}"></i>
                    <span x-show="sidebarOpen" class="whitespace-nowrap">Kho bài thi</span>
                </a>
                <a href="{{ route('user.flashcards') }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all group font-semibold {{ $currentRoute === 'user.flashcards' ? 'bg-indigo-50 text-indigo-600 shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">
                    <i data-lucide="graduation-cap" class="w-5 h-5 flex-shrink-0 {{ $currentRoute === 'user.flashcards' ? 'text-indigo-600' : 'text-slate-500 group-hover:text-indigo-600' }}"></i>
                    <span x-show="sidebarOpen" class="whitespace-nowrap">Kho flashcard</span>
                </a>

                <!-- 6. DROPDOWN: Cộng đồng (MỚI) -->
                <div class="w-full">
                    <button @click="expandedMenus.includes('community') ? expandedMenus = expandedMenus.filter(m => m !== 'community') : expandedMenus.push('community')"
                            class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all group font-semibold {{ $isCommunityActive ? 'text-indigo-600 bg-indigo-50/40' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">
                        <div class="flex items-center gap-3">
                            <i data-lucide="message-square" class="w-5 h-5 flex-shrink-0 {{ $isCommunityActive ? 'text-indigo-600' : 'text-slate-500 group-hover:text-indigo-600' }}"></i>
                            <span x-show="sidebarOpen" class="whitespace-nowrap">Cộng đồng</span>
                        </div>
                        <i data-lucide="chevron-down" x-show="sidebarOpen" 
                           class="w-4 h-4 transition-transform duration-200 {{ $isCommunityActive ? 'text-indigo-600' : 'text-slate-400 group-hover:text-indigo-600' }}"
                           :class="expandedMenus.includes('community') ? 'rotate-180' : ''"></i>
                    </button>
                    
                    <!-- Khối Con Cộng đồng -->
                    <div x-show="expandedMenus.includes('community') && sidebarOpen" 
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="pl-12 pr-2 pt-1 pb-2 space-y-1">
                        <a href="{{ route('user.news.index') }}" class="block py-2 px-3 text-sm font-medium rounded-lg {{ $currentRoute === 'user.community.news' ? 'text-indigo-600 font-semibold' : 'text-slate-500 hover:text-indigo-600' }}">Tin tức</a>
                        <a href="#" class="block py-2 px-3 text-sm font-medium rounded-lg {{ $currentRoute === 'user.community.friends' ? 'text-indigo-600 font-semibold' : 'text-slate-500 hover:text-indigo-600' }}">Bạn bè</a>
                    </div>
                </div>

                <!-- 7. Hồ sơ -->
                <a href="{{ route('user.profile') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all group font-semibold {{ $currentRoute === 'user.profile' ? 'bg-indigo-50 text-indigo-600 shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">
                    <i data-lucide="user" class="w-5 h-5 flex-shrink-0 {{ $currentRoute === 'user.profile' ? 'text-indigo-600' : 'text-slate-500 group-hover:text-indigo-600' }}"></i>
                    <span x-show="sidebarOpen" class="whitespace-nowrap">Hồ sơ</span>
                </a>
                 <!-- 8. Gói đăng ký -->
                <a href="{{ route('user.subscriptions') }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all group font-semibold {{ $currentRoute === 'user.subscriptions' ? 'bg-indigo-50 text-indigo-600 shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">
                    <i data-lucide="credit-card" class="w-5 h-5 flex-shrink-0 {{ $currentRoute === 'user.subscriptions' ? 'text-indigo-600' : 'text-slate-500 group-hover:text-indigo-600' }}"></i>
                    <span x-show="sidebarOpen" class="whitespace-nowrap">Gói đăng ký</span>
                </a>
            @else
                <!-- ADMIN MENU (Giữ nguyên cấu trúc của bạn) -->
                <!-- 1. Tổng quan -->
                <a href="{{ route('admin.dashboard') }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all group font-semibold {{ $currentRoute === 'admin.dashboard' ? 'bg-indigo-50 text-indigo-600 shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">
                    <i data-lucide="bar-chart-3" class="w-5 h-5 flex-shrink-0 {{ $currentRoute === 'admin.dashboard' ? 'text-indigo-600' : 'text-slate-500 group-hover:text-indigo-600' }}"></i>
                    <span x-show="sidebarOpen" class="whitespace-nowrap">Tổng quan</span>
                </a>

                <!-- 2. Quản lý Người dùng -->
                <a href="{{ route('admin.users.index') }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all group font-semibold {{ $currentRoute === 'admin.users' ? 'bg-indigo-50 text-indigo-600 shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">
                    <i data-lucide="users" class="w-5 h-5 flex-shrink-0 {{ $currentRoute === 'admin.users' ? 'text-indigo-600' : 'text-slate-500 group-hover:text-indigo-600' }}"></i>
                    <span x-show="sidebarOpen" class="whitespace-nowrap">Người dùng</span>
                </a>

                <!-- 4. Quản lý Tài liệu -->
                <a href="{{ route('admin.documents.index') }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all group font-semibold {{ $currentRoute === 'admin.documents' ? 'bg-indigo-50 text-indigo-600 shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">
                    <i data-lucide="file-text" class="w-5 h-5 flex-shrink-0 {{ $currentRoute === 'admin.documents' ? 'text-indigo-600' : 'text-slate-500 group-hover:text-indigo-600' }}"></i>
                    <span x-show="sidebarOpen" class="whitespace-nowrap">Tài liệu</span>
                </a>

                <!-- 5. Quản lý Tin tức -->
                <a href="{{ route('admin.news.index') }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all group font-semibold {{ $currentRoute === 'admin.news' ? 'bg-indigo-50 text-indigo-600 shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">
                    <i data-lucide="newspaper" class="w-5 h-5 flex-shrink-0 {{ $currentRoute === 'admin.news' ? 'text-indigo-600' : 'text-slate-500 group-hover:text-indigo-600' }}"></i>
                    <span x-show="sidebarOpen" class="whitespace-nowrap">Tin tức</span>
                </a>

                <!-- 6. Quản lý gói đăng ký -->
                <a href="{{ route('admin.subscriptions.index') }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all group font-semibold {{ $currentRoute === 'admin.subscriptions' ? 'bg-indigo-50 text-indigo-600 shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">
                    <i data-lucide="package" class="w-5 h-5 flex-shrink-0 {{ $currentRoute === 'admin.subscriptions' ? 'text-indigo-600' : 'text-slate-500 group-hover:text-indigo-600' }}"></i>
                    <span x-show="sidebarOpen" class="whitespace-nowrap">Gói đăng ký</span>
                </a>

                <a href="{{ route('admin.notifications.index') }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all group font-semibold {{ $currentRoute === 'admin.notifications' ? 'bg-indigo-50 text-indigo-600 shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600' }}">
                    <i data-lucide="bell" class="w-5 h-5 flex-shrink-0 {{ $currentRoute === 'admin.notifications' ? 'text-indigo-600' : 'text-slate-500 group-hover:text-indigo-600' }}"></i>
                    <span x-show="sidebarOpen" class="whitespace-nowrap">Thông báo</span>
                </a>
            @endif
        </nav>

        <!-- Footer / Logout -->
        <div class="px-3 pt-6 border-t border-slate-100">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-3 px-3 py-3 rounded-xl text-slate-500 hover:bg-red-50 hover:text-red-600 transition-all font-medium">
                    <i data-lucide="log-out" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="sidebarOpen" class="whitespace-nowrap">Đăng xuất</span>
                </button>
            </form>
        </div>
    </div>
</aside>