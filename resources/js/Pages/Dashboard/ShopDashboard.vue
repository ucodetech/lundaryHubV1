<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCard from '@/Components/StatsCard.vue';
import Badge from '@/Components/Badge.vue';
import BarChart from '@/Components/Charts/BarChart.vue';
import PieChart from '@/Components/Charts/PieChart.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
  shop: any;
  stats: {
    total_revenue: number;
    total_orders: number;
    total_categories: number;
    total_services: number;
    total_prices: number;
  };
  shop_revenue_chart?: {
    labels: string[];
    datasets: Array<any>;
  };
  category_breakdown_chart?: {
    labels: string[];
    data: number[];
    colors?: string[];
  };
}>();
</script>

<template>
  <AppLayout>
    <div class="space-y-8">
      <div v-if="!shop" class="bg-amber-500/10 border border-amber-500/20 rounded-2xl p-8 text-center space-y-4">
        <h2 class="text-xl font-bold text-amber-400">Set Up Your Storefront</h2>
        <p class="text-xs text-slate-400 max-w-md mx-auto leading-relaxed">
          You have not created a dry cleaning shop yet. Create your digital shopfront to start offering services and receiving customer bookings.
        </p>
        <Link href="/shop-admin/create" class="inline-block px-6 py-3 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-xs shadow-lg shadow-sky-500/20 hover:scale-105 transition-transform">
          + Create My Shop Storefront
        </Link>
      </div>

      <template v-else>
        <!-- Shop Header Banner -->
        <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xl">
          <div>
            <div class="flex items-center gap-3">
              <h1 class="text-2xl font-bold text-slate-100">{{ shop.name }}</h1>
              <Badge :status="shop.status" />
            </div>
            <p class="text-xs text-slate-400 mt-1">📍 {{ shop.address }} • 📞 {{ shop.phone }}</p>
          </div>

          <div class="flex items-center gap-3">
            <Link :href="`/shop/${shop.slug}`" target="_blank" class="px-4 py-2.5 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-all">
              🔗 Storefront Preview
            </Link>
            <Link :href="`/shop-admin/${shop.id}/edit`" class="px-4 py-2.5 rounded-xl text-xs font-semibold bg-sky-500/10 text-sky-400 border border-sky-500/20 hover:bg-sky-500/20 transition-all">
              ⚙️ Shop Settings
            </Link>
          </div>
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
          <StatsCard title="Total Shop Sales" :value="`₦${Number(stats.total_revenue).toLocaleString()}`" icon="💰" />
          <StatsCard title="Completed Bookings" :value="stats.total_orders" icon="📦" />
          <StatsCard title="Item Categories" :value="stats.total_categories" icon="🏷️" />
          <StatsCard title="Active Services" :value="stats.total_services" icon="🧺" />
          <StatsCard title="Configured Prices" :value="stats.total_prices" icon="💳" />
        </div>

        <!-- Shop Analytics Charts -->
        <div v-if="shop_revenue_chart && category_breakdown_chart" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Shop Revenue Trend Bar Chart (2 cols) -->
          <div class="lg:col-span-2 bg-slate-900/90 border border-slate-800/80 rounded-2xl p-6 shadow-xl space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-base font-bold text-slate-100">Shop Revenue Growth Trend</h3>
                <p class="text-xs text-slate-400 mt-0.5">Weekly revenue performance in NGN</p>
              </div>
              <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20">
                This Month
              </span>
            </div>

            <BarChart
              :labels="shop_revenue_chart.labels"
              :datasets="shop_revenue_chart.datasets"
              :height="280"
            />
          </div>

          <!-- Top Garment Categories Donut Chart (1 col) -->
          <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl p-6 shadow-xl space-y-4">
            <div>
              <h3 class="text-base font-bold text-slate-100">Top Garment Categories</h3>
              <p class="text-xs text-slate-400 mt-0.5">Order volume breakdown by garment type</p>
            </div>

            <PieChart
              :labels="category_breakdown_chart.labels"
              :data="category_breakdown_chart.data"
              :colors="category_breakdown_chart.colors"
              :height="260"
            />
          </div>
        </div>

        <!-- Quick Setup Guidance Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Link href="/shop-admin/categories" class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 hover:border-sky-500/40 transition-all group">
            <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">🏷️</div>
            <h4 class="font-bold text-slate-200 text-sm group-hover:text-sky-400 transition-colors">1. Manage Categories</h4>
            <p class="text-xs text-slate-400 mt-1 leading-relaxed">Add item categories like Shirts, Jeans, Native wear, Duvets or clone from master templates.</p>
          </Link>

          <Link href="/shop-admin/services" class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 hover:border-sky-500/40 transition-all group">
            <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">🧺</div>
            <h4 class="font-bold text-slate-200 text-sm group-hover:text-sky-400 transition-colors">2. Define Services</h4>
            <p class="text-xs text-slate-400 mt-1 leading-relaxed">Create services like Wash Only, Wash & Iron, Starch & Press treatment options.</p>
          </Link>

          <Link href="/shop-admin/pricing" class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 hover:border-sky-500/40 transition-all group">
            <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">💳</div>
            <h4 class="font-bold text-slate-200 text-sm group-hover:text-sky-400 transition-colors">3. Set Price Matrix</h4>
            <p class="text-xs text-slate-400 mt-1 leading-relaxed">Assign custom prices for Category × Service pairs or import all master pricing.</p>
          </Link>
        </div>
      </template>
    </div>
  </AppLayout>
</template>
