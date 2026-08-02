<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue';
import {
  Chart,
  BarController,
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend,
  Title,
} from 'chart.js';

Chart.register(
  BarController,
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend,
  Title
);

const props = defineProps<{
  labels: string[];
  datasets: Array<{
    label: string;
    data: number[];
    backgroundColor?: string | string[];
    borderColor?: string | string[];
    borderWidth?: number;
    borderRadius?: number;
  }>;
  title?: string;
  height?: number;
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
let chartInstance: Chart | null = null;

function renderChart() {
  if (!canvasRef.value) return;

  if (chartInstance) {
    chartInstance.destroy();
  }

  chartInstance = new Chart(canvasRef.value, {
    type: 'bar',
    data: {
      labels: props.labels,
      datasets: props.datasets.map((ds) => ({
        ...ds,
        backgroundColor: ds.backgroundColor || '#0284c7',
        borderColor: ds.borderColor || '#38bdf8',
        borderWidth: ds.borderWidth ?? 1,
        borderRadius: ds.borderRadius ?? 8,
      })),
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: props.datasets.length > 1,
          labels: {
            color: '#94a3b8',
            font: { family: 'Inter', size: 11, weight: 'bold' },
          },
        },
        tooltip: {
          backgroundColor: '#0f172a',
          titleColor: '#f8fafc',
          bodyColor: '#38bdf8',
          borderColor: '#1e293b',
          borderWidth: 1,
          padding: 12,
          boxPadding: 6,
          usePointStyle: true,
          callbacks: {
            label: (context) => {
              const val = context.parsed.y;
              return ` ${context.dataset.label}: ₦${Number(val).toLocaleString()}`;
            },
          },
        },
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: '#64748b', font: { family: 'Inter', size: 10 } },
        },
        y: {
          grid: { color: '#1e293b' },
          ticks: {
            color: '#64748b',
            font: { family: 'Inter', size: 10 },
            callback: (value) => `₦${Number(value).toLocaleString()}`,
          },
        },
      },
    },
  });
}

onMounted(() => {
  renderChart();
});

watch(
  () => [props.labels, props.datasets],
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
  <div class="relative w-full" :style="{ height: `${height || 300}px` }">
    <canvas ref="canvasRef"></canvas>
  </div>
</template>
