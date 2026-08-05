<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import AdvancedFilter, { FilterConfig } from '@/Components/AdvancedFilter.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
  payouts: any;
  stats: {
    pending_count: number;
    pending_amount: number;
    total_paid: number;
  };
  filters?: Record<string, any>;
}>();

const filterState = ref<Record<string, any>>({ ...props.filters });
const selectedRejectPayout = ref<any>(null);

const rejectForm = useForm({
  rejection_reason: '',
});

const filterConfig: FilterConfig[] = [
  {
    key: 'search',
    label: 'Search Payout',
    type: 'text',
    placeholder: 'Search payout ref, account name, user phone...',
  },
  {
    key: 'status',
    label: 'Payout Status',
    type: 'select',
    defaultVisible: true,
    options: [
      { label: 'Pending Approval', value: 'pending' },
      { label: 'Paid & Settled', value: 'paid' },
      { label: 'Rejected', value: 'rejected' },
    ],
  },
  {
    key: 'role',
    label: 'User Role',
    type: 'select',
    defaultVisible: true,
    options: [
      { label: 'Delivery Rider', value: 'rider' },
      { label: 'Shop Owner', value: 'shop_owner' },
    ],
  },
];

const handleFilterChange = (newFilters: Record<string, any>) => {
  router.get('/admin/payouts', newFilters, {
    preserveState: true,
    replace: true,
  });
};

const approvePayout = (id: number) => {
  useForm({}).post(`/admin/payouts/${id}/approve`, {
    preserveScroll: true,
  });
};

const submitReject = () => {
  if (!selectedRejectPayout.value) return;
  rejectForm.post(`/admin/payouts/${selectedRejectPayout.value.id}/reject`, {
    preserveScroll: true,
    onSuccess: () => {
      selectedRejectPayout.value = null;
      rejectForm.reset();
    },
  });
};
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-100">Payout Settlement Command Center</h1>
          <p class="text-xs text-slate-400 mt-1">Approve rider delivery withdrawals and shop owner payout settlements</p>
        </div>
      </div>

      <!-- Stat Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow-xl space-y-2">
          <span class="text-xs text-slate-400 font-bold uppercase">Pending Payout Requests</span>
          <div class="text-2xl font-black text-amber-400">
            {{ stats.pending_count }} Requests
          </div>
          <span class="text-[10px] text-slate-400 block">Awaiting Super Admin approval</span>
        </div>

        <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow-xl space-y-2">
          <span class="text-xs text-slate-400 font-bold uppercase">Total Pending Amount</span>
          <div class="text-2xl font-black text-amber-400">
            ₦{{ Number(stats.pending_amount).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
          </div>
          <span class="text-[10px] text-slate-400 block">Total sum of unfulfilled payouts</span>
        </div>

        <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow-xl space-y-2">
          <span class="text-xs text-slate-400 font-bold uppercase">Total Settled & Paid Out</span>
          <div class="text-2xl font-black text-emerald-400">
            ₦{{ Number(stats.total_paid).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
          </div>
          <span class="text-[10px] text-slate-400 block">Lifetime platform payouts processed</span>
        </div>
      </div>

      <!-- Advanced Filter Component -->
      <AdvancedFilter
        v-model="filterState"
        :filters-config="filterConfig"
        search-placeholder="Search payout ref, account name, or phone..."
        @filter-change="handleFilterChange"
      />

      <!-- Payouts Table -->
      <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl overflow-hidden overflow-x-auto custom-scrollbar shadow-xl">
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-900/80 text-xs uppercase text-slate-400 border-b border-slate-700/60">
            <tr>
              <th class="py-3.5 px-6">Payout Ref</th>
              <th class="py-3.5 px-6">User / Role</th>
              <th class="py-3.5 px-6">Bank Settlement Details</th>
              <th class="py-3.5 px-6">Amount</th>
              <th class="py-3.5 px-6">Status</th>
              <th class="py-3.5 px-6 text-right">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-700/40">
            <tr v-for="pay in payouts.data" :key="pay.id" class="hover:bg-slate-800/40 transition-colors">
              <td class="py-4 px-6 font-mono font-bold text-amber-400 text-xs">
                #{{ pay.payout_number }}
              </td>
              <td class="py-4 px-6 text-xs">
                <span class="font-bold text-slate-200 block">{{ pay.user?.first_name }} {{ pay.user?.last_name }}</span>
                <span class="text-slate-400 font-mono text-[10px]">{{ pay.user?.phone }} • {{ pay.role }}</span>
              </td>
              <td class="py-4 px-6 text-xs space-y-0.5">
                <strong class="text-slate-100 block">{{ pay.account_name }}</strong>
                <span class="text-slate-400 font-mono text-[11px]">{{ pay.bank_name }} • {{ pay.account_number }}</span>
              </td>
              <td class="py-4 px-6 font-mono font-bold text-emerald-400 text-sm">
                ₦{{ Number(pay.amount).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
              </td>
              <td class="py-4 px-6">
                <Badge :status="pay.status" />
              </td>
              <td class="py-4 px-6 text-right">
                <div v-if="pay.status === 'pending'" class="flex items-center justify-end gap-2">
                  <button
                    @click="approvePayout(pay.id)"
                    class="px-3 py-1.5 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20 text-xs font-bold transition-all"
                  >
                    ✓ Approve & Pay
                  </button>
                  <button
                    @click="selectedRejectPayout = pay"
                    class="px-3 py-1.5 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500/20 text-xs font-bold transition-all"
                  >
                    ✕ Reject
                  </button>
                </div>
                <span v-else class="text-[10px] text-slate-400 font-mono">Processed</span>
              </td>
            </tr>

            <tr v-if="!payouts.data || payouts.data.length === 0">
              <td colspan="6" class="py-12 text-center text-slate-400 text-xs">
                No payout requests matching the selected filters.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Reject Payout Modal -->
    <div v-if="selectedRejectPayout" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-700/80 rounded-2xl w-full max-w-md p-6 space-y-4 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <h3 class="text-base font-bold text-slate-100">Reject Payout #{{ selectedRejectPayout.payout_number }}</h3>
          <button @click="selectedRejectPayout = null" class="text-slate-400 hover:text-white text-xl">✕</button>
        </div>

        <form @submit.prevent="submitReject" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Rejection Reason *</label>
            <textarea
              v-model="rejectForm.rejection_reason"
              rows="3"
              required
              placeholder="Explain why the payout request was rejected..."
              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-100 text-xs"
            ></textarea>
          </div>

          <div class="pt-2 flex items-center justify-end gap-3">
            <button
              type="button"
              @click="selectedRejectPayout = null"
              class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-bold text-xs hover:text-white"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="rejectForm.processing"
              class="px-6 py-2 rounded-xl bg-rose-500 text-white font-bold text-xs shadow-lg hover:scale-105 transition-all disabled:opacity-60"
            >
              <span>{{ rejectForm.processing ? 'Rejecting...' : 'Reject & Return Balance' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
