<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCard from '@/Components/StatsCard.vue';
import Badge from '@/Components/Badge.vue';
import BarChart from '@/Components/Charts/BarChart.vue';
import PieChart from '@/Components/Charts/PieChart.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
  profile: any;
  activeSubscription?: any;
  stats?: {
    total_earnings: number;
    total_deliveries: number;
    rating: number;
  };
  rider_earnings_chart?: {
    labels: string[];
    datasets: Array<any>;
  };
  delivery_status_chart?: {
    labels: string[];
    data: number[];
    colors?: string[];
  };
}>();

const toggleForm = useForm({});

const toggleOnline = () => {
  toggleForm.post('/rider/toggle-online');
};

const isPassActive = computed(() => {
  if (!props.activeSubscription) return false;
  return new Date(props.activeSubscription.ends_at) > new Date();
});

const daysRemaining = computed(() => {
  if (!props.activeSubscription) return 0;
  const diff = new Date(props.activeSubscription.ends_at).getTime() - Date.now();
  return Math.max(0, Math.ceil(diff / (1000 * 60 * 60 * 24)));
});
</script>

<template>
  <AppLayout>
    <div class="space-y-8">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Delivery Rider Command</h1>
          <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Manage online dispatch status, view weekly earnings, and track deliveries</p>
        </div>

        <button
          v-if="profile && profile.kyc_status === 'approved'"
          @click="toggleOnline"
          :disabled="toggleForm.processing"
          class="px-6 py-2.5 rounded-xl font-bold text-sm transition-all shadow-lg hover:scale-105"
          :class="profile.is_online ? 'bg-rose-500 hover:bg-rose-600 text-white shadow-rose-500/20' : 'bg-emerald-500 hover:bg-emerald-600 text-slate-950 shadow-emerald-500/20'"
        >
          {{ profile.is_online ? '🔴 Go Offline' : '🟢 Go Online Now' }}
        </button>
      </div>

      <!-- Rider Pass Status Banner -->
      <div
        v-if="activeSubscription"
        class="rounded-2xl p-5 border flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xl"
        :class="isPassActive
          ? (daysRemaining <= 5 ? 'bg-amber-500/5 border-amber-500/30' : 'bg-emerald-500/5 border-emerald-500/20')
          : 'bg-rose-500/5 border-rose-500/30'"
      >
        <div class="flex items-start gap-4">
          <span class="text-3xl mt-0.5">{{ isPassActive ? (daysRemaining <= 5 ? '⚠️' : '🛵') : '🔴' }}</span>
          <div>
            <p class="font-bold text-sm" :class="isPassActive ? (daysRemaining <= 5 ? 'text-amber-400' : 'text-emerald-400') : 'text-rose-400'">
              {{ isPassActive ? activeSubscription.plan_name : 'Rider Pass Expired' }}
            </p>
            <p class="text-xs mt-0.5" :class="isPassActive ? 'text-gray-500 dark:text-slate-400' : 'text-rose-400/70'">
              <template v-if="isPassActive">
                {{ daysRemaining }} day{{ daysRemaining !== 1 ? 's' : '' }} remaining &bull; Expires {{ new Date(activeSubscription.ends_at).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' }) }}
              </template>
              <template v-else>
                Your pass ended on {{ new Date(activeSubscription.ends_at).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' }) }} — you cannot go online until you renew.
              </template>
            </p>
          </div>
        </div>
        <Link
          href="/rider/subscription"
          class="shrink-0 px-5 py-2.5 rounded-xl font-bold text-xs shadow-lg transition-all hover:scale-105"
          :class="isPassActive ? 'bg-gray-100 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-gray-800 dark:text-slate-200 hover:border-emerald-500/40 hover:text-emerald-400' : 'bg-gradient-to-r from-emerald-500 to-teal-500 text-slate-950 shadow-emerald-500/20'"
        >
          {{ isPassActive ? '💳 Manage Pass' : '🚀 Renew Pass →' }}
        </Link>
      </div>

      <!-- No Pass Warning -->
      <div v-else-if="profile && profile.kyc_status === 'approved'" class="bg-amber-500/5 border border-amber-500/30 rounded-2xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <span class="text-2xl">🛵</span>
          <div>
            <p class="font-bold text-sm text-amber-400">No Active Rider Pass</p>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Pay the flat ₦2,000/month pass to activate your dispatch access and start accepting deliveries.</p>
          </div>
        </div>
        <Link href="/rider/subscription" class="shrink-0 px-5 py-2.5 rounded-xl font-bold text-xs bg-gradient-to-r from-emerald-500 to-teal-500 text-slate-950 shadow-lg shadow-emerald-500/20 hover:scale-105 transition-all">
          💳 Get Rider Pass →
        </Link>
      </div>

      <!-- KYC Verification Alert -->
      <div v-if="!profile || profile.kyc_status !== 'approved'" class="bg-amber-500/10 border border-amber-500/20 rounded-2xl p-6 shadow-xl space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h3 class="font-bold text-amber-400">KYC Verification Required</h3>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-1 leading-relaxed">
              Upload your driver's license ID, selfie verification, and vehicle plate details to enable automated trip dispatching.
            </p>
          </div>
          <Link href="/rider/profile" class="px-5 py-2.5 rounded-xl bg-amber-500 text-slate-950 font-bold text-xs shrink-0 shadow-lg hover:bg-amber-400 transition-colors">
            Complete KYC Now →
          </Link>
        </div>
      </div>

      <template v-else>
        <!-- Metric Cards Grid -->
        <div v-if="stats" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <StatsCard title="Total Rider Earnings" :value="`₦${Number(stats.total_earnings).toLocaleString()}`" icon="💸" />
          <StatsCard title="Completed Trips" :value="stats.total_deliveries" icon="🛵" />
          <StatsCard title="On-Time Rating" :value="`${stats.rating} ⭐`" icon="⭐" />
          <div class="bg-white dark:bg-slate-900/90 border border-gray-200 dark:border-slate-800/80 rounded-2xl p-5 shadow-xl flex flex-col justify-between">
            <p class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Dispatch Status</p>
            <div class="mt-2 flex items-center justify-between">
              <Badge :status="profile.is_online ? 'online' : 'offline'" />
              <span class="text-xs font-semibold text-gray-500 dark:text-slate-400 capitalize">{{ profile.vehicle_type }} • {{ profile.vehicle_plate ?? 'No Plate' }}</span>
            </div>
          </div>
        </div>

        <!-- Rider Analytics Charts -->
        <div v-if="rider_earnings_chart && delivery_status_chart" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Weekly Earnings Bar Chart (2 cols) -->
          <div class="lg:col-span-2 bg-white dark:bg-slate-900/90 border border-gray-200 dark:border-slate-800/80 rounded-2xl p-6 shadow-xl space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-slate-100">Daily Delivery Earnings</h3>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Earnings breakdown in NGN for current week</p>
              </div>
              <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                This Week
              </span>
            </div>

            <BarChart
              :labels="rider_earnings_chart.labels"
              :datasets="rider_earnings_chart.datasets"
              :height="280"
            />
          </div>

          <!-- Delivery Status Donut Chart (1 col) -->
          <div class="bg-white dark:bg-slate-900/90 border border-gray-200 dark:border-slate-800/80 rounded-2xl p-6 shadow-xl space-y-4">
            <div>
              <h3 class="text-base font-bold text-gray-900 dark:text-slate-100">Delivery Status Breakdown</h3>
              <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Order fulfillment performance stats</p>
            </div>

            <PieChart
              :labels="delivery_status_chart.labels"
              :data="delivery_status_chart.data"
              :colors="delivery_status_chart.colors"
              :height="260"
            />
          </div>
        </div>
      </template>
    </div>
  </AppLayout>
</template>
