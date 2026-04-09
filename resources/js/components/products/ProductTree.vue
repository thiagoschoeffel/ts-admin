<script setup>
import { ref } from 'vue'
import HeroIcon from '@/components/icons/HeroIcon.vue'
import Badge from '@/components/Badge.vue'

const props = defineProps({
  nodes: { type: Array, default: () => [] },
  level: { type: Number, default: 0 },
  parentLines: { type: Array, default: () => [] },
})

const expandedNodes = ref(new Set())

function toggleNode(id) {
  if (expandedNodes.value.has(id)) expandedNodes.value.delete(id)
  else expandedNodes.value.add(id)
}

function isExpanded(id) {
  return expandedNodes.value.has(id)
}

// compute an inline style for children indentation so child rows align
const childIndent = (lvl) => ({ marginLeft: `${(lvl + 1) * 11}px` })

function formatQuantity(quantity) {
  return Number(quantity).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>

<template>
  <div class="product-tree" @click.stop>
    <div v-if="nodes && nodes.length">
      <div v-for="(node, idx) in nodes" :key="node.id" class="tree-item">
        <div class="tree-row">
          <div class="tree-guides">
            <template v-for="(show, i) in parentLines" :key="i">
              <span class="tree-guide" :class="{ 'tree-guide--active': show }"></span>
            </template>

            <span
              class="tree-branch"
              :class="{ 'tree-branch--mid': idx < nodes.length - 1, 'tree-branch--last': idx === nodes.length - 1 }"
            ></span>
          </div>

          <div class="tree-node-content">
            <div class="tree-row-inner">
              <button
                v-if="node.has_children"
                @click.stop="toggleNode(node.id)"
                :aria-expanded="isExpanded(node.id)"
                :aria-label="isExpanded(node.id) ? 'Recolher' : 'Expandir'"
                class="chev-btn"
              >
                <HeroIcon :name="isExpanded(node.id) ? 'chevron-down' : 'chevron-right'" class="w-4 h-4" />
              </button>

              <div class="tree-content">
                <div class="flex items-center gap-4">
                  <span class="font-medium text-slate-900 truncate">{{ node.name }}</span>
                  <span class="text-sm text-slate-500">x&nbsp;&nbsp;&nbsp;{{ formatQuantity(node.quantity) }}&nbsp;&nbsp;&nbsp;{{ node.unit_of_measure }}</span>
                  <Badge :variant="node.status === 'active' ? 'success' : 'danger'">
                    {{ node.status === 'active' ? 'Ativo' : 'Inativo' }}
                  </Badge>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="tree-children" v-if="node.has_children && isExpanded(node.id)" :style="childIndent(level)">
          <ProductTree :nodes="node.children" :level="level + 1" :parent-lines="[...parentLines, idx < nodes.length - 1]" />
        </div>
      </div>
    </div>

    <div v-else class="text-center py-8 text-slate-500">
      <HeroIcon name="cube" class="w-12 h-12 mx-auto mb-2 text-slate-300" />
      <p class="text-sm">Este produto não possui componentes.</p>
    </div>
  </div>
</template>

<style scoped>
.product-tree { display: block; }
.tree-item { display: block; }
.tree-row { display: flex; align-items: center; }
.tree-guides { display: flex; align-items: center; margin-right: 8px; }
.tree-guide { width: 24px; height: 40px; border-left: 2px solid transparent; }
.tree-guide--active { border-color: #cbd5e1; }
.tree-branch { width: 24px; height: 40px; position: relative; }
.tree-branch--mid::before { content: ''; position: absolute; left: 11px; top: 0; bottom: 0; width: 2px; background: #cbd5e1; }
.tree-branch--mid::after { content: ''; position: absolute; left: 11px; top: 50%; width: 13px; height: 2px; background: #cbd5e1; transform: translateY(-50%); }
.tree-branch--last::before { content: ''; position: absolute; left: 11px; top: 0; height: 50%; width: 2px; background: #cbd5e1; }
.tree-branch--last::after { content: ''; position: absolute; left: 11px; top: 50%; width: 13px; height: 2px; background: #cbd5e1; transform: translateY(-50%); }

.tree-node-content { flex: 1 1 0; }
.tree-row-inner { display: flex; align-items: center; gap: 12px; height: 40px; padding: 0 8px; }
.tree-content { padding-left: 4px; }
.chev-btn { display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; }
</style>
