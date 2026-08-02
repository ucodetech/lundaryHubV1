<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCard from '@/Components/StatsCard.vue';
import Badge from '@/Components/Badge.vue';
import BarChart from '@/Components/Charts/BarChart.vue';
import PieChart from '@/Components/Charts/PieChart.vue';
import { Link, useForm } from '@inertiajs/vue3';

defineProps<{
  profile: any;
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
</script>

<template>
  <AppLayout>
    <div class="space-y-8">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-100">Delivery Rider Command</h1>
          <p class="text-xs text-slate-400 mt-1">Manage online dispatch status, view weekly earnings, and track deliveries</p>
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

      <!-- KYC Verification Alert -->
      <div v-if="!profile || profile.kyc_status !== 'approved'" class="bg-amber-500/10 border border-amber-500/20 rounded-2xl p-6 shadow-xl space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h3 class="font-bold text-amber-400">KYC Verification Required</h3>
            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
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
          <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl p-5 shadow-xl flex flex-col justify-between">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Dispatch Status</p>
            <div class="mt-2 flex items-center justify-between">
              <Badge :status="profile.is_online ? 'online' : 'offline'" />
              <span class="text-xs font-semibold text-slate-400 capitalize">{{ profile.vehicle_type }} • {{ profile.vehicle_plate ?? 'No Plate' }}</span>
            </div>
          </div>
        </div>

        <!-- Rider Analytics Charts -->
        <div v-if="rider_earnings_chart && delivery_status_chart" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Weekly Earnings Bar Chart (2 cols) -->
          <div class="lg:col-span-2 bg-slate-900/90 border border-slate-800/80 rounded-2xl p-6 shadow-xl space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-base font-bold text-slate-100">Daily Delivery Earnings</h3>
                <p class="text-xs text-slate-400 mt-0.5">Earnings breakdown in NGN for current week</p>
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
          <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl p-6 shadow-xl space-y-4">
            <div>
              <h3 class="text-base font-bold text-slate-100">Delivery Status Breakdown</h3>
              <p class="text-xs text-slate-400 mt-0.5">Order fulfillment performance stats</p>
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
