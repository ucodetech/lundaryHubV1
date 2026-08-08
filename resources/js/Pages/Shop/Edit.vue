<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import GoogleAddressInput from '@/Components/GoogleAddressInput.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps<{
  shop: any;
}>();

const form = useForm({
  name: props.shop.name || '',
  description: props.shop.description || '',
  business_type: props.shop.business_type || 'cac_registered',
  phone: props.shop.phone || '',
  email: props.shop.email || '',
  address: props.shop.address || '',
  latitude: props.shop.latitude || null,
  longitude: props.shop.longitude || null,
  pickup_radius_km: props.shop.pickup_radius_km ?? 10,
  delivery_fee: props.shop.delivery_fee ?? 0,
  offers_home_delivery: props.shop.offers_home_delivery ?? true,
  offers_store_pickup: props.shop.offers_store_pickup ?? true,
});

const kycDocs = computed(() => {
  return props.shop?.kyc_documents || props.shop?.kycDocuments || [];
});

const isVerifiedOrApproved = computed(() => {
  return props.shop.is_verified || props.shop.kyc_status === 'approved';
});

const submit = () => {
  form.put(`/shop-admin/${props.shop.id}`, {
    preserveScroll: true,
  });
};
</script>

<template>
  <AppLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header -->
      <div class="bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xl">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Storefront Settings & Profile</h1>
            <Badge :status="shop.status" />
          </div>
          <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">
            Dry cleaning business details, delivery fee, fulfillment methods, and location pin
          </p>
        </div>

        <div class="flex items-center gap-3">
          <Link
            :href="`/shop/${shop.slug}`"
            target="_blank"
            class="px-4 py-2 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 hover:border-sky-500 text-xs font-semibold text-sky-400 flex items-center gap-1.5 transition-all shadow"
          >
            <span>🔗</span>
            <span>View Public Storefront</span>
          </Link>

          <Link
            href="/shop-admin/kyc"
            class="px-4 py-2 rounded-xl bg-purple-500/10 border border-purple-500/30 text-xs font-semibold text-purple-300 hover:bg-purple-500/20 transition-all"
          >
            📷 KYC Status
          </Link>
        </div>
      </div>

      <!-- Settings Form -->
      <form @submit.prevent="submit" class="bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-8 space-y-6 shadow-xl">
        <!-- Store Name & Business Structure -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 uppercase mb-2">Storefront Name *</label>
            <input
              v-model="form.name"
              type="text"
              required
              placeholder="e.g., Express Cleaners Lekki"
              class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500 shadow-inner"
            />
            <div v-if="form.errors.name" class="text-xs text-rose-400 mt-1">{{ form.errors.name }}</div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 uppercase mb-2">Business Registration Structure *</label>
            <select
              v-model="form.business_type"
              class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500 shadow-inner"
            >
              <option value="cac_registered">🏛️ CAC Registered Business</option>
              <option value="sole_proprietorship">🏪 Independent Operator</option>
            </select>
          </div>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 uppercase mb-2">Store Description & Services Offered</label>
          <textarea
            v-model="form.description"
            rows="3"
            placeholder="Brief description of your dry cleaning expertise, turnaround times, or special garment care..."
            class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500 shadow-inner"
          ></textarea>
        </div>

        <!-- Contact Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 uppercase mb-2">Support Phone Number *</label>
            <input
              v-model="form.phone"
              type="text"
              required
              placeholder="e.g., +234 801 234 5678"
              class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-700 text-slate-700 font-mono text-sm focus:border-sky-500 shadow-inner"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 uppercase mb-2">Support Email Address *</label>
            <input
              v-model="form.email"
              type="email"
              required
              placeholder="e.g., contact@expresscleaners.ng"
              class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 font-mono text-sm focus:border-sky-500 shadow-inner"
            />
          </div>
        </div>

        <hr class="border-gray-200 dark:border-slate-700/60" />

        <!-- Order Fulfillment & Logistics Options Toggles -->
        <div class="space-y-4">
          <h3 class="text-sm font-bold text-emerald-400 uppercase tracking-wider">Order Fulfillment & Pickup Options</h3>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Doorstep Home Delivery Toggle -->
            <div class="bg-gray-50 dark:bg-slate-950 p-4 rounded-xl border border-gray-200 dark:border-slate-800 flex items-center justify-between gap-3">
              <div>
                <span class="text-xs font-bold text-gray-800 dark:text-slate-200 block">🚚 Doorstep Home Delivery</span>
                <p class="text-[11px] text-gray-500 dark:text-slate-400 mt-0.5">Riders pick up and deliver garments to customer homes</p>
              </div>

              <label class="relative inline-flex items-center cursor-pointer shrink-0">
                <input
                  type="checkbox"
                  v-model="form.offers_home_delivery"
                  class="sr-only peer"
                />
                <div class="w-11 h-6 bg-gray-100 dark:bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
              </label>
            </div>

            <!-- In-Store Self Pickup Toggle -->
            <div class="bg-gray-50 dark:bg-slate-950 p-4 rounded-xl border border-gray-200 dark:border-slate-800 flex items-center justify-between gap-3">
              <div>
                <span class="text-xs font-bold text-gray-800 dark:text-slate-200 block">🏬 In-Store Self Pickup</span>
                <p class="text-[11px] text-gray-500 dark:text-slate-400 mt-0.5">Customers drop off and pick up garments in person at your shop</p>
              </div>

              <label class="relative inline-flex items-center cursor-pointer shrink-0">
                <input
                  type="checkbox"
                  v-model="form.offers_store_pickup"
                  class="sr-only peer"
                />
                <div class="w-11 h-6 bg-gray-100 dark:bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
              </label>
            </div>
          </div>
        </div>

        <hr class="border-gray-200 dark:border-slate-700/60" />

        <!-- Location Pin & Address with Interactive Google Maps -->
        <div class="space-y-4">
          <h3 class="text-sm font-bold text-sky-400 uppercase tracking-wider">Physical Location & GPS Coordinates</h3>
          <GoogleAddressInput
            v-model:address="form.address"
            v-model:latitude="form.latitude"
            v-model:longitude="form.longitude"
            :error="form.errors.address"
          />
        </div>

        <hr class="border-gray-200 dark:border-slate-700/60" />

        <!-- Logistics Parameters -->
        <div class="space-y-4">
          <h3 class="text-sm font-bold text-cyan-400 uppercase tracking-wider">Logistics & Delivery Pricing</h3>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 uppercase mb-2">Delivery Fee (NGN) *</label>
              <div class="relative">
                <span class="absolute left-4 top-3.5 text-gray-500 dark:text-slate-400 text-xs font-bold">₦</span>
                <input
                  v-model.number="form.delivery_fee"
                  type="number"
                  min="0"
                  step="50"
                  required
                  class="w-full pl-8 pr-4 py-3 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500 shadow-inner font-mono"
                />
              </div>
              <p class="text-[11px] text-gray-500 dark:text-slate-400 mt-1">Base delivery charge added to customer orders</p>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 uppercase mb-2">Pickup Radius (Kilometers) *</label>
              <div class="relative">
                <input
                  v-model.number="form.pickup_radius_km"
                  type="number"
                  min="1"
                  max="100"
                  required
                  class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500 shadow-inner font-mono"
                />
                <span class="absolute right-4 top-3.5 text-gray-500 dark:text-slate-400 text-xs font-semibold">km</span>
              </div>
              <p class="text-[11px] text-gray-500 dark:text-slate-400 mt-1">Maximum coverage distance from your shop location</p>
            </div>
          </div>
        </div>

        <!-- Submit Button -->
        <button
          type="submit"
          :disabled="form.processing"
          class="w-full py-4 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-sm shadow-xl shadow-sky-500/20 hover:opacity-95 transition-all"
        >
          Save Storefront Settings & Fulfillment Preferences
        </button>
      </form>
    </div>
  </AppLayout>
</template>
