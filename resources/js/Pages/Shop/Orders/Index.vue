<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps<{
  shop: any;
  orders: any;
  categories: Array<any>;
  services: Array<any>;
  prices: Array<any>;
}>();

const showManualModal = ref(false);
const showLinkModal = ref(false);
const selectedOrderToLink = ref<any>(null);
const linkQuery = ref('');
const requestingPickupOrderId = ref<number | null>(null);

const manualForm = useForm({
  legacy_customer_name: '',
  legacy_customer_phone: '',
  fulfillment_type: 'store_pickup',
  notes: '',
  items: [
    { category_id: '', service_id: '', unit_price: 0, quantity: 1 }
  ],
});

const linkForm = useForm({
  query: '',
});

const addManualItemRow = () => {
  manualForm.items.push({
    category_id: '',
    service_id: '',
    unit_price: 0,
    quantity: 1,
  });
};

const removeManualItemRow = (index: number) => {
  if (manualForm.items.length > 1) {
    manualForm.items.splice(index, 1);
  }
};

const handleCategoryServiceChange = (itemIndex: number) => {
  const item = manualForm.items[itemIndex];
  if (item.category_id && item.service_id) {
    const matchedPrice = props.prices.find(
      (p: any) => p.category_id == item.category_id && p.service_id == item.service_id
    );
    if (matchedPrice) {
      item.unit_price = Number(matchedPrice.price);
    }
  }
};

const manualSubtotal = computed(() => {
  return manualForm.items.reduce((sum, item) => sum + (Number(item.unit_price) * Number(item.quantity)), 0);
});

const submitManualOrder = () => {
  manualForm.post('/shop-admin/orders/manual', {
    onSuccess: () => {
      showManualModal.value = false;
      manualForm.reset();
      manualForm.items = [{ category_id: '', service_id: '', unit_price: 0, quantity: 1 }];
    },
  });
};

const openLinkModal = (order: any) => {
  selectedOrderToLink.value = order;
  linkForm.query = order.legacy_customer_phone || '';
  showLinkModal.value = true;
};

const submitLinkCustomer = () => {
  if (!selectedOrderToLink.value) return;
  linkForm.post(`/shop-admin/orders/${selectedOrderToLink.value.id}/link-customer`, {
    onSuccess: () => {
      showLinkModal.value = false;
      selectedOrderToLink.value = null;
      linkForm.reset();
    },
  });
};

const updateOrderStatus = (order: any, newStatus: string) => {
  useForm({ status: newStatus }).put(`/shop-admin/orders/${order.id}/status`, {
    preserveScroll: true,
  });
};

const requestRiderPickup = (order: any) => {
  requestingPickupOrderId.value = order.id;
  router.post(`/shop-admin/orders/${order.id}/request-pickup`, {}, {
    preserveScroll: true,
    onFinish: () => {
      requestingPickupOrderId.value = null;
    },
  });
};

onMounted(() => {
  if (typeof window !== 'undefined' && (window as any).Echo && props.shop?.id) {
    (window as any).Echo.channel(`shop.${props.shop.id}`)
      .listen('.bid.submitted', () => {
        router.reload({ preserveScroll: true });
      })
      .listen('.order.accepted', () => {
        router.reload({ preserveScroll: true });
      });
  }
});

onUnmounted(() => {
  if (typeof window !== 'undefined' && (window as any).Echo && props.shop?.id) {
    (window as any).Echo.leaveChannel(`shop.${props.shop.id}`);
  }
});

const statusBadgeColor = (status: string) => {
  switch (status) {
    case 'completed': return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
    case 'cleaning_in_progress': return 'bg-sky-500/10 text-sky-400 border-sky-500/20';
    case 'ready_for_delivery':
    case 'ready_for_pickup': return 'bg-purple-500/10 text-purple-400 border-purple-500/20';
    case 'out_for_delivery': return 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20';
    default: return 'bg-amber-500/10 text-amber-400 border-amber-500/20';
  }
};

// Real-time shop orders polling without triggering full-screen loader
let pollInterval: any = null;

onMounted(() => {
  pollInterval = setInterval(() => {
    router.reload({
      only: ['orders'],
      preserveScroll: true,
      showProgress: false,
    });
  }, 5000);
});

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval);
});
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xl">
        <div>
          <h1 class="text-2xl font-bold text-slate-100">Shop Order Management</h1>
          <p class="text-xs text-slate-400 mt-1">
            Track customer bookings, log walk-in legacy garments, link registered users, and dispatch riders
          </p>
        </div>

        <button
          @click="showManualModal = true"
          class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-xs shadow-lg shadow-sky-500/20 hover:scale-105 transition-all flex items-center gap-2"
        >
          <span>📋</span>
          <span>+ Log Manual / Legacy Order</span>
        </button>
      </div>

      <!-- Orders Table -->
      <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-950/80 text-slate-400 uppercase font-semibold border-b border-slate-700/80">
                <th class="p-4">Order # & Date</th>
                <th class="p-4">Customer</th>
                <th class="p-4">Fulfillment & Rider</th>
                <th class="p-4">Items Breakdown</th>
                <th class="p-4">Total Amount</th>
                <th class="p-4">Status & Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800 text-slate-300">
              <tr v-for="order in orders.data" :key="order.id" class="hover:bg-slate-900/40 transition-colors">
                <!-- Order # & Date -->
                <td class="p-4">
                  <div class="font-mono font-bold text-sky-400 text-sm">#{{ order.order_number }}</div>
                  <div class="text-[10px] text-slate-400 mt-0.5">{{ new Date(order.created_at).toLocaleString() }}</div>
                  <span v-if="order.is_legacy" class="inline-block mt-1 px-2 py-0.5 rounded text-[9px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    Walk-In / Legacy
                  </span>
                </td>

                <!-- Customer -->
                <td class="p-4">
                  <div v-if="order.customer" class="space-y-0.5">
                    <span class="font-bold text-slate-100 block">{{ order.customer.first_name }} {{ order.customer.last_name }}</span>
                    <span class="text-[11px] text-slate-400 font-mono block">📞 {{ order.customer.phone || 'No phone' }}</span>
                  </div>
                  <div v-else class="space-y-1">
                    <span class="font-bold text-amber-300 block">{{ order.legacy_customer_name }}</span>
                    <span class="text-[11px] text-slate-400 font-mono block">📞 {{ order.legacy_customer_phone }}</span>
                    <button
                      @click="openLinkModal(order)"
                      class="px-2 py-1 rounded bg-sky-500/10 text-sky-400 border border-sky-500/20 text-[10px] font-bold hover:bg-sky-500/20 transition-all inline-flex items-center gap-1"
                    >
                      <span>🔗 Link Registered Customer</span>
                    </button>
                  </div>
                </td>

                <!-- Fulfillment & Rider -->
                <td class="p-4 space-y-1.5">
                  <span class="capitalize font-semibold text-slate-200 block">
                    {{ order.fulfillment_type === 'home_delivery' ? '🚚 Home Delivery' : '🏬 Store Self Pickup' }}
                  </span>
                  <span class="text-[10px] text-slate-400 block truncate max-w-[150px]">
                    {{ order.delivery_address || 'At store' }}
                  </span>

                  <!-- Rider Info / Dispatch Trigger -->
                  <div v-if="order.rider" class="bg-emerald-500/10 border border-emerald-500/20 rounded-lg p-1.5 text-[10px] text-emerald-300 space-y-0.5">
                    <span class="font-bold block">🛵 Assigned Rider:</span>
                    <span>{{ order.rider.first_name }} {{ order.rider.last_name }}</span>
                    <span class="block font-mono text-[9.5px]">📞 {{ order.rider.phone || 'N/A' }}</span>
                  </div>
                  <div v-else-if="order.fulfillment_type === 'home_delivery' && order.status !== 'completed' && order.status !== 'cancelled'">
                    <button
                      @click="requestRiderPickup(order)"
                      :disabled="requestingPickupOrderId === order.id"
                      class="px-2.5 py-1.5 rounded-lg bg-gradient-to-r from-purple-500 to-indigo-500 text-white text-[10px] font-bold shadow hover:scale-105 transition-all flex items-center gap-1 disabled:opacity-60"
                    >
                      <span>⚡</span>
                      <span>{{ requestingPickupOrderId === order.id ? 'Dispatching...' : 'Dispatch Pickup Rider' }}</span>
                    </button>
                  </div>
                </td>

                <!-- Items Breakdown -->
                <td class="p-4">
                  <div class="space-y-1">
                    <div v-for="item in order.items" :key="item.id" class="text-[11px] text-slate-300">
                      <span class="font-bold text-slate-100">{{ item.quantity }}x</span> {{ item.category_name }} ({{ item.service_name }})
                    </div>
                  </div>
                </td>

                <!-- Total Amount -->
                <td class="p-4 font-mono font-bold text-emerald-400 text-sm">
                  ₦{{ Number(order.total_amount).toLocaleString() }}
                </td>

                <!-- Status & Action -->
                <td class="p-4">
                  <div class="flex items-center gap-2">
                    <select
                      :value="order.status"
                      @change="updateOrderStatus(order, ($event.target as HTMLSelectElement).value)"
                      class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-950 border border-slate-700 text-slate-100 focus:border-sky-500"
                    >
                      <option value="pending">⏳ Pending Review</option>
                      <option value="confirmed">✓ Confirmed</option>
                      <option value="cleaning_in_progress">🧼 Cleaning in Progress</option>
                      <option value="ready_for_delivery">📦 Ready for Delivery</option>
                      <option value="ready_for_pickup">🏬 Ready for Store Pickup</option>
                      <option value="out_for_delivery">🚚 Out for Delivery</option>
                      <option value="completed">✅ Completed</option>
                      <option value="cancelled">❌ Cancelled</option>
                    </select>

                    <a
                      :href="`/shop-admin/orders/${order.id}/tag`"
                      target="_blank"
                      class="px-2.5 py-1.5 rounded-xl bg-slate-800 border border-slate-700 text-slate-300 hover:text-white font-bold text-xs flex items-center gap-1 transition-colors shrink-0"
                      title="Print Garment Tag"
                    >
                      🏷️ Tag
                    </a>

                    <Link
                      :href="`/orders/${order.order_number}`"
                      target="_blank"
                      class="px-2.5 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-sky-400 hover:text-sky-300 font-bold text-xs flex items-center gap-1 transition-colors shrink-0"
                      title="View Full Order Details & Audit Trail"
                    >
                      👁️ Details
                    </Link>
                  </div>
                </td>
              </tr>

              <tr v-if="!orders.data || orders.data.length === 0">
                <td colspan="6" class="p-12 text-center text-slate-400 text-xs">
                  <div class="space-y-3 max-w-sm mx-auto">
                    <span class="text-4xl block">🧺</span>
                    <h4 class="font-bold text-slate-200 text-sm">No Shop Customer Bookings Yet</h4>
                    <p class="text-xs text-slate-400">
                      No customer bookings received yet. Share your storefront link or click <strong>"+ Log Manual / Legacy Order"</strong> above to record walk-in garments!
                    </p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Manual Legacy Order Creation Modal -->
    <div v-if="showManualModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
      <div class="bg-slate-900 border border-slate-700/80 rounded-2xl w-full max-w-2xl p-6 space-y-6 shadow-2xl my-8">
        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
          <div>
            <h2 class="text-lg font-bold text-slate-100">Log Manual / Legacy Walk-In Order</h2>
            <p class="text-xs text-slate-400">Record garments received offline before customer registered on LaundryHub</p>
          </div>
          <button @click="showManualModal = false" class="text-slate-400 hover:text-white text-xl">✕</button>
        </div>

        <form @submit.prevent="submitManualOrder" class="space-y-4">
          <!-- Walk-in Customer Info -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Customer Name *</label>
              <input
                v-model="manualForm.legacy_customer_name"
                type="text"
                required
                placeholder="e.g., Chief Emeka Okafor"
                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-100 text-xs focus:border-sky-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Customer Phone *</label>
              <input
                v-model="manualForm.legacy_customer_phone"
                type="text"
                required
                placeholder="e.g., 08012345678"
                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-100 text-xs font-mono focus:border-sky-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Fulfillment Option *</label>
            <select
              v-model="manualForm.fulfillment_type"
              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-100 text-xs focus:border-sky-500"
            >
              <option value="store_pickup">🏬 In-Store Self Pickup</option>
              <option value="home_delivery">🚚 Doorstep Home Delivery</option>
            </select>
          </div>

          <!-- Items Rows -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <label class="block text-xs font-bold text-sky-400 uppercase">Garment Items *</label>
              <button
                type="button"
                @click="addManualItemRow"
                class="text-xs text-sky-400 hover:underline font-semibold"
              >
                + Add Another Garment
              </button>
            </div>

            <!-- Column Header Labels -->
            <div class="hidden sm:grid grid-cols-12 gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider px-3">
              <div class="col-span-4">Category *</div>
              <div class="col-span-3">Service Treatment *</div>
              <div class="col-span-2">Price (₦) *</div>
              <div class="col-span-2">Quantity *</div>
              <div class="col-span-1"></div>
            </div>

            <div
              v-for="(item, idx) in manualForm.items"
              :key="idx"
              class="bg-slate-950 p-3 rounded-xl border border-slate-800 grid grid-cols-1 sm:grid-cols-12 gap-2 items-center text-xs"
            >
              <!-- Category -->
              <div class="sm:col-span-4">
                <label class="block sm:hidden text-[10px] font-bold text-slate-400 uppercase mb-1">Category *</label>
                <select
                  v-model="item.category_id"
                  @change="handleCategoryServiceChange(idx)"
                  required
                  class="w-full px-2.5 py-2 rounded-lg bg-slate-900 border border-slate-700 text-slate-100 text-xs"
                >
                  <option value="" disabled>Select Category</option>
                  <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.icon }} {{ c.name }}</option>
                </select>
              </div>

              <!-- Service -->
              <div class="sm:col-span-3">
                <label class="block sm:hidden text-[10px] font-bold text-slate-400 uppercase mb-1">Service *</label>
                <select
                  v-model="item.service_id"
                  @change="handleCategoryServiceChange(idx)"
                  required
                  class="w-full px-2.5 py-2 rounded-lg bg-slate-900 border border-slate-700 text-slate-100 text-xs"
                >
                  <option value="" disabled>Select Service</option>
                  <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
              </div>

              <!-- Unit Price -->
              <div class="sm:col-span-2">
                <label class="block sm:hidden text-[10px] font-bold text-slate-400 uppercase mb-1">Price (₦) *</label>
                <div class="relative">
                  <span class="absolute left-2.5 top-2.5 text-[10px] font-bold text-slate-400">₦</span>
                  <input
                    v-model.number="item.unit_price"
                    type="number"
                    min="0"
                    required
                    placeholder="Price"
                    class="w-full pl-6 pr-2 py-2 rounded-lg bg-slate-900 border border-slate-700 text-slate-100 font-mono text-xs"
                  />
                </div>
              </div>

              <!-- Quantity -->
              <div class="sm:col-span-2">
                <label class="block sm:hidden text-[10px] font-bold text-slate-400 uppercase mb-1">Quantity *</label>
                <input
                  v-model.number="item.quantity"
                  type="number"
                  min="1"
                  required
                  placeholder="Qty"
                  class="w-full px-2.5 py-2 rounded-lg bg-slate-900 border border-slate-700 text-slate-100 font-mono text-xs text-center"
                />
              </div>

              <!-- Delete Row -->
              <div class="sm:col-span-1 text-right">
                <button
                  type="button"
                  @click="removeManualItemRow(idx)"
                  class="text-rose-400 hover:text-rose-300 font-bold text-sm"
                >
                  ✕
                </button>
              </div>
            </div>
          </div>

          <!-- Total Calculation Banner -->
          <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 flex items-center justify-between font-mono">
            <span class="text-xs text-slate-400">Total Calculated Order Value:</span>
            <span class="text-lg font-bold text-emerald-400">₦{{ manualSubtotal.toLocaleString() }}</span>
          </div>

          <button
            type="submit"
            :disabled="manualForm.processing"
            class="w-full py-3 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-xs shadow-lg shadow-sky-500/20"
          >
            Save Manual Legacy Order
          </button>
        </form>
      </div>
    </div>

    <!-- Link Registered Customer Modal -->
    <div v-if="showLinkModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-700/80 rounded-2xl w-full max-w-md p-6 space-y-4 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <h2 class="text-base font-bold text-slate-100">🔗 Link Registered Customer</h2>
          <button @click="showLinkModal = false" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <p class="text-xs text-slate-400 leading-relaxed">
          Search for the customer by phone number or email address after they have registered on LaundryHub:
        </p>

        <form @submit.prevent="submitLinkCustomer" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Customer Phone or Email</label>
            <input
              v-model="linkForm.query"
              type="text"
              required
              placeholder="e.g., 08012345678 or user@email.com"
              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-100 text-xs font-mono"
            />
          </div>

          <button
            type="submit"
            :disabled="linkForm.processing"
            class="w-full py-3 rounded-xl bg-sky-500 text-slate-950 font-bold text-xs shadow-lg"
          >
            Attach Customer to Order #{{ selectedOrderToLink?.order_number }}
          </button>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
