<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
  profile: any;
}>();

const detailsForm = useForm({
  vehicle_type: props.profile?.vehicle_type ?? 'motorcycle',
  vehicle_plate: props.profile?.vehicle_plate ?? '',
  license_number: props.profile?.license_number ?? '',
});

const kycForm = useForm({
  document_type: 'national_id',
  file: null as File | null,
});

const updateDetails = () => {
  detailsForm.put('/rider/profile');
};

const uploadDocument = () => {
  kycForm.post('/rider/kyc', {
    onSuccess: () => kycForm.reset('file'),
  });
};
</script>

<template>
  <AppLayout>
    <div class="max-w-3xl mx-auto space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-100">Rider Profile & KYC</h1>
        <p class="text-xs text-slate-400 mt-1">Vehicle details and identity verification documents</p>
      </div>

      <!-- Verification Status -->
      <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-6 flex items-center justify-between">
        <div>
          <span class="text-xs font-semibold text-slate-400 uppercase">Verification Status</span>
          <div class="mt-1">
            <Badge :status="profile?.kyc_status ?? 'pending'" />
          </div>
        </div>
        <p class="text-xs text-slate-400 max-w-xs text-right">
          Upload Passport, National ID/NIN, and Driver's License for account activation.
        </p>
      </div>

      <!-- Vehicle Form -->
      <form @submit.prevent="updateDetails" class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-6 space-y-4">
        <h3 class="font-bold text-slate-200 text-sm">Vehicle Information</h3>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">Vehicle Type</label>
            <select
              v-model="detailsForm.vehicle_type"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-sm focus:border-sky-500"
            >
              <option value="bicycle">Bicycle</option>
              <option value="motorcycle">Motorcycle / Bike</option>
              <option value="tricycle">Tricycle (Keke)</option>
              <option value="car">Car / Sedan</option>
              <option value="van">Delivery Van</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">Plate Number</label>
            <input
              v-model="detailsForm.vehicle_plate"
              type="text"
              placeholder="LND-452-XY"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-sm focus:border-sky-500"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">License Number</label>
            <input
              v-model="detailsForm.license_number"
              type="text"
              placeholder="DL-98765432"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-sm focus:border-sky-500"
            />
          </div>
        </div>

        <button
          type="submit"
          :disabled="detailsForm.processing"
          class="px-6 py-2.5 rounded-xl bg-sky-500 text-slate-950 font-bold text-xs shadow-lg shadow-sky-500/20"
        >
          Save Vehicle Details
        </button>
      </form>

      <!-- KYC Document Upload Form -->
      <form @submit.prevent="uploadDocument" class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-6 space-y-4">
        <h3 class="font-bold text-slate-200 text-sm">Upload KYC Document</h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">Document Type *</label>
            <select
              v-model="kycForm.document_type"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-sm focus:border-sky-500"
            >
              <option value="passport">Passport Photograph</option>
              <option value="national_id">National ID / NIN Slip</option>
              <option value="drivers_license">Driver's License</option>
              <option value="selfie">Live Selfie</option>
              <option value="guarantor">Guarantor Form</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">Select File (JPG, PNG, PDF) *</label>
            <input
              type="file"
              @change="(e: any) => kycForm.file = e.target.files[0]"
              required
              class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs"
            />
          </div>
        </div>

        <button
          type="submit"
          :disabled="kycForm.processing"
          class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-xs shadow-lg shadow-sky-500/20"
        >
          Upload Verification Document
        </button>
      </form>
    </div>
  </AppLayout>
</template>
