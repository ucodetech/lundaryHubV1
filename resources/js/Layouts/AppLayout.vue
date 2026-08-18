<script setup lang="ts">
import Footer from '@/Components/Footer.vue';
import Sidebar from '@/Components/Sidebar.vue';
import TopBar from '@/Components/TopBar.vue';
import GlobalLoader from '@/Components/GlobalLoader.vue';
import PwaInstallBanner from '@/Components/PwaInstallBanner.vue';
import PushManager from '@/Components/PushManager.vue';
import { usePage, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import customSwal from '@/Utils/swal';
import { UserRole } from '@/Enums/UserRole';

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const flashWarning = computed(() => page.props.flash?.warning);
const flashInfo = computed(() => page.props.flash?.info);

const mobileOpen = ref(false);

function toggleMobileSidebar() {
  mobileOpen.value = !mobileOpen.value;
}

function closeMobileSidebar() {
  mobileOpen.value = false;
}

watch(
  () => page.props.flash,
  (flash: any) => {
    if (!flash) return;

    if (flash.success) {
      customSwal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Success',
        text: flash.success,
        showConfirmButton: false,
        timer: 4500,
        timerProgressBar: true,
      });
    }

    if (flash.error) {
      customSwal.fire({
        toast: true,
        position: 'top-end',
        icon: 'error',
        title: 'Notice',
        text: flash.error,
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true,
      });
    }

    if (flash.warning) {
      customSwal.fire({
        toast: true,
        position: 'top-end',
        icon: 'warning',
        title: 'Warning',
        text: flash.warning,
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true,
      });
    }

    if (flash.info) {
      customSwal.fire({
        toast: true,
        position: 'top-end',
        icon: 'info',
        title: 'Information',
        text: flash.info,
        showConfirmButton: false,
        timer: 4500,
        timerProgressBar: true,
      });
    }
  },
  { immediate: true, deep: true }
);

onMounted(() => {
  if (typeof window !== 'undefined' && window.Echo) {
    // 1. Listen for Real-Time Admin Notifications (Super Admin & Support)
    if (currentUser.value && (currentUser.value.role === UserRole.SUPER_ADMIN || currentUser.value.role === UserRole.SUPPORT)) {
      window.Echo.channel('admin-notifications')
        .listen('.admin.notification', (data: any) => {
          const title = data.notificationData?.title || '🔔 Real-time Admin Notification';
          const message = data.notificationData?.message || 'New system event registered.';
          
          customSwal.fire({
            title: title,
            text: message,
            icon: 'info',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 6000,
            timerProgressBar: true,
          });

          // Also trigger silent Inertia reload to keep lists fresh
          router.reload({ preserveScroll: true });
        });
    }

    // 2. Listen for Approval Reload Signal on user's channel
    if (currentUser.value?.id) {
      window.Echo.channel(`user.${currentUser.value.id}`)
        .listen('.user.approved', (data: any) => {
          const payload = data.approvalData || {};
          customSwal.fire({
            title: payload.title || '🎉 Account Approved!',
            text: payload.message || 'Your status has been updated.',
            icon: 'success',
            confirmButtonText: 'Great!',
          }).then(() => {
            // Auto reload current page/dashboard
            router.reload();
          });
        });
    }
  }
});

onUnmounted(() => {
  if (typeof window !== 'undefined' && window.Echo) {
    if (currentUser.value?.id) {
      window.Echo.leaveChannel(`user.${currentUser.value.id}`);
    }
  }
});
</script>

<template>
  <div class="flex min-h-screen bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 font-sans selection:bg-sky-500 selection:text-slate-950">
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

    <!-- Progressive Web App (PWA) Install Prompt -->
    <PwaInstallBanner />

    <!-- Web Push Notifications Registration -->
    <PushManager />
  </div>
</template>
