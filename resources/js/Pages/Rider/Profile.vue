<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, computed, onUnmounted } from 'vue';

const props = defineProps<{
  profile: any;
}>();

const detailsForm = useForm({
  vehicle_type: props.profile?.vehicle_type ?? 'motorcycle',
  vehicle_plate: props.profile?.vehicle_plate ?? '',
  license_number: props.profile?.license_number ?? '',
});

const fileInputRef = ref<HTMLInputElement | null>(null);

const kycForm = useForm({
  document_type: 'national_id',
  file: null as File | null,
});

// Camera WebRTC Selfie Verification
const showCameraModal = ref(false);
const videoRef = ref<HTMLVideoElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);
const mediaStream = ref<MediaStream | null>(null);
const capturedImage = ref<string | null>(null);
const isCameraActive = ref(false);
const cameraError = ref<string | null>(null);

const kycDocs = computed(() => {
  return props.profile?.kyc_documents || props.profile?.kycDocuments || [];
});

const isVerified = computed(() => {
  return props.profile?.kyc_status === 'approved' || props.profile?.is_verified;
});

const updateDetails = () => {
  detailsForm.put('/rider/profile', {
    preserveScroll: true,
  });
};

const uploadDocument = () => {
  kycForm.post('/rider/kyc', {
    preserveScroll: true,
    onSuccess: () => {
      kycForm.reset('file');
      if (fileInputRef.value) {
        fileInputRef.value.value = '';
      }
    },
  });
};

// WebRTC Live Camera Handler
const startCamera = async () => {
  showCameraModal.value = true;
  cameraError.value = null;
  capturedImage.value = null;

  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
      audio: false,
    });
    mediaStream.value = stream;
    isCameraActive.value = true;

    if (videoRef.value) {
      videoRef.value.srcObject = stream;
    }
  } catch (err: any) {
    console.error('Camera access error:', err);
    cameraError.value = 'Could not access camera. Please enable camera permissions or upload a selfie photo.';
    isCameraActive.value = false;
  }
};

const stopCamera = () => {
  if (mediaStream.value) {
    mediaStream.value.getTracks().forEach((track) => track.stop());
    mediaStream.value = null;
  }
  isCameraActive.value = false;
  showCameraModal.value = false;
};

const captureSnapshot = () => {
  if (!videoRef.value || !canvasRef.value) return;

  const video = videoRef.value;
  const canvas = canvasRef.value;
  canvas.width = video.videoWidth || 640;
  canvas.height = video.videoHeight || 480;

  const ctx = canvas.getContext('2d');
  if (ctx) {
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    capturedImage.value = canvas.toDataURL('image/jpeg', 0.9);
  }
};

const retakePhoto = () => {
  capturedImage.value = null;
};

const submitCapturedSelfie = () => {
  if (!capturedImage.value) return;

  // Convert DataURL to File object
  fetch(capturedImage.value)
    .then((res) => res.blob())
    .then((blob) => {
      const file = new File([blob], 'rider_live_selfie.jpg', { type: 'image/jpeg' });
      const selfieForm = useForm({
        document_type: 'selfie',
        file: file,
      });

      selfieForm.post('/rider/kyc', {
        preserveScroll: true,
        onSuccess: () => {
          stopCamera();
        },
      });
    });
};

onUnmounted(() => {
  stopCamera();
});
</script>

<template>
  <AppLayout>
    <div class="max-w-3xl mx-auto space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Rider Profile & KYC</h1>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Vehicle details, live face selfie verification, and identity documents</p>
      </div>

      <!-- Verified Banner -->
      <div v-if="isVerified" class="bg-gradient-to-r from-emerald-950/80 to-slate-900 border border-emerald-500/40 rounded-2xl p-6 flex items-center justify-between shadow-xl">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center text-2xl">
            ✅
          </div>
          <div>
            <h2 class="text-base font-bold text-emerald-400">Account Verified & Active</h2>
            <p class="text-xs text-gray-700 dark:text-slate-300 mt-0.5">Your rider profile and identity documents have been audited and approved by Super Admin.</p>
          </div>
        </div>
      </div>

      <!-- Pending / Verification Status Header -->
      <div v-else class="bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <span class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase">Verification Audit Status</span>
          <div class="mt-1">
            <Badge :status="profile?.kyc_status ?? 'pending'" />
          </div>
        </div>
        <p class="text-xs text-gray-500 dark:text-slate-400 max-w-xs sm:text-right">
          Upload NIN, Driver's License, and capture a Live Face Selfie for account verification.
        </p>
      </div>

      <!-- Prepopulated Uploaded Documents Grid -->
      <div v-if="kycDocs.length > 0" class="bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-6 space-y-4">
        <h3 class="font-bold text-gray-800 dark:text-slate-200 text-sm flex items-center gap-2">
          <span>📋 Submitted Verification Documents</span>
          <span class="px-2 py-0.5 rounded-full text-[10px] bg-sky-500/10 text-sky-400 border border-sky-500/20 font-mono">{{ kycDocs.length }}</span>
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
          <div
            v-for="doc in kycDocs"
            :key="doc.id"
            class="bg-white dark:bg-slate-900/80 border border-gray-200 dark:border-slate-800 rounded-xl p-3 space-y-2 shadow-md flex flex-col justify-between"
          >
            <!-- Media Preview -->
            <div class="relative aspect-video rounded-lg overflow-hidden bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-800 flex items-center justify-center">
              <img
                v-if="doc.file_path && (doc.file_path.includes('.jpg') || doc.file_path.includes('.jpeg') || doc.file_path.includes('.png') || doc.file_path.includes('cloudinary.com'))"
                :src="doc.file_path"
                :alt="doc.document_type"
                class="w-full h-full object-cover"
              />
              <div v-else class="text-gray-500 dark:text-slate-400 text-xs flex flex-col items-center gap-1">
                <span class="text-xl">📄</span>
                <span>PDF Document</span>
              </div>
            </div>

            <!-- Doc Info -->
            <div class="flex items-center justify-between text-xs pt-1">
              <span class="font-bold text-gray-800 dark:text-slate-200 capitalize flex items-center gap-1">
                <span v-if="doc.document_type === 'selfie'">🤳</span>
                <span>{{ doc.document_type?.replace('_', ' ') }}</span>
              </span>
              <span class="px-2 py-0.5 rounded text-[10px] font-semibold" :class="doc.status === 'approved' ? 'bg-emerald-500/10 text-emerald-400' : doc.status === 'rejected' ? 'bg-rose-500/10 text-rose-400' : 'bg-amber-500/10 text-amber-400'">
                {{ doc.status }}
              </span>
            </div>

            <a
              :href="doc.file_path"
              target="_blank"
              class="block text-center text-[11px] text-sky-400 hover:underline pt-1"
            >
              View Full Resolution ↗
            </a>
          </div>
        </div>
      </div>

      <!-- Vehicle Details Form -->
      <form @submit.prevent="updateDetails" class="bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-6 space-y-4">
        <h3 class="font-bold text-gray-800 dark:text-slate-200 text-sm">Vehicle Information</h3>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-1">Vehicle Type</label>
            <select
              v-model="detailsForm.vehicle_type"
              class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500"
            >
              <option value="bicycle">Bicycle</option>
              <option value="motorcycle">Motorcycle / Bike</option>
              <option value="tricycle">Tricycle (Keke)</option>
              <option value="car">Car / Sedan</option>
              <option value="van">Delivery Van</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-1">Plate Number</label>
            <input
              v-model="detailsForm.vehicle_plate"
              type="text"
              placeholder="LND-452-XY"
              class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-1">License Number</label>
            <input
              v-model="detailsForm.license_number"
              type="text"
              placeholder="DL-98765432"
              class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500"
            />
          </div>
        </div>

        <button
          type="submit"
          :disabled="detailsForm.processing"
          class="px-6 py-2.5 rounded-xl bg-sky-500 text-slate-950 font-bold text-xs shadow-lg shadow-sky-500/20 hover:scale-105 transition-transform"
        >
          Save Vehicle Details
        </button>
      </form>

      <!-- Live Face Verification & KYC Document Uploads (Only if not yet verified) -->
      <div v-if="!isVerified" class="bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-6 space-y-6">
        <div>
          <h3 class="font-bold text-gray-800 dark:text-slate-200 text-sm">Rider Identity & Face Verification</h3>
          <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Capture a live face selfie and upload official identity documents.</p>
        </div>

        <!-- Live Selfie Trigger Banner -->
        <div class="bg-gradient-to-r from-purple-900/40 to-slate-900 border border-purple-500/30 rounded-2xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-purple-500/20 border border-purple-500/30 flex items-center justify-center text-xl">
              🤳
            </div>
            <div>
              <h4 class="font-bold text-purple-300 text-xs">Live Face Verification Selfie</h4>
              <p class="text-[11px] text-gray-500 dark:text-slate-400">Position your face in the camera oval frame for real-time verification.</p>
            </div>
          </div>

          <button
            type="button"
            @click="startCamera"
            class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-bold text-xs shadow-lg shadow-purple-500/20 hover:scale-105 transition-transform shrink-0 flex items-center gap-1.5"
          >
            <span>📸</span>
            <span>Take Live Face Selfie</span>
          </button>
        </div>

        <!-- KYC Document Upload Form -->
        <form @submit.prevent="uploadDocument" class="space-y-4 pt-2 border-t border-gray-200 dark:border-slate-700/60">
          <h4 class="font-bold text-gray-700 dark:text-slate-300 text-xs uppercase">Upload Document File</h4>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-1">Document Type *</label>
              <select
                v-model="kycForm.document_type"
                class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500"
              >
                <option value="national_id">National ID / NIN Slip</option>
                <option value="drivers_license">Driver's License</option>
                <option value="passport">Passport Photograph</option>
                <option value="selfie">Live Selfie Upload</option>
                <option value="guarantor">Guarantor Form</option>
              </select>
              <span v-if="kycForm.errors.document_type" class="text-xs text-rose-400 mt-1 block">{{ kycForm.errors.document_type }}</span>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-1">Select File (JPG, PNG, PDF) *</label>
              <input
                ref="fileInputRef"
                type="file"
                @change="(e: any) => kycForm.file = e.target.files[0]"
                required
                class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 text-xs"
              />
              <span v-if="kycForm.errors.file" class="text-xs text-rose-400 mt-1 block">{{ kycForm.errors.file }}</span>
            </div>
          </div>

          <button
            type="submit"
            :disabled="kycForm.processing"
            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-xs shadow-lg shadow-sky-500/20 disabled:opacity-50 flex items-center gap-2"
          >
            <span v-if="kycForm.processing" class="animate-spin text-sm">⏳</span>
            <span>{{ kycForm.processing ? 'Uploading to Cloudinary...' : 'Upload Verification Document' }}</span>
          </button>
        </form>
      </div>

      <!-- Live WebRTC Camera Modal -->
      <div v-if="showCameraModal" class="fixed inset-0 bg-gray-50 dark:bg-slate-950/90 backdrop-blur-md z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-6 w-full max-w-md space-y-4 shadow-2xl relative">
          <div class="flex items-center justify-between">
            <h3 class="text-base font-bold text-gray-900 dark:text-slate-100 flex items-center gap-2">
              <span>🤳</span>
              <span>Live Face Verification Camera</span>
            </h3>
            <button @click="stopCamera" class="text-gray-500 dark:text-slate-400 hover:text-white text-lg">✕</button>
          </div>

          <!-- Camera Stream / Snapshot Preview -->
          <div class="relative w-full aspect-square bg-gray-50 dark:bg-slate-950 rounded-2xl border-2 border-dashed border-gray-200 dark:border-slate-800 overflow-hidden flex items-center justify-center">
            <!-- Canvas (hidden) -->
            <canvas ref="canvasRef" class="hidden"></canvas>

            <!-- Error State -->
            <div v-if="cameraError" class="p-6 text-center text-xs text-rose-400 space-y-2">
              <p>⚠️ {{ cameraError }}</p>
              <button @click="stopCamera" class="px-4 py-2 rounded-xl bg-gray-100 dark:bg-slate-800 text-gray-800 dark:text-slate-200">Close Camera</button>
            </div>

            <!-- Live Video Stream with Face Positioning Oval Guide -->
            <div v-else-if="!capturedImage" class="relative w-full h-full">
              <video
                ref="videoRef"
                autoplay
                playsinline
                class="w-full h-full object-cover transform -scale-x-100"
              ></video>

              <!-- Glowing Face Oval Overlay -->
              <div class="absolute inset-0 border-4 border-dashed border-cyan-400/70 rounded-full m-8 pointer-events-none animate-pulse flex items-center justify-center">
                <span class="text-[11px] font-bold text-cyan-300 bg-gray-50 dark:bg-slate-950/80 px-3 py-1 rounded-full shadow-lg">
                  Position Face Here
                </span>
              </div>
            </div>

            <!-- Captured Image Preview -->
            <div v-else class="relative w-full h-full">
              <img :src="capturedImage" alt="Captured Selfie" class="w-full h-full object-cover" />
              <div class="absolute bottom-3 left-3 right-3 bg-gray-50 dark:bg-slate-950/80 text-emerald-400 text-[11px] font-bold py-1.5 px-3 rounded-xl text-center backdrop-blur-sm border border-emerald-500/30">
                ✓ Face Snapshot Captured
              </div>
            </div>
          </div>

          <!-- Controls -->
          <div class="flex items-center justify-between gap-3 pt-2">
            <button
              type="button"
              @click="stopCamera"
              class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-500 dark:text-slate-400 hover:text-slate-200"
            >
              Cancel
            </button>

            <button
              v-if="!capturedImage && isCameraActive"
              type="button"
              @click="captureSnapshot"
              class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-bold text-xs shadow-lg shadow-purple-500/20 flex items-center gap-1.5"
            >
              <span>📷</span>
              <span>Capture Snapshot</span>
            </button>

            <template v-else-if="capturedImage">
              <button
                type="button"
                @click="retakePhoto"
                class="px-4 py-2 rounded-xl bg-gray-100 dark:bg-slate-800 text-gray-800 dark:text-slate-200 text-xs font-semibold"
              >
                🔄 Retake
              </button>
              <button
                type="button"
                @click="submitCapturedSelfie"
                class="px-5 py-2.5 rounded-xl bg-emerald-500 text-slate-950 font-bold text-xs shadow-lg shadow-emerald-500/20 flex items-center gap-1.5"
              >
                <span>Upload Selfie 🚀</span>
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
