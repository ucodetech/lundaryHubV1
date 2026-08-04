<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import AdvancedFilter, { FilterConfig } from '@/Components/AdvancedFilter.vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { confirmDialog } from '@/Utils/swal';

const props = defineProps<{
  shops: any;
  filters?: Record<string, any>;
}>();

const filterState = ref<Record<string, any>>({ ...props.filters });

// Selected shop for details modal
const selectedShop = ref<any | null>(null);
const showModal = ref(false);

function openShopModal(shop: any) {
  selectedShop.value = shop;
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  selectedShop.value = null;
}

function getKycDocs(shop: any): Array<any> {
  if (!shop) return [];
  return shop.kyc_documents || shop.kycDocuments || [];
}

// Keep selectedShop reactively updated when props.shops changes
watch(
  () => props.shops,
  (newShops) => {
    if (selectedShop.value && newShops?.data) {
      const updated = newShops.data.find((s: any) => s.id === selectedShop.value.id);
      if (updated) {
        selectedShop.value = updated;
      }
    }
  },
  { deep: true }
);

const filterConfig: FilterConfig[] = [
  {
    key: 'search',
    label: 'Search',
    type: 'text',
    placeholder: 'Search shop name, owner, phone...',
  },
  {
    key: 'status',
    label: 'Shop Status',
    type: 'select',
    defaultVisible: true,
    options: [
      { label: 'Active', value: 'active' },
      { label: 'Pending Approval', value: 'pending' },
      { label: 'Suspended', value: 'suspended' },
    ],
  },
  {
    key: 'is_verified',
    label: 'Verification Status',
    type: 'select',
    options: [
      { label: 'Verified', value: '1' },
      { label: 'Unverified', value: '0' },
    ],
  },
];

const handleFilterChange = (newFilters: Record<string, any>) => {
  router.get('/admin/shops', newFilters, {
    preserveState: true,
    replace: true,
  });
};

const verifyShop = async (shopId: number) => {
  const confirmed = await confirmDialog(
    'Verify Dry Cleaning Shop?',
    'This will activate the shop storefront, unlock categories & pricing management, provision a 1-Month Free Trial, and generate a Paystack Dedicated Virtual Account.'
  );

  if (confirmed) {
    router.post(`/admin/shops/${shopId}/verify`, {}, {
      preserveScroll: true,
      onSuccess: () => {
        closeModal();
      },
    });
  }
};

const generateVirtualAccount = async (shopId: number) => {
  const confirmed = await confirmDialog(
    'Generate Dedicated Virtual Account?',
    'This will trigger Paystack API to create or assign a Dedicated Virtual Account for direct customer transfer settlements.'
  );

  if (confirmed) {
    router.post(`/admin/shops/${shopId}/generate-virtual-account`, {}, {
      preserveScroll: true,
    });
  }
};

const suspendShop = async (shopId: number) => {
  const confirmed = await confirmDialog(
    'Suspend Shop?',
    'This will temporarily deactivate the shop storefront and block customer order bookings.',
    'warning'
  );

  if (confirmed) {
    router.post(`/admin/shops/${shopId}/suspend`, {}, {
      preserveScroll: true,
      onSuccess: () => {
        closeModal();
      },
    });
  }
};
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-100">Dry Cleaning Shops Audit</h1>
          <p class="text-xs text-slate-400 mt-1">Review shop storefront registrations, audit uploaded KYC media, approve shops, and manage Paystack virtual settlement accounts.</p>
        </div>

        <div class="flex items-center gap-3 text-xs">
          <span class="px-3 py-1.5 rounded-xl bg-slate-800 border border-slate-700 text-slate-300">
            Total Shops: <strong class="text-sky-400 font-mono">{{ shops.total || 0 }}</strong>
          </span>
        </div>
      </div>

      <!-- Advanced Filter -->
      <AdvancedFilter
        :config="filterConfig"
        :initial-values="filterState"
        @filter-change="handleFilterChange"
      />

      <!-- Shops Data Table -->
      <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-700/80 bg-slate-900/50 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                <th class="py-3.5 px-4">Shop Details</th>
                <th class="py-3.5 px-4">Owner</th>
                <th class="py-3.5 px-4">Paystack Virtual Account</th>
                <th class="py-3.5 px-4">KYC Audit</th>
                <th class="py-3.5 px-4">Status</th>
                <th class="py-3.5 px-4 text-right">Actions</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-slate-700/50 text-xs">
              <tr v-for="shop in shops.data" :key="shop.id" class="hover:bg-slate-700/30 transition-colors">
                <!-- Shop Details -->
                <td class="py-4 px-4">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-500 to-cyan-400 flex items-center justify-center font-extrabold text-slate-950 text-base shadow">
                      {{ shop.name[0] }}
                    </div>
                    <div>
                      <div class="font-bold text-slate-100 text-sm flex items-center gap-1.5">
                        <span>{{ shop.name }}</span>
                        <a
                          :href="`/shop/${shop.slug}`"
                          target="_blank"
                          class="text-sky-400 text-[11px] font-normal hover:underline"
                          title="View Public Storefront"
                        >
                          🔗
                        </a>
                      </div>
                      <p class="text-[11px] text-slate-400">{{ shop.address }}</p>
                    </div>
                  </div>
                </td>

                <!-- Owner -->
                <td class="py-4 px-4">
                  <div v-if="shop.owner">
                    <div class="font-semibold text-slate-200">{{ shop.owner.first_name }} {{ shop.owner.last_name }}</div>
                    <div class="text-[11px] text-slate-400 font-mono">{{ shop.phone }}</div>
                  </div>
                  <span v-else class="text-slate-500 italic">No Owner Assigned</span>
                </td>

                <!-- Paystack Virtual Account Details -->
                <td class="py-4 px-4">
                  <div v-if="shop.virtual_account || shop.virtualAccount" class="space-y-1">
                    <span class="text-emerald-400 font-mono font-bold text-xs block">
                      {{ (shop.virtual_account || shop.virtualAccount).account_number || 'Pending Number' }}
                    </span>
                    <span class="text-[10px] text-slate-300 block">
                      {{ (shop.virtual_account || shop.virtualAccount).bank_name || 'Wema Bank' }}
                    </span>
                  </div>
                  <div v-else class="space-y-1">
                    <span class="text-amber-400 text-[11px] font-semibold block">Not Provisioned</span>
                    <button
                      @click="generateVirtualAccount(shop.id)"
                      class="px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-bold hover:bg-emerald-500/20 transition-all inline-flex items-center gap-1"
                    >
                      <span>🏦 Generate Account</span>
                    </button>
                  </div>
                </td>

                <!-- KYC Documents Status -->
                <td class="py-4 px-4">
                  <button
                    @click="openShopModal(shop)"
                    class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 hover:border-sky-500 text-slate-200 text-xs font-semibold flex items-center gap-1.5 transition-all"
                  >
                    <span>📷</span>
                    <span>KYC Audit ({{ getKycDocs(shop).length }})</span>
                  </button>
                </td>

                <!-- Status Badge -->
                <td class="py-4 px-4">
                  <Badge :status="shop.status" />
                </td>

                <!-- Actions -->
                <td class="py-4 px-4 text-right space-x-2">
                  <button
                    @click="openShopModal(shop)"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-700/60 text-slate-200 hover:bg-slate-700 transition-all"
                  >
                    Audit
                  </button>

                  <button
                    @click="generateVirtualAccount(shop.id)"
                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-purple-500/10 text-purple-300 border border-purple-500/20 hover:bg-purple-500/20 transition-all"
                    title="Generate or Re-sync Paystack Virtual Account"
                  >
                    🏦 Account
                  </button>

                  <button
                    v-if="shop.status !== 'active'"
                    @click="verifyShop(shop.id)"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20 transition-all"
                  >
                    Verify & Activate
                  </button>

                  <button
                    v-if="shop.status === 'active'"
                    @click="suspendShop(shop.id)"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500/20 transition-all"
                  >
                    Suspend
                  </button>
                </td>
              </tr>

              <tr v-if="!shops.data || shops.data.length === 0">
                <td colspan="6" class="py-12 text-center text-slate-400 text-xs">
                  No dry cleaner shops matching the selected filters.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Shop Details & KYC Audit Modal -->
    <div
      v-if="showModal && selectedShop"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-in fade-in"
      @click.self="closeModal"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto shadow-2xl custom-scrollbar flex flex-col">
        <!-- Modal Header -->
        <div class="p-6 border-b border-slate-800/80 flex items-start justify-between bg-slate-950/50 sticky top-0 z-10">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-sky-500 to-cyan-400 flex items-center justify-center font-extrabold text-slate-950 text-xl shadow-lg">
              {{ selectedShop.name[0] }}
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-lg font-bold text-slate-100">{{ selectedShop.name }}</h3>
                <Badge :status="selectedShop.status" />
              </div>
              <p class="text-xs text-slate-400 font-mono mt-0.5">Slug: {{ selectedShop.slug }}</p>
            </div>
          </div>
          <button @click="closeModal" class="text-slate-400 hover:text-slate-200 text-lg p-1">✕</button>
        </div>

        <!-- Modal Content Body -->
        <div class="p-6 space-y-6">
          <!-- Overview Description -->
          <div>
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Shop Overview & Structure</h4>
            <div class="bg-slate-950/60 p-4 rounded-xl border border-slate-800/80 space-y-2 text-sm text-slate-300">
              <p>{{ selectedShop.description || 'No description provided.' }}</p>
              <div class="flex items-center gap-3 pt-2 text-xs">
                <span class="text-slate-500">Business Structure:</span>
                <span class="font-bold text-purple-400">
                  {{ selectedShop.business_type === 'cac_registered' ? '🏛️ CAC Registered Business' : '🏪 Independent Operator' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Paystack Virtual Account Card -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <h4 class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Paystack Dedicated Settlement Account</h4>
              <button
                @click="generateVirtualAccount(selectedShop.id)"
                class="px-3 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-xs font-bold hover:bg-emerald-500/20 transition-all flex items-center gap-1"
              >
                <span>⚡</span>
                <span>Generate / Re-sync Virtual Account</span>
              </button>
            </div>

            <div v-if="selectedShop.virtual_account || selectedShop.virtualAccount" class="bg-slate-950/80 p-4 rounded-xl border border-emerald-500/30 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs font-mono">
              <div>
                <span class="text-[10px] text-slate-400 uppercase block font-sans">Bank Name</span>
                <strong class="text-slate-200 text-sm block">{{ (selectedShop.virtual_account || selectedShop.virtualAccount).bank_name || 'Wema Bank' }}</strong>
              </div>

              <div>
                <span class="text-[10px] text-slate-400 uppercase block font-sans">Account Number</span>
                <strong class="text-emerald-400 text-base block font-bold">{{ (selectedShop.virtual_account || selectedShop.virtualAccount).account_number || 'Pending' }}</strong>
              </div>

              <div>
                <span class="text-[10px] text-slate-400 uppercase block font-sans">Account Name</span>
                <strong class="text-slate-200 text-xs block truncate">{{ (selectedShop.virtual_account || selectedShop.virtualAccount).account_name || selectedShop.name }}</strong>
              </div>
            </div>

            <div v-else class="bg-slate-950/40 p-4 rounded-xl border border-slate-800 text-xs text-slate-400 flex items-center justify-between">
              <span>No virtual account provisioned yet for this shop.</span>
              <button
                @click="generateVirtualAccount(selectedShop.id)"
                class="px-3 py-1.5 rounded-xl bg-emerald-500 text-slate-950 font-bold text-xs shadow hover:scale-105 transition-transform"
              >
                Generate Paystack DVA Now
              </button>
            </div>
          </div>

          <!-- Storefront Photos Gallery & Documents -->
          <div>
            <h4 class="text-xs font-bold text-sky-400 uppercase tracking-wider mb-3">Uploaded KYC Documents & Storefront Media</h4>

            <div v-if="getKycDocs(selectedShop).length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div
                v-for="doc in getKycDocs(selectedShop)"
                :key="doc.id"
                class="bg-slate-950/60 p-4 rounded-xl border border-slate-800 space-y-2"
              >
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-slate-200 capitalize">
                    {{ doc.document_type.replace('_', ' ') }}
                  </span>
                  <span class="text-[10px] px-2 py-0.5 rounded-full bg-sky-500/10 text-sky-400 border border-sky-500/20 uppercase font-mono">
                    {{ doc.status }}
                  </span>
                </div>

                <div v-if="doc.file_path.match(/\.(jpg|jpeg|png|webp)$/i)" class="mt-2">
                  <img :src="doc.file_path" class="h-36 w-full object-cover rounded-xl border border-slate-800" />
                </div>
                <div v-else class="mt-2 pt-2">
                  <a :href="doc.file_path" target="_blank" class="px-3 py-1.5 rounded-lg bg-sky-500/10 text-sky-400 border border-sky-500/20 text-xs font-semibold hover:underline inline-flex items-center gap-1">
                    📄 View / Download Document
                  </a>
                </div>
              </div>
            </div>
            <div v-else class="p-6 text-center text-xs text-slate-400 bg-slate-950/40 rounded-xl border border-slate-800">
              No KYC files uploaded by shop owner yet.
            </div>
          </div>

          <!-- Contact & Location Coordinates -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-slate-950/40 p-4 rounded-xl border border-slate-800/60 space-y-2">
              <h4 class="text-xs font-bold text-sky-400 uppercase tracking-wider">Contact & Google Address</h4>
              <div class="text-xs space-y-1 text-slate-300">
                <p><span class="text-slate-500">Phone:</span> {{ selectedShop.phone }}</p>
                <p><span class="text-slate-500">Email:</span> {{ selectedShop.email }}</p>
                <p><span class="text-slate-500">Address:</span> {{ selectedShop.address }}</p>
                <p v-if="selectedShop.latitude && selectedShop.longitude" class="text-slate-400 text-[11px]">
                  <span class="text-slate-500">GPS Pin:</span> {{ selectedShop.latitude }}, {{ selectedShop.longitude }}
                </p>
              </div>
            </div>

            <div class="bg-slate-950/40 p-4 rounded-xl border border-slate-800/60 space-y-2">
              <h4 class="text-xs font-bold text-cyan-400 uppercase tracking-wider">Operational Parameters</h4>
              <div class="text-xs space-y-1 text-slate-300">
                <p><span class="text-slate-500">Delivery Fee:</span> ₦{{ Number(selectedShop.delivery_fee).toLocaleString() }}</p>
                <p><span class="text-slate-500">Pickup Radius:</span> {{ selectedShop.pickup_radius_km }} km</p>
                <p><span class="text-slate-500">Verified Status:</span> {{ selectedShop.is_verified ? 'Verified & Approved ✓' : 'Unverified / Pending' }}</p>
                <p><span class="text-slate-500">Registered Date:</span> {{ new Date(selectedShop.created_at).toLocaleDateString() }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer Actions -->
        <div class="p-6 border-t border-slate-800/80 bg-slate-950/50 flex flex-wrap items-center justify-between gap-3 sticky bottom-0 z-10">
          <div class="flex items-center gap-2">
            <button
              v-if="selectedShop.status !== 'active'"
              @click="verifyShop(selectedShop.id)"
              class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-500 text-slate-950 hover:bg-emerald-400 transition-all shadow-lg shadow-emerald-500/20"
            >
              Verify & Approve Shop ✓
            </button>

            <button
              @click="generateVirtualAccount(selectedShop.id)"
              class="px-4 py-2 rounded-xl text-xs font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30 hover:bg-purple-500/30 transition-all"
            >
              🏦 Generate / Re-sync DVA
            </button>

            <button
              v-if="selectedShop.status === 'active'"
              @click="suspendShop(selectedShop.id)"
              class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500/20 transition-all"
            >
              Suspend Shop
            </button>
          </div>

          <button @click="closeModal" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-800 text-slate-300 hover:bg-slate-700 transition-all">
            Close Audit
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
