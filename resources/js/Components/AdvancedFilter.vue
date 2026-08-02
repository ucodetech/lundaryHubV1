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
    filtersConfig: FilterConfig[];
    modelValue?: Record<string, any>;
    searchPlaceholder?: string;
  }>(),
  {
    modelValue: () => ({}),
    searchPlaceholder: 'Search records...',
  }
);

const emit = defineEmits<{
  (e: 'update:modelValue', value: Record<string, any>): void;
  (e: 'filter-change', value: Record<string, any>): void;
  (e: 'reset'): void;
}>();

// Filter Values state
const filterValues = ref<Record<string, any>>({ ...props.modelValue });

// Active Visible Fields (which filters are currently toggled on)
const activeFields = ref<Set<string>>(
  new Set(
    props.filtersConfig
      .filter((f) => f.defaultVisible || props.modelValue[f.key])
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

function removeFilter(key: string) {
  activeFields.value.delete(key);
  delete filterValues.value[key];
  applyFilters();
}

function applyFilters() {
  const cleanValues: Record<string, any> = {};
  Object.keys(filterValues.value).forEach((key) => {
    const val = filterValues.value[key];
    if (val !== '' && val !== null && val !== undefined) {
      cleanValues[key] = val;
    }
  });

  emit('update:modelValue', cleanValues);
  emit('filter-change', cleanValues);
}

function resetAll() {
  filterValues.value = {};
  activeFields.value = new Set(
    props.filtersConfig.filter((f) => f.defaultVisible).map((f) => f.key)
  );
  emit('update:modelValue', {});
  emit('filter-change', {});
  emit('reset');
}

const activeFilterCount = computed(() => {
  return Object.keys(filterValues.value).filter(
    (key) => filterValues.value[key] !== '' && filterValues.value[key] !== null && filterValues.value[key] !== undefined
  ).length;
});

const activeChips = computed(() => {
  return Object.keys(filterValues.value)
    .filter((key) => filterValues.value[key] !== '' && filterValues.value[key] !== null && filterValues.value[key] !== undefined)
    .map((key) => {
      const config = props.filtersConfig.find((f) => f.key === key);
      let displayVal = filterValues.value[key];
      if (config?.type === 'select' && config.options) {
        const match = config.options.find((o) => o.value == displayVal);
        if (match) displayVal = match.label;
      }
      return {
        key,
        label: config?.label || key,
        displayValue: displayVal,
      };
    });
});

watch(
  () => props.modelValue,
  (newVal) => {
    filterValues.value = { ...newVal };
    Object.keys(newVal).forEach((key) => {
      if (newVal[key]) activeFields.value.add(key);
    });
  },
  { deep: true }
);

function handleClickOutside(event: MouseEvent) {
  if (toggleMenuContainer.value && !toggleMenuContainer.value.contains(event.target as Node)) {
    showAddFilterMenu.value = false;
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
  <div class="space-y-3 bg-slate-900/90 border border-slate-800/80 rounded-2xl p-4 shadow-lg backdrop-blur-md">
    <!-- Top Filter Toolbar -->
    <div class="flex flex-wrap items-center justify-between gap-3">
      <!-- Search Input & Add Filter Button -->
      <div class="flex flex-wrap items-center gap-3 flex-1 min-w-[280px]">
        <!-- Global Search Input -->
        <div class="relative flex-1 min-w-[220px]">
          <input
            v-model="filterValues['search']"
            type="text"
            :placeholder="searchPlaceholder"
            @input="applyFilters"
            class="w-full pl-10 pr-8 py-2 text-xs bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-500 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-all"
          />
          <svg class="w-4 h-4 text-slate-500 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <button
            v-if="filterValues['search']"
            @click="filterValues['search'] = ''; applyFilters();"
            class="absolute right-2.5 top-2.5 text-slate-500 hover:text-slate-300 text-xs"
          >
            ✕
          </button>
        </div>

        <!-- Add Filter Toggle Button Dropdown -->
        <div class="relative" ref="toggleMenuContainer">
          <button
            @click="showAddFilterMenu = !showAddFilterMenu"
            class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-800/80 hover:bg-slate-800 text-slate-200 border border-slate-700/80 transition-all flex items-center gap-2 shadow-sm"
          >
            <span>➕ Filter Options</span>
            <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="showAddFilterMenu ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <!-- Toggle Filter Dropdown Menu -->
          <div
            v-if="showAddFilterMenu"
            class="absolute left-0 mt-2 w-56 bg-slate-950 border border-slate-800 rounded-xl shadow-2xl overflow-hidden z-40 animate-in fade-in slide-in-from-top-2 duration-150 p-2 space-y-1"
          >
            <div class="px-2 py-1 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
              Toggle Available Filters
            </div>
            <button
              v-for="config in filtersConfig.filter(f => f.key !== 'search')"
              :key="config.key"
              @click="toggleFieldVisibility(config.key)"
              class="w-full flex items-center justify-between px-2.5 py-2 rounded-lg text-xs font-medium transition-colors text-left"
              :class="activeFields.has(config.key) ? 'bg-sky-500/10 text-sky-400 font-semibold' : 'text-slate-300 hover:bg-slate-900'"
            >
              <span>{{ config.label }}</span>
              <span v-if="activeFields.has(config.key)" class="text-sky-400 font-bold">✓</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Reset Button -->
      <button
        v-if="activeFilterCount > 0"
        @click="resetAll"
        class="px-3 py-1.5 rounded-xl text-xs font-medium bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500/20 transition-all flex items-center gap-1.5"
      >
        <span>Reset Filters</span>
        <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-rose-500/20 font-bold">{{ activeFilterCount }}</span>
      </button>
    </div>

    <!-- Active Filter Controls Row -->
    <div
      v-if="filtersConfig.some(f => f.key !== 'search' && activeFields.has(f.key))"
      class="pt-3 border-t border-slate-800/60 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3"
    >
      <template v-for="config in filtersConfig" :key="config.key">
        <div v-if="config.key !== 'search' && activeFields.has(config.key)" class="space-y-1">
          <div class="flex items-center justify-between text-[11px] font-medium text-slate-400 px-0.5">
            <span>{{ config.label }}</span>
            <button @click="removeFilter(config.key)" class="text-slate-500 hover:text-rose-400 text-xs">✕</button>
          </div>

          <!-- Select Filter -->
          <select
            v-if="config.type === 'select'"
            v-model="filterValues[config.key]"
            @change="applyFilters"
            class="w-full px-3 py-1.5 text-xs bg-slate-950 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-all"
          >
            <option value="">All {{ config.label }}</option>
            <option v-for="opt in config.options" :key="String(opt.value)" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>

          <!-- Text Filter -->
          <input
            v-else-if="config.type === 'text'"
            v-model="filterValues[config.key]"
            type="text"
            :placeholder="config.placeholder || `Filter by ${config.label}...`"
            @input="applyFilters"
            class="w-full px-3 py-1.5 text-xs bg-slate-950 border border-slate-800 rounded-xl text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-all"
          />

          <!-- Date Filter -->
          <input
            v-else-if="config.type === 'date'"
            v-model="filterValues[config.key]"
            type="date"
            @change="applyFilters"
            class="w-full px-3 py-1.5 text-xs bg-slate-950 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-all"
          />
        </div>
      </template>
    </div>

    <!-- Active Filter Badges / Chips Row -->
    <div v-if="activeChips.length > 0" class="pt-2 flex flex-wrap items-center gap-2">
      <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Active:</span>
      <div
        v-for="chip in activeChips"
        :key="chip.key"
        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs bg-sky-500/10 text-sky-400 border border-sky-500/20 font-medium"
      >
        <span class="text-slate-400 font-normal">{{ chip.label }}:</span>
        <span class="font-bold">{{ chip.displayValue }}</span>
        <button
          @click="removeFilter(chip.key)"
          class="hover:text-rose-400 transition-colors ml-0.5 text-xs"
          title="Remove Filter"
        >
          ✕
        </button>
      </div>
    </div>
  </div>
</template>
