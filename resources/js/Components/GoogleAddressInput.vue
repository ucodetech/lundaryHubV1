<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';

const props = defineProps<{
  address?: string;
  latitude?: number | null;
  longitude?: number | null;
}>();

const emit = defineEmits<{
  (e: 'update:address', value: string): void;
  (e: 'update:latitude', value: number): void;
  (e: 'update:longitude', value: number): void;
}>();

const inputRef = ref<HTMLInputElement | null>(null);
const mapRef = ref<HTMLDivElement | null>(null);

const isLoaded = ref(false);
let googleMap: any = null;
let googleMarker: any = null;
let autocomplete: any = null;

const apiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY || 'AIzaSyC2df9i_A809q2eQQizBb7UqSGXASsQHVQ';

function loadGoogleMapsScript() {
  if (typeof window === 'undefined') return;

  const google = (window as any).google;
  if (google && google.maps && google.maps.Map && google.maps.places) {
    initMaps();
    return;
  }

  const existingScript = document.getElementById('google-maps-js-script');
  if (!existingScript) {
    const script = document.createElement('script');
    script.id = 'google-maps-js-script';
    script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places`;
    script.async = true;
    script.defer = true;
    script.onload = () => {
      setTimeout(() => {
        initMaps();
      }, 100);
    };
    document.head.appendChild(script);
  } else {
    existingScript.addEventListener('load', () => {
      setTimeout(() => {
        initMaps();
      }, 100);
    });
  }
}

async function initMaps() {
  const google = (window as any).google;
  if (!google || !google.maps) return;

  if (google.maps.importLibrary) {
    try {
      await google.maps.importLibrary('maps');
      await google.maps.importLibrary('places');
    } catch (e) {
      console.warn('Google Maps importLibrary fallback:', e);
    }
  }

  isLoaded.value = true;

  if (inputRef.value && google.maps.places && google.maps.places.Autocomplete) {
    try {
      autocomplete = new google.maps.places.Autocomplete(inputRef.value, {
        types: ['geocode', 'establishment'],
        componentRestrictions: { country: 'ng' },
        fields: ['geometry', 'formatted_address', 'name', 'address_components'],
      });

      autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace();

        if (place && place.geometry && place.geometry.location) {
          const lat = Number(place.geometry.location.lat().toFixed(6));
          const lng = Number(place.geometry.location.lng().toFixed(6));
          const formattedAddress = place.formatted_address || place.name || props.address || '';

          emit('update:address', formattedAddress);
          emit('update:latitude', lat);
          emit('update:longitude', lng);
          updateMapLocation(lat, lng);

        } else if (props.address) {
          geocodeAddressString(props.address);
        }
      });
    } catch (e) {
      console.warn('Google Places Autocomplete init fallback:', e);
    }
  }

  initMapCanvas();
}

function geocodeAddressString(query: string) {
  const google = (window as any).google;
  if (!google || !google.maps || !google.maps.Geocoder) return;

  const geocoder = new google.maps.Geocoder();
  geocoder.geocode(
    { address: query, componentRestrictions: { country: 'NG' } },
    (results: any, status: string) => {
      if (status === 'OK' && results && results[0] && results[0].geometry) {
        const loc = results[0].geometry.location;
        const lat = Number(loc.lat().toFixed(6));
        const lng = Number(loc.lng().toFixed(6));
        const formatted = results[0].formatted_address || query;

        emit('update:address', formatted);
        emit('update:latitude', lat);
        emit('update:longitude', lng);
        updateMapLocation(lat, lng);

      }
    }
  );
}

function initMapCanvas() {
  const google = (window as any).google;
  if (!mapRef.value || !google || !google.maps || !google.maps.Map) return;

  const initialLat = props.latitude ?? 6.4531;
  const initialLng = props.longitude ?? 3.3958;

  const center = { lat: Number(initialLat), lng: Number(initialLng) };

  try {
    googleMap = new google.maps.Map(mapRef.value, {
      center,
      zoom: 15,
      styles: [
        { elementType: 'geometry', stylers: [{ color: '#0f172a' }] },
        { elementType: 'labels.text.stroke', stylers: [{ color: '#0f172a' }] },
        { elementType: 'labels.text.fill', stylers: [{ color: '#94a3b8' }] },
        { featureType: 'administrative.locality', elementType: 'labels.text.fill', stylers: [{ color: '#38bdf8' }] },
        { featureType: 'poi', elementType: 'labels.text.fill', stylers: [{ color: '#0284c7' }] },
        { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#1e293b' }] },
        { featureType: 'road', elementType: 'geometry.stroke', stylers: [{ color: '#334155' }] },
        { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#0284c7' }] },
      ],
    });

    if (google.maps.Marker) {
      googleMarker = new google.maps.Marker({
        position: center,
        map: googleMap,
        draggable: true,
        title: 'Drag pin to set exact shop location',
      });

      googleMarker.addListener('dragend', (evt: any) => {
        const lat = Number(evt.latLng.lat().toFixed(6));
        const lng = Number(evt.latLng.lng().toFixed(6));

        emit('update:latitude', lat);
        emit('update:longitude', lng);

      });
    }
  } catch (e) {
    console.warn('Google Map Canvas init error:', e);
  }
}

function updateMapLocation(lat: number, lng: number) {
  if (googleMap) {
    const pos = { lat: Number(lat), lng: Number(lng) };
    googleMap.setCenter(pos);
    googleMap.setZoom(16);
    if (googleMarker) {
      googleMarker.setPosition(pos);
    }
  }
}

function onAddressInput(e: Event) {
  const val = (e.target as HTMLInputElement).value;
  emit('update:address', val);
}

function onAddressBlur() {
  if (props.address && (!props.latitude || props.latitude === 6.4531)) {
    geocodeAddressString(props.address);
  }
}

function onLatInput(e: Event) {
  const val = Number((e.target as HTMLInputElement).value);
  if (!isNaN(val)) {
    emit('update:latitude', val);
    updateMapLocation(val, Number(props.longitude ?? 3.3958));
  }
}

function onLngInput(e: Event) {
  const val = Number((e.target as HTMLInputElement).value);
  if (!isNaN(val)) {
    emit('update:longitude', val);
    updateMapLocation(Number(props.latitude ?? 6.4531), val);
  }
}

function useCurrentGPS() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        const lat = Number(pos.coords.latitude.toFixed(6));
        const lng = Number(pos.coords.longitude.toFixed(6));
        const formatted = props.address || `GPS Pin Location (${lat}, ${lng})`;

        emit('update:address', formatted);
        emit('update:latitude', lat);
        emit('update:longitude', lng);
        updateMapLocation(lat, lng);

      },
      (err) => {
        console.warn('GPS location request declined or unavailable.', err);
      }
    );
  }
}

onMounted(() => {
  loadGoogleMapsScript();
});

// Sync map marker if parent props change externally
watch(
  [() => props.latitude, () => props.longitude],
  ([newLat, newLng]) => {
    if (newLat != null && newLng != null) {
      updateMapLocation(Number(newLat), Number(newLng));
    }
  }
);
</script>

<template>
  <div class="space-y-4">
    <div class="relative">
      <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">
        Shop Address (Live Google Places Autocomplete) *
      </label>

      <div class="relative">
        <input
          ref="inputRef"
          :value="address || ''"
          type="text"
          required
          @input="onAddressInput"
          @blur="onAddressBlur"
          placeholder="Start typing your shop address (e.g. Adeola Odeku Street, Victoria Island)..."
          class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-sm focus:border-sky-500 pr-24 shadow-inner"
        />

        <button
          type="button"
          @click="useCurrentGPS"
          title="Use Current Device GPS Location"
          class="absolute right-3 top-1/2 -translate-y-1/2 text-sky-400 hover:text-sky-300 text-xs font-bold px-3 py-1.5 bg-sky-500/10 rounded-lg border border-sky-500/20 flex items-center gap-1 transition-transform hover:scale-105"
        >
          <span>📍</span>
          <span>My GPS</span>
        </button>
      </div>
      <p class="text-[11px] text-slate-400 mt-1">Live Google Places API enables instant address prediction and geo-coordinates extraction.</p>
    </div>

    <!-- Live Google Map Canvas with Drag Marker -->
    <div class="space-y-2">
      <div class="flex items-center justify-between text-xs text-slate-400">
        <span class="font-semibold text-slate-300">🗺️ Live Google Map Location Pin</span>
        <span class="text-[11px]">Drag pin to adjust exact shop entrance</span>
      </div>

      <div class="w-full h-48 rounded-2xl border border-slate-800 bg-slate-950 overflow-hidden shadow-xl relative">
        <div v-if="!isLoaded" class="absolute inset-0 flex items-center justify-center text-xs text-slate-500 bg-slate-950 z-10">
          Loading Google Maps Engine...
        </div>
        <!-- Isolated Map Container: Vue never touches inner Google Maps DOM nodes -->
        <div ref="mapRef" class="w-full h-full"></div>
      </div>
    </div>

    <!-- Map Coordinates Output Fields -->
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Latitude</label>
        <input
          :value="latitude ?? ''"
          type="number"
          step="any"
          required
          @input="onLatInput"
          placeholder="6.4531"
          class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 text-xs font-mono focus:border-sky-500"
        />
      </div>

      <div>
        <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Longitude</label>
        <input
          :value="longitude ?? ''"
          type="number"
          step="any"
          required
          @input="onLngInput"
          placeholder="3.3958"
          class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 text-xs font-mono focus:border-sky-500"
        />
      </div>
    </div>
  </div>
</template>
