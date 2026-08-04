<script setup lang="ts">
import { onMounted } from 'vue';

const props = defineProps<{
  order: any;
}>();

onMounted(() => {
  setTimeout(() => {
    window.print();
  }, 500);
});

const totalItems = (items: Array<any>) => {
  if (!items || !items.length) return 0;
  return items.reduce((sum, item) => sum + Number(item.quantity || 1), 0);
};
</script>

<template>
  <div class="min-h-screen bg-slate-900 text-slate-100 flex flex-col items-center justify-center p-4 print:p-0 print:bg-white print:text-black">
    <!-- Screen Control Bar -->
    <div class="mb-6 flex items-center gap-4 print:hidden">
      <button
        @click="window.print()"
        class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-xs shadow-lg hover:scale-105 transition-all flex items-center gap-2"
      >
        🖨️ Print Garment Tag
      </button>
      <button
        @click="window.close()"
        class="px-4 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-slate-300 text-xs font-bold hover:text-white"
      >
        ✕ Close Window
      </button>
    </div>

    <!-- Thermal Receipt Tag Container (80mm Width format) -->
    <div class="w-full max-w-[340px] bg-slate-950 text-slate-100 print:bg-white print:text-black border border-slate-800 print:border-none p-5 rounded-2xl print:rounded-none shadow-2xl print:shadow-none space-y-4 font-mono">
      <!-- Header -->
      <div class="text-center border-b border-dashed border-slate-700 print:border-slate-400 pb-3 space-y-1">
        <h2 class="text-lg font-black tracking-wider uppercase">{{ order.shop?.name || 'LaundryHub Shop' }}</h2>
        <p class="text-[11px] text-slate-400 print:text-slate-600">Garment Identification Tag</p>
        <p class="text-[10px] text-slate-400 print:text-slate-600">📞 {{ order.shop?.phone }}</p>
      </div>

      <!-- Order Barcode representation -->
      <div class="text-center bg-slate-900 print:bg-slate-100 p-3 rounded-xl print:rounded-lg space-y-1">
        <span class="text-[10px] text-slate-400 print:text-slate-600 uppercase font-bold block">Order Barcode Tag</span>
        <div class="font-bold text-xl tracking-widest text-sky-400 print:text-black font-mono">
          *{{ order.order_number }}*
        </div>
        <span class="text-[11px] text-slate-300 print:text-slate-800 font-bold block">
          Total Items: {{ totalItems(order.items) }} Garment(s)
        </span>
      </div>

      <!-- Customer & Fulfillment Details -->
      <div class="text-xs space-y-1.5 border-b border-dashed border-slate-700 print:border-slate-400 pb-3">
        <div class="flex justify-between">
          <span class="text-slate-400 print:text-slate-600">Customer:</span>
          <strong class="text-slate-100 print:text-black font-bold">
            {{ order.customer ? (order.customer.first_name + ' ' + order.customer.last_name) : (order.legacy_customer_name || 'Walk-in Customer') }}
          </strong>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-400 print:text-slate-600">Phone:</span>
          <span class="text-slate-200 print:text-black font-bold">{{ order.customer?.phone || order.legacy_customer_phone || 'N/A' }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-400 print:text-slate-600">Fulfillment:</span>
          <span class="text-sky-400 print:text-black font-bold uppercase text-[10px]">
            {{ order.fulfillment_type === 'home_delivery' ? '🚚 Home Delivery' : '🏬 Store Pickup' }}
          </span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-400 print:text-slate-600">Date:</span>
          <span class="text-slate-300 print:text-slate-700 text-[10px]">{{ new Date(order.created_at).toLocaleString() }}</span>
        </div>
      </div>

      <!-- Garment List Breakdown -->
      <div class="space-y-2 text-xs">
        <span class="text-[10px] uppercase font-bold text-slate-400 print:text-slate-600 block">Garment Breakdown:</span>
        <div class="space-y-1">
          <div
            v-for="item in order.items"
            :key="item.id"
            class="flex justify-between items-center text-[11px] border-b border-slate-900 print:border-slate-200 pb-1"
          >
            <span>
              <strong class="text-slate-200 print:text-black">{{ item.quantity }}x</strong>
              {{ item.category?.name }} - {{ item.service?.name }}
            </span>
            <span class="text-emerald-400 print:text-black font-bold">₦{{ Number(item.total_price).toLocaleString() }}</span>
          </div>
        </div>
      </div>

      <!-- Special Notes / Instructions -->
      <div v-if="order.notes" class="p-2.5 rounded-lg bg-slate-900 print:bg-slate-100 text-[10px] text-slate-300 print:text-slate-800 space-y-0.5">
        <span class="font-bold uppercase text-[9px] text-amber-400 print:text-slate-900 block">Special Washing Care Instructions:</span>
        <p class="italic">"{{ order.notes }}"</p>
      </div>

      <!-- Footer Tag Info -->
      <div class="text-center pt-2 text-[9px] text-slate-400 print:text-slate-600 border-t border-dashed border-slate-700 print:border-slate-400">
        <p>LaundryHub SaaS Tagging System</p>
        <p class="font-bold">Attach this tag firmly to Garment Bag #{{ order.order_number }}</p>
      </div>
    </div>
  </div>
</template>
