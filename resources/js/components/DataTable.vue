<script setup>
import { computed, getCurrentInstance, ref, onMounted, onBeforeUnmount, nextTick } from 'vue';
import Dropdown from '@/components/Dropdown.vue';
import Button from '@/components/Button.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const props = defineProps({
  columns: {
    type: Array,
    required: true,
    validator: (columns) => columns.every(col => col.header && (col.key || col.component))
  },
  data: {
    type: Array,
    default: () => []
  },
  actions: {
    type: [Array, Function],
    default: () => []
  },
  // Optional inline action buttons rendered before the dropdown per row
  inlineActions: {
    type: [Array, Function],
    default: () => []
  },
  // Keep last column sticky to the right
  stickyActions: {
    type: Boolean,
    default: true,
  },
  // Optionally freeze the first column (sticky left)
  freezeFirst: {
    type: Boolean,
    default: false,
  },
  emptyMessage: {
    type: String,
    default: 'Nenhum registro encontrado.'
  },
  rowKey: {
    type: String,
    default: 'id'
  }
});

const emit = defineEmits(['action']);

const instance = getCurrentInstance();
const route = instance.appContext.config.globalProperties.route;

const colspan = computed(() => {
  const hasDropdown = (typeof props.actions === 'function' || (Array.isArray(props.actions) && props.actions.length > 0));
  const hasInline = (typeof props.inlineActions === 'function' || (Array.isArray(props.inlineActions) && props.inlineActions.length > 0));
  return props.columns.length + ((hasDropdown || hasInline) ? 1 : 0);
});

const handleAction = (action, item) => {
  emit('action', { action, item });
};

// Scroll state for showing/hiding sticky shadows
const rootEl = ref(null);
const scrollEl = ref(null);
const atRightEdge = ref(true);
const atLeftEdge = ref(true);

const updateShadows = () => {
  const el = scrollEl.value;
  if (!el) return;
  const max = el.scrollWidth - el.clientWidth;
  const sl = el.scrollLeft;
  // Treat no-overflow as both edges
  if (max <= 0) {
    atLeftEdge.value = true;
    atRightEdge.value = true;
    return;
  }
  atLeftEdge.value = sl <= 1;
  atRightEdge.value = sl >= (max - 1);
};

const onScroll = () => updateShadows();
const onResize = () => updateShadows();

onMounted(() => {
  nextTick(() => {
    updateShadows();
  });
  if (scrollEl.value) {
    scrollEl.value.addEventListener('scroll', onScroll, { passive: true });
  }
  window.addEventListener('resize', onResize, { passive: true });
});

onBeforeUnmount(() => {
  if (scrollEl.value) {
    scrollEl.value.removeEventListener('scroll', onScroll);
  }
  window.removeEventListener('resize', onResize);
});
</script>

<template>
  <div ref="rootEl" class="datatable relative" :class="{ 'dt-at-right': atRightEdge, 'dt-at-left': atLeftEdge }">
    <div ref="scrollEl" class="datatable-scroll overflow-x-auto overflow-y-hidden">
      <table class="min-w-full table-auto border-separate table">
        <thead>
          <tr>
            <th v-for="(column, idx) in columns" :key="column.key || column.header" :class="[
              'dt-cell border-b-2 border-slate-200 px-3 py-3 text-left text-sm font-semibold text-slate-600',
              column.class,
              freezeFirst && idx === 0 ? 'sticky-left' : ''
            ]" :style="freezeFirst && idx === 0 ? { left: '0px' } : undefined">
              {{ column.header }}
            </th>
            <th
              v-if="(typeof actions === 'function' || (Array.isArray(actions) && actions.length > 0)) || (typeof inlineActions === 'function' || (Array.isArray(inlineActions) && inlineActions.length > 0))"
              :class="[
                'dt-cell dt-actions border-b-2 border-slate-200 px-3 py-3 text-left text-sm font-semibold text-slate-600',
                stickyActions ? 'dt-sticky-actions' : ''
              ]">
              Ações
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in data" :key="item[rowKey]">
            <td v-for="(column, idx) in columns" :key="column.key || column.header" :class="[
              'dt-cell border-b border-slate-200 px-3 py-3 text-sm text-slate-800',
              column.class,
              freezeFirst && idx === 0 ? 'sticky-left' : ''
            ]" :style="freezeFirst && idx === 0 ? { left: '0px' } : undefined">
              <template v-if="column.cellRenderer">
                <!-- Renderiza VNode customizado -->
                <component :is="column.cellRenderer(item, idx)" />
              </template>
              <component v-else-if="column.component" :is="column.component"
                v-bind="column.props ? column.props(item) : {}">
                {{ column.formatter ? column.formatter(item[column.key], item) : item[column.key] }}
              </component>
              <template v-else>
                {{ column.formatter ? column.formatter(item[column.key], item) : item[column.key] }}
              </template>
            </td>
            <td
              v-if="(typeof actions === 'function' || (Array.isArray(actions) && actions.length > 0)) || (typeof inlineActions === 'function' || (Array.isArray(inlineActions) && inlineActions.length > 0))"
              :class="[
                'dt-cell dt-actions border-b border-slate-200 px-3 py-3 text-sm text-slate-800',
                stickyActions ? 'dt-sticky-actions' : ''
              ]">
              <!-- Inline actions before dropdown -->
              <template
                v-for="action in (typeof inlineActions === 'function' ? inlineActions(item, data.indexOf(item), route) : inlineActions)"
                :key="['inline', item[rowKey], action.key].join('-')">
                <Button :variant="action.variant || 'ghost'" size="sm" class="mr-2" :aria-label="action.label || 'Ação'"
                  @click="handleAction(action, item)">
                  <HeroIcon v-if="action.icon" :name="action.icon" class="h-5 w-5" />
                </Button>
              </template>
              <Dropdown
                v-if="typeof actions === 'function' ? actions(item, data.indexOf(item), route).length > 0 : (Array.isArray(actions) && actions.length > 0)"
                :zIndex="2000">
                <template #trigger="{ toggle }">
                  <Button variant="ghost" size="sm" @click="toggle" aria-label="Abrir menu de ações">
                    <HeroIcon name="ellipsis-horizontal" class="h-5 w-5" />
                  </Button>
                </template>
                <template #default="{ close }">
                  <template
                    v-for="action in (typeof actions === 'function' ? actions(item, data.indexOf(item), route) : actions)"
                    :key="action.key">
                    <component v-if="action.component" :is="action.component"
                      v-bind="action.props ? action.props(item, route) : {}"
                      @click="handleAction(action, item); close()">
                      <HeroIcon v-if="action.icon" :name="action.icon" class="h-4 w-4" />
                      <span>{{ action.label }}</span>
                    </component>
                    <button v-else type="button"
                      :class="action.class ? `menu-panel-link ${action.class}` : 'menu-panel-link'"
                      @click="handleAction(action, item); close()">
                      <HeroIcon v-if="action.icon" :name="action.icon" class="h-4 w-4" />
                      <span>{{ action.label }}</span>
                    </button>
                  </template>
                </template>
              </Dropdown>
            </td>
          </tr>
          <tr v-if="!data || data.length === 0">
            <td :colspan="colspan" class="px-4 py-6 text-center text-sm text-slate-500">{{ emptyMessage }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.table {
  border-collapse: separate;
  border-spacing: 0;
}

/* Cells: no-wrap, ellipsis, left-aligned */
.dt-cell {
  white-space: nowrap;
  text-align: left;
  vertical-align: middle;
}

/* Sticky actions (right) */
.dt-sticky-actions {
  position: sticky;
  right: -1px;
  background: #fff;
  z-index: 40;
}

.dt-actions {
  white-space: nowrap;
  min-width: 8rem;
}

/* Optional sticky first column (left) */
.sticky-left {
  position: sticky;
  left: 0;
  background: #fff;
  z-index: 30;
  min-width: 10rem;
}

/* Subtle shadow separators for sticky edges */
.dt-sticky-actions::after,
.sticky-left::before {
  content: "";
  position: absolute;
  top: 0;
  bottom: 0;
  width: 8px;
  pointer-events: none;
  opacity: 1;
  transition: opacity .15s ease-in-out;
}

.dt-sticky-actions::after {
  right: 100%;
  background: linear-gradient(to left, rgba(0, 0, 0, 0.08), transparent);
}

.sticky-left::before {
  left: 100%;
  background: linear-gradient(to right, rgba(0, 0, 0, 0.08), transparent);
}

/* Hide right-edge shadow when at scroll end (or no overflow) */
.datatable.dt-at-right .dt-sticky-actions::after {
  opacity: 0;
}

/* Hide left-edge shadow when at left edge */
.datatable.dt-at-left .sticky-left::before {
  opacity: 0;
}
</style>
