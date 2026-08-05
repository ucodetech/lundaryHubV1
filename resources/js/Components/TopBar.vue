<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import NotificationDropdown from '@/Components/NotificationDropdown.vue';

defineEmits(['toggle-mobile-sidebar']);

const page = usePage();
const user = computed(() => page.props.auth?.user);
</script>

<template>
  <header class="h-16 bg-slate-950/80 backdrop-blur-md border-b border-slate-800/80 px-4 md:px-6 flex items-center justify-between sticky top-0 z-30">
    <!-- Left: Mobile Toggle & Welcome Message -->
    <div class="flex items-center gap-2.5 sm:gap-3">
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

      <Link href="/dashboard" class="md:hidden flex items-center shrink-0">
        <img
          src="/images/logo.png"
          alt="LaundryHub"
          class="w-8 h-8 rounded-xl border border-sky-500/30 object-cover shadow-sm"
        />
      </Link>

      <div>
        <h2 class="text-xs md:text-sm font-medium text-slate-400 truncate max-w-[130px] sm:max-w-none">
          Welcome, <span class="text-sky-400 font-bold">{{ user?.first_name || 'User' }}</span> 👋
        </h2>
      </div>
    </div>

    <!-- Right: Header Controls -->
    <div class="flex items-center gap-3 md:gap-4">
      <!-- Real-Time Notification Dropdown -->
      <NotificationDropdown />

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
