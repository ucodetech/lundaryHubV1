<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
  shop: any;
  kycDocuments: Array<any>;
}>();

const form = useForm({
  business_type: props.shop.business_type || 'cac_registered',
  cac_certificate: null as File | null,
  storefront_photo: null as File | null,
  interior_photo: null as File | null,
  utility_bill: null as File | null,
  owner_id: null as File | null,
});

const previews = ref<Record<string, string>>({});

function handleFileChange(event: Event, key: string) {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    const file = target.files[0];
    (form as any)[key] = file;

    if (file.type.startsWith('image/')) {
      previews.value[key] = URL.createObjectURL(file);
    } else {
      previews.value[key] = file.name;
    }
  }
}

const submit = () => {
  form.post('/shop-admin/kyc', {
    preserveScroll: true,
  });
};
</script>

<template>
  <AppLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Status Header -->
      <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xl">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-slate-100">Shop KYC & Storefront Verification</h1>
            <Badge :status="shop.status" />
          </div>
          <p class="text-xs text-slate-400 mt-1">
            Submit required storefront photos and business registration documents for Super Admin verification
          </p>
        </div>

        <div class="text-right shrink-0">
          <span class="text-xs text-slate-400 block">KYC Audit Status</span>
          <span
            class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider mt-1"
            :class="{
              'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20': shop.kyc_status === 'approved' || shop.is_verified,
              'bg-amber-500/10 text-amber-400 border border-amber-500/20': shop.kyc_status === 'submitted',
              'bg-rose-500/10 text-rose-400 border border-rose-500/20': shop.kyc_status === 'rejected',
              'bg-slate-700 text-slate-300': shop.kyc_status === 'pending'
            }"
          >
            {{ shop.is_verified ? 'Verified & Active ✓' : shop.kyc_status || 'Pending Submission' }}
          </span>
        </div>
      </div>

      <!-- Feature Gating Warning Banner -->
      <div v-if="!shop.is_verified" class="bg-gradient-to-r from-amber-500/10 via-purple-500/10 to-slate-900 border border-amber-500/30 rounded-2xl p-6 space-y-2">
        <div class="flex items-center gap-2 text-amber-400 font-bold text-sm">
          <span>🔒</span>
          <span>Operational Features Locked</span>
        </div>
        <p class="text-xs text-slate-300 leading-relaxed">
          Categories, Services, Pricing customization, and Public Storefront booking link are currently locked. Complete your KYC upload below so our compliance team can verify your storefront.
        </p>
      </div>

      <!-- KYC Upload Form -->
      <form @submit.prevent="submit" class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-8 space-y-6 shadow-xl">
        <!-- Business Structure Toggle -->
        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase mb-2">Business Registration Status *</label>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <button
              type="button"
              @click="form.business_type = 'cac_registered'"
              class="p-4 rounded-xl border text-left transition-all"
              :class="form.business_type === 'cac_registered' ? 'bg-sky-500/10 border-sky-500 text-sky-400' : 'bg-slate-900 border-slate-700 text-slate-400'"
            >
              <div class="font-bold text-xs text-slate-200">🏛️ CAC Registered Business</div>
              <p class="text-[11px] text-slate-400 mt-1">Has Corporate Affairs Commission Certificate. Storefront will display "Verified Business" badge.</p>
            </button>

            <button
              type="button"
              @click="form.business_type = 'sole_proprietorship'"
              class="p-4 rounded-xl border text-left transition-all"
              :class="form.business_type === 'sole_proprietorship' ? 'bg-sky-500/10 border-sky-500 text-sky-400' : 'bg-slate-900 border-slate-700 text-slate-400'"
            >
              <div class="font-bold text-xs text-slate-200">🏪 Independent Operator</div>
              <p class="text-[11px] text-slate-400 mt-1">Unregistered local shop. Storefront will notify customers transparently about independent status.</p>
            </button>
          </div>
        </div>

        <hr class="border-slate-700/60" />

        <!-- Upload Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- CAC Certificate (Conditional Upload) -->
          <div v-if="form.business_type === 'cac_registered'" class="bg-slate-900/60 p-5 rounded-2xl border border-slate-700/60 space-y-3">
            <div>
              <h4 class="font-bold text-xs text-slate-200">CAC Registration Certificate *</h4>
              <p class="text-[11px] text-slate-400">Upload RC / BN Certificate (PDF or Image, max 5MB)</p>
            </div>
            <input
              type="file"
              accept="image/*,.pdf"
              @change="(e) => handleFileChange(e, 'cac_certificate')"
              class="block w-full text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-sky-500/10 file:text-sky-400 hover:file:bg-sky-500/20"
            />
            <div v-if="previews.cac_certificate" class="text-[11px] text-sky-400 font-mono">
              Selected: {{ previews.cac_certificate }}
            </div>
          </div>

          <!-- Storefront Exterior Photo -->
          <div class="bg-slate-900/60 p-5 rounded-2xl border border-slate-700/60 space-y-3">
            <div>
              <h4 class="font-bold text-xs text-slate-200">Main Storefront Exterior Photo *</h4>
              <p class="text-[11px] text-slate-400">Clear photo showing your shop sign & entrance</p>
            </div>
            <input
              type="file"
              accept="image/*"
              @change="(e) => handleFileChange(e, 'storefront_photo')"
              class="block w-full text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-sky-500/10 file:text-sky-400 hover:file:bg-sky-500/20"
            />
            <div v-if="previews.storefront_photo" class="mt-2">
              <img :src="previews.storefront_photo" class="h-24 w-full object-cover rounded-xl border border-slate-700" />
            </div>
          </div>

          <!-- Interior Photo -->
          <div class="bg-slate-900/60 p-5 rounded-2xl border border-slate-700/60 space-y-3">
            <div>
              <h4 class="font-bold text-xs text-slate-200">Shop Interior / Equipment Photo *</h4>
              <p class="text-[11px] text-slate-400">Photo of washing machines, pressing tables, or counter</p>
            </div>
            <input
              type="file"
              accept="image/*"
              @change="(e) => handleFileChange(e, 'interior_photo')"
              class="block w-full text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-sky-500/10 file:text-sky-400 hover:file:bg-sky-500/20"
            />
            <div v-if="previews.interior_photo" class="mt-2">
              <img :src="previews.interior_photo" class="h-24 w-full object-cover rounded-xl border border-slate-700" />
            </div>
          </div>

          <!-- Utility Bill / Proof of Address -->
          <div class="bg-slate-900/60 p-5 rounded-2xl border border-slate-700/60 space-y-3">
            <div>
              <h4 class="font-bold text-xs text-slate-200">Utility Bill / Proof of Address *</h4>
              <p class="text-[11px] text-slate-400">Electricity bill, tenancy agreement, or waste bill</p>
            </div>
            <input
              type="file"
              accept="image/*,.pdf"
              @change="(e) => handleFileChange(e, 'utility_bill')"
              class="block w-full text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-sky-500/10 file:text-sky-400 hover:file:bg-sky-500/20"
            />
            <div v-if="previews.utility_bill" class="text-[11px] text-sky-400 font-mono">
              Selected: {{ previews.utility_bill }}
            </div>
          </div>
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="w-full py-4 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-sm shadow-xl shadow-sky-500/20 hover:opacity-95 transition-all"
        >
          Submit KYC Verification Documents
        </button>
      </form>
    </div>
  </AppLayout>
</template>
