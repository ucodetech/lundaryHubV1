<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps<{
  dispute: any;
}>();

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);
const isAdminOrSupport = computed(() => ['super_admin', 'support'].includes(currentUser.value?.role?.value || currentUser.value?.role));

const selectedPhoto = ref<string | null>(null);

const replyForm = useForm({
  message: '',
  attachments: [] as File[],
});

const resolveForm = useForm({
  status: 'resolved_refund',
  resolution_notes: '',
  refund_amount: Number(props.dispute.order?.total_amount || 0),
});

const submitReply = () => {
  replyForm.post(`/disputes/${props.dispute.id}/reply`, {
    preserveScroll: true,
    onSuccess: () => {
      replyForm.reset();
    },
  });
};

const executeResolution = () => {
  resolveForm.post(`/admin/disputes/${props.dispute.id}/resolve`, {
    preserveScroll: true,
  });
};
</script>

<template>
  <AppLayout>
    <div class="max-w-5xl mx-auto space-y-6">
      <!-- Breadcrumb & Top Bar -->
      <div class="flex items-center justify-between">
        <Link
          :href="isAdminOrSupport ? '/admin/disputes' : '/disputes'"
          class="text-xs text-sky-400 font-bold hover:underline flex items-center gap-1"
        >
          ← Back to Disputes Directory
        </Link>
        <Badge :status="dispute.status" />
      </div>

      <!-- Ticket Header Card -->
      <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-700/60 pb-4">
          <div class="space-y-1">
            <div class="flex items-center gap-3">
              <span class="font-mono font-bold text-amber-400 text-lg">#{{ dispute.dispute_number }}</span>
              <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-slate-900 border border-slate-700 text-slate-300">
                Reason: {{ dispute.reason?.replace('_', ' ') }}
              </span>
            </div>
            <h1 class="text-xl font-bold text-slate-100">{{ dispute.subject }}</h1>
          </div>

          <div class="text-xs text-slate-400 space-y-0.5 text-right font-mono">
            <div>Order: <Link :href="`/orders/${dispute.order?.order_number}`" class="text-sky-400 font-bold hover:underline">#{{ dispute.order?.order_number }}</Link></div>
            <div>Shop: <strong class="text-slate-200">{{ dispute.order?.shop?.name }}</strong></div>
            <div>Filed: {{ new Date(dispute.created_at).toLocaleString() }}</div>
          </div>
        </div>

        <!-- Reporter & Description -->
        <div class="space-y-3">
          <div class="text-xs text-slate-300">
            <strong class="text-slate-100">Reporter:</strong> {{ dispute.reporter?.first_name }} {{ dispute.reporter?.last_name }} ({{ dispute.reporter?.email }} • {{ dispute.reporter?.phone }})
          </div>

          <div class="p-4 rounded-xl bg-slate-950/80 border border-slate-800 text-xs text-slate-200 leading-relaxed">
            <p class="font-semibold text-slate-400 uppercase text-[10px] mb-1">Issue Description:</p>
            {{ dispute.description }}
          </div>
        </div>

        <!-- Photo Evidence Gallery -->
        <div v-if="dispute.evidence_photos && dispute.evidence_photos.length > 0" class="space-y-2 pt-2">
          <span class="text-[11px] font-bold uppercase text-slate-400 block">Photo Evidence Attachments ({{ dispute.evidence_photos.length }}):</span>
          <div class="flex flex-wrap gap-3">
            <img
              v-for="(photo, idx) in dispute.evidence_photos"
              :key="idx"
              :src="photo.startsWith('http') ? photo : `/storage/${photo}`"
              @click="selectedPhoto = (photo.startsWith('http') ? photo : `/storage/${photo}`)"
              class="w-24 h-24 object-cover rounded-xl border border-slate-700 hover:scale-105 transition-transform cursor-pointer shadow-md"
            />
          </div>
        </div>
      </div>

      <!-- Admin / Support Resolution Card -->
      <div v-if="isAdminOrSupport && dispute.status !== 'closed'" class="bg-gradient-to-r from-purple-900/40 via-indigo-900/40 to-slate-900 border border-purple-500/30 rounded-2xl p-6 shadow-2xl space-y-4">
        <div class="flex items-center gap-2">
          <span class="text-xl">⚖️</span>
          <div>
            <h3 class="text-base font-bold text-slate-100">Execute Ticket Resolution</h3>
            <p class="text-xs text-slate-400">Issue full/partial refund credit directly to reporter's bonus wallet or dismiss ticket</p>
          </div>
        </div>

        <form @submit.prevent="executeResolution" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div>
              <label class="block font-bold text-slate-300 uppercase mb-1">Resolution Decision *</label>
              <select v-model="resolveForm.status" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-100 font-bold">
                <option value="resolved_refund">💰 Approve Refund Credit to Bonus Wallet</option>
                <option value="resolved_compensated">🛡️ Issue Compensation Credit</option>
                <option value="resolved_rejected">❌ Dismiss / Reject Dispute Ticket</option>
                <option value="closed">🔒 Close Ticket Without Action</option>
              </select>
            </div>

            <div v-if="['resolved_refund', 'resolved_compensated'].includes(resolveForm.status)">
              <label class="block font-bold text-slate-300 uppercase mb-1">Refund Amount (₦) *</label>
              <input
                v-model="resolveForm.refund_amount"
                type="number"
                step="0.01"
                required
                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-emerald-400 font-mono font-bold text-sm"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Resolution Explanation Notes *</label>
            <textarea
              v-model="resolveForm.resolution_notes"
              rows="2"
              required
              placeholder="Explain the audit findings and settlement details..."
              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-100 text-xs"
            ></textarea>
          </div>

          <button
            type="submit"
            :disabled="resolveForm.processing"
            class="w-full py-3 rounded-xl bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-bold text-xs shadow-lg hover:scale-[1.01] transition-all flex items-center justify-center gap-2 disabled:opacity-60"
          >
            <span>{{ resolveForm.processing ? 'Executing Settlement...' : '⚖️ Execute Settlement & Close Dispute Ticket' }}</span>
          </button>
        </form>
      </div>

      <!-- Resolution Summary (If Already Resolved) -->
      <div v-if="dispute.resolution_notes" class="bg-emerald-950/40 border border-emerald-500/30 rounded-2xl p-6 shadow-xl space-y-2">
        <div class="flex items-center gap-2 text-emerald-400 font-bold text-sm">
          <span>✅ Ticket Resolution Notes (Resolved by {{ dispute.resolved_by?.first_name || 'Admin' }})</span>
        </div>
        <p class="text-xs text-slate-200 italic">"{{ dispute.resolution_notes }}"</p>
      </div>

      <!-- Live Ticket Chat Thread -->
      <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-6">
        <h3 class="text-base font-bold text-slate-100 flex items-center gap-2">
          <span>💬 Ticket Conversation History</span>
        </h3>

        <!-- Messages List -->
        <div class="space-y-4">
          <div
            v-for="msg in dispute.messages"
            :key="msg.id"
            class="p-4 rounded-xl border space-y-2 transition-all"
            :class="msg.user_id === currentUser?.id ? 'bg-sky-500/10 border-sky-500/30 ml-6' : 'bg-slate-900/90 border-slate-800 mr-6'"
          >
            <div class="flex items-center justify-between text-xs border-b border-slate-800 pb-2">
              <span class="font-bold text-slate-200">
                {{ msg.user?.first_name }} {{ msg.user?.last_name }}
                <span class="text-[10px] text-slate-400 font-mono">({{ msg.user?.role?.value || msg.user?.role }})</span>
              </span>
              <span class="text-[10px] text-slate-400 font-mono">{{ new Date(msg.created_at).toLocaleString() }}</span>
            </div>

            <p class="text-xs text-slate-300 whitespace-pre-line leading-relaxed">{{ msg.message }}</p>
          </div>

          <div v-if="!dispute.messages || dispute.messages.length === 0" class="py-6 text-center text-slate-400 text-xs italic">
            No replies on this dispute ticket yet.
          </div>
        </div>

        <!-- Reply Input Form -->
        <form @submit.prevent="submitReply" class="space-y-3 pt-4 border-t border-slate-700/60">
          <textarea
            v-model="replyForm.message"
            rows="3"
            required
            placeholder="Type your message reply here..."
            class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-700 text-slate-100 text-xs focus:border-sky-500"
          ></textarea>

          <div class="flex items-center justify-between">
            <span class="text-[11px] text-slate-400">Support monitors all ticket responses</span>
            <button
              type="submit"
              :disabled="replyForm.processing"
              class="px-5 py-2.5 rounded-xl bg-sky-500 text-slate-950 font-bold text-xs shadow-lg hover:scale-105 transition-all disabled:opacity-60"
            >
              <span>{{ replyForm.processing ? 'Sending...' : '✉️ Send Message' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Full Photo Modal -->
    <div v-if="selectedPhoto" class="fixed inset-0 z-50 bg-slate-950/90 backdrop-blur-md flex items-center justify-center p-4" @click="selectedPhoto = null">
      <img :src="selectedPhoto" class="max-w-full max-h-[85vh] rounded-2xl border border-slate-700 shadow-2xl" />
    </div>
  </AppLayout>
</template>
