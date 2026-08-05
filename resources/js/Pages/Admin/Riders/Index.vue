<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import AdvancedFilter, { FilterConfig } from '@/Components/AdvancedFilter.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { confirmDialog, promptDialog } from '@/Utils/swal';

const props = defineProps<{
  riders: any;
  filters?: Record<string, any>;
}>();

const filterState = ref<Record<string, any>>({ ...props.filters });

const selectedRider = ref<any | null>(null);
const showAuditModal = ref(false);

const filterConfig: FilterConfig[] = [
  {
    key: 'search',
    label: 'Search',
    type: 'text',
    placeholder: 'Search rider name, phone, plate...',
  },
  {
    key: 'kyc_status',
    label: 'KYC Status',
    type: 'select',
    defaultVisible: true,
    options: [
      { label: 'Approved', value: 'approved' },
      { label: 'Pending Audit', value: 'pending' },
      { label: 'Rejected', value: 'rejected' },
    ],
  },
  {
    key: 'vehicle_type',
    label: 'Vehicle Type',
    type: 'select',
    defaultVisible: true,
    options: [
      { label: 'Motorcycle', value: 'motorcycle' },
      { label: 'Bicycle', value: 'bicycle' },
      { label: 'Car', value: 'car' },
      { label: 'Van', value: 'van' },
    ],
  },
  {
    key: 'is_online',
    label: 'Online Status',
    type: 'select',
    options: [
      { label: 'Online Now', value: '1' },
      { label: 'Offline', value: '0' },
    ],
  },
];

const handleFilterChange = (newFilters: Record<string, any>) => {
  router.get('/admin/riders', newFilters, {
    preserveState: true,
    replace: true,
  });
};

const openAuditModal = (rider: any) => {
  selectedRider.value = rider;
  showAuditModal.value = true;
};

const closeModal = () => {
  showAuditModal.value = false;
  selectedRider.value = null;
};

const getKycDocs = (rider: any) => {
  return rider?.kyc_documents || rider?.kycDocuments || [];
};

const approveRider = async (id: number) => {
  const confirmed = await confirmDialog(
    'Approve Rider KYC?',
    'Are you sure you want to approve this rider account for deliveries?'
  );
  if (confirmed) {
    useForm({}).post(`/admin/riders/${id}/approve`, {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    });
  }
};

const rejectRider = async (id: number) => {
  const reason = await promptDialog(
    'Reject Rider KYC',
    'Enter specific reason for rejecting verification documents...'
  );
  if (reason) {
    useForm({ reason }).post(`/admin/riders/${id}/reject`, {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    });
  }
};
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-100">Rider Verification & KYC Audit</h1>
          <p class="text-xs text-slate-400 mt-1">Review rider verification documents, live selfie face verification, and manage approval statuses</p>
        </div>
      </div>

      <!-- Advanced Filter Component -->
      <AdvancedFilter
        v-model="filterState"
        :filters-config="filterConfig"
        search-placeholder="Search rider name, vehicle plate, or license..."
        @filter-change="handleFilterChange"
      />

      <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl overflow-hidden overflow-x-auto custom-scrollbar shadow-xl">
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-900/80 text-xs uppercase text-slate-400 border-b border-slate-700/60">
            <tr>
              <th class="py-3.5 px-6">Rider Name</th>
              <th class="py-3.5 px-6">Vehicle Details</th>
              <th class="py-3.5 px-6">KYC Status</th>
              <th class="py-3.5 px-6 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-700/40">
            <tr v-for="rider in riders.data" :key="rider.id" class="hover:bg-slate-800/40 transition-colors">
              <td class="py-4 px-6 font-semibold text-slate-200">
                {{ rider.user?.first_name }} {{ rider.user?.last_name }}
                <div class="text-xs text-slate-400 font-normal">{{ rider.user?.phone }}</div>
              </td>
              <td class="py-4 px-6 text-slate-300 text-xs capitalize">
                {{ rider.vehicle_type }} • {{ rider.vehicle_plate ?? 'No Plate' }}
              </td>
              <td class="py-4 px-6">
                <Badge :status="rider.kyc_status" />
              </td>
              <td class="py-4 px-6 text-right space-x-2">
                <button
                  @click="openAuditModal(rider)"
                  class="px-3 py-1.5 rounded-lg text-xs font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20 hover:bg-sky-500/20 transition-all"
                >
                  🔍 Audit KYC Docs
                </button>
                <button
                  v-if="rider.kyc_status !== 'approved'"
                  @click="approveRider(rider.id)"
                  class="px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20 transition-all"
                >
                  Approve
                </button>
                <button
                  v-if="rider.kyc_status !== 'rejected'"
                  @click="rejectRider(rider.id)"
                  class="px-3 py-1.5 rounded-lg text-xs font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500/20 transition-all"
                >
                  Reject
                </button>
              </td>
            </tr>

            <tr v-if="!riders.data || riders.data.length === 0">
              <td colspan="4" class="py-12 text-center text-slate-400 text-xs">
                No delivery riders matching the selected filters.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Super Admin Rider KYC Audit Modal -->
      <div v-if="showAuditModal && selectedRider" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-3xl space-y-6 shadow-2xl max-h-[90vh] overflow-y-auto custom-scrollbar">
          <div class="flex items-center justify-between border-b border-slate-800 pb-4">
            <div>
              <h3 class="text-lg font-bold text-slate-100 flex items-center gap-2">
                <span>🛵 {{ selectedRider.user?.first_name }} {{ selectedRider.user?.last_name }}</span>
                <Badge :status="selectedRider.kyc_status" />
              </h3>
              <p class="text-xs text-slate-400 mt-0.5">📞 {{ selectedRider.user?.phone }} • ✉️ {{ selectedRider.user?.email }}</p>
            </div>
            <button @click="closeModal" class="text-slate-400 hover:text-white text-lg">✕</button>
          </div>

          <!-- Rider Vehicle & License Info -->
          <div class="grid grid-cols-3 gap-4 bg-slate-950/80 border border-slate-800 rounded-xl p-4 text-xs">
            <div>
              <span class="text-slate-500 block uppercase font-semibold text-[10px]">Vehicle Type</span>
              <span class="font-bold text-slate-200 capitalize">{{ selectedRider.vehicle_type }}</span>
            </div>
            <div>
              <span class="text-slate-500 block uppercase font-semibold text-[10px]">Plate Number</span>
              <span class="font-bold text-slate-200">{{ selectedRider.vehicle_plate || '—' }}</span>
            </div>
            <div>
              <span class="text-slate-500 block uppercase font-semibold text-[10px]">License Number</span>
              <span class="font-bold text-slate-200">{{ selectedRider.license_number || '—' }}</span>
            </div>
          </div>

          <!-- Submitted Verification Documents & Face Selfie -->
          <div class="space-y-3">
            <h4 class="text-xs font-bold text-slate-200 uppercase tracking-wider">Submitted Identity & Live Face Documents</h4>

            <div v-if="getKycDocs(selectedRider).length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
              <div
                v-for="doc in getKycDocs(selectedRider)"
                :key="doc.id"
                class="bg-slate-950 border border-slate-800 rounded-xl p-3 space-y-2 flex flex-col justify-between"
              >
                <!-- Image or PDF Preview -->
                <div class="relative aspect-video rounded-lg overflow-hidden bg-slate-900 border border-slate-800 flex items-center justify-center">
                  <img
                    v-if="doc.file_path && (doc.file_path.includes('.jpg') || doc.file_path.includes('.jpeg') || doc.file_path.includes('.png') || doc.file_path.includes('cloudinary.com'))"
                    :src="doc.file_path"
                    :alt="doc.document_type"
                    class="w-full h-full object-cover"
                  />
                  <div v-else class="text-slate-400 text-xs flex flex-col items-center gap-1">
                    <span class="text-xl">📄</span>
                    <span>PDF Document</span>
                  </div>
                </div>

                <div class="flex items-center justify-between text-xs">
                  <span class="font-bold text-slate-200 capitalize flex items-center gap-1">
                    <span v-if="doc.document_type === 'selfie'">🤳</span>
                    <span>{{ doc.document_type?.replace('_', ' ') }}</span>
                  </span>
                  <span class="text-[10px] font-mono text-slate-400">{{ doc.status }}</span>
                </div>

                <a
                  :href="doc.file_path"
                  target="_blank"
                  class="block text-center text-[11px] text-sky-400 hover:underline pt-1"
                >
                  View Cloudinary Asset ↗
                </a>
              </div>
            </div>

            <div v-else class="py-8 text-center bg-slate-950/60 border border-slate-800 rounded-xl text-slate-400 text-xs">
              No KYC documents uploaded by this rider yet.
            </div>
          </div>

          <!-- Audit Modal Action Buttons -->
          <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-slate-200"
            >
              Close
            </button>
            <button
              v-if="selectedRider.kyc_status !== 'rejected'"
              type="button"
              @click="rejectRider(selectedRider.id)"
              class="px-4 py-2 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500/20 text-xs font-bold"
            >
              Reject Rider
            </button>
            <button
              v-if="selectedRider.kyc_status !== 'approved'"
              type="button"
              @click="approveRider(selectedRider.id)"
              class="px-5 py-2 rounded-xl bg-emerald-500 text-slate-950 text-xs font-bold shadow-lg shadow-emerald-500/20"
            >
              Approve Rider Account
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
