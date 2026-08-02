<script setup lang="ts">
defineProps<{
  title: string;
  value: string | number;
  subtitle?: string;
  icon?: string;
  color?: string;
}>();
</script>

<template>
  <div class="bg-slate-800/80 backdrop-blur border border-slate-700/60 rounded-xl p-5 shadow-lg">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ title }}</p>
        <h3 class="text-2xl font-bold text-slate-100 mt-1">{{ value }}</h3>
        <p v-if="subtitle" class="text-xs text-slate-400 mt-1">{{ subtitle }}</p>
      </div>
      <div v-if="icon" class="w-12 h-12 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center text-sky-400 text-xl font-bold">
        {{ icon }}
      </div>
    </div>
  </div>
</template>
