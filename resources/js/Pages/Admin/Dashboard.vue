<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import HeroIcon from '@/components/icons/HeroIcon.vue';
import Badge from '@/components/Badge.vue';
import LineChart from '@/components/LineChart.vue';
import FunnelChart from '@/components/FunnelChart.vue';
import InputDatePicker from '@/components/InputDatePicker.vue';
import { ref, computed, getCurrentInstance } from 'vue';

const props = defineProps({
  stats: Object,
  salesChart: Object,
  funnelData: Object,
  filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const name = page.props.auth?.user?.name ?? 'usuário';

// Prepare chart data reactively
const chartSeries = computed(() => ([{
  name: 'Vendas',
  data: props.salesChart?.data || []
}]));

// Prepare funnel data
const funnelSeries = [{
  name: 'Quantidade',
  data: props.funnelData?.data || [0, 0, 0, 0]
}];
const funnelLabels = props.funnelData?.labels || ['Leads', 'Leads Qualificados', 'Oportunidades', 'Oportunidades Vencidas'];

// Sales period state (default from props -> server clamps to 15 days)
const salesPeriod = ref({
  start: props.filters?.sales_from || null,
  end: props.filters?.sales_to || null,
});

// Ziggy `route` helper from app globalProperties
const instance = getCurrentInstance();
const route = instance.appContext.config.globalProperties.route;

// On change, enforce 15-day max and request new data
function onSalesPeriodChange(val) {
  if (!val || !val.start || !val.end) return;
  const start = new Date(val.start.replace(' ', 'T'));
  const end = new Date(val.end.replace(' ', 'T'));
  if (isNaN(start.getTime()) || isNaN(end.getTime())) return;
  let a = start, b = end;
  if (a > b) { const tmp = a; a = b; b = tmp; }
  const maxEnd = new Date(a.getTime());
  maxEnd.setDate(maxEnd.getDate() + 15);
  if (b > maxEnd) b = maxEnd;

  // Format back to 'YYYY-MM-DD HH:mm'
  const pad2 = (n) => String(n).padStart(2, '0');
  const toStr = (d) => `${d.getFullYear()}-${pad2(d.getMonth()+1)}-${pad2(d.getDate())} ${pad2(d.getHours())}:${pad2(d.getMinutes())}`;
  const sales_from = toStr(a);
  const sales_to = toStr(b);

  // Reflect clamped value in the UI if it changed
  if (salesPeriod.value.start !== sales_from || salesPeriod.value.end !== sales_to) {
    salesPeriod.value = { start: sales_from, end: sales_to };
  }

  router.get(route('dashboard'), { sales_from, sales_to }, { preserveState: true, replace: true });
}
</script>

<template>
  <AdminLayout>
    <Head title="Dashboard" />

    <section class="space-y-8">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Olá, {{ name }}!</h1>
        <p class="text-base leading-relaxed text-slate-600">
          Bem-vindo ao painel administrativo. Aqui você tem uma visão geral do sistema.
        </p>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Users Card -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="p-3 bg-blue-100 rounded-lg">
                <HeroIcon name="users" class="h-6 w-6 text-blue-600" />
              </div>
            </div>
            <div class="text-right">
              <div class="text-3xl font-bold text-slate-900">{{ stats?.users || 0 }}</div>
              <div class="text-sm text-slate-500">Usuários</div>
            </div>
          </div>
        </div>

        <!-- Clients Card -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="p-3 bg-green-100 rounded-lg">
                <HeroIcon name="identification" class="h-6 w-6 text-green-600" />
              </div>
            </div>
            <div class="text-right">
              <div class="text-3xl font-bold text-slate-900">{{ stats?.clients || 0 }}</div>
              <div class="text-sm text-slate-500">Clientes</div>
            </div>
          </div>
        </div>

        <!-- Products Card -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="p-3 bg-purple-100 rounded-lg">
                <HeroIcon name="cube" class="h-6 w-6 text-purple-600" />
              </div>
            </div>
            <div class="text-right">
              <div class="text-3xl font-bold text-slate-900">{{ stats?.products || 0 }}</div>
              <div class="text-sm text-slate-500">Produtos</div>
            </div>
          </div>
        </div>

        <!-- Orders Card -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="p-3 bg-orange-100 rounded-lg">
                <HeroIcon name="shopping-bag" class="h-6 w-6 text-orange-600" />
              </div>
            </div>
            <div class="text-right">
              <div class="text-3xl font-bold text-slate-900">{{ stats?.orders || 0 }}</div>
              <div class="text-sm text-slate-500">Pedidos</div>
            </div>
          </div>
        </div>

        <!-- Leads Card -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="p-3 bg-yellow-100 rounded-lg">
                <HeroIcon name="chat-bubble-left-right" class="h-6 w-6 text-yellow-600" />
              </div>
            </div>
            <div class="text-right">
              <div class="text-3xl font-bold text-slate-900">{{ stats?.leads || 0 }}</div>
              <div class="text-sm text-slate-500">Leads</div>
            </div>
          </div>
        </div>

        <!-- Opportunities Card -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="p-3 bg-indigo-100 rounded-lg">
                <HeroIcon name="document-currency-dollar" class="h-6 w-6 text-indigo-600" />
              </div>
            </div>
            <div class="text-right">
              <div class="text-3xl font-bold text-slate-900">{{ stats?.opportunities || 0 }}</div>
              <div class="text-sm text-slate-500">Oportunidades</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <LineChart
          title="Vendas"
          :series="chartSeries"
          :categories="salesChart?.categories || []"
          height="400"
        >
          <template #header-actions>
            <div class="w-[320px]">
              <InputDatePicker
                v-model="salesPeriod"
                :range="true"
                :withTime="true"
                :maxRangeDays="15"
                placeholder="Selecione o período (máx. 15 dias)"
                @change="onSalesPeriodChange"
              />
            </div>
          </template>
        </LineChart>
        <FunnelChart
          title="Funil de vendas"
          :series="funnelSeries"
          :labels="funnelLabels"
          height="400"
        />
      </div>
    </section>
  </AdminLayout>
</template>
