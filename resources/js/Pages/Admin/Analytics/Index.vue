<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps<{
  metrics: {
    total_gmv: number;
    active_shops: number;
    active_riders: number;
    fulfillment_rate: number;
    total_paid_out: number;
    completed_orders: number;
    avg_turnaround_hours: number;
  };
  monthlyBreakdown: Array<{
    month: string;
    gmv: number;
    orders: number;
  }>;
}>();
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-100">Financial & Platform Analytics</h1>
          <p class="text-xs text-slate-400 mt-1">Super Admin executive overview of revenue, GMV, fulfillment efficiency, and marketplace volume</p>
        </div>
      </div>

      <!-- Stat Cards Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- GMV Card -->
        <div class="bg-gradient-to-br from-slate-900 to-slate-800 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-400 font-bold uppercase">Gross Merchandise Value (GMV)</span>
            <span class="text-xl">💰</span>
          </div>
          <div class="text-3xl font-black text-sky-400">
            ₦{{ Number(metrics.total_gmv).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
          </div>
          <span class="text-[11px] text-slate-400 block">Total processed order volume across all shops</span>
        </div>

        <!-- Rider Fulfillment Rate -->
        <div class="bg-gradient-to-br from-slate-900 to-slate-800 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-400 font-bold uppercase">Rider Fulfillment Rate</span>
            <span class="text-xl">🎯</span>
          </div>
          <div class="text-3xl font-black text-emerald-400">
            {{ metrics.fulfillment_rate }}%
          </div>
          <span class="text-[11px] text-slate-400 block">Percentage of dispatches successfully fulfilled</span>
        </div>

        <!-- Turnaround Time -->
        <div class="bg-gradient-to-br from-slate-900 to-slate-800 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-400 font-bold uppercase">Avg Washing Turnaround</span>
            <span class="text-xl">⚡</span>
          </div>
          <div class="text-3xl font-black text-amber-400">
            {{ metrics.avg_turnaround_hours }} hrs
          </div>
          <span class="text-[11px] text-slate-400 block">Average time from pickup receipt to delivery dispatch</span>
        </div>

        <!-- Active Shops -->
        <div class="bg-gradient-to-br from-slate-900 to-slate-800 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-400 font-bold uppercase">Active Verified Shops</span>
            <span class="text-xl">🏪</span>
          </div>
          <div class="text-3xl font-black text-purple-400">
            {{ metrics.active_shops }} Shops
          </div>
          <span class="text-[11px] text-slate-400 block">Onboarded dry cleaners receiving customer bookings</span>
        </div>

        <!-- Active Riders -->
        <div class="bg-gradient-to-br from-slate-900 to-slate-800 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-400 font-bold uppercase">Approved Delivery Riders</span>
            <span class="text-xl">🛵</span>
          </div>
          <div class="text-3xl font-black text-cyan-400">
            {{ metrics.active_riders }} Riders
          </div>
          <span class="text-[11px] text-slate-400 block">Active riders fulfilling order dispatches</span>
        </div>

        <!-- Total Paid Out -->
        <div class="bg-gradient-to-br from-slate-900 to-slate-800 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-400 font-bold uppercase">Total Rider Payouts Settled</span>
            <span class="text-xl">💸</span>
          </div>
          <div class="text-3xl font-black text-teal-400">
            ₦{{ Number(metrics.total_paid_out).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
          </div>
          <span class="text-[11px] text-slate-400 block">Lifetime rider withdrawal payouts processed</span>
        </div>
      </div>

      <!-- Monthly Revenue & Order Volume Breakdown -->
      <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-4">
        <h3 class="text-base font-bold text-slate-100">Monthly GMV Performance Breakdown (Past 6 Months)</h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 pt-2">
          <div
            v-for="(item, idx) in monthlyBreakdown"
            :key="idx"
            class="bg-slate-900 border border-slate-700/80 rounded-xl p-4 space-y-2"
          >
            <div class="flex items-center justify-between border-b border-slate-800 pb-2">
              <span class="font-bold text-xs text-slate-300">{{ item.month }}</span>
              <span class="text-[10px] text-slate-400 font-mono">{{ item.orders }} Orders</span>
            </div>
            <div class="text-lg font-mono font-extrabold text-sky-400">
              ₦{{ Number(item.gmv).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
