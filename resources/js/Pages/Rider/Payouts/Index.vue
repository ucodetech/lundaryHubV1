<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import BankAccountForm from '@/Components/BankAccountForm.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
  totalEarned: number;
  availableBalance: number;
  bankAccount: any;
  payouts: any;
}>();

const showWithdrawModal = ref(false);

const withdrawForm = useForm({
  amount: props.availableBalance > 1000 ? props.availableBalance : 1000,
});

const submitWithdrawal = () => {
  withdrawForm.post('/rider/payouts', {
    preserveScroll: true,
    onSuccess: () => {
      showWithdrawModal.value = false;
    },
  });
};
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-100">Rider Earnings & Payout Hub</h1>
          <p class="text-xs text-slate-400 mt-1">Track delivery fee payouts, manage verified bank details, and request withdrawals</p>
        </div>

        <button
          @click="showWithdrawModal = true"
          :disabled="availableBalance < 1000 || !bankAccount"
          class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-400 text-slate-950 font-bold text-xs shadow-lg shadow-emerald-500/20 hover:scale-105 transition-all flex items-center gap-2 disabled:opacity-50"
        >
          <span>💳 Request Withdrawal (₦{{ Number(availableBalance).toLocaleString() }})</span>
        </button>
      </div>

      <!-- Stat Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-2">
          <span class="text-xs text-slate-400 font-bold uppercase">Available Withdrawal Balance</span>
          <div class="text-3xl font-black text-emerald-400">
            ₦{{ Number(availableBalance).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
          </div>
          <span class="text-[11px] text-slate-400 block">Accumulated delivery fees ready for payout</span>
        </div>

        <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-2">
          <span class="text-xs text-slate-400 font-bold uppercase">Lifetime Delivery Fees Earned</span>
          <div class="text-3xl font-black text-sky-400">
            ₦{{ Number(totalEarned).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
          </div>
          <span class="text-[11px] text-slate-400 block">Total gross delivery earnings across all completed orders</span>
        </div>
      </div>

      <!-- Bank Account Setup Component -->
      <BankAccountForm :existing-account="bankAccount" />

      <!-- Payout Request Ledger Table -->
      <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl overflow-hidden shadow-xl space-y-4">
        <div class="p-6 border-b border-slate-700/60">
          <h3 class="text-base font-bold text-slate-100">Payout Withdrawal History</h3>
        </div>

        <table class="w-full text-left text-sm">
          <thead class="bg-slate-900/80 text-xs uppercase text-slate-400 border-b border-slate-700/60">
            <tr>
              <th class="py-3.5 px-6">Payout Reference</th>
              <th class="py-3.5 px-6">Requested Amount</th>
              <th class="py-3.5 px-6">Bank Destination</th>
              <th class="py-3.5 px-6">Status</th>
              <th class="py-3.5 px-6">Requested Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-700/40">
            <tr v-for="pay in payouts.data" :key="pay.id" class="hover:bg-slate-800/40 transition-colors">
              <td class="py-4 px-6 font-mono font-bold text-amber-400 text-xs">#{{ pay.payout_number }}</td>
              <td class="py-4 px-6 font-mono font-bold text-emerald-400 text-sm">
                ₦{{ Number(pay.amount).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
              </td>
              <td class="py-4 px-6 text-xs">
                <span class="font-bold text-slate-200 block">{{ pay.account_name }}</span>
                <span class="text-slate-400 font-mono text-[11px]">{{ pay.bank_name }} • {{ pay.account_number }}</span>
              </td>
              <td class="py-4 px-6">
                <Badge :status="pay.status" />
              </td>
              <td class="py-4 px-6 text-xs text-slate-400 font-mono">
                {{ new Date(pay.created_at).toLocaleString() }}
              </td>
            </tr>

            <tr v-if="!payouts.data || payouts.data.length === 0">
              <td colspan="5" class="py-12 text-center text-slate-400 text-xs">
                No payout withdrawal requests recorded yet.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Payout Request Modal -->
    <div v-if="showWithdrawModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-700/80 rounded-2xl w-full max-w-md p-6 space-y-5 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <div>
            <h3 class="text-base font-bold text-slate-100">Request Delivery Fee Payout</h3>
            <p class="text-xs text-slate-400">Available: ₦{{ Number(availableBalance).toLocaleString() }}</p>
          </div>
          <button @click="showWithdrawModal = false" class="text-slate-400 hover:text-white text-xl">✕</button>
        </div>

        <form @submit.prevent="submitWithdrawal" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Withdrawal Amount (₦) *</label>
            <input
              v-model="withdrawForm.amount"
              type="number"
              step="100"
              min="1000"
              :max="availableBalance"
              required
              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-emerald-400 font-mono font-bold text-lg focus:border-emerald-500"
            />
            <span v-if="withdrawForm.errors.amount" class="text-xs text-rose-400 mt-1 block">{{ withdrawForm.errors.amount }}</span>
          </div>

          <div v-if="bankAccount" class="bg-slate-950 p-3 rounded-xl border border-slate-800 text-xs space-y-1">
            <span class="text-slate-400 font-bold uppercase text-[10px]">Destination Account:</span>
            <div class="font-bold text-slate-200">{{ bankAccount.account_name }}</div>
            <div class="text-slate-400 font-mono text-[11px]">{{ bankAccount.bank_name }} • {{ bankAccount.account_number }}</div>
          </div>

          <div class="pt-2 flex items-center justify-end gap-3">
            <button
              type="button"
              @click="showWithdrawModal = false"
              class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-bold text-xs hover:text-white"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="withdrawForm.processing"
              class="px-6 py-2 rounded-xl bg-emerald-500 text-slate-950 font-bold text-xs shadow-lg hover:scale-105 transition-all disabled:opacity-60"
            >
              <span>{{ withdrawForm.processing ? 'Submitting...' : '💳 Confirm Withdrawal Request' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
