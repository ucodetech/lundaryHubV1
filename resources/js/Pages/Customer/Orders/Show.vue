<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import DisputeModal from '@/Components/DisputeModal.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';

const showDisputeModal = ref(false);

const props = defineProps<{
  order: any;
}>();

const page = usePage();
const authUser = computed(() => page.props.auth?.user);
const isSuperAdmin = computed(() => authUser.value?.role === 'super_admin');
const isShopOwner = computed(() => authUser.value?.role === 'shop_owner');
const isCustomerOwner = computed(() => authUser.value?.id === props.order.customer_id || !props.order.customer_id);

const updateStatus = (newStatus: string) => {
  if (!isShopOwner.value) return;
  router.put(`/shop-admin/orders/${props.order.id}/status`, { status: newStatus }, {
    preserveScroll: true,
  });
};

const steps = [
  { key: 'pending', title: 'Order Submitted', icon: '📝' },
  { key: 'confirmed', title: 'Order Confirmed', icon: '✓' },
  { key: 'pickup_assigned', title: 'Rider Assigned for Pickup', icon: '🛵' },
  { key: 'garments_picked_up', title: 'Garments Picked Up', icon: '🧺' },
  { key: 'cleaning_in_progress', title: 'Washing & Cleaning', icon: '🧼' },
  { key: 'ready_for_delivery', title: 'Ready for Return Dispatch', icon: '📦' },
  { key: 'out_for_delivery', title: 'Out for Final Delivery', icon: '🚚' },
  { key: 'completed', title: 'Delivered & Complete', icon: '✅' },
];

const currentStepIndex = computed(() => {
  switch (props.order.status) {
    case 'pending': return 0;
    case 'confirmed': return 1;
    case 'pickup_assigned': return 2;
    case 'garments_picked_up': return 3;
    case 'cleaning_in_progress': return 4;
    case 'ready_for_delivery':
    case 'ready_for_pickup': return 5;
    case 'out_for_delivery': return 6;
    case 'completed': return 7;
    default: return 0;
  }
});

const acceptingBidId = ref<number | null>(null);
const confirmingDelivery = ref(false);
const selectedRiderImage = ref<{ url: string; name: string; vehicle?: string } | null>(null);

const confirmDelivery = () => {
  confirmingDelivery.value = true;
  router.post(`/orders/${props.order.id}/confirm-delivery`, {}, {
    preserveScroll: true,
    onFinish: () => {
      confirmingDelivery.value = false;
    },
  });
};

const reviewForm = ref({
  shop_rating: 5,
  rider_rating: 5,
  shop_comment: '',
  rider_comment: '',
});
const submittingReview = ref(false);

const submitReview = () => {
  submittingReview.value = true;
  router.post(`/orders/${props.order.id}/review`, reviewForm.value, {
    preserveScroll: true,
    onFinish: () => {
      submittingReview.value = false;
    },
  });
};

const acceptBid = (bidId: number) => {
  acceptingBidId.value = bidId;
  router.post(`/orders/${props.order.id}/bids/${bidId}/accept`, {}, {
    preserveScroll: true,
    onFinish: () => {
      acceptingBidId.value = null;
    },
  });
};

const rejectBid = (bidId: number) => {
  router.post(`/orders/${props.order.id}/bids/${bidId}/reject`, {}, {
    preserveScroll: true,
  });
};

const getRiderSelfieUrl = (rider: any) => {
  if (!rider) return '';
  if (rider.avatar) return rider.avatar;
  const kycDocs = rider.rider_profile?.kyc_documents || rider.riderProfile?.kycDocuments || rider.rider_profile?.kycDocuments;
  const selfieDoc = kycDocs?.find((doc: any) => doc.document_type === 'selfie' || doc.document_type?.value === 'selfie');
  if (selfieDoc?.file_path) {
    if (selfieDoc.file_path.startsWith('http')) return selfieDoc.file_path;
    return `/storage/${selfieDoc.file_path}`;
  }
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(rider.first_name + ' ' + rider.last_name)}&background=7C3AED&color=fff`;
};

const openRiderImageModal = (rider: any) => {
  if (!rider) return;
  const url = getRiderSelfieUrl(rider);
  const name = `${rider.first_name || ''} ${rider.last_name || ''}`.trim() || 'Rider Selfie';
  const vehicle = rider.rider_profile?.vehicle_type || 'Verified Rider';
  selectedRiderImage.value = { url, name, vehicle };
};

const closeRiderImageModal = () => {
  selectedRiderImage.value = null;
};

// Real-time status update polling without triggering full-screen loader
let pollInterval: any = null;

onMounted(() => {
  pollInterval = setInterval(() => {
    router.reload({
      only: ['order'],
      preserveScroll: true,
      showProgress: false,
    });
  }, 4000);
});

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval);
});
</script>

<template>
  <AppLayout>
    <div class="max-w-4xl mx-auto space-y-8">
      <!-- Header Banner -->
      <div class="bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xl">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Live Order Tracking</h1>
            <span class="font-mono font-bold text-sky-400">#{{ order.order_number }}</span>
          </div>
          <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">
            Real-time status updates from {{ order.shop?.name }}
          </p>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="showDisputeModal = true"
            class="px-3.5 py-2 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs font-bold text-amber-400 hover:bg-amber-500/20 transition-all flex items-center gap-1.5"
          >
            ⚠️ Report Issue
          </button>

          <Link
            href="/orders"
            class="px-4 py-2 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-xs font-bold text-gray-700 dark:text-slate-300 hover:text-white transition-all"
          >
            ← Back to Orders
          </Link>
        </div>
      </div>

      <!-- Dispute Modal Component -->
      <DisputeModal
        :order="order"
        :show="showDisputeModal"
        @close="showDisputeModal = false"
      />

      <!-- Customer Delivery Consent & Confirmation Banner -->
      <div
        v-if="(order.status === 'out_for_delivery' || order.status === 'ready_for_delivery') && isCustomerOwner"
        class="p-6 rounded-2xl bg-gradient-to-r from-emerald-950/90 via-slate-900 to-emerald-950/90 border border-emerald-500/50 shadow-2xl space-y-4 animate-in fade-in"
      >
        <div class="flex items-start sm:items-center gap-4">
          <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-2xl shrink-0 animate-bounce">
            🧺
          </div>
          <div>
            <h3 class="text-base font-bold text-emerald-300">Clean Garments Delivery Confirmation Required</h3>
            <p class="text-xs text-gray-700 dark:text-slate-300 mt-0.5">
              Rider <strong class="text-white">{{ order.rider ? (order.rider.first_name + ' ' + order.rider.last_name) : 'assigned' }}</strong> is delivering your clean clothes. Please inspect your garments and confirm receipt below to complete the order.
            </p>
          </div>
        </div>

        <button
          @click="confirmDelivery"
          :disabled="confirmingDelivery"
          class="w-full py-3.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-400 text-slate-950 font-black text-sm shadow-xl shadow-emerald-500/20 hover:scale-[1.01] transition-transform flex items-center justify-center gap-2 disabled:opacity-60"
        >
          <svg v-if="confirmingDelivery" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          <span>{{ confirmingDelivery ? 'Confirming Receipt...' : '✅ Confirm Receipt of Garments & Complete Order' }}</span>
        </button>
      </div>

      <!-- Customer Rating & Review Component (Post-Completion) -->
      <div v-if="order.status === 'completed' && isCustomerOwner" class="p-6 rounded-2xl bg-gray-100 dark:bg-slate-800/80 border border-gray-200 dark:border-slate-700/80 shadow-2xl space-y-4">
        <!-- Existing Submitted Review View -->
        <div v-if="order.review" class="space-y-3">
          <div class="flex items-center justify-between border-b border-gray-200 dark:border-slate-700/60 pb-3">
            <div class="flex items-center gap-2">
              <span class="text-xl">⭐</span>
              <h3 class="text-sm font-bold text-gray-900 dark:text-slate-100">Your Submitted Experience Review</h3>
            </div>
            <span class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Feedback Received</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="bg-white dark:bg-slate-900/80 p-3.5 rounded-xl border border-gray-200 dark:border-slate-800 space-y-1">
              <span class="text-gray-500 dark:text-slate-400 block font-semibold text-[11px]">Laundry Shop Wash Quality:</span>
              <div class="text-amber-400 font-bold text-base">
                {{ '★'.repeat(order.review.shop_rating) }}{{ '☆'.repeat(5 - order.review.shop_rating) }} ({{ order.review.shop_rating }}/5)
              </div>
              <p v-if="order.review.shop_comment" class="text-gray-700 dark:text-slate-300 italic text-[11px]">"{{ order.review.shop_comment }}"</p>
            </div>

            <div v-if="order.rider" class="bg-white dark:bg-slate-900/80 p-3.5 rounded-xl border border-gray-200 dark:border-slate-800 space-y-1">
              <span class="text-gray-500 dark:text-slate-400 block font-semibold text-[11px]">Rider Delivery Speed:</span>
              <div class="text-amber-400 font-bold text-base">
                {{ '★'.repeat(order.review.rider_rating) }}{{ '☆'.repeat(5 - order.review.rider_rating) }} ({{ order.review.rider_rating }}/5)
              </div>
              <p v-if="order.review.rider_comment" class="text-gray-700 dark:text-slate-300 italic text-[11px]">"{{ order.review.rider_comment }}"</p>
            </div>
          </div>
        </div>

        <!-- Interactive Rating Submission Form -->
        <form v-else @submit.prevent="submitReview" class="space-y-4">
          <div class="flex items-center gap-3">
            <span class="text-2xl">⭐</span>
            <div>
              <h3 class="text-base font-bold text-gray-900 dark:text-slate-100">Rate Your Laundry & Delivery Experience</h3>
              <p class="text-xs text-gray-500 dark:text-slate-400">Help us maintain top washing quality and fast rider deliveries</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <!-- Shop Wash Rating -->
            <div class="bg-white dark:bg-slate-900/90 p-4 rounded-xl border border-gray-200 dark:border-slate-700/80 space-y-2">
              <label class="block font-bold text-gray-800 dark:text-slate-200 uppercase text-[11px]">Laundry Shop Washing ({{ order.shop?.name }}) *</label>
              <div class="flex items-center gap-1.5 text-2xl">
                <button
                  v-for="star in 5"
                  :key="'shop-' + star"
                  type="button"
                  @click="reviewForm.shop_rating = star"
                  class="transition-transform hover:scale-125 focus:outline-none"
                  :class="star <= reviewForm.shop_rating ? 'text-amber-400' : 'text-slate-700'"
                >
                  ★
                </button>
                <span class="text-xs text-gray-500 dark:text-slate-400 font-bold ml-2">({{ reviewForm.shop_rating }}/5 Stars)</span>
              </div>
              <input
                v-model="reviewForm.shop_comment"
                type="text"
                placeholder="Comment on washing quality / scent (optional)"
                class="w-full px-3 py-2 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 text-xs focus:border-amber-500"
              />
            </div>

            <!-- Rider Delivery Rating -->
            <div v-if="order.rider" class="bg-white dark:bg-slate-900/90 p-4 rounded-xl border border-gray-200 dark:border-slate-700/80 space-y-2">
              <label class="block font-bold text-gray-800 dark:text-slate-200 uppercase text-[11px]">Rider Delivery Speed ({{ order.rider?.first_name }}) *</label>
              <div class="flex items-center gap-1.5 text-2xl">
                <button
                  v-for="star in 5"
                  :key="'rider-' + star"
                  type="button"
                  @click="reviewForm.rider_rating = star"
                  class="transition-transform hover:scale-125 focus:outline-none"
                  :class="star <= reviewForm.rider_rating ? 'text-amber-400' : 'text-slate-700'"
                >
                  ★
                </button>
                <span class="text-xs text-gray-500 dark:text-slate-400 font-bold ml-2">({{ reviewForm.rider_rating }}/5 Stars)</span>
              </div>
              <input
                v-model="reviewForm.rider_comment"
                type="text"
                placeholder="Comment on rider politeness / speed (optional)"
                class="w-full px-3 py-2 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 text-xs focus:border-amber-500"
              />
            </div>
          </div>

          <button
            type="submit"
            :disabled="submittingReview"
            class="w-full py-3 rounded-xl bg-gradient-to-r from-amber-500 to-yellow-500 text-slate-950 font-bold text-xs shadow-lg hover:scale-[1.01] transition-transform flex items-center justify-center gap-2 disabled:opacity-60"
          >
            <span>{{ submittingReview ? 'Submitting Feedback...' : '⭐ Submit Rating & Feedback' }}</span>
          </button>
        </form>
      </div>

      <!-- Real-Time Visual Progress Stepper -->
      <div class="bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-8 shadow-xl space-y-6">
        <h2 class="text-base font-bold text-gray-900 dark:text-slate-100 uppercase tracking-wider">Garment Care Progress Stepper</h2>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <div
            v-for="(step, idx) in steps"
            :key="step.key"
            @click="isShopOwner ? updateStatus(step.key) : null"
            class="p-4 rounded-xl border flex flex-col items-center justify-center text-center gap-2 transition-all"
            :class="[
              idx <= currentStepIndex ? 'bg-sky-500/10 border-sky-500 text-sky-300' : 'bg-gray-50 dark:bg-slate-950/40 border-gray-200 dark:border-slate-800 text-gray-500 dark:text-slate-500 opacity-60',
              isShopOwner ? 'cursor-pointer hover:scale-105 hover:shadow-lg' : ''
            ]"
          >
            <span class="text-2xl">{{ step.icon }}</span>
            <span class="text-[11px] font-bold leading-tight">{{ step.title }}</span>
            <span v-if="idx <= currentStepIndex" class="text-[9px] font-mono font-bold text-emerald-400">✓ Done</span>
          </div>
        </div>
      </div>

      <!-- Rider Negotiation / Bids Section (For Delivery Orders) -->
      <div v-if="order.fulfillment_type === 'home_delivery'" class="bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-6 space-y-4 shadow-xl">
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-slate-700 pb-4">
          <div>
            <h3 class="text-base font-bold text-purple-300 flex items-center gap-2">
              <span>🤝 Rider Delivery Fee Offers & Negotiation</span>
            </h3>
            <p class="text-xs text-gray-500 dark:text-slate-400">Review price proposals from available riders and choose your preferred delivery partner</p>
          </div>

          <span v-if="order.rider" class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
            🛵 Rider Assigned
          </span>
        </div>

        <!-- Assigned Rider Selfie & Contact Card -->
        <div v-if="order.rider" class="bg-gray-50 dark:bg-slate-950 p-5 rounded-2xl border border-purple-500/30 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-lg">
          <div class="flex items-center gap-4">
            <div class="relative group cursor-pointer" @click="openRiderImageModal(order.rider)" title="Click to view full size selfie">
              <img
                :src="getRiderSelfieUrl(order.rider)"
                :alt="order.rider.first_name"
                class="w-16 h-16 rounded-2xl object-cover border-2 border-purple-500/60 shadow-xl shrink-0 group-hover:scale-105 transition-all group-hover:border-purple-400"
              />
              <div class="absolute inset-0 bg-black/30 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold">
                🔍
              </div>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <strong class="text-gray-900 dark:text-slate-100 text-sm block font-bold">{{ order.rider?.first_name }} {{ order.rider?.last_name }}</strong>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 uppercase">Verified Rider</span>
              </div>
              <p class="text-gray-500 dark:text-slate-400 text-xs mt-0.5">
                Vehicle: <strong class="text-gray-800 dark:text-slate-200">{{ order.rider?.rider_profile?.vehicle_type || 'Motorcycle' }}</strong>
                <span v-if="order.rider?.rider_profile?.vehicle_plate" class="font-mono text-purple-300"> ({{ order.rider?.rider_profile?.vehicle_plate }})</span>
              </p>
              <p class="text-purple-400 font-mono font-bold text-xs mt-1">📞 Phone: {{ order.rider?.phone || 'N/A' }}</p>
            </div>
          </div>

          <div class="text-right border-t sm:border-t-0 sm:border-l border-gray-200 dark:border-slate-800 pt-3 sm:pt-0 sm:pl-6 w-full sm:w-auto">
            <span class="text-[10px] text-gray-500 dark:text-slate-400 uppercase font-bold block">Agreed Delivery Fee:</span>
            <span class="text-emerald-400 font-mono font-bold text-lg">₦{{ Number(order.delivery_fee).toLocaleString() }}</span>
          </div>
        </div>

        <!-- Incoming Bids Cards with Rider Selfie -->
        <template v-else>
          <div v-if="!order.bids || order.bids.length === 0" class="bg-gray-50 dark:bg-slate-950 p-6 rounded-xl border border-gray-200 dark:border-slate-800 text-center space-y-2 text-xs text-gray-500 dark:text-slate-400">
            <span class="text-2xl block">💬</span>
            <p>No rider price proposals yet. Nearby riders are currently reviewing your dispatch request!</p>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="bid in order.bids"
              :key="bid.id"
              class="bg-gray-50 dark:bg-slate-950 p-4 rounded-xl border border-gray-200 dark:border-slate-800 hover:border-purple-500/40 space-y-3 transition-all text-xs shadow-lg"
            >
              <div class="flex items-center justify-between border-b border-gray-200 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-3">
                  <div class="relative group cursor-pointer" @click="openRiderImageModal(bid.rider)" title="Click to view full size selfie">
                    <img
                      :src="getRiderSelfieUrl(bid.rider)"
                      :alt="bid.rider?.first_name"
                      class="w-12 h-12 rounded-xl object-cover border border-purple-500/40 shrink-0 group-hover:scale-105 transition-all group-hover:border-purple-400"
                    />
                    <div class="absolute inset-0 bg-black/30 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-[10px]">
                      🔍
                    </div>
                  </div>
                  <div>
                    <strong class="text-gray-800 dark:text-slate-200 block font-bold text-xs">{{ bid.rider?.first_name }} {{ bid.rider?.last_name }}</strong>
                    <span class="text-[10px] text-gray-500 dark:text-slate-400 capitalize">{{ bid.rider?.rider_profile?.vehicle_type || 'Delivery Rider' }}</span>
                  </div>
                </div>

                <div class="text-right">
                  <span class="text-base font-black font-mono text-emerald-400 block">₦{{ Number(bid.amount).toLocaleString() }}</span>
                  <span
                    class="text-[9px] uppercase font-bold px-2 py-0.5 rounded border"
                    :class="bid.status === 'accepted' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : bid.status === 'rejected' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : 'bg-amber-500/10 text-amber-400 border-amber-500/20'"
                  >
                    {{ bid.status }}
                  </span>
                </div>
              </div>

              <p v-if="bid.note" class="text-gray-700 dark:text-slate-300 italic text-[11px]">"{{ bid.note }}"</p>

              <div v-if="bid.status === 'pending'" class="flex items-center gap-2 pt-1">
                <button
                  @click="acceptBid(bid.id)"
                  :disabled="acceptingBidId === bid.id"
                  class="flex-1 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 text-slate-950 font-bold text-xs shadow-md hover:scale-105 transition-transform flex items-center justify-center gap-1 disabled:opacity-60"
                >
                  <span>✓ Agree ₦{{ Number(bid.amount).toLocaleString() }}</span>
                </button>

                <button
                  @click="rejectBid(bid.id)"
                  class="px-3 py-2 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 font-bold text-xs hover:bg-rose-500/20 transition-colors"
                >
                  ✕ Decline
                </button>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- Order Items & Bill Breakdown -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Items List -->
        <div class="md:col-span-2 bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-6 space-y-4 shadow-xl">
          <h3 class="text-sm font-bold text-gray-800 dark:text-slate-200 uppercase tracking-wider">Garments & Treatment Services</h3>

          <div class="space-y-3">
            <div
              v-for="item in order.items"
              :key="item.id"
              class="p-4 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 flex items-center justify-between text-xs"
            >
              <div>
                <span class="font-bold text-gray-900 dark:text-slate-100 block">{{ item.category_name }}</span>
                <span class="text-gray-500 dark:text-slate-400 block mt-0.5">{{ item.service_name }} — ₦{{ Number(item.unit_price).toLocaleString() }} each</span>
              </div>
              <div class="text-right font-mono">
                <span class="text-gray-500 dark:text-slate-400 block font-bold">Qty: {{ item.quantity }}</span>
                <span class="text-emerald-400 font-bold text-sm">₦{{ Number(item.subtotal).toLocaleString() }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Shop & Summary Side Card -->
        <div class="bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-6 space-y-6 shadow-xl text-xs">
          <!-- Shop Details -->
          <div class="space-y-2 border-b border-gray-200 dark:border-slate-700 pb-4">
            <h4 class="font-bold text-sky-400 uppercase tracking-wider">Dry Cleaner</h4>
            <p class="font-bold text-gray-900 dark:text-slate-100 text-sm">{{ order.shop?.name }}</p>
            <p class="text-gray-500 dark:text-slate-400">📍 {{ order.shop?.address }}</p>
            <p class="text-gray-500 dark:text-slate-400 font-mono">📞 {{ order.shop?.phone }}</p>
          </div>

          <!-- Fulfillment Details -->
          <div class="space-y-2 border-b border-gray-200 dark:border-slate-700 pb-4">
            <h4 class="font-bold text-emerald-400 uppercase tracking-wider">Fulfillment Method</h4>
            <p class="font-bold text-gray-900 dark:text-slate-100">
              {{ order.fulfillment_type === 'home_delivery' ? '🚚 Doorstep Home Delivery' : '🏬 In-Store Self Pickup' }}
            </p>
            <p class="text-gray-500 dark:text-slate-400">{{ order.delivery_address || 'Shop Location' }}</p>
          </div>

          <!-- Total Summary -->
          <div class="space-y-2 font-mono">
            <div class="flex justify-between text-gray-500 dark:text-slate-400">
              <span>Subtotal:</span>
              <span>₦{{ Number(order.subtotal).toLocaleString() }}</span>
            </div>
            <div class="flex justify-between text-gray-500 dark:text-slate-400">
              <span>Delivery Charge:</span>
              <span>₦{{ Number(order.delivery_fee).toLocaleString() }}</span>
            </div>
            <hr class="border-gray-200 dark:border-slate-700" />
            <div class="flex justify-between text-gray-900 dark:text-slate-100 font-bold text-sm">
              <span>Total Paid:</span>
              <span class="text-emerald-400">₦{{ Number(order.total_amount).toLocaleString() }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Super Admin Audit Trail & Conflict Resolution Log -->
      <div v-if="isSuperAdmin && order.status_logs && order.status_logs.length > 0" class="bg-gray-100 dark:bg-slate-800/60 border border-purple-500/30 rounded-2xl p-6 space-y-4 shadow-xl">
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-slate-700/60 pb-3">
          <div class="flex items-center gap-3">
            <span class="text-2xl">📜</span>
            <div>
              <h3 class="text-sm font-bold text-purple-300">Order Audit Trail & Conflict Resolution Log</h3>
              <p class="text-[11px] text-gray-500 dark:text-slate-400">Timestamped record of all state transitions visible to Super Admins</p>
            </div>
          </div>
          <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30 uppercase">Super Admin Only</span>
        </div>

        <div class="space-y-3">
          <div
            v-for="log in order.status_logs"
            :key="log.id"
            class="p-4 rounded-xl bg-white dark:bg-slate-900/80 border border-gray-200 dark:border-slate-700/60 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3"
          >
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <strong class="text-gray-900 dark:text-slate-100 font-bold text-xs">{{ log.user_name || 'System' }}</strong>
                <span class="px-2 py-0.5 rounded-full text-[10px] uppercase font-bold bg-gray-100 dark:bg-slate-800 text-purple-400 border border-purple-500/20">{{ log.user_role || 'system' }}</span>
              </div>
              <p class="text-gray-700 dark:text-slate-300 text-[11px]">
                Status changed: <span class="font-mono font-bold text-amber-400">{{ log.from_status || 'initial' }}</span> ➔ <span class="font-mono font-bold text-emerald-400">{{ log.to_status }}</span>
              </p>
              <p v-if="log.notes" class="text-gray-500 dark:text-slate-400 text-[11px] italic">"{{ log.notes }}"</p>
            </div>
            <span class="text-[10px] font-mono text-gray-500 dark:text-slate-400 shrink-0 bg-gray-50 dark:bg-slate-950 px-2.5 py-1 rounded-lg border border-gray-200 dark:border-slate-800">{{ new Date(log.created_at).toLocaleString() }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Full-Size Rider Selfie Lightbox Modal -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="selectedRiderImage"
        @click.self="closeRiderImageModal"
        class="fixed inset-0 z-[99999] flex items-center justify-center p-4 bg-gray-50 dark:bg-slate-950/85 backdrop-blur-md"
      >
        <div class="relative bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700/80 rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-4 text-center overflow-hidden">
          <!-- Modal Header & Close Button -->
          <div class="flex items-center justify-between border-b border-gray-200 dark:border-slate-800 pb-3">
            <div class="text-left">
              <h3 class="text-base font-bold text-gray-900 dark:text-slate-100">{{ selectedRiderImage.name }}</h3>
              <span class="text-xs text-purple-400 font-medium">{{ selectedRiderImage.vehicle }}</span>
            </div>
            <button
              @click="closeRiderImageModal"
              class="w-9 h-9 rounded-full bg-gray-100 dark:bg-slate-800 hover:bg-slate-700 text-gray-700 dark:text-slate-300 hover:text-white flex items-center justify-center font-bold text-base transition-colors"
            >
              ✕
            </button>
          </div>

          <!-- Full-Size Image Container -->
          <div class="rounded-2xl overflow-hidden bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 flex items-center justify-center max-h-[70vh] p-2">
            <img
              :src="selectedRiderImage.url"
              :alt="selectedRiderImage.name"
              class="w-full max-h-[70vh] object-contain rounded-2xl shadow-xl"
            />
          </div>

          <p class="text-[11px] text-gray-500 dark:text-slate-400">
            Verified LaundryHub Rider Identity Verification
          </p>
        </div>
      </div>
    </Transition>
  </AppLayout>
</template>
