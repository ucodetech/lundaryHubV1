<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
  plan: any;
  activeSubscription?: any;
}>();

const isPassActive = computed(() => {
  if (!props.activeSubscription) return false;
  return new Date(props.activeSubscription.ends_at) > new Date();
});

const isLoading = ref(false);

const goToCheckout = () => {
  if (!props.plan) return;
  isLoading.value = true;
  window.location.href = `/subscriptions/checkout/${props.plan.id}`;
};
</script>

<template>
  <AppLayout>
    <div class="max-w-2xl mx-auto space-y-6">
      <!-- Header -->
      <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-3">
        <div class="flex items-center justify-between">
          <h1 class="text-2xl font-bold text-slate-100">Rider Monthly Dispatch Pass</h1>
          <span
            class="px-3 py-1 rounded-full text-xs font-mono font-bold border"
            :class="isPassActive ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border-amber-500/20'"
          >
            {{ isPassActive ? '🟢 PASS ACTIVE' : '🔴 PASS EXPIRED / INACTIVE' }}
          </span>
        </div>
        <p class="text-xs text-slate-400">
          Pay flat monthly subscription pass fee to accept unlimited customer laundry delivery dispatches
        </p>
      </div>

      <!-- Pass Card -->
      <div class="bg-gradient-to-br from-slate-900 via-slate-900 to-slate-800 border border-slate-700/80 rounded-3xl p-8 shadow-2xl space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <span class="text-xs font-bold text-amber-400 uppercase tracking-wider block">Monthly Access Fee</span>
            <h2 class="text-4xl font-black text-slate-100 font-mono mt-1">
              ₦{{ Number(plan?.price || 2000).toLocaleString() }}
              <span class="text-xs font-normal text-slate-400">/ 30 Days</span>
            </h2>
          </div>
          <span class="text-5xl">🛵</span>
        </div>

        <div class="bg-slate-950/80 p-4 rounded-xl border border-slate-800 space-y-2 text-xs">
          <div class="flex justify-between text-slate-300">
            <span>Pass Status:</span>
            <strong :class="isPassActive ? 'text-emerald-400' : 'text-amber-400'">
              {{ isPassActive ? 'Active & Ready for Dispatch' : 'Pass Required to Go Online' }}
            </strong>
          </div>

          <div v-if="activeSubscription" class="flex justify-between text-slate-400 font-mono pt-1 border-t border-slate-800">
            <span>Expires On:</span>
            <span class="text-sky-400 font-bold">{{ new Date(activeSubscription.ends_at).toLocaleDateString() }}</span>
          </div>
        </div>

        <!-- Benefits Checklist -->
        <div class="space-y-2 pt-2">
          <h3 class="text-xs font-bold text-slate-300 uppercase">Rider Pass Benefits:</h3>
          <ul class="text-xs text-slate-300 space-y-2">
            <li class="flex items-center gap-2">
              <span class="text-emerald-400 font-bold">✓</span>
              <span>Unlimited customer laundry pickup and delivery requests</span>
            </li>
            <li class="flex items-center gap-2">
              <span class="text-emerald-400 font-bold">✓</span>
              <span>Keep 100% of your delivery trip earnings</span>
            </li>
            <li class="flex items-center gap-2">
              <span class="text-emerald-400 font-bold">✓</span>
              <span>Priority dispatch matching based on your location</span>
            </li>
          </ul>
        </div>

        <!-- Action Button -->
        <button
          @click="goToCheckout"
          :disabled="isLoading || !plan"
          class="w-full py-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 text-slate-950 font-bold text-sm shadow-xl shadow-emerald-500/20 hover:scale-105 transition-transform flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:scale-100"
        >
          <svg v-if="isLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          <span v-if="!isLoading">💳</span>
          <span>{{ isLoading ? 'Redirecting to Paystack...' : (isPassActive ? 'Extend Pass (₦' + Number(plan?.price || 2000).toLocaleString() + ' via Paystack)' : 'Pay Rider Pass (₦' + Number(plan?.price || 2000).toLocaleString() + ' via Paystack)') }}</span>
        </button>
      </div>
    </div>
  </AppLayout>
</template>
