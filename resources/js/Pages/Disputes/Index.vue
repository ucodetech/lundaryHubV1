<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
  disputes: any;
}>();

const reasonLabel = (reason: string) => {
  switch (reason) {
    case 'damaged_garment': return '👔 Damaged Garment';
    case 'missing_item': return '❓ Missing Item';
    case 'late_delivery': return '⏳ Delivery Delay';
    case 'overcharge': return '💸 Overcharge';
    default: return '💬 General Dispute';
  }
};
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">My Support & Dispute Tickets</h1>
          <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Track status and resolution progress for order issues</p>
        </div>

        <Link
          href="/orders"
          class="px-5 py-2.5 rounded-xl bg-gray-100 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-gray-800 dark:text-slate-200 hover:text-white font-bold text-xs shadow transition-all"
        >
          📦 View My Orders
        </Link>
      </div>

      <div class="space-y-4">
        <div
          v-for="dispute in disputes.data"
          :key="dispute.id"
          class="bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-6 shadow-xl hover:border-slate-600 transition-all flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
        >
          <div class="space-y-2">
            <div class="flex flex-wrap items-center gap-3">
              <span class="font-mono font-bold text-amber-400 text-base">#{{ dispute.dispute_number }}</span>
              <Badge :status="dispute.status" />
              <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-700 dark:text-slate-300">
                {{ reasonLabel(dispute.reason) }}
              </span>
            </div>

            <h3 class="font-bold text-gray-800 dark:text-slate-200 text-sm">{{ dispute.subject }}</h3>

            <div class="text-[11px] text-gray-500 dark:text-slate-400 flex flex-wrap gap-4 font-mono">
              <span>Order: <strong class="text-sky-400">#{{ dispute.order?.order_number }}</strong></span>
              <span>Shop: {{ dispute.order?.shop?.name }}</span>
              <span>Filed: {{ new Date(dispute.created_at).toLocaleDateString() }}</span>
            </div>
          </div>

          <Link
            :href="`/disputes/${dispute.id}`"
            class="px-5 py-2.5 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 hover:border-purple-500 text-xs font-bold text-purple-400 transition-all shadow shrink-0"
          >
            Open Ticket Thread →
          </Link>
        </div>

        <div v-if="!disputes.data || disputes.data.length === 0" class="bg-gray-100 dark:bg-slate-800/40 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-12 text-center text-gray-500 dark:text-slate-400 text-xs shadow-xl space-y-2">
          <span class="text-4xl block">🛡️</span>
          <h3 class="font-bold text-gray-800 dark:text-slate-200 text-sm">No Active Dispute Tickets</h3>
          <p class="text-xs text-gray-500 dark:text-slate-400">
            If you ever experience damaged garments, missing items, or payment issues, you can file a dispute directly from your order page.
          </p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
