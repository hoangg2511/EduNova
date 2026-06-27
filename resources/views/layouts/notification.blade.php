{{-- ════════════════════════════════════════════════════════════
     NOTIFICATION DROPDOWN + DETAIL MODAL — gắn vào header.blade.php
     Thay thế nguyên khối Notification cũ trong header
════════════════════════════════════════════════════════════ --}}

<div class="relative" x-data="notificationDropdown()" x-init="init()" @click.away="open = false">

    {{-- Bell button --}}
    <button @click="open = !open; if (open) fetchNotifications()"
        class="relative w-[34px] h-[34px] rounded-xl border border-slate-200 flex items-center
               justify-center text-slate-500 hover:bg-slate-100 transition-all">
        <i data-lucide="bell" class="w-4 h-4"></i>
        <span x-show="unreadCount > 0"
            class="absolute top-1.5 right-1.5 w-1.5 h-1.5 rounded-full bg-red-500
                   border-2 border-white"></span>
    </button>

    {{-- Dropdown panel --}}
    <div x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 top-full mt-2 w-[380px] bg-white rounded-2xl border border-slate-200
               shadow-xl z-50 overflow-hidden"
        style="display:none;">

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3.5 border-b border-slate-100">
            <p class="text-sm font-bold text-slate-900">Thông báo</p>
            <button @click="markAllRead()"
                x-show="unreadCount > 0"
                class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                Đánh dấu đã đọc
            </button>
        </div>

        {{-- Filter tabs --}}
        <div class="flex gap-1.5 px-4 py-2.5 border-b border-slate-100 overflow-x-auto">
            <template x-for="tab in tabs" :key="tab.key">
                <button @click="activeTab = tab.key"
                    class="shrink-0 text-xs font-semibold px-3 py-1.5 rounded-full transition-all"
                    :class="activeTab === tab.key
                        ? 'bg-slate-900 text-white'
                        : 'bg-slate-50 text-slate-500 hover:bg-slate-100'"
                    x-text="tab.label">
                </button>
            </template>
        </div>

        {{-- List --}}
        <div class="max-h-[380px] overflow-y-auto">

            {{-- Loading --}}
            <div x-show="loading" class="py-10 flex flex-col items-center gap-2">
                <svg class="w-5 h-5 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <p class="text-xs text-slate-400">Đang tải...</p>
            </div>

            {{-- Empty state --}}
            <div x-show="!loading && filteredNotifications.length === 0"
                class="py-12 flex flex-col items-center gap-2 px-6 text-center">
                <div class="w-10 h-10 rounded-2xl bg-slate-50 flex items-center justify-center">
                    <i data-lucide="bell-off" class="w-5 h-5 text-slate-300"></i>
                </div>
                <p class="text-sm font-semibold text-slate-600">Chưa có thông báo</p>
                <p class="text-xs text-slate-400">Thông báo mới sẽ xuất hiện ở đây</p>
            </div>

            {{-- Items — giờ là <button>, click mở modal thay vì điều hướng --}}
            <template x-for="notif in filteredNotifications" :key="notif.id">
                <button type="button"
                    @click="openDetail(notif)"
                    class="w-full text-left flex gap-3 px-4 py-3 border-b border-slate-50 last:border-0
                           hover:bg-slate-50 transition-colors cursor-pointer"
                    :class="!notif.read_at ? 'bg-indigo-50/40' : ''">

                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                        :class="iconBg(notif.type)">
                        <i :data-lucide="iconName(notif.type)"
                           class="w-4 h-4"
                           :class="iconColor(notif.type)"></i>
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-semibold leading-snug"
                            :class="!notif.read_at ? 'text-slate-900' : 'text-slate-500'"
                            x-text="notif.title"></p>
                        <p class="text-xs mt-0.5 leading-relaxed line-clamp-2"
                            :class="!notif.read_at ? 'text-slate-500' : 'text-slate-400'"
                            x-text="notif.body"></p>
                        <p class="text-[11px] text-slate-300 mt-1" x-text="timeAgo(notif.created_at)"></p>
                    </div>

                    <div x-show="!notif.read_at"
                        class="w-1.5 h-1.5 rounded-full bg-indigo-500 shrink-0 mt-1"></div>
                </button>
            </template>

        </div>

        {{-- Footer --}}
        <!-- <div class="px-4 py-2.5 border-t border-slate-100 text-center">
            <a href="{{ route('user.notifications.index') }}"
                class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                Xem tất cả thông báo
            </a>
        </div> -->
    </div>

    {{-- ═══════════════════════════════════════════════
         DETAIL MODAL — hiển thị nội dung đầy đủ khi click
    ═══════════════════════════════════════════════ --}}
    <template x-teleport="body">
    <div x-show="detailOpen"
        x-cloak
        class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
        style="display:none;">
 
        {{-- Backdrop --}}
        <div x-show="detailOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="detailOpen = false"
            class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
 
        {{-- Modal box --}}
        <div x-show="detailOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden">
 
            <template x-if="activeNotif">
                <div>
                    {{-- Header --}}
                    <div class="flex items-start gap-3 px-6 pt-6 pb-4">
                        <div class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0"
                            :class="iconBg(activeNotif.type)">
                            <i :data-lucide="iconName(activeNotif.type)"
                               class="w-5 h-5"
                               :class="iconColor(activeNotif.type)"></i>
                        </div>
                        <div class="flex-1 min-w-0 pt-1">
                            <p class="text-base font-bold text-slate-900 leading-snug" x-text="activeNotif.title"></p>
                            <p class="text-xs text-slate-400 mt-1" x-text="timeAgo(activeNotif.created_at)"></p>
                        </div>
                        <button @click="detailOpen = false"
                            class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400
                                   hover:bg-slate-100 hover:text-slate-600 transition-all shrink-0">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
 
                    {{-- Body — nội dung đầy đủ, không bị cắt --}}
                    <div class="px-6 pb-6">
                        <div class="bg-slate-50 rounded-2xl p-4">
                            <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line" x-text="activeNotif.body"></p>
                        </div>
 
                        {{-- Footer actions --}}
                        <div class="flex gap-2 mt-5">
                            <button @click="detailOpen = false"
                                class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold
                                       text-slate-600 hover:bg-slate-50 transition-all">
                                Đóng
                            </button>
                            
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>
 
</div>

{{-- Script — đặt trong @once @push('scripts') ở layout chính nếu chưa có --}}
@once
@push('scripts')
<script>
function notificationDropdown() {
    return {
        open: false,
        detailOpen: false,
        activeNotif: null,
        loading: false,
        notifications: [],
        unreadCount: 0,
        activeTab: 'all',
        tabs: [
            { key: 'all',     label: 'Tất cả' },
            { key: 'schedule_reminder', label: 'Lịch học' },
            { key: 'exam_reminder',     label: 'Bài thi' },
            { key: 'system',  label: 'Hệ thống' },
        ],

        get filteredNotifications() {
            if (this.activeTab === 'all') return this.notifications;
            return this.notifications.filter(n => n.type === this.activeTab);
        },

        init() {
            this.fetchUnreadCount();
            setInterval(() => this.fetchUnreadCount(), 30000);
        },

        async fetchUnreadCount() {
            try {
                const res  = await fetch('/user/notifications/unread-count', { credentials: 'same-origin' });
                const data = await res.json();
                this.unreadCount = data.count ?? 0;
            } catch (err) {
                console.warn('Không thể tải số thông báo chưa đọc:', err);
            }
        },

        async fetchNotifications() {
            this.loading = true;
            try {
                const res  = await fetch('/user/notifications', { credentials: 'same-origin' });
                const data = await res.json();
                this.notifications = data.notifications ?? [];
                this.$nextTick(() => lucide.createIcons());
            } catch (err) {
                console.warn('Không thể tải thông báo:', err);
            } finally {
                this.loading = false;
            }
        },

        // ── Mở modal chi tiết + đóng dropdown + đánh dấu đã đọc ──
        openDetail(notif) {
            this.activeNotif = notif;
            this.detailOpen  = true;
            this.open        = false;   
            this.markRead(notif);
            this.$nextTick(() => lucide.createIcons());
        },

        async markRead(notif) {
            if (notif.read_at) return;
            notif.read_at = new Date().toISOString();
            this.unreadCount = Math.max(0, this.unreadCount - 1);

            try {
                await fetch(`/user/notifications/${notif.id}/read`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
            } catch (err) {
                console.warn('Không thể đánh dấu đã đọc:', err);
            }
        },

        async markAllRead() {
            this.notifications.forEach(n => n.read_at = n.read_at || new Date().toISOString());
            this.unreadCount = 0;

            try {
                await fetch('/user/notifications/read-all', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
            } catch (err) {
                console.warn('Không thể đánh dấu tất cả đã đọc:', err);
            }
        },

        iconName(type) {
            return {
                schedule_reminder: 'calendar',
                exam_reminder:     'clock',
                ai_result:         'sparkles',
                streak:            'flame',
                system:            'megaphone',
            }[type] ?? 'bell';
        },

        iconBg(type) {
            return {
                schedule_reminder: 'bg-indigo-100',
                exam_reminder:     'bg-amber-100',
                ai_result:         'bg-violet-100',
                streak:            'bg-orange-100',
                system:            'bg-slate-100',
            }[type] ?? 'bg-slate-100';
        },

        iconColor(type) {
            return {
                schedule_reminder: 'text-indigo-600',
                exam_reminder:     'text-amber-600',
                ai_result:         'text-violet-600',
                streak:            'text-orange-600',
                system:            'text-slate-500',
            }[type] ?? 'text-slate-500';
        },

        timeAgo(dateStr) {
            const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
            if (diff < 60)    return 'Vừa xong';
            if (diff < 3600)  return Math.floor(diff / 60) + ' phút trước';
            if (diff < 86400) return Math.floor(diff / 3600) + ' giờ trước';
            if (diff < 604800)return Math.floor(diff / 86400) + ' ngày trước';
            return new Date(dateStr).toLocaleDateString('vi-VN');
        },
    };
}
</script>
@endpush
@endonce