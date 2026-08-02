<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCard from '@/Components/StatsCard.vue';
import Badge from '@/Components/Badge.vue';
import BarChart from '@/Components/Charts/BarChart.vue';
import PieChart from '@/Components/Charts/PieChart.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
  stats: {
    total_revenue: number;
    total_customers: number;
    total_shops: number;
    pending_shops: number;
    total_riders: number;
    pending_kyc: number;
  };
  monthly_revenue_chart: {
    labels: string[];
    datasets: Array<any>;
  };
  shop_breakdown_chart: {
    labels: string[];
    data: number[];
    colors?: string[];
  };
  order_status_chart: {
    labels: string[];
    data: number[];
    colors?: string[];
  };
  recent_shops: Array<any>;
  recent_riders: Array<any>;
}>();
</script>

<template>
  <AppLayout>
    <div class="space-y-8">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-100">Super Admin Command Center</h1>
          <p class="text-xs text-slate-400 mt-1">Platform performance, revenue growth trends, and partner verifications</p>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
        <StatsCard title="Total Platform Revenue" :value="`₦${Number(stats.total_revenue).toLocaleString()}`" icon="💰" />
        <StatsCard title="Total Customers" :value="stats.total_customers" icon="👥" />
        <StatsCard title="Active Dry Cleaners" :value="stats.total_shops" icon="🏪" />
        <StatsCard title="Pending Shops" :value="stats.pending_shops" icon="⏳" />
        <StatsCard title="Registered Riders" :value="stats.total_riders" icon="🛵" />
        <StatsCard title="Pending KYC" :value="stats.pending_kyc" icon="📄" />
      </div>

      <!-- Analytics Charts Row 1 -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Monthly Revenue Trend Bar Chart (2 cols) -->
        <div class="lg:col-span-2 bg-slate-900/90 border border-slate-800/80 rounded-2xl p-6 shadow-xl space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-base font-bold text-slate-100">Platform Revenue & Order Volume</h3>
              <p class="text-xs text-slate-400 mt-0.5">Monthly breakdown of gross volume and platform revenue</p>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20">
              YTD 2026
            </span>
          </div>

          <BarChart
            :labels="monthly_revenue_chart.labels"
            :datasets="monthly_revenue_chart.datasets"
            :height="280"
          />
        </div>

        <!-- Revenue Share by Dry Cleaner Donut Chart (1 col) -->
        <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl p-6 shadow-xl space-y-4">
          <div>
            <h3 class="text-base font-bold text-slate-100">Revenue Share by Storefront</h3>
            <p class="text-xs text-slate-400 mt-0.5">Market share distribution across top dry cleaners</p>
          </div>

          <PieChart
            :labels="shop_breakdown_chart.labels"
            :data="shop_breakdown_chart.data"
            :colors="shop_breakdown_chart.colors"
            :height="260"
          />
        </div>
      </div>

      <!-- Analytics Charts Row 2 & Recent Verifications -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Order Status Distribution Donut Chart (1 col) -->
        <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl p-6 shadow-xl space-y-4">
          <div>
            <h3 class="text-base font-bold text-slate-100">Order Status Distribution</h3>
            <p class="text-xs text-slate-400 mt-0.5">Breakdown of active platform order statuses</p>
          </div>

          <PieChart
            :labels="order_status_chart.labels"
            :data="order_status_chart.data"
            :colors="order_status_chart.colors"
            :height="260"
          />
        </div>

        <!-- Recent Shops & Riders Grid (2 cols) -->
        <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-6">
          <!-- Recent Shops -->
          <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl p-5 space-y-3">
            <div class="flex items-center justify-between">
              <h3 class="font-bold text-sm text-slate-200">Recent Dry Cleaners</h3>
              <Link href="/admin/shops" class="text-xs text-sky-400 hover:underline">View All →</Link>
            </div>

            <div class="space-y-2">
              <div
                v-for="shop in recent_shops"
                :key="shop.id"
                class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/60 flex items-center justify-between gap-3"
              >
                <div class="min-w-0">
                  <h4 class="text-xs font-bold text-slate-200 truncate">{{ shop.name }}</h4>
                  <p class="text-[11px] text-slate-400 truncate">{{ shop.owner?.first_name }} {{ shop.owner?.last_name }}</p>
                </div>
                <Badge :status="shop.status" />
              </div>
            </div>
          </div>

          <!-- Recent Riders -->
          <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl p-5 space-y-3">
            <div class="flex items-center justify-between">
              <h3 class="font-bold text-sm text-slate-200">Rider Audit Requests</h3>
              <Link href="/admin/riders" class="text-xs text-sky-400 hover:underline">View All →</Link>
            </div>

            <div class="space-y-2">
              <div
                v-for="rider in recent_riders"
                :key="rider.id"
                class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/60 flex items-center justify-between gap-3"
              >
                <div class="min-w-0">
                  <h4 class="text-xs font-bold text-slate-200 truncate">{{ rider.user?.first_name }} {{ rider.user?.last_name }}</h4>
                  <p class="text-[11px] text-slate-400 capitalize truncate">{{ rider.vehicle_type }} • {{ rider.vehicle_plate ?? 'No Plate' }}</p>
                </div>
                <Badge :status="rider.kyc_status" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
