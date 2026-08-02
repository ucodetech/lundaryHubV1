<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

const isLoading = ref(false);

let removeStartEventListener: (() => void) | null = null;
let removeFinishEventListener: (() => void) | null = null;

onMounted(() => {
  removeStartEventListener = router.on('start', () => {
    isLoading.value = true;
  });

  removeFinishEventListener = router.on('finish', () => {
    isLoading.value = false;
  });
});

onUnmounted(() => {
  if (removeStartEventListener) removeStartEventListener();
  if (removeFinishEventListener) removeFinishEventListener();
});
</script>

<template>
  <Transition
    enter-active-class="transition duration-200 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition duration-150 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="isLoading"
      class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-slate-950/75 backdrop-blur-sm pointer-events-auto"
    >
      <div class="relative flex flex-col items-center space-y-4 bg-slate-900/90 border border-slate-800 p-8 rounded-3xl shadow-2xl">
        <!-- Glowing Pulse Ring -->
        <div class="relative flex items-center justify-center">
          <div class="w-16 h-16 rounded-full border-4 border-sky-500/20 border-t-sky-400 animate-spin"></div>
          <div class="absolute w-8 h-8 rounded-xl bg-gradient-to-tr from-sky-500 to-cyan-400 flex items-center justify-center font-black text-slate-950 text-xs shadow-lg animate-pulse">
            L
          </div>
        </div>

        <div class="text-center space-y-1">
          <p class="text-sm font-bold text-slate-100 tracking-wide">Processing Request...</p>
          <p class="text-xs text-slate-400">Please wait a moment while LaundryHub connects</p>
        </div>
      </div>
    </div>
  </Transition>
</template>
