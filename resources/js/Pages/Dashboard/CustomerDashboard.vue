<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
  shops: Array<any>;
}>();
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-100">Nearby Dry Cleaners</h1>
          <p class="text-xs text-slate-400 mt-1">Select a verified laundry shop to request pickup or drop off clothes</p>
        </div>
      </div>

      <!-- Shop Marketplace Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="shop in shops"
          :key="shop.id"
          class="bg-slate-800/60 border border-slate-700/60 hover:border-sky-500/50 rounded-2xl p-5 flex flex-col justify-between transition-all"
        >
          <div>
            <div class="flex items-center justify-between mb-3">
              <h3 class="font-bold text-lg text-slate-100">{{ shop.name }}</h3>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                Verified
              </span>
            </div>
            <p class="text-xs text-slate-400 line-clamp-2 mb-4">{{ shop.description }}</p>
            <p class="text-xs text-slate-400">📍 {{ shop.address }}</p>
            <p class="text-xs text-slate-400 mt-1">🚚 Delivery Fee: ₦{{ shop.delivery_fee }}</p>
          </div>

          <div class="mt-6 pt-4 border-t border-slate-700/40 flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-300">⭐ 4.9 (Verified)</span>
            <Link
              :href="`/shop/${shop.slug}`"
              class="px-4 py-2 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-xs shadow-md shadow-sky-500/10 hover:shadow-sky-500/20 transition-all"
            >
              Order Now →
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
