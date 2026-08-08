<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import AdvancedFilter, { FilterConfig } from '@/Components/AdvancedFilter.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
  disputes: any;
  filters?: Record<string, any>;
}>();

const filterState = ref<Record<string, any>>({ ...props.filters });

const filterConfig: FilterConfig[] = [
  {
    key: 'search',
    label: 'Search Ticket',
    type: 'text',
    placeholder: 'Search dispute number, subject, reporter name...',
  },
  {
    key: 'status',
    label: 'Ticket Status',
    type: 'select',
    defaultVisible: true,
    options: [
      { label: 'Open Ticket', value: 'open' },
      { label: 'Under Review', value: 'under_review' },
      { label: 'Resolved Refund', value: 'resolved_refund' },
      { label: 'Resolved Compensated', value: 'resolved_compensated' },
      { label: 'Resolved Rejected', value: 'resolved_rejected' },
    ],
  },
  {
    key: 'reason',
    label: 'Dispute Reason',
    type: 'select',
    defaultVisible: true,
    options: [
      { label: 'Damaged Garment', value: 'damaged_garment' },
      { label: 'Missing Item', value: 'missing_item' },
      { label: 'Unreasonable Delay', value: 'late_delivery' },
      { label: 'Overcharge', value: 'overcharge' },
    ],
  },
];

const handleFilterChange = (newFilters: Record<string, any>) => {
  router.get('/admin/disputes', newFilters, {
    preserveState: true,
    replace: true,
  });
};

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
          <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Dispute & Ticket Command Center</h1>
          <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Investigate customer, shop, and rider conflict tickets, inspect photo evidence, and execute resolution refunds</p>
        </div>
      </div>

      <!-- Advanced Filter Component -->
      <AdvancedFilter
        v-model="filterState"
        :filters-config="filterConfig"
        search-placeholder="Search ticket number, subject, or reporter..."
        @filter-change="handleFilterChange"
      />

      <!-- Disputes Table -->
      <div class="bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl overflow-hidden overflow-x-auto custom-scrollbar shadow-xl">
        <table class="w-full text-left text-sm">
          <thead class="bg-white dark:bg-slate-900/80 text-xs uppercase text-gray-500 dark:text-slate-400 border-b border-gray-200 dark:border-slate-700/60">
            <tr>
              <th class="py-3.5 px-6">Ticket ID</th>
              <th class="py-3.5 px-6">Order / Shop</th>
              <th class="py-3.5 px-6">Reporter</th>
              <th class="py-3.5 px-6">Reason / Subject</th>
              <th class="py-3.5 px-6">Status</th>
              <th class="py-3.5 px-6 text-right">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-slate-700/40">
            <tr v-for="dispute in disputes.data" :key="dispute.id" class="hover:bg-slate-800/40 transition-colors">
              <td class="py-4 px-6 font-mono font-bold text-amber-400 text-xs">
                #{{ dispute.dispute_number }}
              </td>
              <td class="py-4 px-6 text-xs">
                <span class="font-mono text-sky-400 font-bold">#{{ dispute.order?.order_number }}</span>
                <span class="text-gray-500 dark:text-slate-400 block">{{ dispute.order?.shop?.name }}</span>
              </td>
              <td class="py-4 px-6 text-xs">
                <span class="font-bold text-gray-800 dark:text-slate-200 block">{{ dispute.reporter?.first_name }} {{ dispute.reporter?.last_name }}</span>
                <span class="text-gray-500 dark:text-slate-400 font-mono text-[10px]">{{ dispute.reporter?.phone }}</span>
              </td>
              <td class="py-4 px-6 text-xs space-y-0.5">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-700 dark:text-slate-300 inline-block mb-1">
                  {{ reasonLabel(dispute.reason) }}
                </span>
                <p class="font-semibold text-gray-800 dark:text-slate-200 truncate max-w-xs">{{ dispute.subject }}</p>
              </td>
              <td class="py-4 px-6">
                <Badge :status="dispute.status" />
              </td>
              <td class="py-4 px-6 text-right">
                <Link
                  :href="`/disputes/${dispute.id}`"
                  class="px-3.5 py-1.5 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20 hover:bg-purple-500/20 text-xs font-bold transition-all inline-flex items-center gap-1"
                >
                  🔍 Investigate Ticket
                </Link>
              </td>
            </tr>

            <tr v-if="!disputes.data || disputes.data.length === 0">
              <td colspan="6" class="py-12 text-center text-gray-500 dark:text-slate-400 text-xs">
                No dispute tickets matching the selected filters.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
