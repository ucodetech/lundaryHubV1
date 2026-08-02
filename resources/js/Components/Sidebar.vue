<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
  mobileOpen?: boolean;
}>();

const emit = defineEmits(['close-mobile']);

const page = usePage();
const user = computed(() => page.props.auth?.user);
const role = computed(() => user.value?.role);

const roleBadgeColor = computed(() => {
  switch (role.value) {
    case 'super_admin':
      return 'bg-purple-500/10 text-purple-400 border-purple-500/20';
    case 'shop_owner':
      return 'bg-sky-500/10 text-sky-400 border-sky-500/20';
    case 'rider':
      return 'bg-amber-500/10 text-amber-400 border-amber-500/20';
    default:
      return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
  }
});

const formatRoleName = computed(() => {
  switch (role.value) {
    case 'super_admin':
      return 'Super Admin';
    case 'shop_owner':
      return 'Shop Owner';
    case 'rider':
      return 'Delivery Rider';
    default:
      return 'Customer';
  }
});

function closeSidebar() {
  emit('close-mobile');
}
</script>

<template>
  <!-- Mobile Backdrop -->
  <div
    v-if="mobileOpen"
    class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-40 md:hidden transition-opacity"
    @click="closeSidebar"
  ></div>

  <!-- Sidebar Container -->
  <aside
    class="fixed top-0 bottom-0 left-0 z-50 w-64 bg-slate-950/95 border-r border-slate-800/80 flex flex-col min-h-screen transition-transform duration-300 ease-in-out md:static md:translate-x-0"
    :class="mobileOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
  >
    <!-- Brand Header -->
    <div class="h-16 flex items-center justify-between px-5 border-b border-slate-800/80">
      <Link href="/" class="flex items-center gap-3 group" @click="closeSidebar">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-sky-500 to-cyan-400 flex items-center justify-center font-black text-slate-950 text-xl shadow-lg shadow-sky-500/20 group-hover:scale-105 transition-transform">
          L
        </div>
        <div class="flex flex-col">
          <span class="font-bold text-base leading-tight bg-gradient-to-r from-sky-400 to-cyan-300 bg-clip-text text-transparent">
            LaundryHub
          </span>
          <span class="text-[10px] text-slate-500 font-semibold uppercase tracking-wider">Marketplace</span>
        </div>
      </Link>

      <!-- Mobile Close Button -->
      <button
        class="md:hidden p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-900 transition-colors"
        @click="closeSidebar"
        aria-label="Close Sidebar"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- User Mini Profile -->
    <div v-if="user" class="p-4 mx-3 my-3 rounded-xl bg-slate-900/90 border border-slate-800/80 flex items-center gap-3">
      <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-sky-500 to-cyan-400 flex items-center justify-center font-bold text-slate-950 text-sm shadow-md">
        {{ user.first_name?.[0] }}{{ user.last_name?.[0] }}
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-xs font-bold text-slate-200 truncate">{{ user.first_name }} {{ user.last_name }}</p>
        <span class="inline-block px-2 py-0.5 mt-0.5 rounded text-[10px] font-semibold border" :class="roleBadgeColor">
          {{ formatRoleName }}
        </span>
      </div>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 p-3 space-y-1 overflow-y-auto custom-scrollbar">
      <!-- Common Dashboard Link -->
      <Link
        href="/dashboard"
        @click="closeSidebar"
        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all"
        :class="$page.url === '/dashboard' ? 'bg-sky-500/10 text-sky-400 font-semibold border border-sky-500/20 shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80'"
      >
        <span class="text-base">📊</span>
        <span>Dashboard</span>
      </Link>

      <!-- Shop Owner Links -->
      <template v-if="role === 'shop_owner' || role === 'super_admin'">
        <div class="pt-5 pb-1 px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
          Shop Operations
        </div>

        <Link
          href="/shop-admin/categories"
          @click="closeSidebar"
          class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all"
          :class="$page.url.startsWith('/shop-admin/categories') ? 'bg-sky-500/10 text-sky-400 font-semibold border border-sky-500/20 shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80'"
        >
          <span class="text-base">🏷️</span>
          <span>Item Categories</span>
        </Link>

        <Link
          href="/shop-admin/services"
          @click="closeSidebar"
          class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all"
          :class="$page.url.startsWith('/shop-admin/services') ? 'bg-sky-500/10 text-sky-400 font-semibold border border-sky-500/20 shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80'"
        >
          <span class="text-base">🧺</span>
          <span>Services Catalog</span>
        </Link>

        <Link
          href="/shop-admin/pricing"
          @click="closeSidebar"
          class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all"
          :class="$page.url.startsWith('/shop-admin/pricing') ? 'bg-sky-500/10 text-sky-400 font-semibold border border-sky-500/20 shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80'"
        >
          <span class="text-base">💳</span>
          <span>Pricing Matrix</span>
        </Link>
      </template>

      <!-- Rider Links -->
      <template v-if="role === 'rider'">
        <div class="pt-5 pb-1 px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
          Rider Center
        </div>

        <Link
          href="/rider/profile"
          @click="closeSidebar"
          class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all"
          :class="$page.url.startsWith('/rider/profile') ? 'bg-sky-500/10 text-sky-400 font-semibold border border-sky-500/20 shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80'"
        >
          <span class="text-base">🛵</span>
          <span>KYC & Vehicle</span>
        </Link>
      </template>

      <!-- Super Admin Links -->
      <template v-if="role === 'super_admin'">
        <div class="pt-5 pb-1 px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
          Platform Admin
        </div>

        <Link
          href="/admin/shops"
          @click="closeSidebar"
          class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all"
          :class="$page.url.startsWith('/admin/shops') ? 'bg-sky-500/10 text-sky-400 font-semibold border border-sky-500/20 shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80'"
        >
          <span class="text-base">🏪</span>
          <span>Dry Cleaners</span>
        </Link>

        <Link
          href="/admin/riders"
          @click="closeSidebar"
          class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all"
          :class="$page.url.startsWith('/admin/riders') ? 'bg-sky-500/10 text-sky-400 font-semibold border border-sky-500/20 shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80'"
        >
          <span class="text-base">🛵</span>
          <span>Rider Verifications</span>
        </Link>

        <Link
          href="/admin/users"
          @click="closeSidebar"
          class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all"
          :class="$page.url.startsWith('/admin/users') ? 'bg-sky-500/10 text-sky-400 font-semibold border border-sky-500/20 shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80'"
        >
          <span class="text-base">👥</span>
          <span>User Directory</span>
        </Link>
      </template>

      <!-- Account Settings -->
      <div class="pt-5 pb-1 px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
        Account
      </div>
      <Link
        href="/profile"
        @click="closeSidebar"
        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all"
        :class="$page.url.startsWith('/profile') ? 'bg-sky-500/10 text-sky-400 font-semibold border border-sky-500/20 shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80'"
      >
        <span class="text-base">⚙️</span>
        <span>Profile & Security</span>
      </Link>
    </nav>

    <!-- Bottom Actions -->
    <div class="p-4 border-t border-slate-800/80">
      <Link
        href="/logout"
        method="post"
        as="button"
        @click="closeSidebar"
        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500/20 transition-all shadow-sm"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
        <span>Sign Out Account</span>
      </Link>
    </div>
  </aside>
</template>
