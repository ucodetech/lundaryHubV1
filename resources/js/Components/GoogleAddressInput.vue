<script setup lang="ts">
import { ref, watch } from 'vue';

const props = defineProps<{
  addressValue?: string;
  latValue?: number | null;
  lngValue?: number | null;
}>();

const emit = defineEmits<{
  (e: 'update:address', value: string): void;
  (e: 'update:latitude', value: number): void;
  (e: 'update:longitude', value: number): void;
}>();

const addressInput = ref(props.addressValue || '');
const latitudeInput = ref<number | null>(props.latValue ?? 6.4531);
const longitudeInput = ref<number | null>(props.lngValue ?? 3.3958);

// Suggested locations in Nigeria for quick selection / autocomplete demo
const suggestedLocations = [
  { name: 'Plot 12, Adeola Odeku Street, Victoria Island, Lagos', lat: 6.4281, lng: 3.4219 },
  { name: '24 Isaac John Street, GRA Ikeja, Lagos', lat: 6.5862, lng: 3.3592 },
  { name: 'Plot 45 Ahmadu Bello Way, Victoria Island, Lagos', lat: 6.4312, lng: 3.4150 },
  { name: '15 Admiralty Way, Lekki Phase 1, Lagos', lat: 6.4474, lng: 3.4723 },
  { name: 'Plot 88 Awolowo Road, Ikoyi, Lagos', lat: 6.4520, lng: 3.4350 },
];

const showSuggestions = ref(false);

function selectLocation(loc: { name: string; lat: number; lng: number }) {
  addressInput.value = loc.name;
  latitudeInput.value = loc.lat;
  longitudeInput.value = loc.lng;
  showSuggestions.value = false;

  emit('update:address', loc.name);
  emit('update:latitude', loc.lat);
  emit('update:longitude', loc.lng);
}

function handleInput() {
  emit('update:address', addressInput.value);
}

function handleLatChange() {
  if (latitudeInput.value !== null) {
    emit('update:latitude', Number(latitudeInput.value));
  }
}

function handleLngChange() {
  if (longitudeInput.value !== null) {
    emit('update:longitude', Number(longitudeInput.value));
  }
}

function useCurrentGPS() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        latitudeInput.value = Number(pos.coords.latitude.toFixed(6));
        longitudeInput.value = Number(pos.coords.longitude.toFixed(6));
        if (!addressInput.value) {
          addressInput.value = `GPS Pin (${latitudeInput.value}, ${longitudeInput.value})`;
          emit('update:address', addressInput.value);
        }
        emit('update:latitude', latitudeInput.value);
        emit('update:longitude', longitudeInput.value);
      },
      (err) => {
        console.warn('GPS location request declined or unavailable.', err);
      }
    );
  }
}

watch(() => props.addressValue, (newVal) => {
  if (newVal !== undefined) addressInput.value = newVal;
});
</script>

<template>
  <div class="space-y-3">
    <div class="relative">
      <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">
        Shop Address (Google Maps Autocomplete) *
      </label>

      <div class="relative">
        <input
          v-model="addressInput"
          type="text"
          required
          @input="handleInput"
          @focus="showSuggestions = true"
          placeholder="Type shop address e.g. Adeola Odeku Street, Victoria Island..."
          class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-sm focus:border-sky-500 pr-10"
        />

        <button
          type="button"
          @click="useCurrentGPS"
          title="Use My Current GPS Pin"
          class="absolute right-3 top-1/2 -translate-y-1/2 text-sky-400 hover:text-sky-300 text-xs font-bold px-2 py-1 bg-sky-500/10 rounded-lg border border-sky-500/20"
        >
          📍 GPS Pin
        </button>
      </div>

      <!-- Quick Autocomplete Dropdown -->
      <div
        v-if="showSuggestions"
        class="absolute left-0 right-0 top-full mt-1 bg-slate-900 border border-slate-800 rounded-xl shadow-2xl z-30 overflow-hidden"
      >
        <div class="p-2 border-b border-slate-800 text-[11px] font-semibold text-slate-400 uppercase">
          Suggested Locations
        </div>
        <div class="divide-y divide-slate-800/60 max-h-48 overflow-y-auto">
          <button
            v-for="(loc, idx) in suggestedLocations"
            :key="idx"
            type="button"
            @click="selectLocation(loc)"
            class="w-full px-4 py-2.5 text-left text-xs text-slate-300 hover:bg-slate-800 hover:text-sky-400 transition-colors flex items-center justify-between"
          >
            <div class="truncate">📍 {{ loc.name }}</div>
            <span class="text-[10px] text-slate-500 font-mono ml-2 shrink-0">{{ loc.lat }}, {{ loc.lng }}</span>
          </button>
        </div>
        <div class="p-2 bg-slate-950 text-right">
          <button type="button" @click="showSuggestions = false" class="text-[11px] text-slate-400 hover:underline">
            Close Dropdown
          </button>
        </div>
      </div>
    </div>

    <!-- Map Coordinates Output Fields -->
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Latitude</label>
        <input
          v-model.number="latitudeInput"
          type="number"
          step="any"
          required
          @input="handleLatChange"
          placeholder="6.4531"
          class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 text-xs font-mono focus:border-sky-500"
        />
      </div>

      <div>
        <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Longitude</label>
        <input
          v-model.number="longitudeInput"
          type="number"
          step="any"
          required
          @input="handleLngChange"
          placeholder="3.3958"
          class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 text-xs font-mono focus:border-sky-500"
        />
      </div>
    </div>
  </div>
</template>
