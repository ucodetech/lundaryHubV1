<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps<{
  shop: any;
}>();

const selectedService = ref<number | null>(null);
const selectedCategory = ref<number | null>(null);
const quantity = ref(1);

const calculatedPrice = computed(() => {
  if (!selectedService.value || !selectedCategory.value) return 0;
  const match = props.shop.prices?.find(
    (p: any) => p.category_id === selectedCategory.value && p.service_id === selectedService.value
  );
  return match ? Number(match.amount) * quantity.value : 0;
});
</script>

<template>
  <Head :title="`${shop.name} — LaundryHub`" />

  <div class="min-h-screen bg-slate-950 text-slate-100 font-sans">
    <!-- Navbar Header -->
    <header class="h-16 border-b border-slate-800 px-6 flex items-center justify-between">
      <Link href="/" class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-sky-500 to-cyan-400 flex items-center justify-center font-black text-slate-950 text-lg">
          L
        </div>
        <span class="font-bold text-lg text-slate-100">LaundryHub</span>
      </Link>

      <Link href="/dashboard" class="px-4 py-2 rounded-xl text-xs font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20">
        Dashboard
      </Link>
    </header>

    <div class="max-w-4xl mx-auto p-6 space-y-8">
      <!-- Shop Header Banner -->
      <div class="bg-gradient-to-br from-slate-900 via-slate-900 to-slate-800 border border-slate-800 rounded-3xl p-8 shadow-2xl space-y-6">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
          <div class="space-y-3">
            <div class="flex flex-wrap items-center gap-3">
              <h1 class="text-3xl font-black text-slate-100">{{ shop.name }}</h1>
              <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                ⭐ 4.8 Verified Storefront
              </span>

              <!-- Business Registration Badge -->
              <span
                v-if="shop.business_type === 'cac_registered'"
                class="px-3 py-1 rounded-full text-xs font-bold bg-purple-500/10 text-purple-300 border border-purple-500/20 flex items-center gap-1"
                title="Corporate Affairs Commission Registered Business"
              >
                <span>🏛️ Verified Business (CAC Registered)</span>
              </span>
              <span
                v-else
                class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-800 text-slate-300 border border-slate-700 flex items-center gap-1"
                title="Independent Local Dry Cleaner"
              >
                <span>🏪 Independent Operator</span>
              </span>
            </div>

            <p class="text-sm text-slate-400 leading-relaxed max-w-xl">{{ shop.description || 'Professional dry cleaning and garment care services.' }}</p>

            <div class="flex flex-wrap gap-4 text-xs text-slate-400 pt-1">
              <span>📍 {{ shop.address }}</span>
              <span>📞 {{ shop.phone }}</span>
              <span>🚚 Delivery: ₦{{ Number(shop.delivery_fee).toLocaleString() }}</span>
            </div>
          </div>
        </div>

        <!-- Transparency Notice for Independent Operators -->
        <div v-if="shop.business_type !== 'cac_registered'" class="p-3.5 rounded-xl bg-slate-950/80 border border-slate-800/80 text-[11px] text-slate-400 flex items-center gap-2">
          <span>ℹ️</span>
          <span><strong>Customer Transparency Notice:</strong> This storefront operates as an independent local laundry provider verified by address, location coordinates, and storefront photo audit.</span>
        </div>
      </div>

      <!-- Pricing & Order Calculator Widget -->
      <div class="bg-slate-900 border border-slate-800 rounded-3xl p-8 space-y-6 shadow-2xl">
        <h2 class="text-xl font-bold text-slate-100">Calculate Price & Order</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase mb-2">1. Select Category</label>
            <select
              v-model="selectedCategory"
              class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-sm focus:border-sky-500"
            >
              <option :value="null" disabled>Select item type...</option>
              <option v-for="cat in shop.categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase mb-2">2. Select Service</label>
            <select
              v-model="selectedService"
              class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-sm focus:border-sky-500"
            >
              <option :value="null" disabled>Select service...</option>
              <option v-for="srv in shop.services" :key="srv.id" :value="srv.id">
                {{ srv.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase mb-2">3. Quantity</label>
            <input
              v-model="quantity"
              type="number"
              min="1"
              class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-sm focus:border-sky-500"
            />
          </div>
        </div>

        <div class="p-6 rounded-2xl bg-slate-950 border border-slate-800 flex items-center justify-between">
          <div>
            <span class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Total Price</span>
            <h3 class="text-3xl font-black text-sky-400">₦{{ calculatedPrice.toLocaleString() }}</h3>
          </div>

          <button
            :disabled="calculatedPrice === 0"
            class="px-8 py-3.5 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-sm disabled:opacity-40 shadow-xl shadow-sky-500/20 hover:scale-105 transition-transform"
          >
            Place Order Now →
          </button>
        </div>
      </div>

      <!-- Pricing Matrix Reference Table -->
      <div class="bg-slate-900 border border-slate-800 rounded-3xl p-8 shadow-2xl">
        <h2 class="text-xl font-bold text-slate-100 mb-4">Services & Pricing Menu</h2>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="text-xs uppercase text-slate-400 border-b border-slate-800">
              <tr>
                <th class="py-3 px-4">Category</th>
                <th class="py-3 px-4">Service</th>
                <th class="py-3 px-4 text-right">Price</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
              <tr v-for="price in shop.prices" :key="price.id" class="hover:bg-slate-800/40">
                <td class="py-3 px-4 font-semibold text-slate-200">{{ price.category?.name }}</td>
                <td class="py-3 px-4 text-slate-400">{{ price.service?.name }}</td>
                <td class="py-3 px-4 text-right font-bold text-sky-400">₦{{ Number(price.amount).toLocaleString() }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
