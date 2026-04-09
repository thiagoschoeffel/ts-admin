<script setup>
import VueApexCharts from 'vue3-apexcharts'
import { computed } from 'vue'

const props = defineProps({
  title: {
    type: String,
    default: ''
  },
  series: {
    type: Array,
    default: () => []
  },
  labels: {
    type: Array,
    default: () => []
  },
  height: {
    type: [Number, String],
    default: 300
  },
  width: {
    type: [Number, String],
    default: '100%'
  },
  colors: {
    type: Array,
    default: () => ['#FF4560', '#00E396', '#FEB019', '#775DD0']
  }
})

// Use single series as in ApexCharts funnel example
const funnelSeries = computed(() => [{
  name: 'Quantidade',
  data: props.series[0]?.data || [0, 0, 0, 0]
}])

// Ensure each stage color matches its label regardless of order
const STAGE_COLOR_MAP = {
  'Leads': '#FF4560',                 // Vermelho (como está)
  'Leads Qualificados': '#775DD0',    // Roxo (igual ao oportunidades vencidas anterior)
  'Oportunidades': '#FEB019',         // Amarelo (como está)
  'Oportunidades Vencidas': '#10B981' // Verde (mais escuro)
}

const resolvedColors = computed(() => {
  if (!props.labels?.length) return props.colors
  return props.labels.map((label, idx) => STAGE_COLOR_MAP[label] || props.colors[idx % props.colors.length])
})

const chartOptions = computed(() => ({
  chart: {
    type: 'bar',
    height: props.height,
    toolbar: {
      show: false
    }
  },
  plotOptions: {
    bar: {
      borderRadius: 0,
      horizontal: true,
      barHeight: '80%',
      isFunnel: true,
      distributed: true, // each data point uses its own color
    }
  },
  fill: {
    type: 'solid'
  },
  colors: resolvedColors.value,
  dataLabels: {
    enabled: true,
    formatter: function (val, opt) {
      return opt.w.globals.labels[opt.dataPointIndex] + ":  " + val
    },
    dropShadow: {
      enabled: true
    }
  },
  xaxis: {
    categories: props.labels
  },
  legend: {
    show: false
  },
  tooltip: {
    theme: 'light'
  }
}))
</script>

<template>
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
    <h3 v-if="title" class="text-lg font-semibold text-slate-900 mb-4">{{ title }}</h3>
    <div class="w-full overflow-hidden">
      <apexchart
        type="bar"
        :options="chartOptions"
        :series="funnelSeries"
        :width="width"
        :height="height"
      />
    </div>
  </div>
</template>
