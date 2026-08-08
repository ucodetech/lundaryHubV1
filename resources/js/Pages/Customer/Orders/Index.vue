<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
  orders: any;
}>();

const statusBadgeColor = (status: string) => {
  switch (status) {
    case 'completed': return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
    case 'cleaning_in_progress': return 'bg-sky-500/10 text-sky-400 border-sky-500/20';
    case 'ready_for_delivery':
    case 'ready_for_pickup': return 'bg-purple-500/10 text-purple-400 border-purple-500/20';
    case 'out_for_delivery': return 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20';
    case 'cancelled': return 'bg-rose-500/10 text-rose-400 border-rose-500/20';
    default: return 'bg-amber-500/10 text-amber-400 border-amber-500/20';
  }
};

const formatStatusText = (status: string) => {
  return status.replace(/_/g, ' ').toUpperCase();
};
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-6 shadow-xl">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">My Laundry Orders</h1>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">
          Track real-time cleaning status, delivery progress, and past order history
        </p>
      </div>

      <!-- Orders List -->
      <div class="space-y-4">
        <div
          v-for="order in orders.data"
          :key="order.id"
          class="bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-6 shadow-xl hover:border-slate-600 transition-all flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
        >
          <div class="space-y-2">
            <div class="flex flex-wrap items-center gap-3">
              <span class="font-mono font-bold text-sky-400 text-lg">#{{ order.order_number }}</span>
              <span class="px-3 py-1 rounded-full text-[10px] font-mono font-bold border" :class="statusBadgeColor(order.status)">
                {{ formatStatusText(order.status) }}
              </span>
              <span v-if="order.is_legacy" class="px-2 py-0.5 rounded text-[9px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                Walk-In Order Linked
              </span>
            </div>

            <div class="text-xs text-gray-700 dark:text-slate-300 font-semibold">
              🏪 {{ order.shop?.name }}
            </div>

            <div class="text-[11px] text-gray-500 dark:text-slate-400 flex flex-wrap gap-4 font-mono">
              <span>📅 {{ new Date(order.created_at).toLocaleDateString() }}</span>
              <span>{{ order.fulfillment_type === 'home_delivery' ? '🚚 Home Delivery' : '🏬 Store Self Pickup' }}</span>
              <span class="text-emerald-400 font-bold">₦{{ Number(order.total_amount).toLocaleString() }}</span>
            </div>
          </div>

          <Link
            :href="`/orders/${order.order_number}`"
            class="px-5 py-2.5 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 hover:border-sky-500 text-xs font-bold text-sky-400 transition-all shadow shrink-0"
          >
            Track Order Progress →
          </Link>
        </div>

        <div v-if="!orders.data || orders.data.length === 0" class="bg-gray-100 dark:bg-slate-800/40 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-12 text-center text-gray-500 dark:text-slate-400 text-xs shadow-xl space-y-4">
          <span class="text-4xl block">🧺</span>
          <div class="space-y-1">
            <h3 class="font-bold text-gray-800 dark:text-slate-200 text-sm">No Personal Customer Bookings Placed</h3>
            <p v-if="$page.props.auth?.user?.role === 'shop_owner'" class="text-xs text-gray-500 dark:text-slate-400 max-w-md mx-auto">
              You have not placed any personal laundry orders as a customer. To manage customer bookings received at your laundry shop, go to <strong>Shop Operations ➔ Shop Orders</strong>.
            </p>
            <p v-else class="text-xs text-gray-500 dark:text-slate-400 max-w-md mx-auto">
              No orders placed yet. Visit a dry cleaner storefront to book your first laundry order!
            </p>
          </div>
          <div class="flex items-center justify-center gap-3 pt-2">
            <Link v-if="$page.props.auth?.user?.role === 'shop_owner'" href="/shop-admin/orders" class="px-5 py-2.5 rounded-xl bg-sky-500 text-slate-950 font-bold text-xs shadow-lg hover:scale-105 transition-all">
              📋 Go to Shop Customer Orders ➔
            </Link>
            <Link href="/" class="px-5 py-2.5 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-800 dark:text-slate-200 font-bold text-xs hover:text-white">
              Browse Shop Marketplace
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
