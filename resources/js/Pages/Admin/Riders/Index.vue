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

const approveRider = async (id: number) => {
  const confirmed = await confirmDialog(
    'Approve Rider KYC?',
    'Are you sure you want to approve this rider account for deliveries?'
  );
  if (confirmed) {
    useForm({}).post(`/admin/riders/${id}/approve`);
  }
};

const rejectRider = async (id: number) => {
  const reason = await promptDialog(
    'Reject Rider KYC',
    'Enter specific reason for rejecting verification documents...'
  );
  if (reason) {
    useForm({ reason }).post(`/admin/riders/${id}/reject`);
  }
};
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-100">Rider Verification & KYC Audit</h1>
          <p class="text-xs text-slate-400 mt-1">Review rider verification documents and manage approval statuses</p>
        </div>
      </div>

      <!-- Advanced Filter Component -->
      <AdvancedFilter
        v-model="filterState"
        :filters-config="filterConfig"
        search-placeholder="Search rider name, vehicle plate, or license..."
        @filter-change="handleFilterChange"
      />

      <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl overflow-hidden shadow-xl">
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
              <td class="py-4 px-6 text-right space-x-3">
                <button
                  v-if="rider.kyc_status !== 'approved'"
                  @click="approveRider(rider.id)"
                  class="px-3 py-1 rounded-lg text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20 transition-all"
                >
                  Approve KYC
                </button>
                <button
                  v-if="rider.kyc_status !== 'rejected'"
                  @click="rejectRider(rider.id)"
                  class="px-3 py-1 rounded-lg text-xs font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500/20 transition-all"
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
    </div>
  </AppLayout>
</template>
