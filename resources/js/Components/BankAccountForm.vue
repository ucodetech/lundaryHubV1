<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps<{
  existingAccount?: any;
  shopId?: number;
}>();

const banks = ref<Array<{ name: string; code: string }>>([]);
const resolving = ref(false);
const resolveSuccess = ref(false);
const resolveError = ref('');

const form = useForm({
  bank_code: props.existingAccount?.bank_code || '',
  bank_name: props.existingAccount?.bank_name || '',
  account_number: props.existingAccount?.account_number || '',
  account_name: props.existingAccount?.account_name || '',
  shop_id: props.shopId || null,
});

onMounted(async () => {
  try {
    const res = await axios.get('/bank-accounts/banks');
    if (res.data?.banks) {
      banks.value = res.data.banks;
    }
  } catch (e) {
    console.warn('Failed to load bank list', e);
  }
});

const onBankChange = () => {
  const selected = banks.value.find(b => b.code === form.bank_code);
  if (selected) {
    form.bank_name = selected.name;
  }
  if (form.account_number.length === 10) {
    resolveNUBAN();
  }
};

const resolveNUBAN = async () => {
  if (!form.bank_code || form.account_number.length !== 10) return;

  resolving.value = true;
  resolveSuccess.value = false;
  resolveError.value = '';

  try {
    const res = await axios.post('/bank-accounts/resolve', {
      bank_code: form.bank_code,
      account_number: form.account_number,
    });

    if (res.data?.status && res.data?.account_name) {
      form.account_name = res.data.account_name;
      resolveSuccess.value = true;
    } else {
      resolveError.value = res.data?.message || 'Could not verify account name.';
    }
  } catch (e: any) {
    resolveError.value = e.response?.data?.message || 'Failed to verify account number with bank.';
  } finally {
    resolving.value = false;
  }
};

watch(() => form.account_number, (newVal) => {
  if (newVal.length === 10) {
    resolveNUBAN();
  } else {
    resolveSuccess.value = false;
  }
});

const saveBankDetails = () => {
  form.post('/bank-accounts/save', {
    preserveScroll: true,
  });
};
</script>

<template>
  <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4 shadow-xl">
    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
      <div class="flex items-center gap-2">
        <span class="text-xl">🏦</span>
        <div>
          <h3 class="text-sm font-bold text-slate-100">Verified Bank Account Settlement Details</h3>
          <p class="text-[11px] text-slate-400">Paystack NUBAN Account Name Auto-Verification</p>
        </div>
      </div>
      <span v-if="existingAccount?.is_verified" class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
        ✓ Bank Account Verified
      </span>
    </div>

    <form @submit.prevent="saveBankDetails" class="space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
        <!-- Select Bank -->
        <div>
          <label class="block font-bold text-slate-300 uppercase mb-1">Select Bank *</label>
          <select
            v-model="form.bank_code"
            @change="onBankChange"
            required
            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-100 focus:border-sky-500"
          >
            <option value="" disabled>-- Select Commercial / Microfinance Bank --</option>
            <option v-for="b in banks" :key="b.code" :value="b.code">
              {{ b.name }}
            </option>
          </select>
        </div>

        <!-- Account Number -->
        <div>
          <label class="block font-bold text-slate-300 uppercase mb-1">10-Digit NUBAN Account Number *</label>
          <div class="relative">
            <input
              v-model="form.account_number"
              type="text"
              maxlength="10"
              required
              placeholder="e.g. 0123456789"
              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-100 font-mono focus:border-sky-500"
            />
            <div v-if="resolving" class="absolute right-3 top-3">
              <svg class="w-4 h-4 text-sky-400 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Account Name Verification Box -->
      <div>
        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Account Holder Official Name</label>
        <div class="flex items-center gap-2">
          <input
            v-model="form.account_name"
            type="text"
            readonly
            placeholder="Auto-verifies upon entering 10-digit NUBAN..."
            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/80 border text-xs font-bold font-mono"
            :class="resolveSuccess ? 'border-emerald-500/50 text-emerald-300' : 'border-slate-800 text-slate-300'"
          />
          <button
            type="button"
            @click="resolveNUBAN"
            :disabled="resolving || form.account_number.length !== 10"
            class="px-3.5 py-2.5 rounded-xl bg-slate-800 text-xs font-bold text-slate-300 hover:text-white shrink-0 disabled:opacity-50"
          >
            Verify
          </button>
        </div>
        <p v-if="resolveSuccess" class="text-[11px] text-emerald-400 font-bold mt-1">✓ Paystack NUBAN Account Name Verified!</p>
        <p v-if="resolveError" class="text-[11px] text-rose-400 font-bold mt-1">✕ {{ resolveError }}</p>
      </div>

      <div class="flex items-center justify-end pt-2">
        <button
          type="submit"
          :disabled="form.processing || !form.account_name"
          class="px-6 py-2.5 rounded-xl bg-sky-500 text-slate-950 font-bold text-xs shadow-lg hover:scale-105 transition-all disabled:opacity-60"
        >
          <span>{{ form.processing ? 'Saving Bank Details...' : '💾 Save & Verify Bank Account' }}</span>
        </button>
      </div>
    </form>
  </div>
</template>
