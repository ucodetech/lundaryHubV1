<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref } from 'vue';

const props = defineProps<{
  shop: any;
  plans: Array<any>;
  activeSubscription?: any;
}>();

const isSubActive = computed(() => {
  if (!props.activeSubscription) return false;
  return new Date(props.activeSubscription.ends_at) > new Date();
});

const loadingPlanId = ref<number | null>(null);

const subscribeToPlan = (plan: any) => {
  loadingPlanId.value = plan.id;
  window.location.href = `/subscriptions/checkout/${plan.id}`;
};
</script>

<template>
  <AppLayout>
    <div class="space-y-8">
      <!-- Header & Active Banner -->
      <div class="bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Shop Subscription & Billing</h1>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">
              Select or upgrade your dry cleaning storefront monthly subscription plan
            </p>
          </div>

          <div
            class="px-4 py-2 rounded-xl text-xs font-mono font-bold border shrink-0"
            :class="isSubActive ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border-amber-500/20'"
          >
            {{ isSubActive ? `🟢 Active Plan: ${activeSubscription?.plan_name}` : '🟡 No Active Subscription' }}
          </div>
        </div>

        <!-- Expiration Alert -->
        <div v-if="activeSubscription" class="bg-gray-50 dark:bg-slate-950/80 p-4 rounded-xl border border-gray-200 dark:border-slate-800 flex items-center justify-between text-xs text-gray-700 dark:text-slate-300">
          <span>
            Current subscription expires on <strong class="text-sky-400 font-mono">{{ new Date(activeSubscription.ends_at).toLocaleDateString() }}</strong>.
          </span>
          <span class="font-mono text-emerald-400 font-bold">₦{{ Number(activeSubscription.amount).toLocaleString() }} paid</span>
        </div>
      </div>

      <!-- Plan Cards Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div
          v-for="plan in plans"
          :key="plan.id"
          class="bg-gray-100 dark:bg-slate-800/60 border rounded-2xl p-8 flex flex-col justify-between space-y-6 shadow-xl relative overflow-hidden transition-all hover:scale-[1.02]"
          :class="activeSubscription?.plan_key === plan.key && isSubActive ? 'border-emerald-500 shadow-emerald-500/10' : 'border-gray-200 dark:border-slate-700/60'"
        >
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase tracking-wider text-sky-400">{{ plan.name }}</span>
              <span v-if="activeSubscription?.plan_key === plan.key && isSubActive" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300">
                Current Plan ✓
              </span>
            </div>

            <div class="font-mono font-black text-3xl text-gray-900 dark:text-slate-100">
              ₦{{ Number(plan.price).toLocaleString() }}
              <span class="text-xs font-normal text-gray-500 dark:text-slate-400">/ 30 days</span>
            </div>

            <p class="text-xs text-gray-500 dark:text-slate-400 leading-relaxed">{{ plan.description }}</p>

            <!-- Features -->
            <ul v-if="plan.features" class="space-y-2 text-xs text-gray-700 dark:text-slate-300 pt-3 border-t border-gray-200 dark:border-slate-700/60">
              <li v-for="(feat, fIdx) in plan.features" :key="fIdx" class="flex items-center gap-2">
                <span class="text-emerald-400 font-bold">✓</span>
                <span>{{ feat }}</span>
              </li>
            </ul>
          </div>

          <button
            @click="subscribeToPlan(plan)"
            :disabled="loadingPlanId === plan.id"
            class="w-full py-3.5 rounded-xl font-bold text-xs shadow-lg transition-all flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed disabled:hover:scale-100"
            :class="activeSubscription?.plan_key === plan.key && isSubActive ? 'bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-700 dark:text-slate-300 hover:text-white' : 'bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 hover:scale-105'"
          >
            <svg v-if="loadingPlanId === plan.id" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ loadingPlanId === plan.id ? 'Redirecting to Paystack...' : (activeSubscription?.plan_key === plan.key && isSubActive ? 'Renew Plan via Paystack' : 'Subscribe via Paystack →') }}
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
