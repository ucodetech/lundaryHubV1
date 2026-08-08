<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps<{
  shop: any;
}>();

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const selectedCategory = ref<number | null>(null);
const selectedService = ref<number | null>(null);
const quantity = ref(1);

interface CartItem {
  category_id: number;
  service_id: number;
  category_name: string;
  service_name: string;
  unit_price: number;
  quantity: number;
}

const cart = ref<CartItem[]>([]);
const showCartDrawer = ref(false);
const showCheckoutModal = ref(false);

// Auto-select first category on load if available
if (props.shop.categories && props.shop.categories.length > 0) {
  selectedCategory.value = props.shop.categories[0].id;
}

// Available services for selected garment category
const availableServicesForCategory = computed(() => {
  if (!selectedCategory.value) return props.shop.services || [];
  const validServiceIds = props.shop.prices
    ?.filter((p: any) => p.category_id === selectedCategory.value)
    ?.map((p: any) => p.service_id) || [];
  
  if (validServiceIds.length === 0) return props.shop.services || [];
  return props.shop.services?.filter((s: any) => validServiceIds.includes(s.id)) || [];
});

// Auto pre-select service & unit price when garment category changes
watch(selectedCategory, (newCatId) => {
  if (!newCatId) {
    selectedService.value = null;
    return;
  }
  const categoryPrices = props.shop.prices?.filter((p: any) => p.category_id === newCatId) || [];
  if (categoryPrices.length > 0) {
    selectedService.value = categoryPrices[0].service_id;
  } else if (props.shop.services && props.shop.services.length > 0) {
    selectedService.value = props.shop.services[0].id;
  }
}, { immediate: true });

const activePrice = computed(() => {
  if (!selectedCategory.value || !selectedService.value) return null;
  return props.shop.prices?.find(
    (p: any) => p.category_id === selectedCategory.value && p.service_id === selectedService.value
  );
});

const calculatedItemPrice = computed(() => {
  return activePrice.value ? Number(activePrice.value.amount) : 0;
});

const addToCart = () => {
  if (!selectedCategory.value || !selectedService.value || !activePrice.value) return;

  const cat = props.shop.categories?.find((c: any) => c.id === selectedCategory.value);
  const srv = props.shop.services?.find((s: any) => s.id === selectedService.value);

  const existingIndex = cart.value.findIndex(
    (item) => item.category_id === selectedCategory.value && item.service_id === selectedService.value
  );

  if (existingIndex > -1) {
    cart.value[existingIndex].quantity += quantity.value;
  } else {
    cart.value.push({
      category_id: selectedCategory.value,
      service_id: selectedService.value,
      category_name: cat ? (cat.icon ? `${cat.icon} ${cat.name}` : cat.name) : 'Garment',
      service_name: srv ? srv.name : 'Cleaning Service',
      unit_price: Number(activePrice.value.amount),
      quantity: quantity.value,
    });
  }

  showCartDrawer.value = true;
};

const updateCartQuantity = (index: number, delta: number) => {
  cart.value[index].quantity += delta;
  if (cart.value[index].quantity <= 0) {
    cart.value.splice(index, 1);
  }
};

const cartSubtotal = computed(() => {
  return cart.value.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
});

const checkoutForm = useForm({
  shop_id: props.shop.id,
  fulfillment_type: props.shop.offers_home_delivery ? 'home_delivery' : 'store_pickup',
  payment_method: 'paystack',
  pickup_address: '',
  delivery_address: '',
  notes: '',
  items: [] as any[],
});

const deliveryFee = computed(() => {
  return checkoutForm.fulfillment_type === 'home_delivery' ? Number(props.shop.delivery_fee) : 0;
});

const totalPayable = computed(() => {
  return cartSubtotal.value + deliveryFee.value;
});

const openCheckout = () => {
  if (!currentUser.value) {
    window.location.href = '/login';
    return;
  }
  checkoutForm.pickup_address = currentUser.value.address || props.shop.address || '';
  checkoutForm.delivery_address = currentUser.value.address || props.shop.address || '';
  showCartDrawer.value = false;
  showCheckoutModal.value = true;
};

const submitOrderBooking = () => {
  checkoutForm.items = cart.value.map((item) => ({
    category_id: item.category_id,
    service_id: item.service_id,
    unit_price: item.unit_price,
    quantity: item.quantity,
  }));

  checkoutForm.post('/orders', {
    onSuccess: () => {
      cart.value = [];
      showCheckoutModal.value = false;
    },
  });
};
</script>

<template>
  <Head :title="`${shop.name} — LaundryHub`" />

  <div class="min-h-screen bg-gray-50 dark:bg-slate-950 text-gray-900 dark:text-slate-100 font-sans">
    <!-- Navbar Header -->
    <header class="h-16 border-b border-gray-200 dark:border-slate-800 px-6 flex items-center justify-between sticky top-0 bg-gray-50 dark:bg-slate-950/90 backdrop-blur-md z-30">
      <Link href="/" class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-sky-500 to-cyan-400 flex items-center justify-center font-black text-slate-950 text-lg shadow-lg shadow-sky-500/20">
          L
        </div>
        <span class="font-bold text-lg text-gray-900 dark:text-slate-100">LaundryHub</span>
      </Link>

      <div class="flex items-center gap-3">
        <!-- Floating Cart Button -->
        <button
          @click="showCartDrawer = true"
          class="relative px-4 py-2 rounded-xl text-xs font-bold bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 hover:border-sky-500 text-sky-400 flex items-center gap-2 transition-all shadow"
        >
          <span>🛒 Cart</span>
          <span v-if="cart.length > 0" class="px-2 py-0.5 rounded-full bg-sky-500 text-slate-950 text-[10px] font-black">
            {{ cart.reduce((acc, i) => acc + i.quantity, 0) }}
          </span>
        </button>

        <Link v-if="currentUser" href="/dashboard" class="px-4 py-2 rounded-xl text-xs font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20">
          Dashboard
        </Link>
        <Link v-else href="/login" class="px-4 py-2 rounded-xl text-xs font-bold bg-sky-500 text-slate-950 shadow-lg">
          Sign In
        </Link>
      </div>
    </header>

    <div class="max-w-4xl mx-auto p-6 space-y-8">
      <!-- Shop Header Banner -->
      <div class="bg-gradient-to-br from-slate-900 via-slate-900 to-slate-800 border border-gray-200 dark:border-slate-800 rounded-3xl p-8 shadow-2xl space-y-6">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
          <div class="space-y-3">
            <div class="flex flex-wrap items-center gap-3">
              <h1 class="text-3xl font-black text-gray-900 dark:text-slate-100">{{ shop.name }}</h1>
              <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                ⭐ 4.8 Verified Storefront
              </span>

              <!-- Business Registration Badge -->
              <span
                v-if="shop.business_type === 'cac_registered'"
                class="px-3 py-1 rounded-full text-xs font-bold bg-purple-500/10 text-purple-300 border border-purple-500/20 flex items-center gap-1"
              >
                <span>🏛️ Verified Business (CAC Registered)</span>
              </span>
              <span
                v-else
                class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-slate-700 flex items-center gap-1"
              >
                <span>🏪 Independent Operator</span>
              </span>
            </div>

            <p class="text-sm text-gray-500 dark:text-slate-400 leading-relaxed max-w-xl">{{ shop.description || 'Professional dry cleaning and garment care services.' }}</p>

            <div class="flex flex-wrap gap-4 text-xs text-gray-500 dark:text-slate-400 pt-1 font-mono">
              <span>📍 {{ shop.address }}</span>
              <span>📞 {{ shop.phone }}</span>
              <span>🚚 Delivery Fee: ₦{{ Number(shop.delivery_fee).toLocaleString() }}</span>
            </div>
          </div>
        </div>

        <!-- Fulfillment Availability Badges -->
        <div class="flex flex-wrap items-center gap-3 pt-2">
          <span
            class="px-3 py-1 rounded-xl text-xs font-bold border"
            :class="shop.offers_home_delivery ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-500 border-gray-200 dark:border-slate-700 line-through'"
          >
            🚚 Doorstep Delivery {{ shop.offers_home_delivery ? 'Available' : 'Disabled' }}
          </span>

          <span
            class="px-3 py-1 rounded-xl text-xs font-bold border"
            :class="shop.offers_store_pickup ? 'bg-sky-500/10 text-sky-400 border-sky-500/20' : 'bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-500 border-gray-200 dark:border-slate-700 line-through'"
          >
            🏬 In-Store Pickup {{ shop.offers_store_pickup ? 'Available' : 'Disabled' }}
          </span>
        </div>
      </div>

      <!-- Pricing & Garment Item Selector Widget -->
      <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-3xl p-8 space-y-6 shadow-2xl">
        <h2 class="text-xl font-bold text-gray-900 dark:text-slate-100">Select Garments & Add to Basket</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-2">1. Select Garment Category</label>
            <select
              v-model="selectedCategory"
              class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500"
            >
              <option :value="null" disabled>Select garment type...</option>
              <option v-for="cat in shop.categories" :key="cat.id" :value="cat.id">
                {{ cat.icon ? `${cat.icon} ${cat.name}` : cat.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-2">2. Laundry Treatment</label>
            <select
              v-model="selectedService"
              class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500"
            >
              <option :value="null" disabled>Select service...</option>
              <option v-for="srv in availableServicesForCategory" :key="srv.id" :value="srv.id">
                {{ srv.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-2">3. Quantity</label>
            <input
              v-model.number="quantity"
              type="number"
              min="1"
              class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500 font-mono"
            />
          </div>
        </div>

        <div class="p-6 rounded-2xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-center gap-6">
            <div>
              <span class="text-xs text-gray-500 dark:text-slate-400 uppercase tracking-wider font-semibold">Unit Price</span>
              <h3 class="text-2xl font-bold text-gray-900 dark:text-slate-100 font-mono">
                ₦{{ calculatedItemPrice.toLocaleString() }}
              </h3>
            </div>

            <div v-if="quantity > 1">
              <span class="text-xs text-sky-400 uppercase tracking-wider font-semibold">Subtotal ({{ quantity }} items)</span>
              <h3 class="text-2xl font-black text-sky-400 font-mono">
                ₦{{ (calculatedItemPrice * quantity).toLocaleString() }}
              </h3>
            </div>
          </div>

          <button
            @click="addToCart"
            :disabled="!activePrice"
            class="px-8 py-3.5 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-sm disabled:opacity-40 shadow-xl shadow-sky-500/20 hover:scale-105 transition-transform flex items-center justify-center gap-2"
          >
            <span>🧺</span>
            <span>Add to Basket</span>
          </button>
        </div>
      </div>

      <!-- Pricing Matrix Reference Table -->
      <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-3xl p-8 shadow-2xl">
        <h2 class="text-xl font-bold text-gray-900 dark:text-slate-100 mb-4">Complete Services & Price Menu</h2>
        <div class="overflow-x-auto custom-scrollbar">
          <table class="w-full text-left text-sm">
            <thead class="text-xs uppercase text-gray-500 dark:text-slate-400 border-b border-gray-200 dark:border-slate-800">
              <tr>
                <th class="py-3 px-4">Category</th>
                <th class="py-3 px-4">Service</th>
                <th class="py-3 px-4 text-right">Price per Item</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-slate-800/60">
              <tr v-for="price in shop.prices" :key="price.id" class="hover:bg-slate-800/40">
                <td class="py-3 px-4 font-semibold text-gray-800 dark:text-slate-200">
                  {{ price.category?.icon ? `${price.category?.icon} ${price.category?.name}` : price.category?.name }}
                </td>
                <td class="py-3 px-4 text-gray-500 dark:text-slate-400">{{ price.service?.name }}</td>
                <td class="py-3 px-4 text-right font-bold text-sky-400 font-mono">₦{{ Number(price.amount).toLocaleString() }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Slide-over Shopping Cart Drawer -->
    <div v-if="showCartDrawer" class="fixed inset-0 z-50 bg-gray-50 dark:bg-slate-950/80 backdrop-blur-sm flex justify-end">
      <div class="w-full max-w-md bg-white dark:bg-slate-900 border-l border-gray-200 dark:border-slate-800 h-full p-6 flex flex-col justify-between shadow-2xl space-y-6">
        <div class="space-y-6">
          <div class="flex items-center justify-between border-b border-gray-200 dark:border-slate-800 pb-4">
            <div class="flex items-center gap-2">
              <span class="text-xl">🧺</span>
              <h2 class="text-lg font-bold text-gray-900 dark:text-slate-100">Your Basket</h2>
            </div>
            <button @click="showCartDrawer = false" class="text-gray-500 dark:text-slate-400 hover:text-white text-xl">✕</button>
          </div>

          <div v-if="cart.length === 0" class="py-12 text-center text-gray-500 dark:text-slate-400 text-xs">
            Your laundry basket is empty. Select garments above to add items!
          </div>

          <div v-else class="space-y-3 max-h-[60vh] overflow-y-auto pr-1">
            <div
              v-for="(item, idx) in cart"
              :key="idx"
              class="p-4 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 flex items-center justify-between"
            >
              <div>
                <h4 class="font-bold text-sm text-gray-800 dark:text-slate-200">{{ item.category_name }}</h4>
                <p class="text-xs text-gray-500 dark:text-slate-400">{{ item.service_name }} • ₦{{ item.unit_price.toLocaleString() }} / item</p>
              </div>

              <div class="flex items-center gap-3">
                <div class="flex items-center gap-1 border border-gray-200 dark:border-slate-800 rounded-lg p-1 bg-white dark:bg-slate-900">
                  <button @click="updateCartQuantity(idx, -1)" class="w-6 h-6 rounded bg-gray-100 dark:bg-slate-800 text-xs font-bold text-gray-700 dark:text-slate-300 hover:bg-slate-700">-</button>
                  <span class="text-xs font-bold px-2 font-mono text-gray-900 dark:text-slate-100">{{ item.quantity }}</span>
                  <button @click="updateCartQuantity(idx, 1)" class="w-6 h-6 rounded bg-gray-100 dark:bg-slate-800 text-xs font-bold text-gray-700 dark:text-slate-300 hover:bg-slate-700">+</button>
                </div>
                <span class="font-bold text-sm text-sky-400 font-mono">₦{{ (item.unit_price * item.quantity).toLocaleString() }}</span>
              </div>
            </div>
          </div>
        </div>

        <div v-if="cart.length > 0" class="space-y-4 border-t border-gray-200 dark:border-slate-800 pt-4">
          <div class="flex justify-between items-center text-sm font-bold text-gray-800 dark:text-slate-200">
            <span>Basket Subtotal:</span>
            <span class="text-sky-400 font-mono text-lg">₦{{ cartSubtotal.toLocaleString() }}</span>
          </div>

          <button
            @click="openCheckout"
            class="w-full py-3.5 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-sm shadow-xl shadow-sky-500/20 hover:scale-[1.02] transition-transform"
          >
            Proceed to Checkout →
          </button>
        </div>
      </div>
    </div>

    <!-- Checkout Modal -->
    <div v-if="showCheckoutModal" class="fixed inset-0 z-50 bg-gray-50 dark:bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-3xl w-full max-w-lg p-6 space-y-6 shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-slate-800 pb-4">
          <h2 class="text-lg font-bold text-gray-900 dark:text-slate-100">🛒 Complete Booking & Order</h2>
          <button @click="showCheckoutModal = false" class="text-gray-500 dark:text-slate-400 hover:text-white text-xl">✕</button>
        </div>

        <form @submit.prevent="submitOrderBooking" class="space-y-4">
          <!-- Fulfillment Method Selector -->
          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-2">Fulfillment Method *</label>
            <div class="grid grid-cols-2 gap-3">
              <button
                type="button"
                :disabled="!shop.offers_home_delivery"
                @click="checkoutForm.fulfillment_type = 'home_delivery'"
                class="p-3.5 rounded-xl border text-left transition-all disabled:opacity-40"
                :class="checkoutForm.fulfillment_type === 'home_delivery' ? 'bg-sky-500/10 border-sky-500 text-sky-400 font-bold' : 'bg-gray-50 dark:bg-slate-950 border-gray-200 dark:border-slate-800 text-gray-500 dark:text-slate-400'"
              >
                <div class="text-xs">🚚 Home Delivery</div>
                <div class="text-[10px] text-gray-500 dark:text-slate-400 mt-0.5">+ ₦{{ Number(shop.delivery_fee).toLocaleString() }}</div>
              </button>

              <button
                type="button"
                :disabled="!shop.offers_store_pickup"
                @click="checkoutForm.fulfillment_type = 'store_pickup'"
                class="p-3.5 rounded-xl border text-left transition-all disabled:opacity-40"
                :class="checkoutForm.fulfillment_type === 'store_pickup' ? 'bg-sky-500/10 border-sky-500 text-sky-400 font-bold' : 'bg-gray-50 dark:bg-slate-950 border-gray-200 dark:border-slate-800 text-gray-500 dark:text-slate-400'"
              >
                <div class="text-xs">🏬 Store Self Pickup</div>
                <div class="text-[10px] text-gray-500 dark:text-slate-400 mt-0.5">Free Pickup</div>
              </button>
            </div>
          </div>

          <div v-if="checkoutForm.fulfillment_type === 'home_delivery'">
            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-1">Doorstep Delivery Address *</label>
            <textarea
              v-model="checkoutForm.delivery_address"
              required
              rows="2"
              placeholder="Full address for garment pickup & delivery..."
              class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-gray-900 dark:text-slate-100 text-xs focus:border-sky-500"
            ></textarea>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-1">Special Garment Instructions / Notes</label>
            <textarea
              v-model="checkoutForm.notes"
              rows="2"
              placeholder="e.g., Handle silk dress with care, starch white shirts heavily..."
              class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 text-gray-900 dark:text-slate-100 text-xs focus:border-sky-500"
            ></textarea>
          </div>

          <!-- Total Calculation Card -->
          <div class="bg-gray-50 dark:bg-slate-950 p-4 rounded-xl border border-gray-200 dark:border-slate-800 space-y-2 text-xs font-mono">
            <div class="flex justify-between text-gray-500 dark:text-slate-400">
              <span>Garments Subtotal:</span>
              <span>₦{{ cartSubtotal.toLocaleString() }}</span>
            </div>
            <div class="flex justify-between text-gray-500 dark:text-slate-400">
              <span>Logistics Delivery Fee:</span>
              <span>₦{{ deliveryFee.toLocaleString() }}</span>
            </div>
            <div class="flex justify-between text-gray-900 dark:text-slate-100 font-bold border-t border-gray-200 dark:border-slate-800 pt-2 text-sm">
              <span>Total Payable Amount:</span>
              <span class="text-emerald-400 font-black">₦{{ totalPayable.toLocaleString() }}</span>
            </div>
          </div>

          <button
            type="submit"
            :disabled="checkoutForm.processing"
            class="w-full py-3.5 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-sm shadow-xl shadow-sky-500/20 hover:scale-[1.02] transition-transform disabled:opacity-60"
          >
            Confirm & Pay ₦{{ totalPayable.toLocaleString() }} via Paystack →
          </button>
        </form>
      </div>
    </div>
  </div>
</template>
