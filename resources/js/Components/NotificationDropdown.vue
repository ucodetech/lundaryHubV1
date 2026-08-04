<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const isOpen = ref(false);
const notifications = ref<any[]>([]);
const unreadCount = ref(0);
let pollInterval: any = null;

const fetchNotifications = async () => {
  try {
    const res = await axios.get('/notifications/unread');
    if (res.data) {
      notifications.value = res.data.notifications || [];
      unreadCount.value = res.data.unreadCount || 0;
    }
  } catch (e) {
    // Silent fail if unauthenticated
  }
};

const markAllRead = async () => {
  try {
    await axios.post('/notifications/read-all');
    unreadCount.value = 0;
    notifications.value.forEach(n => n.is_read = true);
  } catch (e) {
    console.error(e);
  }
};

const handleClick = async (n: any) => {
  isOpen.value = false;
  if (!n.is_read) {
    n.is_read = true;
    if (unreadCount.value > 0) unreadCount.value--;
    try {
      await axios.post(`/notifications/${n.id}/read`);
    } catch (e) {}
  }
  if (n.link) {
    router.visit(n.link);
  }
};

onMounted(() => {
  fetchNotifications();
  pollInterval = setInterval(fetchNotifications, 15000); // poll every 15s
});

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval);
});
</script>

<template>
  <div class="relative">
    <button
      @click="isOpen = !isOpen"
      class="relative p-2 rounded-xl bg-slate-800/80 border border-slate-700 text-slate-300 hover:text-white hover:bg-slate-800 transition-all focus:outline-none"
    >
      <span class="text-base">🔔</span>
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 px-1.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-500 text-white shadow-lg shadow-rose-500/50 animate-pulse"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown Panel -->
    <div
      v-if="isOpen"
      class="absolute right-0 mt-2 w-80 sm:w-96 bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl z-50 overflow-hidden space-y-0"
    >
      <div class="p-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/60">
        <div class="flex items-center gap-2">
          <span class="text-sm font-bold text-slate-100">Notifications</span>
          <span v-if="unreadCount > 0" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-500/20 text-sky-400">
            {{ unreadCount }} new
          </span>
        </div>

        <button
          v-if="unreadCount > 0"
          @click="markAllRead"
          class="text-[11px] font-bold text-sky-400 hover:text-sky-300 transition-all"
        >
          ✓ Mark all as read
        </button>
      </div>

      <div class="max-h-80 overflow-y-auto divide-y divide-slate-800/60 custom-scrollbar">
        <div
          v-for="n in notifications"
          :key="n.id"
          @click="handleClick(n)"
          class="p-3.5 hover:bg-slate-800/50 transition-colors cursor-pointer space-y-1"
          :class="!n.is_read ? 'bg-sky-950/20' : ''"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-200" :class="!n.is_read ? 'text-sky-300' : ''">
              {{ n.title }}
            </span>
            <span class="text-[10px] text-slate-500 font-mono">
              {{ new Date(n.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
            </span>
          </div>
          <p class="text-xs text-slate-400 line-clamp-2">{{ n.message }}</p>
        </div>

        <div v-if="notifications.length === 0" class="p-8 text-center text-slate-500 text-xs">
          No notifications yet.
        </div>
      </div>
    </div>
  </div>
</template>
