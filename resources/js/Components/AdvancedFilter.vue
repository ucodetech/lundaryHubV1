<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

export interface FilterOption {
  label: string;
  value: string | number | boolean;
}

export interface FilterConfig {
  key: string;
  label: string;
  type: 'text' | 'select' | 'boolean' | 'date';
  placeholder?: string;
  options?: FilterOption[];
  defaultVisible?: boolean;
}

const props = withDefaults(
  defineProps<{
    filtersConfig?: FilterConfig[];
    config?: FilterConfig[];
    modelValue?: Record<string, any>;
    initialValues?: Record<string, any>;
    searchPlaceholder?: string;
  }>(),
  {
    filtersConfig: () => [],
    config: () => [],
    modelValue: () => ({}),
    initialValues: () => ({}),
    searchPlaceholder: 'Search records...',
  }
);

const emit = defineEmits<{
  (e: 'update:modelValue', value: Record<string, any>): void;
  (e: 'filter-change', value: Record<string, any>): void;
  (e: 'reset'): void;
}>();

const effectiveConfig = computed<FilterConfig[]>(() => {
  if (props.config && props.config.length > 0) return props.config;
  return props.filtersConfig || [];
});

const initialFilterState = computed(() => {
  return { ...props.initialValues, ...props.modelValue };
});

// Filter Values state
const filterValues = ref<Record<string, any>>({ ...initialFilterState.value });

// Active Visible Fields (which filters are currently toggled on)
const activeFields = ref<Set<string>>(
  new Set(
    (effectiveConfig.value || [])
      .filter((f) => f && (f.defaultVisible || initialFilterState.value[f.key]))
      .map((f) => f.key)
  )
);

// Toggle Menu State
const showAddFilterMenu = ref(false);

const toggleMenuContainer = ref<HTMLElement | null>(null);

function toggleFieldVisibility(key: string) {
  if (activeFields.value.has(key)) {
    activeFields.value.delete(key);
    delete filterValues.value[key];
    applyFilters();
  } else {
    activeFields.value.add(key);
  }
}

function applyFilters() {
  const activePayload: Record<string, any> = {};
  for (const [key, val] of Object.entries(filterValues.value)) {
    if (val !== '' && val !== null && val !== undefined) {
      activePayload[key] = val;
    }
  }
  emit('update:modelValue', activePayload);
  emit('filter-change', activePayload);
}

function resetAllFilters() {
  filterValues.value = {};
  activeFields.value = new Set(
    (effectiveConfig.value || [])
      .filter((f) => f && f.defaultVisible)
      .map((f) => f.key)
  );
  emit('update:modelValue', {});
  emit('filter-change', {});
  emit('reset');
}

const activeFilterCount = computed(() => {
  return Object.values(filterValues.value).filter(
    (val) => val !== '' && val !== null && val !== undefined
  ).length;
});

const inactiveFilters = computed(() => {
  return (effectiveConfig.value || []).filter((f) => !activeFields.value.has(f.key));
});

function handleClickOutside(event: MouseEvent) {
  if (
    toggleMenuContainer.value &&
    !toggleMenuContainer.value.contains(event.target as Node)
  ) {
    showAddFilterMenu.value = false;
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});

watch(
  () => initialFilterState.value,
  (newValues) => {
    filterValues.value = { ...newValues };
  },
  { deep: true }
);
</script>

<template>
  <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-4 space-y-4 shadow-xl">
    <!-- Active Filters Bar -->
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-2">
        <span class="text-xs font-semibold text-slate-400 flex items-center gap-1.5 mr-1">
          <span>🔍</span>
          <span>Filters</span>
          <span
            v-if="activeFilterCount > 0"
            class="px-2 py-0.5 rounded-full bg-sky-500/20 text-sky-400 border border-sky-500/30 text-[10px] font-bold font-mono"
          >
            {{ activeFilterCount }}
          </span>
        </span>

        <!-- Reset Button -->
        <button
          v-if="activeFilterCount > 0"
          @click="resetAllFilters"
          class="text-xs font-semibold text-rose-400 hover:text-rose-300 px-2.5 py-1 rounded-lg bg-rose-500/10 border border-rose-500/20 transition-all"
        >
          Clear All
        </button>
      </div>

      <!-- Add Filter Dropdown Button -->
      <div v-if="inactiveFilters.length > 0" ref="toggleMenuContainer" class="relative">
        <button
          @click="showAddFilterMenu = !showAddFilterMenu"
          class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 hover:border-slate-600 text-xs font-semibold text-slate-200 flex items-center gap-1.5 transition-all"
        >
          <span>+</span>
          <span>Add Filter Field</span>
        </button>

        <!-- Dropdown Menu -->
        <div
          v-if="showAddFilterMenu"
          class="absolute right-0 mt-2 w-48 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl z-30 py-1 overflow-hidden animate-in fade-in zoom-in-95 duration-100"
        >
          <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800">
            Available Filters
          </div>
          <button
            v-for="filter in inactiveFilters"
            :key="filter.key"
            @click="toggleFieldVisibility(filter.key); showAddFilterMenu = false"
            class="w-full text-left px-3 py-2 text-xs text-slate-300 hover:bg-slate-800 hover:text-sky-400 flex items-center justify-between transition-colors"
          >
            <span>{{ filter.label }}</span>
            <span class="text-[10px] text-slate-400 font-mono">+ Add</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Active Input Fields Grid -->
    <div v-if="effectiveConfig && effectiveConfig.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 pt-1">
      <template v-for="field in effectiveConfig" :key="field.key">
        <div v-if="activeFields.has(field.key)" class="space-y-1">
          <div class="flex items-center justify-between text-[11px]">
            <label class="font-semibold text-slate-300">{{ field.label }}</label>
            <button
              v-if="!field.defaultVisible"
              @click="toggleFieldVisibility(field.key)"
              class="text-slate-400 hover:text-slate-200 text-[10px]"
              title="Remove filter"
            >
              ✕
            </button>
          </div>

          <!-- Text Field -->
          <input
            v-if="field.type === 'text'"
            v-model="filterValues[field.key]"
            type="text"
            :placeholder="field.placeholder || 'Filter ' + field.label"
            @input="applyFilters"
            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-slate-100 text-xs focus:border-sky-500 shadow-inner"
          />

          <!-- Select Field -->
          <select
            v-else-if="field.type === 'select'"
            v-model="filterValues[field.key]"
            @change="applyFilters"
            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-slate-100 text-xs focus:border-sky-500 shadow-inner"
          >
            <option value="">All {{ field.label }}</option>
            <option
              v-for="opt in field.options || []"
              :key="String(opt.value)"
              :value="opt.value"
            >
              {{ opt.label }}
            </option>
          </select>

          <!-- Boolean Field -->
          <select
            v-else-if="field.type === 'boolean'"
            v-model="filterValues[field.key]"
            @change="applyFilters"
            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-slate-100 text-xs focus:border-sky-500 shadow-inner"
          >
            <option value="">All</option>
            <option :value="true">Yes</option>
            <option :value="false">No</option>
          </select>

          <!-- Date Field -->
          <input
            v-else-if="field.type === 'date'"
            v-model="filterValues[field.key]"
            type="date"
            @change="applyFilters"
            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-slate-100 text-xs focus:border-sky-500 shadow-inner"
          />
        </div>
      </template>
    </div>
  </div>
</template>
