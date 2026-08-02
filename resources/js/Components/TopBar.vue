<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';

defineEmits(['toggle-mobile-sidebar']);

const page = usePage();
const user = computed(() => page.props.auth?.user);

// Notifications State
const showNotifications = ref(false);
const notifications = ref([
  {
    id: 1,
    title: 'New Shop Verification Pending',
    message: 'Sparkle Dry Cleaners submitted verification details for review.',
    time: '10 mins ago',
    unread: true,
    type: 'verification',
  },
  {
    id: 2,
    title: 'KYC Document Received',
    message: 'Rider Babajide Salami uploaded driver\'s license document.',
    time: '45 mins ago',
    unread: true,
    type: 'kyc',
  },
  {
    id: 3,
    title: 'System Backup Completed',
    message: 'Scheduled database snapshot completed successfully.',
    time: '2 hours ago',
    unread: true,
    type: 'system',
  },
]);

const unreadCount = computed(() => notifications.value.filter(n => n.unread).length);

function toggleNotifications() {
  showNotifications.value = !showNotifications.value;
}

function markAllRead() {
  notifications.value.forEach(n => (n.unread = false));
}

function handleClickOutside(event: MouseEvent) {
  const target = event.target as HTMLElement;
  if (!target.closest('.notification-container')) {
    showNotifications.value = false;
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
  <header class="h-16 bg-slate-950/80 backdrop-blur-md border-b border-slate-800/80 px-4 md:px-6 flex items-center justify-between sticky top-0 z-30">
    <!-- Left: Mobile Toggle & Welcome Message -->
    <div class="flex items-center gap-3">
      <!-- Mobile Sidebar Toggle -->
      <button
        class="md:hidden p-2 rounded-xl text-slate-400 hover:text-slate-100 hover:bg-slate-900 border border-slate-800 transition-colors"
        @click="$emit('toggle-mobile-sidebar')"
        aria-label="Toggle Navigation"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>

      <div>
        <h2 class="text-xs md:text-sm font-medium text-slate-400">
          Welcome back, <span class="text-sky-400 font-bold">{{ user?.first_name || 'User' }}</span> 👋
        </h2>
      </div>
    </div>

    <!-- Right: Header Controls -->
    <div class="flex items-center gap-3 md:gap-4">
      <!-- Notification Icon Dropdown -->
      <div class="relative notification-container">
        <button
          @click="toggleNotifications"
          class="relative p-2 rounded-xl text-slate-400 hover:text-slate-100 hover:bg-slate-900 border border-slate-800/80 transition-colors"
          aria-label="Notifications"
        >
          <!-- Bell Icon -->
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 01-6 0v-1m6 0H9" />
          </svg>

          <!-- Unread Badge -->
          <span
            v-if="unreadCount > 0"
            class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-sky-500 text-slate-950 font-extrabold text-[10px] flex items-center justify-center shadow-lg shadow-sky-500/50 animate-pulse"
          >
            {{ unreadCount }}
          </span>
        </button>

        <!-- Dropdown Menu -->
        <div
          v-if="showNotifications"
          class="absolute right-0 mt-2 w-80 md:w-96 bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden z-50 animate-in fade-in slide-in-from-top-2 duration-150"
        >
          <!-- Header -->
          <div class="p-4 border-b border-slate-800/80 flex items-center justify-between bg-slate-950/50">
            <div class="flex items-center gap-2">
              <span class="font-bold text-xs text-slate-200">Notifications</span>
              <span v-if="unreadCount > 0" class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20">
                {{ unreadCount }} new
              </span>
            </div>
            <button
              v-if="unreadCount > 0"
              @click="markAllRead"
              class="text-[11px] font-semibold text-sky-400 hover:text-sky-300 transition-colors"
            >
              Mark all read
            </button>
          </div>

          <!-- List -->
          <div class="max-h-80 overflow-y-auto divide-y divide-slate-800/60 custom-scrollbar">
            <div
              v-for="item in notifications"
              :key="item.id"
              class="p-4 hover:bg-slate-800/40 transition-colors flex items-start gap-3"
              :class="item.unread ? 'bg-sky-500/5' : ''"
            >
              <div class="w-8 h-8 rounded-lg bg-slate-800 border border-slate-700/80 flex items-center justify-center text-xs shrink-0 mt-0.5">
                <span v-if="item.type === 'verification'">🏪</span>
                <span v-else-if="item.type === 'kyc'">🪪</span>
                <span v-else>⚡</span>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2 mb-1">
                  <p class="text-xs font-semibold text-slate-200 truncate">{{ item.title }}</p>
                  <span class="text-[10px] text-slate-500 shrink-0">{{ item.time }}</span>
                </div>
                <p class="text-[11px] text-slate-400 leading-relaxed">{{ item.message }}</p>
              </div>
            </div>

            <div v-if="notifications.length === 0" class="p-8 text-center text-xs text-slate-500">
              No notifications yet
            </div>
          </div>
        </div>
      </div>

      <div class="h-5 w-px bg-slate-800"></div>

      <!-- Quick Action Buttons -->
      <Link
        href="/profile"
        class="hidden sm:inline-flex px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-300 hover:text-slate-100 hover:bg-slate-900 border border-slate-800 transition-all"
      >
        Settings
      </Link>

      <Link
        href="/logout"
        method="post"
        as="button"
        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500/20 transition-all shadow-sm"
      >
        Logout
      </Link>
    </div>
  </header>
</template>
