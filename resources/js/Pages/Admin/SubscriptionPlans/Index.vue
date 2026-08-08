<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
  plans: Array<any>;
}>();

const editingPlan = ref<any>(null);
const editForm = useForm({
  name: '',
  price: 0,
  interval_days: 30,
  order_limit: null as number | null,
  description: '',
  is_active: true,
});

const openEditModal = (plan: any) => {
  editingPlan.value = plan;
  editForm.name = plan.name;
  editForm.price = Number(plan.price);
  editForm.interval_days = plan.interval_days || 30;
  editForm.order_limit = plan.order_limit;
  editForm.description = plan.description || '';
  editForm.is_active = !!plan.is_active;
};

const submitUpdatePlan = () => {
  if (!editingPlan.value) return;

  editForm.put(`/admin/subscription-plans/${editingPlan.value.id}`, {
    onSuccess: () => {
      editingPlan.value = null;
    },
  });
};
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xl">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Subscription Plans & Pricing Configurator</h1>
          <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">
            Configure platform subscription prices, features, order limits, and rider pass fees
          </p>
        </div>
      </div>

      <!-- Plans Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div
          v-for="plan in plans"
          :key="plan.id"
          class="bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-6 flex flex-col justify-between space-y-6 shadow-xl relative overflow-hidden"
        >
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase border"
                :class="plan.target_role === 'rider' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : 'bg-sky-500/10 text-sky-400 border-sky-500/20'"
              >
                {{ plan.target_role === 'rider' ? '🛵 Rider Pass' : '🏪 Shop Plan' }}
              </span>

              <span class="text-xs font-mono font-bold text-gray-500 dark:text-slate-400">Key: {{ plan.key }}</span>
            </div>

            <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100">{{ plan.name }}</h3>

            <div class="font-mono font-black text-2xl text-emerald-400">
              ₦{{ Number(plan.price).toLocaleString() }}
              <span class="text-xs font-normal text-gray-500 dark:text-slate-400">/ {{ plan.interval_days }} days</span>
            </div>

            <p class="text-xs text-gray-700 dark:text-slate-300 leading-relaxed">{{ plan.description }}</p>

            <div v-if="plan.features && plan.features.length" class="space-y-1 pt-2 border-t border-gray-200 dark:border-slate-700/60">
              <span class="text-[10px] font-bold text-gray-500 dark:text-slate-400 uppercase">Features:</span>
              <ul class="text-xs text-gray-700 dark:text-slate-300 space-y-1">
                <li v-for="(feat, fIdx) in plan.features" :key="fIdx" class="flex items-center gap-1.5">
                  <span class="text-emerald-400">✓</span>
                  <span>{{ feat }}</span>
                </li>
              </ul>
            </div>
          </div>

          <button
            @click="openEditModal(plan)"
            class="w-full py-2.5 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 hover:border-sky-500 text-sky-400 font-bold text-xs shadow transition-all"
          >
            ✏️ Configure Plan & Price
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Plan Modal -->
    <div v-if="editingPlan" class="fixed inset-0 z-50 bg-gray-50 dark:bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700/80 rounded-2xl w-full max-w-md p-6 space-y-4 shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-slate-800 pb-3">
          <h2 class="text-base font-bold text-gray-900 dark:text-slate-100">Configure '{{ editingPlan.name }}'</h2>
          <button @click="editingPlan = null" class="text-gray-500 dark:text-slate-400 hover:text-white">✕</button>
        </div>

        <form @submit.prevent="submitUpdatePlan" class="space-y-4 text-xs">
          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 uppercase mb-1">Plan Display Name *</label>
            <input
              v-model="editForm.name"
              type="text"
              required
              class="w-full px-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 text-xs"
            />
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 uppercase mb-1">Price (NGN) *</label>
            <input
              v-model.number="editForm.price"
              type="number"
              min="0"
              required
              class="w-full px-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 font-mono text-xs"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block font-semibold text-gray-700 dark:text-slate-300 uppercase mb-1">Billing Interval (Days)</label>
              <input
                v-model.number="editForm.interval_days"
                type="number"
                min="1"
                required
                class="w-full px-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 font-mono text-xs"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 dark:text-slate-300 uppercase mb-1">Monthly Order Limit</label>
              <input
                v-model.number="editForm.order_limit"
                type="number"
                min="1"
                placeholder="Unlimited if empty"
                class="w-full px-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 font-mono text-xs"
              />
            </div>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 dark:text-slate-300 uppercase mb-1">Description</label>
            <textarea
              v-model="editForm.description"
              rows="2"
              class="w-full px-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 text-xs"
            ></textarea>
          </div>

          <button
            type="submit"
            :disabled="editForm.processing"
            class="w-full py-3 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-xs shadow-lg"
          >
            Save Subscription Configuration
          </button>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
