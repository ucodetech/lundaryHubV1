<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps<{
  shop: any;
  kycDocuments?: Array<any>;
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

// Map existing uploaded documents by document_type
const existingDocs = computed(() => {
  const list = props.kycDocuments || props.shop?.kyc_documents || props.shop?.kycDocuments || [];
  const map: Record<string, any> = {};
  for (const doc of list) {
    map[doc.document_type] = doc;
  }
  return map;
});

const isVerifiedOrApproved = computed(() => {
  return props.shop.is_verified || props.shop.kyc_status === 'approved';
});

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
      <div class="bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xl">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Shop KYC & Storefront Verification</h1>
            <Badge :status="shop.status" />
          </div>
          <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">
            Storefront verification and compliance audit details
          </p>
        </div>

        <div class="text-right shrink-0">
          <span class="text-xs text-gray-500 dark:text-slate-400 block">KYC Audit Status</span>
          <span
            class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider mt-1"
            :class="{
              'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20': isVerifiedOrApproved,
              'bg-amber-500/10 text-amber-400 border border-amber-500/20': shop.kyc_status === 'submitted',
              'bg-rose-500/10 text-rose-400 border border-rose-500/20': shop.kyc_status === 'rejected',
              'bg-slate-700 text-gray-700 dark:text-slate-300': shop.kyc_status === 'pending'
            }"
          >
            {{ isVerifiedOrApproved ? 'Verified & Active ✓' : shop.kyc_status || 'Pending Submission' }}
          </span>
        </div>
      </div>

      <!-- IF APPROVED: Show Verified Card & Read-Only Media Gallery -->
      <div v-if="isVerifiedOrApproved" class="space-y-6">
        <div class="bg-gradient-to-br from-emerald-500/10 via-slate-900 to-slate-900 border border-emerald-500/30 rounded-2xl p-8 space-y-6 shadow-2xl">
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center text-3xl shadow-lg">
              ✅
            </div>
            <div>
              <h2 class="text-xl font-bold text-gray-900 dark:text-slate-100">Storefront Fully Verified & Active</h2>
              <p class="text-xs text-emerald-400 font-medium mt-0.5">
                Super Admin has audited and approved your dry cleaning business registration and media documents.
              </p>
            </div>
          </div>

          <div class="p-4 rounded-xl bg-gray-50 dark:bg-slate-950/80 border border-gray-200 dark:border-slate-800 text-xs text-gray-700 dark:text-slate-300 leading-relaxed space-y-2">
            <p>
              Your public storefront is live on the marketplace. All operational tools including custom item categories, service rates, pricing matrices, and pickup orders are fully unlocked.
            </p>
            <div class="flex items-center gap-2 pt-1 font-mono text-[11px] text-purple-400">
              <span>Business Classification:</span>
              <span class="font-bold">
                {{ shop.business_type === 'cac_registered' ? '🏛️ CAC Registered Business (Verified)' : '🏪 Independent Operator (Verified)' }}
              </span>
            </div>
          </div>

          <!-- Quick Navigation Actions -->
          <div class="flex flex-wrap items-center gap-3">
            <Link
              href="/dashboard"
              class="px-5 py-2.5 rounded-xl bg-emerald-500 text-slate-950 font-bold text-xs hover:bg-emerald-400 transition-all shadow-lg shadow-emerald-500/20"
            >
              Go to Owner Dashboard
            </Link>

            <Link
              href="/shop-admin/categories"
              class="px-5 py-2.5 rounded-xl bg-gray-100 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 hover:border-sky-500 text-gray-800 dark:text-slate-200 text-xs font-semibold transition-all"
            >
              Manage Categories & Pricing
            </Link>

            <Link
              :href="`/shop/${shop.slug}`"
              target="_blank"
              class="px-5 py-2.5 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 hover:border-purple-500 text-purple-300 text-xs font-semibold transition-all"
            >
              View Public Storefront ↗
            </Link>
          </div>
        </div>

        <!-- Read-Only Approved Documents Gallery -->
        <div class="bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-8 space-y-4 shadow-xl">
          <h3 class="text-sm font-bold text-sky-400 uppercase tracking-wider">Approved KYC Documents & Media</h3>

          <div v-if="Object.keys(existingDocs).length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div
              v-for="(doc, type) in existingDocs"
              :key="type"
              class="bg-gray-50 dark:bg-slate-950 p-4 rounded-xl border border-gray-200 dark:border-slate-800 space-y-2"
            >
              <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-gray-800 dark:text-slate-200 capitalize">{{ String(type).replace('_', ' ') }}</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 uppercase font-mono font-bold">
                  Approved ✓
                </span>
              </div>

              <div v-if="doc.file_path.match(/\.(jpg|jpeg|png|webp)$/i)" class="mt-2">
                <img :src="doc.file_path" class="h-36 w-full object-cover rounded-lg border border-gray-200 dark:border-slate-800" />
              </div>
              <div v-else class="mt-2 pt-2">
                <a :href="doc.file_path" target="_blank" class="px-3 py-1.5 rounded-lg bg-sky-500/10 text-sky-400 border border-sky-500/20 text-xs font-semibold hover:underline inline-flex items-center gap-1">
                  📄 View Approved Document ↗
                </a>
              </div>
            </div>
          </div>
          <div v-else class="p-6 text-center text-xs text-gray-500 dark:text-slate-400 bg-gray-50 dark:bg-slate-950/40 rounded-xl border border-gray-200 dark:border-slate-800">
            No media documents on file.
          </div>
        </div>
      </div>

      <!-- IF UNVERIFIED: Show Feature Gating Warning Banner and Upload Form -->
      <template v-else>
        <!-- Feature Gating Warning Banner -->
        <div class="bg-gradient-to-r from-amber-500/10 via-purple-500/10 to-slate-900 border border-amber-500/30 rounded-2xl p-6 space-y-2">
          <div class="flex items-center gap-2 text-amber-400 font-bold text-sm">
            <span>🔒</span>
            <span>Operational Features Locked</span>
          </div>
          <p class="text-xs text-gray-700 dark:text-slate-300 leading-relaxed">
            Categories, Services, Pricing customization, and Public Storefront booking link are currently locked. Complete your KYC upload below so our compliance team can verify your storefront.
          </p>
        </div>

        <!-- KYC Upload Form -->
        <form @submit.prevent="submit" class="bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-8 space-y-6 shadow-xl">
          <!-- Business Structure Toggle -->
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 uppercase mb-2">Business Registration Status *</label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <button
                type="button"
                @click="form.business_type = 'cac_registered'"
                class="p-4 rounded-xl border text-left transition-all"
                :class="form.business_type === 'cac_registered' ? 'bg-sky-500/10 border-sky-500 text-sky-400' : 'bg-white dark:bg-slate-900 border-gray-200 dark:border-slate-700 text-gray-500 dark:text-slate-400'"
              >
                <div class="font-bold text-xs text-gray-800 dark:text-slate-200">🏛️ CAC Registered Business</div>
                <p class="text-[11px] text-gray-500 dark:text-slate-400 mt-1">Has Corporate Affairs Commission Certificate. Storefront will display "Verified Business" badge.</p>
              </button>

              <button
                type="button"
                @click="form.business_type = 'sole_proprietorship'"
                class="p-4 rounded-xl border text-left transition-all"
                :class="form.business_type === 'sole_proprietorship' ? 'bg-sky-500/10 border-sky-500 text-sky-400' : 'bg-white dark:bg-slate-900 border-gray-200 dark:border-slate-700 text-gray-500 dark:text-slate-400'"
              >
                <div class="font-bold text-xs text-gray-800 dark:text-slate-200">🏪 Independent Operator</div>
                <p class="text-[11px] text-gray-500 dark:text-slate-400 mt-1">Unregistered local shop. Storefront will notify customers transparently about independent status.</p>
              </button>
            </div>
          </div>

          <hr class="border-gray-200 dark:border-slate-700/60" />

          <!-- Upload Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- CAC Certificate (Conditional Upload) -->
            <div v-if="form.business_type === 'cac_registered'" class="bg-white dark:bg-slate-900/60 p-5 rounded-2xl border border-gray-200 dark:border-slate-700/60 space-y-3">
              <div class="flex items-start justify-between">
                <div>
                  <h4 class="font-bold text-xs text-gray-800 dark:text-slate-200">CAC Registration Certificate *</h4>
                  <p class="text-[11px] text-gray-500 dark:text-slate-400">Upload RC / BN Certificate (PDF or Image, max 5MB)</p>
                </div>
                <span
                  v-if="existingDocs.cac_certificate"
                  class="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20 font-bold"
                >
                  Pending Review
                </span>
              </div>

              <!-- Existing Prepopulated Doc Preview -->
              <div v-if="existingDocs.cac_certificate" class="p-3 bg-gray-50 dark:bg-slate-950 rounded-xl border border-gray-200 dark:border-slate-800 space-y-2">
                <div class="flex items-center justify-between text-[11px]">
                  <span class="text-gray-500 dark:text-slate-400">Current File:</span>
                  <span class="px-2 py-0.5 rounded bg-sky-500/10 text-sky-400 uppercase font-mono text-[10px]">
                    {{ existingDocs.cac_certificate.status }}
                  </span>
                </div>
                <a
                  :href="existingDocs.cac_certificate.file_path"
                  target="_blank"
                  class="inline-flex items-center gap-1.5 text-xs text-sky-400 hover:underline font-semibold"
                >
                  <span>📄 View Uploaded CAC Certificate</span>
                  <span>↗</span>
                </a>
              </div>

              <input
                type="file"
                accept="image/*,.pdf"
                @change="(e) => handleFileChange(e, 'cac_certificate')"
                class="block w-full text-xs text-gray-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-sky-500/10 file:text-sky-400 hover:file:bg-sky-500/20"
              />
              <div v-if="previews.cac_certificate" class="text-[11px] text-sky-400 font-mono">
                New Selection: {{ previews.cac_certificate }}
              </div>
            </div>

            <!-- Storefront Exterior Photo -->
            <div class="bg-white dark:bg-slate-900/60 p-5 rounded-2xl border border-gray-200 dark:border-slate-700/60 space-y-3">
              <div class="flex items-start justify-between">
                <div>
                  <h4 class="font-bold text-xs text-gray-800 dark:text-slate-200">Main Storefront Exterior Photo *</h4>
                  <p class="text-[11px] text-gray-500 dark:text-slate-400">Clear photo showing your shop sign & entrance</p>
                </div>
                <span
                  v-if="existingDocs.storefront_photo"
                  class="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20 font-bold"
                >
                  Pending Review
                </span>
              </div>

              <!-- Existing Prepopulated Doc Preview -->
              <div v-if="existingDocs.storefront_photo" class="p-3 bg-gray-50 dark:bg-slate-950 rounded-xl border border-gray-200 dark:border-slate-800 space-y-2">
                <img
                  :src="existingDocs.storefront_photo.file_path"
                  class="h-28 w-full object-cover rounded-lg border border-gray-200 dark:border-slate-800"
                />
                <div class="flex items-center justify-between text-[11px]">
                  <span class="text-gray-500 dark:text-slate-400 text-[10px]">Cloudinary Hosted Media</span>
                  <a :href="existingDocs.storefront_photo.file_path" target="_blank" class="text-sky-400 hover:underline text-[11px]">View Full Photo ↗</a>
                </div>
              </div>

              <input
                type="file"
                accept="image/*"
                @change="(e) => handleFileChange(e, 'storefront_photo')"
                class="block w-full text-xs text-gray-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-sky-500/10 file:text-sky-400 hover:file:bg-sky-500/20"
              />
              <div v-if="previews.storefront_photo" class="mt-2">
                <img :src="previews.storefront_photo" class="h-24 w-full object-cover rounded-xl border border-gray-200 dark:border-slate-700" />
              </div>
            </div>

            <!-- Interior Photo -->
            <div class="bg-white dark:bg-slate-900/60 p-5 rounded-2xl border border-gray-200 dark:border-slate-700/60 space-y-3">
              <div class="flex items-start justify-between">
                <div>
                  <h4 class="font-bold text-xs text-gray-800 dark:text-slate-200">Shop Interior / Equipment Photo *</h4>
                  <p class="text-[11px] text-gray-500 dark:text-slate-400">Photo of washing machines, pressing tables, or counter</p>
                </div>
                <span
                  v-if="existingDocs.interior_photo"
                  class="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20 font-bold"
                >
                  Pending Review
                </span>
              </div>

              <!-- Existing Prepopulated Doc Preview -->
              <div v-if="existingDocs.interior_photo" class="p-3 bg-gray-50 dark:bg-slate-950 rounded-xl border border-gray-200 dark:border-slate-800 space-y-2">
                <img
                  :src="existingDocs.interior_photo.file_path"
                  class="h-28 w-full object-cover rounded-lg border border-gray-200 dark:border-slate-800"
                />
                <div class="flex items-center justify-between text-[11px]">
                  <span class="text-gray-500 dark:text-slate-400 text-[10px]">Cloudinary Hosted Media</span>
                  <a :href="existingDocs.interior_photo.file_path" target="_blank" class="text-sky-400 hover:underline text-[11px]">View Full Photo ↗</a>
                </div>
              </div>

              <input
                type="file"
                accept="image/*"
                @change="(e) => handleFileChange(e, 'interior_photo')"
                class="block w-full text-xs text-gray-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-sky-500/10 file:text-sky-400 hover:file:bg-sky-500/20"
              />
              <div v-if="previews.interior_photo" class="mt-2">
                <img :src="previews.interior_photo" class="h-24 w-full object-cover rounded-xl border border-gray-200 dark:border-slate-700" />
              </div>
            </div>

            <!-- Utility Bill / Proof of Address -->
            <div class="bg-white dark:bg-slate-900/60 p-5 rounded-2xl border border-gray-200 dark:border-slate-700/60 space-y-3">
              <div class="flex items-start justify-between">
                <div>
                  <h4 class="font-bold text-xs text-gray-800 dark:text-slate-200">Utility Bill / Proof of Address *</h4>
                  <p class="text-[11px] text-gray-500 dark:text-slate-400">Electricity bill, tenancy agreement, or waste bill</p>
                </div>
                <span
                  v-if="existingDocs.utility_bill"
                  class="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20 font-bold"
                >
                  Pending Review
                </span>
              </div>

              <!-- Existing Prepopulated Doc Preview -->
              <div v-if="existingDocs.utility_bill" class="p-3 bg-gray-50 dark:bg-slate-950 rounded-xl border border-gray-200 dark:border-slate-800 space-y-2">
                <div class="flex items-center justify-between text-[11px]">
                  <span class="text-gray-500 dark:text-slate-400">Current Utility Document:</span>
                  <span class="px-2 py-0.5 rounded bg-sky-500/10 text-sky-400 uppercase font-mono text-[10px]">
                    {{ existingDocs.utility_bill.status }}
                  </span>
                </div>
                <a
                  :href="existingDocs.utility_bill.file_path"
                  target="_blank"
                  class="inline-flex items-center gap-1.5 text-xs text-sky-400 hover:underline font-semibold"
                >
                  <span>📄 View Uploaded Utility Document</span>
                  <span>↗</span>
                </a>
              </div>

              <input
                type="file"
                accept="image/*,.pdf"
                @change="(e) => handleFileChange(e, 'utility_bill')"
                class="block w-full text-xs text-gray-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-sky-500/10 file:text-sky-400 hover:file:bg-sky-500/20"
              />
              <div v-if="previews.utility_bill" class="text-[11px] text-sky-400 font-mono">
                New Selection: {{ previews.utility_bill }}
              </div>
            </div>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full py-4 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-sm shadow-xl shadow-sky-500/20 hover:opacity-95 transition-all"
          >
            Submit / Update KYC Verification Documents
          </button>
        </form>
      </template>
    </div>
  </AppLayout>
</template>
