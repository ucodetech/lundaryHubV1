<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import GoogleAddressInput from '@/Components/GoogleAddressInput.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps<{
  shops: Array<any>;
  customerLocation?: {
    address?: string;
    city?: string;
    latitude?: number;
    longitude?: number;
  };
}>();

const showLocationModal = ref(false);
const isLocating = ref(false);

const locationForm = useForm({
  address: props.customerLocation?.address || '',
  city: props.customerLocation?.city || 'Lagos',
  latitude: props.customerLocation?.latitude || null as number | null,
  longitude: props.customerLocation?.longitude || null as number | null,
});

const submitLocation = () => {
  locationForm.post('/customer/location', {
    preserveScroll: true,
    onSuccess: () => {
      showLocationModal.value = false;
    },
  });
};

const autoDetectGps = () => {
  if (!navigator.geolocation) {
    alert('Geolocation is not supported by your browser.');
    return;
  }

  isLocating.value = true;
  navigator.geolocation.getCurrentPosition(
    (position) => {
      locationForm.latitude = position.coords.latitude;
      locationForm.longitude = position.coords.longitude;
      if (!locationForm.address) {
        locationForm.address = `GPS Pin (${position.coords.latitude.toFixed(4)}, ${position.coords.longitude.toFixed(4)})`;
      }
      isLocating.value = false;
      submitLocation();
    },
    (error) => {
      isLocating.value = false;
      alert('Could not detect your current location. Please select an address manually.');
    },
    { enableHighAccuracy: true, timeout: 10000 }
  );
};

// Mode: 'nearby' (shops in city / within 50km) vs 'all'
const activeFilter = ref<'nearby' | 'doorstep' | 'all'>('nearby');

const nearbyCount = computed(() => {
  return props.shops.filter(s => {
    if (s.distance_km === null || s.distance_km === undefined) return s.delivers_to_user;
    return s.distance_km <= 50 || s.delivers_to_user;
  }).length;
});

const filteredShops = computed(() => {
  if (activeFilter.value === 'all') {
    return props.shops;
  }

  if (activeFilter.value === 'doorstep') {
    return props.shops.filter(s => s.delivers_to_user);
  }

  // Default: 'nearby' -> Shops within 50km or matching city
  const nearby = props.shops.filter(s => {
    if (s.distance_km === null || s.distance_km === undefined) return s.delivers_to_user;
    return s.distance_km <= 50 || s.delivers_to_user;
  });

  // If no shops found nearby, return all platform shops
  return nearby.length > 0 ? nearby : props.shops;
});
</script>

<template>
  <AppLayout>
    <div class="space-y-8 max-w-6xl mx-auto">
      <!-- Customer Location & Streamline Banner -->
      <div class="bg-gradient-to-r from-slate-900 via-slate-850 to-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-6 shadow-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <span class="text-xl">📍</span>
            <h2 class="text-sm font-bold text-gray-800 dark:text-slate-200 uppercase tracking-wider">Your Location & Service Area</h2>
          </div>

          <div v-if="customerLocation?.address" class="text-xs text-sky-400 font-semibold flex items-center gap-2">
            <span class="truncate max-w-md">{{ customerLocation.address }}</span>
            <span v-if="customerLocation.latitude" class="px-2 py-0.5 rounded text-[10px] bg-sky-500/10 border border-sky-500/20 font-mono text-sky-300">
              GPS Set ✓
            </span>
          </div>

          <p v-else class="text-xs text-amber-400">
            ⚠️ Set your delivery location to streamline dry cleaners operating in your city!
          </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
          <button
            @click="autoDetectGps"
            :disabled="isLocating"
            class="px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-xs font-bold text-gray-800 dark:text-slate-200 hover:bg-slate-700 transition-all flex items-center gap-1.5 disabled:opacity-60"
          >
            <span>⚡</span>
            <span>{{ isLocating ? 'Detecting GPS...' : 'Use GPS' }}</span>
          </button>

          <button
            @click="showLocationModal = true"
            class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-xs shadow-lg shadow-sky-500/20 hover:scale-105 transition-transform"
          >
            <span>📍 Change Location</span>
          </button>
        </div>
      </div>

      <!-- Marketplace Header & Strict Location Tabs -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-slate-800 pb-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Dry Cleaners Marketplace</h1>
          <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Book professional garment cleaning from verified shops in your area</p>
        </div>

        <div class="flex items-center bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-1 text-xs">
          <button
            @click="activeFilter = 'nearby'"
            class="px-3 py-1.5 rounded-lg font-bold transition-all flex items-center gap-1.5"
            :class="activeFilter === 'nearby' ? 'bg-sky-500 text-slate-950 shadow-md' : 'text-gray-500 dark:text-slate-400 hover:text-slate-200'"
          >
            <span>📍 In My City / Area</span>
            <span v-if="nearbyCount > 0" class="px-1.5 py-0.2 rounded text-[10px]" :class="activeFilter === 'nearby' ? 'bg-gray-50 dark:bg-slate-950/20 text-slate-950 font-extrabold' : 'bg-gray-100 dark:bg-slate-800 text-sky-400'">
              {{ nearbyCount }}
            </span>
          </button>

          <button
            @click="activeFilter = 'doorstep'"
            class="px-3 py-1.5 rounded-lg font-bold transition-all"
            :class="activeFilter === 'doorstep' ? 'bg-sky-500 text-slate-950 shadow-md' : 'text-gray-500 dark:text-slate-400 hover:text-slate-200'"
          >
            🚚 Doorstep Only
          </button>

          <button
            @click="activeFilter = 'all'"
            class="px-3 py-1.5 rounded-lg font-bold transition-all"
            :class="activeFilter === 'all' ? 'bg-sky-500 text-slate-950 shadow-md' : 'text-gray-500 dark:text-slate-400 hover:text-slate-200'"
          >
            🌐 All Shops ({{ shops.length }})
          </button>
        </div>
      </div>

      <!-- Out-of-city alert if showing all shops -->
      <div v-if="activeFilter === 'nearby' && nearbyCount === 0" class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-4 text-xs text-amber-300 flex items-center justify-between">
        <span>⚠️ No dry cleaners were found within 50km of your saved location. Showing all available platform shops below.</span>
        <button @click="showLocationModal = true" class="underline font-bold hover:text-white">Update Location</button>
      </div>

      <!-- Shop Marketplace Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="shop in filteredShops"
          :key="shop.id"
          class="bg-white dark:bg-slate-900/90 border border-gray-200 dark:border-slate-800 hover:border-sky-500/40 rounded-2xl p-5 flex flex-col justify-between transition-all shadow-xl space-y-4"
        >
          <div class="space-y-3">
            <div class="flex items-start justify-between">
              <div>
                <h3 class="font-bold text-lg text-gray-900 dark:text-slate-100">{{ shop.name }}</h3>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">📍 {{ shop.address }}</p>
              </div>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shrink-0">
                Verified
              </span>
            </div>

            <!-- Distance & Delivery Radius Badging -->
            <div class="flex flex-wrap items-center gap-2 text-xs">
              <span
                v-if="shop.distance_km !== null"
                class="px-2.5 py-1 rounded-lg font-mono font-bold border text-[11px]"
                :class="shop.distance_km <= 50 ? 'bg-sky-500/10 text-sky-300 border-sky-500/20' : 'bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 border-gray-200 dark:border-slate-700'"
              >
                📍 {{ shop.distance_km }} km away {{ shop.distance_km > 50 ? '(Different City)' : '' }}
              </span>

              <!-- Fulfillment Methods Badges -->
              <span
                v-if="shop.offers_home_delivery && shop.offers_store_pickup"
                class="px-2.5 py-1 rounded-lg text-[11px] font-bold border bg-emerald-500/10 text-emerald-400 border-emerald-500/20"
              >
                ⚡ Home Delivery & Store Pickup
              </span>

              <span
                v-else-if="shop.offers_home_delivery"
                class="px-2.5 py-1 rounded-lg text-[11px] font-bold border bg-sky-500/10 text-sky-400 border-sky-500/20"
              >
                🚚 Home Delivery Only
              </span>

              <span
                v-else-if="shop.offers_store_pickup"
                class="px-2.5 py-1 rounded-lg text-[11px] font-bold border bg-amber-500/10 text-amber-400 border-amber-500/20"
              >
                🏬 In-Store Pickup Only
              </span>
            </div>

            <p class="text-xs text-gray-500 dark:text-slate-400 line-clamp-2 leading-relaxed">{{ shop.description || 'Professional dry cleaning and garment care services.' }}</p>

            <div class="bg-gray-50 dark:bg-slate-950 p-3 rounded-xl border border-gray-200 dark:border-slate-800 text-xs space-y-1 font-mono">
              <div class="flex justify-between text-gray-700 dark:text-slate-300">
                <span>Delivery Fee:</span>
                <span class="text-emerald-400 font-bold">₦{{ Number(shop.delivery_fee).toLocaleString() }}</span>
              </div>
              <div class="flex justify-between text-gray-500 dark:text-slate-400 text-[11px]">
                <span>Service Radius:</span>
                <span>Within {{ shop.pickup_radius_km }} km</span>
              </div>
            </div>
          </div>

          <div class="pt-4 border-t border-gray-200 dark:border-slate-800 flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-700 dark:text-slate-300">⭐ 4.9 (Verified)</span>
            <Link
              :href="`/shop/${shop.slug}`"
              class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-xs shadow-lg shadow-sky-500/20 hover:scale-105 transition-transform"
            >
              Book Laundry →
            </Link>
          </div>
        </div>

        <div v-if="!filteredShops || filteredShops.length === 0" class="col-span-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-12 text-center space-y-3">
          <span class="text-4xl block">🏬</span>
          <h3 class="text-base font-bold text-gray-800 dark:text-slate-200">No Dry Cleaners Matching Selected Filter</h3>
          <p class="text-xs text-gray-500 dark:text-slate-400 max-w-md mx-auto">
            Try expanding your search or updating your delivery location address above.
          </p>
        </div>
      </div>
    </div>

    <!-- Customer Address Update Modal -->
    <div v-if="showLocationModal" class="fixed inset-0 z-50 bg-gray-50 dark:bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700/80 rounded-2xl w-full max-w-lg p-6 space-y-5 shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-slate-800 pb-3">
          <div>
            <h2 class="text-base font-bold text-gray-900 dark:text-slate-100">📍 Update Delivery Location</h2>
            <p class="text-xs text-gray-500 dark:text-slate-400">Set your delivery address to streamline nearby dry cleaners</p>
          </div>
          <button @click="showLocationModal = false" class="text-gray-500 dark:text-slate-400 hover:text-white text-lg">✕</button>
        </div>

        <form @submit.prevent="submitLocation" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 uppercase mb-1">Street Address or Landmark *</label>
            <GoogleAddressInput
              v-model:address="locationForm.address"
              v-model:latitude="locationForm.latitude"
              v-model:longitude="locationForm.longitude"
              :error="locationForm.errors.address"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 uppercase mb-1">City / Region</label>
              <input
                v-model="locationForm.city"
                type="text"
                placeholder="e.g., Ikeja, Lagos"
                class="w-full px-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 text-xs"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 uppercase mb-1">Auto GPS Pin</label>
              <button
                type="button"
                @click="autoDetectGps"
                :disabled="isLocating"
                class="w-full py-2.5 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-700 text-xs font-bold text-sky-400 hover:bg-slate-800 transition-colors flex items-center justify-center gap-1 disabled:opacity-60"
              >
                <span>⚡</span>
                <span>{{ isLocating ? 'Detecting...' : 'Fetch My GPS' }}</span>
              </button>
            </div>
          </div>

          <button
            type="submit"
            :disabled="locationForm.processing || !locationForm.address"
            class="w-full py-3 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-xs shadow-lg shadow-sky-500/20 disabled:opacity-60"
          >
            Save Location & Streamline Shops
          </button>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
