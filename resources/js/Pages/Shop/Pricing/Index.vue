<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { confirmDialog } from '@/Utils/swal';
import { UserRole } from '@/Enums/UserRole';

defineProps<{
  prices: Array<any>;
  categories: Array<any>;
  services: Array<any>;
  masterPrices?: Array<any>;
  shop: any;
}>();

const page = usePage();
const userRole = computed(() => page.props.auth?.user?.role);
const isShopOwner = computed(() => userRole.value === UserRole.SHOP_OWNER);

const showEditModal = ref(false);
const editingPrice = ref<any | null>(null);

const form = useForm({
  category_id: null,
  service_id: null,
  amount: 500,
});

const editForm = useForm({
  amount: 0,
});

const submit = () => {
  form.post('/shop-admin/pricing', {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
    },
  });
};

const openEditModal = (price: any) => {
  editingPrice.value = price;
  editForm.amount = price.amount;
  showEditModal.value = true;
};

const submitEdit = () => {
  if (!editingPrice.value) return;
  editForm.put(`/shop-admin/pricing/${editingPrice.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showEditModal.value = false;
      editingPrice.value = null;
    },
  });
};

const deletePrice = async (id: number) => {
  const confirmed = await confirmDialog(
    'Delete Price Rule?',
    'Are you sure you want to delete this price entry from your catalog?'
  );
  if (confirmed) {
    useForm({}).delete(`/shop-admin/pricing/${id}`, {
      preserveScroll: true,
    });
  }
};

const cloneMasterPrice = (priceId: number) => {
  useForm({}).post(`/shop-admin/pricing/${priceId}/clone`, {
    preserveScroll: true,
  });
};

const cloneAllMasterTemplates = async () => {
  const confirmed = await confirmDialog(
    'Import All Master Templates?',
    'Batch import all platform master categories, services, and default pricing matrix into your shop catalog?',
    'Yes, Import Catalog'
  );
  if (confirmed) {
    useForm({}).post('/shop-admin/pricing/clone-all', {
      preserveScroll: true,
    });
  }
const normalizeName = (name: string) => {
  if (!name) return '';
  return name.replace(/\s*\(Master Template\)\s*/gi, '').trim().toLowerCase();
};

const isMasterPriceCloned = (mPrice: any) => {
  if (!props.prices || props.prices.length === 0) return false;
  const mCatName = normalizeName(mPrice.category?.name);
  const mSvcName = normalizeName(mPrice.service?.name);

  return props.prices.some((p: any) => {
    const pCatName = normalizeName(p.category?.name);
    const pSvcName = normalizeName(p.service?.name);
    return pCatName === mCatName && pSvcName === mSvcName;
  });
};
</script>

<template>
  <AppLayout>
    <div class="space-y-8">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Pricing Engine</h1>
          <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Set custom Category × Service prices or clone master platform templates</p>
        </div>

        <!-- Import All Button (Only visible for Shop Owners) -->
        <button
          v-if="isShopOwner && shop"
          @click="cloneAllMasterTemplates"
          class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-bold text-xs shadow-lg shadow-purple-500/20 hover:scale-105 transition-transform flex items-center gap-2"
        >
          <span>⚡</span>
          <span>Import All Master Templates</span>
        </button>
      </div>

      <!-- Add Price Form -->
      <div class="bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-6 shadow-xl">
        <h3 class="font-bold text-gray-800 dark:text-slate-200 text-sm mb-4">Set Price Rule</h3>

        <form @submit.prevent="submit" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-1">Category *</label>
            <select
              v-model="form.category_id"
              required
              class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500"
            >
              <option :value="null" disabled>Select Category...</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-1">Service *</label>
            <select
              v-model="form.service_id"
              required
              class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500"
            >
              <option :value="null" disabled>Select Service...</option>
              <option v-for="srv in services" :key="srv.id" :value="srv.id">
                {{ srv.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-1">Price (₦) *</label>
            <input
              v-model="form.amount"
              type="number"
              required
              min="0"
              placeholder="500"
              class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500"
            />
          </div>

          <div>
            <button
              type="submit"
              :disabled="form.processing"
              class="w-full py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-sm shadow-lg shadow-sky-500/20 hover:opacity-90 transition-opacity"
            >
              Save Price Rule
            </button>
          </div>
        </form>
      </div>

      <!-- Section 1: Active Shop Pricing Table -->
      <div class="space-y-3">
        <h2 class="text-sm font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2">
          <span>💳 Active Shop Price Rules</span>
          <span class="px-2 py-0.5 rounded-full text-[10px] bg-sky-500/10 text-sky-400 border border-sky-500/20 font-mono">{{ prices.length }}</span>
        </h2>

        <div class="bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl overflow-hidden overflow-x-auto custom-scrollbar shadow-xl">
          <table class="w-full text-left text-sm">
            <thead class="bg-white dark:bg-slate-900/80 text-xs uppercase text-gray-500 dark:text-slate-400 border-b border-gray-200 dark:border-slate-700/60">
              <tr>
                <th class="py-3.5 px-6">Category</th>
                <th class="py-3.5 px-6">Service</th>
                <th class="py-3.5 px-6 text-right">Price (₦)</th>
                <th class="py-3.5 px-6 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-slate-700/40">
              <tr v-for="price in prices" :key="price.id" class="hover:bg-slate-800/40 transition-colors">
                <td class="py-4 px-6 font-semibold text-gray-800 dark:text-slate-200">
                  <span v-if="price.category?.icon" class="mr-1.5">{{ price.category.icon }}</span>
                  <span>{{ price.category?.name }}</span>
                </td>
                <td class="py-4 px-6 text-gray-700 dark:text-slate-300">{{ price.service?.name }}</td>
                <td class="py-4 px-6 text-right font-bold text-sky-400">₦{{ Number(price.amount).toLocaleString() }}</td>
                <td class="py-4 px-6 text-right space-x-2">
                  <button
                    @click="openEditModal(price)"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20 hover:bg-sky-500/20 transition-all"
                  >
                    Edit
                  </button>
                  <button
                    @click="deletePrice(price.id)"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500/20 transition-all"
                  >
                    Delete
                  </button>
                </td>
              </tr>
              <tr v-if="prices.length === 0">
                <td colspan="4" class="py-8 text-center text-gray-500 dark:text-slate-400 text-xs">
                  No shop price rules defined. Use the form above to add pricing or click "Import All Master Templates".
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Section 2: Platform Master Default Pricing Matrix -->
      <div v-if="masterPrices && masterPrices.length > 0" class="space-y-3 pt-4 border-t border-gray-200 dark:border-slate-800/80">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-sm font-bold text-purple-300 flex items-center gap-2">
              <span>📋 Platform Master Pricing Templates</span>
              <span class="px-2 py-0.5 rounded-full text-[10px] bg-purple-500/10 text-purple-400 border border-purple-500/20">Admin Catalog</span>
            </h2>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Platform default price rules template matrix</p>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900/80 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden overflow-x-auto custom-scrollbar shadow-xl">
          <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 dark:bg-slate-950/80 text-xs uppercase text-gray-500 dark:text-slate-400 border-b border-gray-200 dark:border-slate-800">
              <tr>
                <th class="py-3 px-6">Category Template</th>
                <th class="py-3 px-6">Service Template</th>
                <th class="py-3 px-6 text-right">Default Base Price</th>
                <th v-if="isShopOwner" class="py-3 px-6 text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-slate-800/60">
              <tr v-for="mPrice in masterPrices" :key="mPrice.id" class="hover:bg-slate-800/30">
                <td class="py-3 px-6 text-xs font-medium text-gray-700 dark:text-slate-300">
                  <span v-if="mPrice.category?.icon" class="mr-1.5">{{ mPrice.category.icon }}</span>
                  <span>{{ mPrice.category?.name }}</span>
                </td>
                <td class="py-3 px-6 text-xs text-gray-500 dark:text-slate-400">{{ mPrice.service?.name }}</td>
                <td class="py-3 px-6 text-right text-xs font-semibold text-purple-300">₦{{ Number(mPrice.amount).toLocaleString() }}</td>
                <td v-if="isShopOwner" class="py-3 px-6 text-right">
                  <span
                    v-if="isMasterPriceCloned(mPrice)"
                    class="px-2.5 py-1 rounded-xl text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"
                  >
                    ✓ Already Cloned
                  </span>
                  <button
                    v-else
                    @click="cloneMasterPrice(mPrice.id)"
                    class="px-3 py-1 rounded-xl text-xs font-semibold bg-purple-500/10 text-purple-300 border border-purple-500/20 hover:bg-purple-500/20 transition-all"
                  >
                    📋 Clone to Shop
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Edit Price Rule Modal -->
      <div v-if="showEditModal" class="fixed inset-0 bg-gray-50 dark:bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-6 w-full max-w-md space-y-4 shadow-2xl">
          <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100">Edit Shop Price Rule</h3>
          <p class="text-xs text-gray-500 dark:text-slate-400">
            Category: <strong class="text-gray-800 dark:text-slate-200">{{ editingPrice?.category?.name }}</strong> • Service: <strong class="text-gray-800 dark:text-slate-200">{{ editingPrice?.service?.name }}</strong>
          </p>

          <form @submit.prevent="submitEdit" class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-1">Custom Price Amount (₦) *</label>
              <input
                v-model="editForm.amount"
                type="number"
                required
                min="0"
                class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500"
              />
            </div>

            <div class="flex justify-end gap-3 pt-2">
              <button
                type="button"
                @click="showEditModal = false"
                class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-500 dark:text-slate-400 hover:text-slate-200"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="editForm.processing"
                class="px-5 py-2 rounded-xl bg-sky-500 text-slate-950 font-bold text-xs shadow-lg shadow-sky-500/20"
              >
                Update Price Rule
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
