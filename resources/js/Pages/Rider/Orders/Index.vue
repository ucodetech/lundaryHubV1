<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps<{
  profile: any;
  activeSubscription?: any;
  availableOrders?: Array<any>;
  myActiveDeliveries?: Array<any>;
}>();

const isVerified = computed(() => {
  return props.profile?.kyc_status === 'approved' || props.profile?.is_verified;
});

const isOnline = computed(() => {
  return !!props.profile?.is_online;
});

const submittingBidOrderId = ref<number | null>(null);
const acceptingOrderId = ref<number | null>(null);
const decliningOrderId = ref<number | null>(null);
const updatingOrderId = ref<number | null>(null);

// Bid form amounts mapped by order id
const bidAmounts = ref<Record<number, number>>({});
const bidNotes = ref<Record<number, string>>({});

// --- Web Audio Synthesizer (Bolt/Uber Style Dispatch Sound) ---
let audioCtx: AudioContext | null = null;
let chimeInterval: any = null;
const isMuted = ref(false);

const playBoltDispatchChime = () => {
  if (isMuted.value) return;
  try {
    const ctx = audioCtx || new (window.AudioContext || (window as any).webkitAudioContext)();
    audioCtx = ctx;

    if (ctx.state === 'suspended') {
      ctx.resume();
    }

    const now = ctx.currentTime;

    // Tone 1: High A5 (880 Hz)
    const osc1 = ctx.createOscillator();
    const gain1 = ctx.createGain();
    osc1.type = 'sine';
    osc1.frequency.setValueAtTime(880, now);
    gain1.gain.setValueAtTime(0.35, now);
    gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.25);
    osc1.connect(gain1);
    gain1.connect(ctx.destination);
    osc1.start(now);
    osc1.stop(now + 0.25);

    // Tone 2: High C6 (1046.5 Hz)
    const osc2 = ctx.createOscillator();
    const gain2 = ctx.createGain();
    osc2.type = 'sine';
    osc2.frequency.setValueAtTime(1046.5, now + 0.15);
    gain2.gain.setValueAtTime(0.45, now + 0.15);
    gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.55);
    osc2.connect(gain2);
    gain2.connect(ctx.destination);
    osc2.start(now + 0.15);
    osc2.stop(now + 0.55);
  } catch (e) {
    console.warn('Audio Context chime error:', e);
  }
};

const hasUnactedDispatches = computed(() => {
  if (!props.availableOrders || props.availableOrders.length === 0) return false;
  return props.availableOrders.some(order => !order.my_bid);
});

const startAudioLoop = () => {
  stopAudioLoop();
  playBoltDispatchChime();
  chimeInterval = setInterval(() => {
    if (isOnline.value && hasUnactedDispatches.value && !isMuted.value) {
      playBoltDispatchChime();
    } else {
      stopAudioLoop();
    }
  }, 2500);
};

const stopAudioLoop = () => {
  if (chimeInterval) {
    clearInterval(chimeInterval);
    chimeInterval = null;
  }
};

watch([isOnline, hasUnactedDispatches, isMuted], ([online, unacted, muted]) => {
  if (online && unacted && !muted) {
    startAudioLoop();
  } else {
    stopAudioLoop();
  }
}, { immediate: true });

const toggleOnline = () => {
  playBoltDispatchChime(); // Warm up AudioContext on user click
  useForm({}).post('/rider/toggle-online', {
    preserveScroll: true,
  });
};

const submitBid = (order: any) => {
  const amount = bidAmounts.value[order.id] || Number(order.delivery_fee);
  const note = bidNotes.value[order.id] || '';

  submittingBidOrderId.value = order.id;
  router.post(`/rider/orders/${order.id}/bid`, { amount, note }, {
    preserveScroll: true,
    onFinish: () => {
      submittingBidOrderId.value = null;
    },
  });
};

const acceptOrderDirectly = (orderId: number) => {
  acceptingOrderId.value = orderId;
  router.post(`/rider/orders/${orderId}/accept`, {}, {
    preserveScroll: true,
    onFinish: () => {
      acceptingOrderId.value = null;
    },
  });
};

const declineOrder = (orderId: number) => {
  decliningOrderId.value = orderId;
  router.post(`/rider/orders/${orderId}/decline`, {}, {
    preserveScroll: true,
    onFinish: () => {
      decliningOrderId.value = null;
    },
  });
};

const updateStatus = (orderId: number, status: string) => {
  updatingOrderId.value = orderId;
  router.put(`/rider/orders/${orderId}/status`, { status }, {
    preserveScroll: true,
    onFinish: () => {
      updatingOrderId.value = null;
    },
  });
};

// Real-time polling timer when online (without triggering full-screen loading spinner)
let pollInterval: any = null;

onMounted(() => {
  pollInterval = setInterval(() => {
    if (isOnline.value) {
      router.reload({
        only: ['availableOrders', 'myActiveDeliveries'],
        preserveScroll: true,
        showProgress: false,
      });
    }
  }, 5000);
});

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval);
  stopAudioLoop();
});
</script>

<template>
  <AppLayout>
    <div class="space-y-8 max-w-6xl mx-auto">
      <!-- Header & Dispatch Toggle -->
      <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xl">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-slate-100">Pickup & Delivery Dispatch Center</h1>
            <span
              class="px-3 py-1 rounded-full text-xs font-bold font-mono border"
              :class="isOnline ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border-amber-500/20'"
            >
              {{ isOnline ? '🟢 ONLINE FOR DISPATCH' : '🟡 OFFLINE' }}
            </span>
          </div>
          <p class="text-xs text-slate-400 mt-1">
            Negotiate delivery fees directly with customers or accept shop-triggered dispatches in your area
          </p>
        </div>

        <div class="flex items-center gap-3">
          <button
            @click="isMuted = !isMuted"
            class="px-3.5 py-2.5 rounded-xl border text-xs font-bold transition-all flex items-center gap-1.5"
            :class="isMuted ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : 'bg-slate-900 text-slate-300 border-slate-700 hover:text-white'"
          >
            <span>{{ isMuted ? '🔕 Muted' : '🔊 Sound On' }}</span>
          </button>

          <button
            @click="toggleOnline"
            class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all shadow-lg flex items-center gap-2"
            :class="isOnline ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40 hover:bg-amber-500/30' : 'bg-gradient-to-r from-emerald-500 to-teal-500 text-slate-950 shadow-emerald-500/20 hover:scale-105'"
          >
            <span>{{ isOnline ? '⏸️ Go Offline' : '⚡ Go Online Now' }}</span>
          </button>
        </div>
      </div>

      <!-- Bolt-Style Audio Dispatch Alert Banner -->
      <div
        v-if="isOnline && hasUnactedDispatches"
        class="bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-600 border border-purple-400/40 rounded-2xl p-5 text-white shadow-2xl animate-pulse flex flex-col sm:flex-row items-center justify-between gap-4"
      >
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center text-2xl animate-bounce shrink-0">
            🔊
          </div>
          <div>
            <h3 class="text-sm font-black uppercase tracking-wider">NEW DISPATCH REQUEST NEARBY!</h3>
            <p class="text-xs text-purple-100 mt-0.5">
              A dry cleaner triggered a pickup request. Propose your fee or accept below to claim it!
            </p>
          </div>
        </div>

        <button
          @click="isMuted = !isMuted"
          class="px-4 py-2.5 rounded-xl bg-white/20 hover:bg-white/30 text-xs font-bold transition-all border border-white/30 shrink-0 flex items-center gap-2 shadow"
        >
          <span>{{ isMuted ? '🔊 Unmute Sound' : '🔕 Silence Chime' }}</span>
        </button>
      </div>

      <!-- KYC Verification Required Alert (If Pending) -->
      <div v-if="!isVerified" class="bg-amber-500/10 border border-amber-500/30 rounded-2xl p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xl">
        <div class="flex items-start gap-4">
          <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-xl shrink-0">
            ⏳
          </div>
          <div>
            <h3 class="text-sm font-bold text-amber-400">Rider Account Verification Pending</h3>
            <p class="text-xs text-slate-300 mt-0.5 max-w-xl leading-relaxed">
              Your rider profile and identity documents are currently being audited by Super Admin. You will start receiving live pickup & delivery dispatch requests as soon as your account is approved.
            </p>
          </div>
        </div>

        <Link
          href="/rider/profile"
          class="px-4 py-2.5 rounded-xl bg-amber-500 text-slate-950 font-bold text-xs shadow-lg shrink-0 hover:scale-105 transition-transform"
        >
          📷 Check KYC Status
        </Link>
      </div>

      <!-- Main Dispatch Center (If Verified) -->
      <template v-else>
        <!-- Offline Warning Banner -->
        <div v-if="!isOnline" class="bg-slate-900/80 border border-slate-800 rounded-2xl p-6 text-center space-y-3 shadow-lg">
          <span class="text-3xl block">🛵</span>
          <h3 class="text-base font-bold text-slate-200">You Are Currently Offline</h3>
          <p class="text-xs text-slate-400 max-w-md mx-auto">
            Toggle your status to <strong class="text-emerald-400">ONLINE</strong> at the top right of this page to view nearby laundry requests and propose delivery fees.
          </p>
        </div>

        <template v-else>
          <!-- Active Deliveries Section -->
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <h2 class="text-sm font-bold text-slate-200 flex items-center gap-2">
                <span>🚀 My Active Deliveries</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] bg-sky-500/10 text-sky-400 border border-sky-500/20 font-mono">
                  {{ myActiveDeliveries?.length || 0 }}
                </span>
              </h2>
            </div>

            <div v-if="!myActiveDeliveries || myActiveDeliveries.length === 0" class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-8 text-center text-slate-400 text-xs">
              No active delivery assignments right now. Check available nearby dispatch requests below!
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div
                v-for="order in myActiveDeliveries"
                :key="order.id"
                class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 border border-sky-500/30 rounded-2xl p-5 shadow-xl space-y-4"
              >
                <div class="flex items-center justify-between border-b border-slate-700/60 pb-3">
                  <div>
                    <div class="flex items-center gap-2">
                      <span class="text-xs font-mono font-bold text-sky-400 block">#{{ order.order_number }}</span>
                      <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border" :class="order.is_return_delivery ? 'bg-sky-500/10 text-sky-300 border-sky-500/20' : 'bg-purple-500/10 text-purple-300 border-purple-500/20'">
                        {{ order.phase_label }}
                      </span>
                    </div>
                    <span class="text-[11px] text-slate-400 mt-0.5 block">{{ order.created_at }}</span>
                  </div>
                  <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-sky-500/10 text-sky-400 border border-sky-500/20">
                    {{ order.status_label }}
                  </span>
                </div>

                <!-- Phase-Aware Origin & Destination Routing -->
                <div class="space-y-2 text-xs">
                  <div class="bg-slate-950/60 p-3 rounded-xl border border-slate-800/80 space-y-1">
                    <span class="text-[10px] text-amber-400 uppercase font-bold block">{{ order.origin_title }}</span>
                    <strong class="text-slate-200 block text-xs">{{ order.origin_name }}</strong>
                    <p class="text-slate-400 text-[11px]">{{ order.origin_address }}</p>
                    <p class="text-sky-400 text-[11px] font-mono">📞 {{ order.origin_phone }}</p>
                  </div>

                  <div class="bg-slate-950/60 p-3 rounded-xl border border-slate-800/80 space-y-1">
                    <span class="text-[10px] text-emerald-400 uppercase font-bold block">{{ order.destination_title }}</span>
                    <strong class="text-slate-200 block text-xs">{{ order.destination_name }}</strong>
                    <p class="text-slate-400 text-[11px]">{{ order.destination_address }}</p>
                    <p class="text-emerald-400 text-[11px] font-mono">📞 {{ order.destination_phone }}</p>
                  </div>
                </div>

                <!-- Status Update Controls -->
                <div class="pt-2 border-t border-slate-700/60 space-y-2">
                  <span class="text-[11px] font-bold text-slate-300 block">Update Delivery Progress:</span>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <!-- Phase 1: Initial Pickup (Customer -> Shop) -->
                    <template v-if="!order.is_return_delivery">
                      <button
                        v-if="order.status === 'pickup_assigned'"
                        @click="updateStatus(order.id, 'garments_picked_up')"
                        :disabled="updatingOrderId === order.id"
                        class="py-2.5 px-3 rounded-xl bg-amber-500/20 border border-amber-500/30 text-amber-300 font-bold text-xs hover:bg-amber-500/30 transition-colors"
                      >
                        🧺 1. Clothes Picked Up
                      </button>
                      <button
                        @click="updateStatus(order.id, 'dropped_off_at_shop')"
                        :disabled="updatingOrderId === order.id"
                        class="col-span-2 py-2.5 px-3 rounded-xl bg-gradient-to-r from-sky-500 to-indigo-500 text-white font-bold text-xs shadow-md hover:scale-[1.02] transition-transform"
                      >
                        🏬 2. Hand Over / Drop Off at Shop
                      </button>
                    </template>

                    <!-- Phase 2: Return Delivery (Shop -> Customer) -->
                    <template v-else>
                      <button
                        v-if="order.status !== 'out_for_delivery' && order.status !== 'completed'"
                        @click="updateStatus(order.id, 'out_for_delivery')"
                        :disabled="updatingOrderId === order.id"
                        class="py-2.5 px-3 rounded-xl bg-sky-500/20 border border-sky-500/30 text-sky-300 font-bold text-xs hover:bg-sky-500/30 transition-colors"
                      >
                        🛵 1. Picked Up (Out for Delivery)
                      </button>
                      <button
                        v-if="order.status !== 'completed'"
                        @click="updateStatus(order.id, 'completed')"
                        :disabled="updatingOrderId === order.id"
                        class="col-span-2 py-2.5 px-3 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 text-slate-950 font-bold text-xs shadow-md hover:scale-[1.02] transition-transform"
                      >
                        ✅ 2. Complete Final Delivery to Customer
                      </button>
                    </template>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Available Nearby Pickup Requests & Fee Negotiator Section -->
          <div class="space-y-4 pt-6 border-t border-slate-800">
            <div class="flex items-center justify-between">
              <h2 class="text-sm font-bold text-purple-300 flex items-center gap-2">
                <span>💬 Negotiate Delivery Fees & Accept Dispatches</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] bg-purple-500/10 text-purple-400 border border-purple-500/20 font-mono">
                  {{ availableOrders?.length || 0 }}
                </span>
              </h2>
              <span class="text-[11px] text-slate-400 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                Shop-Triggered Dispatches
              </span>
            </div>

            <div v-if="!availableOrders || availableOrders.length === 0" class="bg-slate-900/80 border border-slate-800 rounded-2xl p-12 text-center space-y-3 shadow-xl">
              <span class="text-4xl block">✨</span>
              <h3 class="text-base font-bold text-slate-200">No Pending Shop Dispatches Right Now</h3>
              <p class="text-xs text-slate-400 max-w-md mx-auto">
                Dispatches will appear here automatically when nearby dry cleaners trigger a pickup request or mark clothes ready for delivery!
              </p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div
                v-for="order in availableOrders"
                :key="order.id"
                class="bg-slate-900/90 border border-slate-800 hover:border-purple-500/40 rounded-2xl p-5 shadow-xl space-y-4 transition-all"
              >
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                  <div>
                    <div class="flex items-center gap-2">
                      <span class="text-xs font-mono font-bold text-purple-400 block">#{{ order.order_number }}</span>
                      <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border" :class="order.is_return_delivery ? 'bg-sky-500/10 text-sky-300 border-sky-500/20' : 'bg-purple-500/10 text-purple-300 border-purple-500/20'">
                        {{ order.phase_label }}
                      </span>
                    </div>
                    <span class="text-[11px] text-slate-400 mt-0.5 block">{{ order.created_at }}</span>
                  </div>
                  <span class="text-xs font-mono font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-1 rounded-full">
                    Base Fee: ₦{{ order.delivery_fee }}
                  </span>
                </div>

                <!-- Phase-Aware Origin & Destination Routing -->
                <div class="space-y-2 text-xs">
                  <div class="bg-slate-950 p-3 rounded-xl border border-slate-800/60 space-y-1">
                    <span class="text-[10px] text-amber-400 uppercase font-bold block">{{ order.origin_title }}</span>
                    <strong class="text-slate-200 block text-xs">{{ order.origin_name }}</strong>
                    <p class="text-slate-400 text-[11px]">{{ order.origin_address }}</p>
                    <p class="text-sky-400 text-[11px] font-mono">📞 {{ order.origin_phone }}</p>
                  </div>

                  <div class="bg-slate-950 p-3 rounded-xl border border-slate-800/60 space-y-1">
                    <span class="text-[10px] text-emerald-400 uppercase font-bold block">{{ order.destination_title }}</span>
                    <strong class="text-slate-200 block text-xs">{{ order.destination_name }}</strong>
                    <p class="text-slate-400 text-[11px]">{{ order.destination_address }}</p>
                    <p class="text-emerald-400 text-[11px] font-mono">📞 {{ order.destination_phone }}</p>
                  </div>
                </div>

                <!-- My Current Proposal / Status -->
                <div v-if="order.my_bid" class="bg-purple-950/40 border border-purple-500/30 p-3 rounded-xl space-y-1 text-xs">
                  <div class="flex items-center justify-between">
                    <span class="text-[10px] uppercase font-bold text-purple-400">My Negotiated Fee Offer:</span>
                    <span
                      class="px-2 py-0.5 rounded text-[10px] font-bold font-mono uppercase border"
                      :class="order.my_bid.status === 'accepted' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' : order.my_bid.status === 'rejected' ? 'bg-rose-500/20 text-rose-300 border-rose-500/30' : 'bg-amber-500/20 text-amber-300 border-amber-500/30'"
                    >
                      {{ order.my_bid.status === 'pending' ? '⏳ Customer Reviewing' : order.my_bid.status }}
                    </span>
                  </div>
                  <div class="text-base font-black font-mono text-purple-200">
                    ₦{{ Number(order.my_bid.amount).toLocaleString() }}
                  </div>
                  <p v-if="order.my_bid.note" class="text-[11px] text-slate-300 italic">"{{ order.my_bid.note }}"</p>
                </div>

                <!-- Fee Negotiator Input Form -->
                <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 space-y-3">
                  <span class="text-[11px] font-bold text-slate-300 block uppercase tracking-wider">🤝 Offer / Negotiate Fee (₦):</span>
                  
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div class="relative">
                      <span class="absolute left-3 top-2.5 text-xs font-bold text-slate-400">₦</span>
                      <input
                        :value="bidAmounts[order.id] ?? Number(order.delivery_fee)"
                        @input="bidAmounts[order.id] = Number(($event.target as HTMLInputElement).value)"
                        type="number"
                        min="100"
                        placeholder="Fee"
                        class="w-full pl-7 pr-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 font-mono text-xs focus:border-purple-500"
                      />
                    </div>

                    <input
                      :value="bidNotes[order.id] ?? ''"
                      @input="bidNotes[order.id] = ($event.target as HTMLInputElement).value"
                      type="text"
                      placeholder="Note (e.g. ETA 15m)"
                      class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs focus:border-purple-500"
                    />
                  </div>

                  <button
                    @click="submitBid(order)"
                    :disabled="submittingBidOrderId === order.id"
                    class="w-full py-2.5 rounded-xl bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-bold text-xs shadow-lg shadow-purple-500/20 hover:scale-[1.02] transition-transform flex items-center justify-center gap-2 disabled:opacity-60"
                  >
                    <svg v-if="submittingBidOrderId === order.id" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <span>{{ submittingBidOrderId === order.id ? 'Sending Proposal...' : (order.my_bid ? '🔄 Update Proposed Fee' : '💬 Send Delivery Fee Offer') }}</span>
                  </button>
                </div>

                <!-- Action Buttons (Accept vs Decline Request) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  <button
                    @click="acceptOrderDirectly(order.id)"
                    :disabled="acceptingOrderId === order.id"
                    class="py-2.5 px-3 rounded-xl bg-slate-800 border border-slate-700 text-slate-200 font-bold text-xs hover:bg-slate-700 hover:text-white transition-colors flex items-center justify-center gap-1 disabled:opacity-60"
                  >
                    <span>⚡ Accept (₦{{ order.delivery_fee }})</span>
                  </button>

                  <button
                    @click="declineOrder(order.id)"
                    :disabled="decliningOrderId === order.id"
                    class="py-2.5 px-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 font-bold text-xs hover:bg-rose-500/20 transition-colors flex items-center justify-center gap-1 disabled:opacity-60"
                  >
                    <span>🙈 Decline Request</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </template>
      </template>
    </div>
  </AppLayout>
</template>
