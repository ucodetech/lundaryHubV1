<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  status: string;
}>();

const badgeClass = computed(() => {
  switch (props.status.toLowerCase()) {
    case 'active':
    case 'approved':
    case 'online':
      return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
    case 'pending':
      return 'bg-amber-500/10 text-amber-400 border-amber-500/20';
    case 'suspended':
    case 'rejected':
    case 'offline':
      return 'bg-rose-500/10 text-rose-400 border-rose-500/20';
    default:
      return 'bg-slate-500/10 text-gray-500 dark:text-slate-400 border-slate-500/20';
  }
});
</script>

<template>
  <span
    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border capitalize"
    :class="badgeClass"
  >
    {{ status }}
  </span>
</template>
