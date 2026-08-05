<script setup lang="ts">
import { ref, onMounted } from 'vue';

const deferredPrompt = ref<any>(null);
const showBanner = ref(false);
const isIos = ref(false);
const showIosInstructions = ref(false);

onMounted(() => {
  // Check if user already dismissed or installed app
  const dismissed = localStorage.getItem('laundryhub_pwa_dismissed');
  const isStandalone = window.matchMedia('(display-mode: standalone)').matches || (window.navigator as any).standalone;

  if (isStandalone || dismissed) {
    return;
  }

  // Detect iOS
  const userAgent = window.navigator.userAgent.toLowerCase();
  isIos.value = /iphone|ipad|ipod/.test(userAgent);

  // Listen for Chrome/Android/Desktop install prompt
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt.value = e;
    showBanner.value = true;
  });

  // Show banner for iOS if not installed
  if (isIos.value && !isStandalone) {
    showBanner.value = true;
  }
});

async function installApp() {
  if (deferredPrompt.value) {
    deferredPrompt.value.prompt();
    const { outcome } = await deferredPrompt.value.userChoice;
    if (outcome === 'accepted') {
      showBanner.value = false;
    }
    deferredPrompt.value = null;
  } else if (isIos.value) {
    showIosInstructions.value = !showIosInstructions.value;
  }
}

function dismissBanner() {
  showBanner.value = false;
  localStorage.setItem('laundryhub_pwa_dismissed', 'true');
}
</script>

<template>
  <Transition
    enter-active-class="transition ease-out duration-300 transform"
    enter-from-class="translate-y-full opacity-0"
    enter-to-class="translate-y-0 opacity-100"
    leave-active-class="transition ease-in duration-200 transform"
    leave-from-class="translate-y-0 opacity-100"
    leave-to-class="translate-y-full opacity-0"
  >
    <div
      v-if="showBanner"
      class="fixed bottom-4 right-4 left-4 sm:left-auto sm:w-96 z-50 p-4 rounded-2xl bg-slate-900/95 border border-sky-500/30 backdrop-blur-xl shadow-2xl shadow-sky-500/10 text-slate-100 space-y-3"
    >
      <div class="flex items-start justify-between gap-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-500 to-cyan-400 flex items-center justify-center text-xl shadow-lg shadow-sky-500/20 shrink-0 font-black text-slate-950">
            🧺
          </div>
          <div>
            <h4 class="font-bold text-sm text-slate-100 flex items-center gap-1.5">
              <span>Install LaundryHub App</span>
              <span class="px-1.5 py-0.5 rounded-md bg-sky-500/20 text-sky-400 text-[10px] uppercase font-mono font-bold">PWA</span>
            </h4>
            <p class="text-xs text-slate-400 mt-0.5">
              Add LaundryHub to your home screen for quick access, order tracking & instant dispatches!
            </p>
          </div>
        </div>
        <button
          @click="dismissBanner"
          class="text-slate-400 hover:text-white text-base p-1 shrink-0 transition-colors"
          title="Dismiss"
        >
          ✕
        </button>
      </div>

      <!-- iOS Step-by-Step Instructions -->
      <div v-if="showIosInstructions" class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-300 space-y-1.5 animate-in fade-in">
        <p class="font-bold text-sky-400">📱 How to Install on iOS (Safari):</p>
        <ol class="list-decimal list-inside space-y-1 text-[11px] text-slate-400">
          <li>Tap the <strong>Share</strong> button at the bottom of Safari.</li>
          <li>Scroll down and tap <strong>Add to Home Screen ➕</strong>.</li>
          <li>Tap <strong>Add</strong> in the top right corner.</li>
        </ol>
      </div>

      <div class="flex items-center gap-2 pt-1">
        <button
          @click="installApp"
          class="flex-1 py-2.5 px-4 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-xs shadow-lg shadow-sky-500/20 hover:scale-[1.02] active:scale-95 transition-all text-center"
        >
          {{ isIos ? '📱 How to Install on iOS' : '📲 Install App Now' }}
        </button>
        <button
          @click="dismissBanner"
          class="py-2.5 px-3 rounded-xl bg-slate-800 text-slate-300 font-semibold text-xs hover:bg-slate-700 transition-colors"
        >
          Later
        </button>
      </div>
    </div>
  </Transition>
</template>
