<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCard from '@/Components/StatsCard.vue';
import Badge from '@/Components/Badge.vue';
import BarChart from '@/Components/Charts/BarChart.vue';
import PieChart from '@/Components/Charts/PieChart.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { confirmDialog } from '@/Utils/swal';

const props = defineProps<{
  shop: any;
  activeSubscription?: any;
  virtualAccount?: any;
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

const isSubActive = computed(() => {
  if (!props.activeSubscription) return false;
  return new Date(props.activeSubscription.ends_at) > new Date();
});

const daysRemaining = computed(() => {
  if (!props.activeSubscription) return 0;
  const diff = new Date(props.activeSubscription.ends_at).getTime() - Date.now();
  return Math.max(0, Math.ceil(diff / (1000 * 60 * 60 * 24)));
});

const isTrial = computed(() => props.activeSubscription?.plan_key === 'shop_trial');

const copiedField = ref('');
const copyToClipboard = (text: string, field: string) => {
  navigator.clipboard.writeText(text);
  copiedField.value = field;
  setTimeout(() => { copiedField.value = ''; }, 1800);
};

const cloneAllMasterTemplates = async () => {
  const confirmed = await confirmDialog(
    'Import All Master Platform Templates?',
    'This will instantly populate your shop with all default platform garment categories, laundry service treatments, and standard price matrices.'
  );

  if (confirmed) {
    useForm({}).post('/shop-admin/pricing/clone-all', {
      preserveScroll: true,
    });
  }
};
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

          <div class="flex flex-wrap items-center gap-3">
            <button
              @click="cloneAllMasterTemplates"
              class="px-4 py-2.5 rounded-xl text-xs font-bold bg-gradient-to-r from-purple-500 to-indigo-500 text-white shadow-lg shadow-purple-500/20 hover:opacity-95 transition-all flex items-center gap-1.5"
            >
              <span>⚡</span>
              <span>Import All Master Templates</span>
            </button>

            <Link :href="`/shop/${shop.slug}`" target="_blank" class="px-4 py-2.5 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-all">
              🔗 Storefront Preview
            </Link>
            <Link :href="`/shop-admin/${shop.id}/edit`" class="px-4 py-2.5 rounded-xl text-xs font-semibold bg-sky-500/10 text-sky-400 border border-sky-500/20 hover:bg-sky-500/20 transition-all">
              ⚙️ Shop Settings
            </Link>
          </div>
        </div>

        <!-- Virtual Account Settlement Card -->
        <div
          v-if="virtualAccount && virtualAccount.account_number"
          class="bg-gradient-to-br from-slate-900 via-slate-900 to-slate-800/80 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-4"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-xl">🏦</div>
              <div>
                <h3 class="font-bold text-slate-100 text-sm">Shop Settlement Account</h3>
                <p class="text-[11px] text-slate-400">Paystack Dedicated Virtual Account — customer payments go here directly</p>
              </div>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">● ACTIVE</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <!-- Account Number -->
            <div class="bg-slate-950/60 rounded-xl p-4 border border-slate-800/80 space-y-1.5">
              <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Account Number</p>
              <div class="flex items-center justify-between gap-2">
                <span class="font-mono font-black text-2xl text-slate-100 tracking-widest">{{ virtualAccount.account_number }}</span>
                <button
                  @click="copyToClipboard(virtualAccount.account_number, 'account')"
                  title="Copy account number"
                  class="p-1.5 rounded-lg transition-all"
                  :class="copiedField === 'account' ? 'text-emerald-400 bg-emerald-500/10' : 'text-slate-500 hover:text-sky-400 hover:bg-sky-500/10'"
                >
                  <svg v-if="copiedField !== 'account'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                  <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </button>
              </div>
            </div>

            <!-- Bank Name -->
            <div class="bg-slate-950/60 rounded-xl p-4 border border-slate-800/80 space-y-1.5">
              <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Bank</p>
              <p class="font-bold text-slate-100 text-base">{{ virtualAccount.bank_name }}</p>
            </div>

            <!-- Account Name -->
            <div class="bg-slate-950/60 rounded-xl p-4 border border-slate-800/80 space-y-1.5">
              <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Account Name</p>
              <p class="font-bold text-slate-100 text-base">{{ virtualAccount.account_name }}</p>
            </div>
          </div>

          <p class="text-[11px] text-slate-500 leading-relaxed pt-2 border-t border-slate-800/60">
            ⚡ Customers transfer directly to this account. Payments settle automatically to your Paystack dashboard.
          </p>
        </div>

        <!-- DVA Pending Banner -->
        <div v-else-if="shop && shop.is_verified" class="bg-amber-500/5 border border-amber-500/20 rounded-2xl p-5 flex items-center gap-4">
          <span class="text-2xl">🏦</span>
          <div>
            <p class="font-bold text-sm text-amber-400">Virtual Account Pending</p>
            <p class="text-xs text-slate-400 mt-0.5">Your Paystack settlement account is being provisioned. Contact support if this persists.</p>
          </div>
        </div>

        <!-- Subscription Status Banner -->
        <div
          v-if="activeSubscription"
          class="rounded-2xl p-5 border flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xl"
          :class="isSubActive
            ? (daysRemaining <= 7 ? 'bg-amber-500/5 border-amber-500/30' : 'bg-emerald-500/5 border-emerald-500/20')
            : 'bg-rose-500/5 border-rose-500/30'"
        >
          <div class="flex items-start gap-4">
            <span class="text-3xl mt-0.5">{{ isSubActive ? (daysRemaining <= 7 ? '⚠️' : '✅') : '🔴' }}</span>
            <div>
              <p class="font-bold text-sm" :class="isSubActive ? (daysRemaining <= 7 ? 'text-amber-400' : 'text-emerald-400') : 'text-rose-400'">
                {{ isSubActive ? activeSubscription.plan_name : 'Subscription Expired' }}
                <span v-if="isTrial && isSubActive" class="ml-2 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-sky-500/20 text-sky-400 border border-sky-500/30">
                  Free Trial
                </span>
              </p>
              <p class="text-xs mt-0.5" :class="isSubActive ? 'text-slate-400' : 'text-rose-400/70'">
                <template v-if="isSubActive">
                  {{ daysRemaining }} day{{ daysRemaining !== 1 ? 's' : '' }} remaining &bull; Expires {{ new Date(activeSubscription.ends_at).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                </template>
                <template v-else>
                  Your subscription ended on {{ new Date(activeSubscription.ends_at).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' }) }} — operational features are locked.
                </template>
              </p>
            </div>
          </div>
          <Link
            href="/shop-admin/subscription"
            class="shrink-0 px-5 py-2.5 rounded-xl font-bold text-xs shadow-lg transition-all hover:scale-105"
            :class="isSubActive ? 'bg-slate-800 border border-slate-700 text-slate-200 hover:border-sky-500/40 hover:text-sky-400' : 'bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 shadow-sky-500/20'"
          >
            {{ isSubActive ? '⚡ Manage Subscription' : '🚀 Subscribe Now →' }}
          </Link>
        </div>

        <!-- No Subscription Warning -->
        <div v-else-if="shop" class="bg-amber-500/5 border border-amber-500/30 rounded-2xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <span class="text-2xl">⚡</span>
            <div>
              <p class="font-bold text-sm text-amber-400">No Active Subscription</p>
              <p class="text-xs text-slate-400 mt-0.5">Subscribe to a monthly plan to unlock all operational features and start accepting customer orders.</p>
            </div>
          </div>
          <Link href="/shop-admin/subscription" class="shrink-0 px-5 py-2.5 rounded-xl font-bold text-xs bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 shadow-lg shadow-sky-500/20 hover:scale-105 transition-all">
            🚀 View Plans →
          </Link>
        </div>

        <!-- 1-Click Catalog Setup Prompt Banner if Shop is Fresh -->
        <div v-if="stats.total_categories === 0 || stats.total_prices === 0" class="bg-gradient-to-r from-purple-900/40 via-slate-900 to-slate-900 border border-purple-500/30 rounded-2xl p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-xl">
          <div class="space-y-1">
            <div class="flex items-center gap-2 text-purple-300 font-bold text-sm">
              <span>✨</span>
              <span>Fast Track Catalog Setup</span>
            </div>
            <p class="text-xs text-slate-300 max-w-xl leading-relaxed">
              Want to get your catalog ready in 1 click? Import default platform categories, service treatments, and standard price matrices into your shop.
            </p>
          </div>

          <button
            @click="cloneAllMasterTemplates"
            class="px-6 py-3 rounded-xl bg-purple-500 text-white font-bold text-xs shadow-xl shadow-purple-500/20 hover:bg-purple-400 transition-all shrink-0 flex items-center gap-2"
          >
            <span>⚡</span>
            <span>Import All Master Templates Now</span>
          </button>
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
