<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue';
import {
  Chart,
  DoughnutController,
  ArcElement,
  Tooltip,
  Legend,
  Title,
} from 'chart.js';

Chart.register(
  DoughnutController,
  ArcElement,
  Tooltip,
  Legend,
  Title
);

const props = defineProps<{
  labels: string[];
  data: number[];
  colors?: string[];
  height?: number;
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
let chartInstance: Chart | null = null;

const defaultColors = [
  '#0284c7', // sky-600
  '#38bdf8', // sky-400
  '#a855f7', // purple-500
  '#c084fc', // purple-400
  '#10b981', // emerald-500
  '#f59e0b', // amber-500
  '#f43f5e', // rose-500
];

function renderChart() {
  if (!canvasRef.value) return;

  if (chartInstance) {
    chartInstance.destroy();
  }

  chartInstance = new Chart(canvasRef.value, {
    type: 'doughnut',
    data: {
      labels: props.labels,
      datasets: [
        {
          data: props.data,
          backgroundColor: props.colors || defaultColors,
          borderColor: '#0f172a',
          borderWidth: 3,
          hoverOffset: 6,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '70%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            color: '#94a3b8',
            font: { family: 'Inter', size: 11, weight: 'medium' },
            padding: 16,
            usePointStyle: true,
          },
        },
        tooltip: {
          backgroundColor: '#0f172a',
          titleColor: '#f8fafc',
          bodyColor: '#38bdf8',
          borderColor: '#1e293b',
          borderWidth: 1,
          padding: 10,
        },
      },
    },
  });
}

onMounted(() => {
  renderChart();
});

watch(
  () => [props.labels, props.data],
  () => renderChart(),
  { deep: true }
);

onUnmounted(() => {
  if (chartInstance) {
    chartInstance.destroy();
  }
});
</script>

<template>
  <div class="relative w-full flex items-center justify-center" :style="{ height: `${height || 260}px` }">
    <canvas ref="canvasRef"></canvas>
  </div>
</template>
