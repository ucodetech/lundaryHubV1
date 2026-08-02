<script setup lang="ts">
import Footer from '@/Components/Footer.vue';
import Sidebar from '@/Components/Sidebar.vue';
import TopBar from '@/Components/TopBar.vue';
import GlobalLoader from '@/Components/GlobalLoader.vue';
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const mobileOpen = ref(false);

function toggleMobileSidebar() {
  mobileOpen.value = !mobileOpen.value;
}

function closeMobileSidebar() {
  mobileOpen.value = false;
}
</script>

<template>
  <div class="flex min-h-screen bg-slate-900 text-slate-100 font-sans selection:bg-sky-500 selection:text-slate-950">
    <!-- Central Loader Overlay -->
    <GlobalLoader />

    <!-- Sidebar Navigation -->
    <Sidebar :mobile-open="mobileOpen" @close-mobile="closeMobileSidebar" />

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 min-h-screen">
      <!-- Top Bar Header -->
      <TopBar @toggle-mobile-sidebar="toggleMobileSidebar" />

      <!-- Flash Banner Messages -->
      <div v-if="flashSuccess" class="m-6 mb-0 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-medium flex items-center gap-3 animate-in fade-in">
        <span class="text-base">✓</span>
        <span>{{ flashSuccess }}</span>
      </div>

      <div v-if="flashError" class="m-6 mb-0 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm font-medium flex items-center gap-3 animate-in fade-in">
        <span class="text-base">✕</span>
        <span>{{ flashError }}</span>
      </div>

      <!-- Page Content -->
      <main class="flex-1 p-4 sm:p-6 md:p-8">
        <slot />
      </main>

      <!-- App Footer -->
      <Footer />
    </div>
  </div>
</template>
