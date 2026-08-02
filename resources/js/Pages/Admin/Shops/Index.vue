<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import AdvancedFilter, { FilterConfig } from '@/Components/AdvancedFilter.vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
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

const verifyShop = async (id: number) => {
  const confirmed = await confirmDialog(
    'Verify & Activate Shop?',
    'Are you sure you want to verify and activate this dry cleaner storefront and unlock operational features?'
  );
  if (confirmed) {
    useForm({}).post(`/admin/shops/${id}/verify`, {
      onSuccess: () => {
        if (selectedShop.value && selectedShop.value.id === id) {
          selectedShop.value.status = 'active';
          selectedShop.value.is_verified = true;
          selectedShop.value.kyc_status = 'approved';
        }
      },
    });
  }
};

const suspendShop = async (id: number) => {
  const confirmed = await confirmDialog(
    'Suspend Dry Cleaner Shop?',
    'Are you sure you want to suspend this shop? It will be hidden from customer search results and operational features will be locked.'
  );
  if (confirmed) {
    useForm({}).post(`/admin/shops/${id}/suspend`, {
      onSuccess: () => {
        if (selectedShop.value && selectedShop.value.id === id) {
          selectedShop.value.status = 'suspended';
        }
      },
    });
  }
};
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-100">Dry Cleaner Verification & KYC Audit</h1>
          <p class="text-xs text-slate-400 mt-1">Review shop registrations, storefront photos, CAC legal documents, and address coordinates</p>
        </div>
      </div>

      <!-- Advanced Filter Component -->
      <AdvancedFilter
        v-model="filterState"
        :filters-config="filterConfig"
        search-placeholder="Search shop name, email, or phone..."
        @filter-change="handleFilterChange"
      />

      <!-- Table Container -->
      <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl overflow-hidden shadow-xl">
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-900/80 text-xs uppercase text-slate-400 border-b border-slate-700/60">
            <tr>
              <th class="py-3.5 px-6">Shop Name</th>
              <th class="py-3.5 px-6">Structure</th>
              <th class="py-3.5 px-6">Owner</th>
              <th class="py-3.5 px-6">Contact & Location</th>
              <th class="py-3.5 px-6">Status</th>
              <th class="py-3.5 px-6 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-700/40">
            <tr v-for="shop in shops.data" :key="shop.id" class="hover:bg-slate-800/40 transition-colors">
              <td class="py-4 px-6 font-semibold text-slate-200">
                <div class="flex items-center gap-2">
                  <span>{{ shop.name }}</span>
                  <span v-if="shop.is_verified" class="text-xs text-sky-400" title="Verified Storefront">✓</span>
                </div>
                <div class="text-[11px] text-slate-500 font-mono">/shop/{{ shop.slug }}</div>
              </td>
              <td class="py-4 px-6 text-xs">
                <span
                  class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                  :class="shop.business_type === 'cac_registered' ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : 'bg-slate-800 text-slate-400'"
                >
                  {{ shop.business_type === 'cac_registered' ? '🏛️ CAC Registered' : '🏪 Independent' }}
                </span>
              </td>
              <td class="py-4 px-6 text-slate-300 text-xs">
                <div class="font-medium text-slate-200">{{ shop.owner?.first_name }} {{ shop.owner?.last_name }}</div>
                <div class="text-slate-400 text-[11px]">{{ shop.owner?.email }}</div>
              </td>
              <td class="py-4 px-6 text-slate-400 text-xs">
                <div>{{ shop.phone }}</div>
                <div class="text-slate-500 text-[11px] truncate max-w-xs" :title="shop.address">{{ shop.address }}</div>
              </td>
              <td class="py-4 px-6">
                <Badge :status="shop.status" />
              </td>
              <td class="py-4 px-6 text-right space-x-2">
                <!-- View Details Button -->
                <button
                  @click="openShopModal(shop)"
                  class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-sky-500/10 text-sky-400 border border-sky-500/20 hover:bg-sky-500/20 transition-all inline-flex items-center gap-1"
                >
                  <span>👁️</span>
                  <span>View Audit</span>
                </button>

                <!-- Status Action Button -->
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

          <!-- Storefront Photos Gallery & Documents -->
          <div>
            <h4 class="text-xs font-bold text-sky-400 uppercase tracking-wider mb-3">Uploaded KYC Documents & Storefront Media</h4>

            <div v-if="selectedShop.kycDocuments && selectedShop.kycDocuments.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div
                v-for="doc in selectedShop.kycDocuments"
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
          <a
            :href="`/shop/${selectedShop.slug}`"
            target="_blank"
            class="px-4 py-2 rounded-xl text-xs font-semibold bg-sky-500/10 text-sky-400 border border-sky-500/20 hover:bg-sky-500/20 transition-all inline-flex items-center gap-1.5"
          >
            <span>🔗 Preview Public Storefront</span>
          </a>

          <div class="flex items-center gap-3">
            <button
              v-if="selectedShop.status !== 'active'"
              @click="verifyShop(selectedShop.id)"
              class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20 transition-all"
            >
              Approve KYC & Activate Shop
            </button>
            <button
              v-if="selectedShop.status === 'active'"
              @click="suspendShop(selectedShop.id)"
              class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500/20 transition-all"
            >
              Suspend Shop
            </button>
            <button
              @click="closeModal"
              class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-800 text-slate-300 hover:bg-slate-700 transition-all"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
