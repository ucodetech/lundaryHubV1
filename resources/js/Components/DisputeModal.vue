<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
  order: any;
  show: boolean;
}>();

const emit = defineEmits(['close']);

const form = useForm({
  order_id: props.order.id,
  against_type: 'shop',
  reason: 'damaged_garment',
  subject: '',
  description: '',
  photos: [] as File[],
});

const previewPhotos = ref<string[]>([]);

const handlePhotoSelect = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (!target.files) return;

  const files = Array.from(target.files);
  form.photos = files;

  previewPhotos.value = [];
  files.forEach(file => {
    const reader = new FileReader();
    reader.onload = (e) => {
      if (e.target?.result) {
        previewPhotos.value.push(e.target.result as string);
      }
    };
    reader.readAsDataURL(file);
  });
};

const submitDispute = () => {
  form.post('/disputes', {
    preserveScroll: true,
    onSuccess: () => {
      emit('close');
      form.reset();
      previewPhotos.value = [];
    },
  });
};
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 bg-gray-50 dark:bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700/80 rounded-2xl w-full max-w-lg p-6 space-y-5 shadow-2xl my-8">
      <div class="flex items-center justify-between border-b border-gray-200 dark:border-slate-800 pb-3">
        <div class="flex items-center gap-2">
          <span class="text-xl">⚠️</span>
          <div>
            <h2 class="text-base font-bold text-gray-900 dark:text-slate-100">Report Issue / File Order Dispute</h2>
            <p class="text-[11px] text-gray-500 dark:text-slate-400">Order #{{ order.order_number }}</p>
          </div>
        </div>
        <button @click="emit('close')" class="text-gray-500 dark:text-slate-400 hover:text-white text-xl">✕</button>
      </div>

      <form @submit.prevent="submitDispute" class="space-y-4">
        <!-- Target Selection -->
        <div class="grid grid-cols-3 gap-2">
          <div>
            <label class="block text-[10px] font-bold text-gray-500 dark:text-slate-400 uppercase mb-1">Target</label>
            <select v-model="form.against_type" class="w-full px-3 py-2 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-700 text-gray-800 dark:text-slate-200 text-xs">
              <option value="shop">🏪 Laundry Shop</option>
              <option value="rider">🛵 Rider</option>
              <option value="platform">💳 Platform</option>
            </select>
          </div>

          <div class="col-span-2">
            <label class="block text-[10px] font-bold text-gray-500 dark:text-slate-400 uppercase mb-1">Dispute Reason *</label>
            <select v-model="form.reason" class="w-full px-3 py-2 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-700 text-gray-800 dark:text-slate-200 text-xs">
              <option value="damaged_garment">👔 Damaged / Stained Garment</option>
              <option value="missing_item">❓ Missing Garment Item</option>
              <option value="late_delivery">⏳ Unreasonable Delay</option>
              <option value="overcharge">💸 Overcharge / Payment Issue</option>
              <option value="other">💬 Other Dispute Reason</option>
            </select>
          </div>
        </div>

        <!-- Subject -->
        <div>
          <label class="block text-[10px] font-bold text-gray-500 dark:text-slate-400 uppercase mb-1">Subject Headline *</label>
          <input
            v-model="form.subject"
            type="text"
            required
            placeholder="Brief summary of the issue..."
            class="w-full px-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 text-xs focus:border-amber-500"
          />
          <span v-if="form.errors.subject" class="text-[11px] text-rose-400 mt-1 block">{{ form.errors.subject }}</span>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-[10px] font-bold text-gray-500 dark:text-slate-400 uppercase mb-1">Detailed Explanation *</label>
          <textarea
            v-model="form.description"
            rows="3"
            required
            placeholder="Explain what went wrong in detail so support can investigate..."
            class="w-full px-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 text-xs focus:border-amber-500"
          ></textarea>
          <span v-if="form.errors.description" class="text-[11px] text-rose-400 mt-1 block">{{ form.errors.description }}</span>
        </div>

        <!-- Photo Evidence Uploader -->
        <div>
          <label class="block text-[10px] font-bold text-gray-500 dark:text-slate-400 uppercase mb-1">
            Photo Evidence <span class="text-gray-500 dark:text-slate-500 font-normal lowercase">(optional, max 3 photos)</span>
          </label>
          <input
            type="file"
            multiple
            accept="image/*"
            @change="handlePhotoSelect"
            class="w-full text-xs text-gray-500 dark:text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-800 file:text-slate-200 hover:file:bg-slate-700"
          />

          <!-- Photo Previews -->
          <div v-if="previewPhotos.length > 0" class="flex items-center gap-2 mt-2">
            <img
              v-for="(img, idx) in previewPhotos"
              :key="idx"
              :src="img"
              class="w-14 h-14 object-cover rounded-lg border border-gray-200 dark:border-slate-700"
            />
          </div>
        </div>

        <!-- Submit Actions -->
        <div class="pt-3 border-t border-gray-200 dark:border-slate-800 flex items-center justify-end gap-3">
          <button
            type="button"
            @click="emit('close')"
            class="px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-bold text-xs hover:text-white"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-rose-500 text-slate-950 font-bold text-xs shadow-lg hover:scale-105 transition-all flex items-center gap-2 disabled:opacity-60"
          >
            <svg v-if="form.processing" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span>{{ form.processing ? 'Submitting Dispute...' : '⚠️ File Dispute Ticket' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
