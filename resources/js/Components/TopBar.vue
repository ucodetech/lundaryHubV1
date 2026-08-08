<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import NotificationDropdown from '@/Components/NotificationDropdown.vue';

const isDark = ref(true);

onMounted(() => {
  isDark.value = document.documentElement.classList.contains('dark');
});

const toggleTheme = () => {
  isDark.value = !isDark.value;
  if (isDark.value) {
    document.documentElement.classList.add('dark');
    localStorage.theme = 'dark';
  } else {
    document.documentElement.classList.remove('dark');
    localStorage.theme = 'light';
  }
};

defineEmits(['toggle-mobile-sidebar']);

const page = usePage();
const user = computed(() => page.props.auth?.user);
</script>

<template>
  <header class="h-16 bg-gray-50 dark:bg-slate-950/80 backdrop-blur-md border-b border-gray-200 dark:border-slate-800/80 px-4 md:px-6 flex items-center justify-between sticky top-0 z-30">
    <!-- Left: Mobile Toggle & Welcome Message -->
    <div class="flex items-center gap-2.5 sm:gap-3">
      <!-- Mobile Sidebar Toggle -->
      <button
        class="md:hidden p-2 rounded-xl text-gray-500 dark:text-slate-400 hover:text-slate-100 hover:bg-slate-900 border border-gray-200 dark:border-slate-800 transition-colors"
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
        <h2 class="text-xs md:text-sm font-medium text-gray-500 dark:text-slate-400 truncate max-w-[130px] sm:max-w-none">
          Welcome, <span class="text-sky-400 font-bold">{{ user?.first_name || 'User' }}</span> 👋
        </h2>
      </div>
    </div>

    <!-- Right: Header Controls -->
    <div class="flex items-center gap-3 md:gap-4">
      <!-- Theme Toggle Button -->
      <button 
        @click="toggleTheme" 
        class="p-2 rounded-xl text-gray-500 dark:text-slate-400 hover:text-gray-900 hover:bg-gray-100 dark:hover:text-slate-100 dark:hover:bg-slate-900 border border-gray-200 dark:border-slate-800 transition-colors flex items-center justify-center"
        aria-label="Toggle Dark Mode"
      >
        <!-- Sun Icon for Light Mode -->
        <svg v-if="isDark" class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
        <!-- Moon Icon for Dark Mode -->
        <svg v-else class="w-5 h-5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
        </svg>
      </button>

      <!-- Real-Time Notification Dropdown -->
      <NotificationDropdown />

      <div class="h-5 w-px bg-gray-100 dark:bg-slate-800"></div>

      <!-- Quick Action Buttons -->
      <Link
        href="/profile"
        class="hidden sm:inline-flex px-3 py-1.5 rounded-xl text-xs font-semibold text-gray-700 dark:text-slate-300 hover:text-slate-100 hover:bg-slate-900 border border-gray-200 dark:border-slate-800 transition-all"
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
