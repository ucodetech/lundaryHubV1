<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import GoogleAddressInput from '@/Components/GoogleAddressInput.vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  name: '',
  description: '',
  business_type: 'cac_registered',
  phone: '',
  email: '',
  address: '',
  latitude: 6.4531,
  longitude: 3.3958,
  pickup_radius_km: 10,
  delivery_fee: 1000,
});

const submit = () => {
  form.post('/shop-admin/store');
};
</script>

<template>
  <AppLayout>
    <div class="max-w-2xl mx-auto space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-100">Create Digital Storefront</h1>
        <p class="text-xs text-slate-400 mt-1">Register your dry cleaning business to receive online pickup bookings</p>
      </div>

      <form @submit.prevent="submit" class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-6 space-y-5 shadow-xl">
        <!-- Business Registration Type Selector -->
        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase mb-2">Business Registration Structure *</label>
          <div class="grid grid-cols-2 gap-3">
            <button
              type="button"
              @click="form.business_type = 'cac_registered'"
              class="p-4 rounded-xl border text-left transition-all"
              :class="form.business_type === 'cac_registered' ? 'bg-sky-500/10 border-sky-500 text-sky-400' : 'bg-slate-900 border-slate-700 text-slate-400'"
            >
              <div class="font-bold text-xs text-slate-200">🏛️ CAC Registered Business</div>
              <p class="text-[11px] text-slate-400 mt-0.5">Corporate Affairs Commission (RC/BN) registered shop</p>
            </button>

            <button
              type="button"
              @click="form.business_type = 'sole_proprietorship'"
              class="p-4 rounded-xl border text-left transition-all"
              :class="form.business_type === 'sole_proprietorship' ? 'bg-sky-500/10 border-sky-500 text-sky-400' : 'bg-slate-900 border-slate-700 text-slate-400'"
            >
              <div class="font-bold text-xs text-slate-200">🏪 Independent Operator</div>
              <p class="text-[11px] text-slate-400 mt-0.5">Sole proprietorship / Unregistered local shop</p>
            </button>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Shop Name *</label>
          <input
            v-model="form.name"
            type="text"
            required
            placeholder="e.g., Sparkle Dry Cleaners Victoria Island"
            class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-sm focus:border-sky-500"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Description</label>
          <textarea
            v-model="form.description"
            rows="3"
            placeholder="Describe your services, eco-friendly detergents, express options..."
            class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-sm focus:border-sky-500"
          ></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Phone Number *</label>
            <input
              v-model="form.phone"
              type="text"
              required
              placeholder="+2348000000000"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-sm focus:border-sky-500"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Email Address *</label>
            <input
              v-model="form.email"
              type="email"
              required
              placeholder="contact@sparkledrycleaners.com"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-sm focus:border-sky-500"
            />
          </div>
        </div>

        <!-- Google Address & Location Coordinates Picker -->
        <GoogleAddressInput
          v-model:address="form.address"
          v-model:latitude="form.latitude"
          v-model:longitude="form.longitude"
        />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Pickup Radius (KM)</label>
            <input
              v-model="form.pickup_radius_km"
              type="number"
              step="0.5"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-sm focus:border-sky-500"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Default Delivery Fee (₦)</label>
            <input
              v-model="form.delivery_fee"
              type="number"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-sm focus:border-sky-500"
            />
          </div>
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="w-full py-3.5 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-sm shadow-lg shadow-sky-500/20 hover:opacity-95 transition-all"
        >
          Create Storefront & Proceed to KYC Uploads →
        </button>
      </form>
    </div>
  </AppLayout>
</template>
