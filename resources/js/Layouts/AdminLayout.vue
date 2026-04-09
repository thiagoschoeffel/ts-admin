<script setup>
import { computed, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { route } from '@/ziggy-client';
import Dropdown from '@/components/Dropdown.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import { router } from '@inertiajs/vue3';
import ToastContainer from '@/components/toast/ToastContainer.vue';
import { useToasts } from '@/components/toast/useToasts';
import DropdownDivider from '@/components/DropdownDivider.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const isAdmin = computed(() => user.value?.role === 'admin');
const canViewClients = computed(() => isAdmin.value || !!user.value?.permissions?.clients?.view);
const canViewOrders = computed(() => isAdmin.value || !!user.value?.permissions?.orders?.view);
const canViewProducts = computed(() => isAdmin.value || !!user.value?.permissions?.products?.view);
const canViewSectors = computed(() => isAdmin.value || !!user.value?.permissions?.sectors?.view);
const canViewAlmoxarifados = computed(() => isAdmin.value || !!user.value?.permissions?.almoxarifados?.view);
const canViewMachines = computed(() => isAdmin.value || !!user.value?.permissions?.machines?.view);
const canViewOperators = computed(() => isAdmin.value || !!user.value?.permissions?.operators?.view);
const canViewReasonTypes = computed(() => isAdmin.value || !!user.value?.permissions?.reason_types?.view);
const canViewReasons = computed(() => isAdmin.value || !!user.value?.permissions?.reasons?.view);
const canViewMachineDowntimes = computed(() => isAdmin.value || !!user.value?.permissions?.machine_downtimes?.view);
const canViewRawMaterials = computed(() => isAdmin.value || !!user.value?.permissions?.raw_materials?.view);
const canViewProductionPointings = computed(() => isAdmin.value || !!user.value?.permissions?.production_pointings?.view);
const canViewBlockDispatches = computed(() => isAdmin.value || !!user.value?.permissions?.block_dispatches?.view);
const canViewMoldedDispatches = computed(() => isAdmin.value || !!user.value?.permissions?.molded_dispatches?.view);
const canViewBlockTypes = computed(() => isAdmin.value || !!user.value?.permissions?.block_types?.view);
const canViewMoldTypes = computed(() => isAdmin.value || !!user.value?.permissions?.mold_types?.view);
const canViewSilos = computed(() => isAdmin.value || !!user.value?.permissions?.silos?.view);

// Logout modal state and action
const logoutOpen = ref(false);
const isLoggingOut = ref(false);
const doLogout = async () => {
    if (isLoggingOut.value) return;
    isLoggingOut.value = true;
    try {
        await router.post('/admin/logout');
    } finally {
        isLoggingOut.value = false;
        logoutOpen.value = false;
    }
};

const { success, error } = useToasts();
let lastFlash = '';
watch(() => page.props.flash, (f) => {
    const key = JSON.stringify(f || {});
    if (key === lastFlash) return;
    lastFlash = key;
    if (!f) return;
    if (f.status) success(f.status);
    if (f.success) success(f.success);
    if (f.error) error(f.error);
}, { deep: true, immediate: true });
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900">
        <header class="sticky top-0 z-50 bg-slate-900 text-white">
            <nav class="container-default flex flex-col gap-4 py-4 items-center sm:flex-row sm:justify-between">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-6">
                    <Link class="text-lg font-semibold tracking-tight text-white transition hover:text-blue-200"
                        :href="route('dashboard')">
                    {{ $page.props.app?.name ?? 'Example App' }}
                    </Link>

                    <div class="flex flex-wrap items-center gap-3 text-sm font-semibold text-slate-200 sm:gap-5">
                        <Link class="group transition hover:text-white" :href="route('dashboard')">
                        <span class="inline-flex items-center gap-2">
                            <HeroIcon name="chart-bar" class="h-4 w-4 transition-colors group-hover:text-white" />
                            <span>Dashboard</span>
                        </span>
                        </Link>
                        <Dropdown panelClass="menu-panel" openClass="is-open" align="center">
                            <template #trigger="{ toggle }">
                                <button type="button" class="group transition hover:text-white" @click="toggle">
                                    <span class="inline-flex items-center gap-2">
                                        <HeroIcon name="clipboard-document-list"
                                            class="h-4 w-4 transition-colors group-hover:text-white" />
                                        <span>Cadastros</span>
                                        <HeroIcon name="chevron-down"
                                            class="h-4 w-4 transition-colors group-hover:text-white" />
                                    </span>
                                </button>
                            </template>
                            <template #default>
                                <Link v-if="isAdmin" class="dropdown-link" :href="route('users.index')">
                                <HeroIcon name="users" class="h-5 w-5" />
                                <span>Usuários</span>
                                </Link>
                                <DropdownDivider v-if="isAdmin" />
                                <Link v-if="canViewClients" class="dropdown-link" :href="route('clients.index')">
                                <HeroIcon name="identification" class="h-5 w-5" />
                                <span>Clientes</span>
                                </Link>
                                <Link v-if="canViewProducts" class="dropdown-link" :href="route('products.index')">
                                <HeroIcon name="cube" class="h-5 w-5" />
                                <span>Produtos</span>
                                </Link>
                                <DropdownDivider />
                                <Link v-if="canViewAlmoxarifados" class="dropdown-link"
                                    :href="route('almoxarifados.index')">
                                <HeroIcon name="view-columns" class="h-5 w-5" />
                                <span>Almoxarifados</span>
                                </Link>
                                <Link v-if="canViewSectors" class="dropdown-link" :href="route('sectors.index')">
                                <HeroIcon name="rectangle-group" class="h-5 w-5" />
                                <span>Setores</span>
                                </Link>
                                <Link v-if="canViewMachines" class="dropdown-link" :href="route('machines.index')">
                                <HeroIcon name="cpu-chip" class="h-5 w-5" />
                                <span>Máquinas</span>
                                </Link>
                                <Link v-if="canViewOperators" class="dropdown-link" :href="route('operators.index')">
                                <HeroIcon name="user-group" class="h-5 w-5" />
                                <span>Operadores</span>
                                </Link>
                                <Link v-if="canViewReasonTypes" class="dropdown-link"
                                    :href="route('reason-types.index')">
                                <HeroIcon name="swatch" class="h-5 w-5" />
                                <span>Tipos de Motivos</span>
                                </Link>
                                <Link v-if="canViewReasons" class="dropdown-link" :href="route('reasons.index')">
                                <HeroIcon name="tag" class="h-5 w-5" />
                                <span>Motivos</span>
                                </Link>
                            </template>
                        </Dropdown>
                        <Dropdown panelClass="menu-panel" openClass="is-open" align="center">
                            <template #trigger="{ toggle }">
                                <button type="button" class="group transition hover:text-white" @click="toggle">
                                    <span class="inline-flex items-center gap-2">
                                        <HeroIcon name="arrows-right-left"
                                            class="h-4 w-4 transition-colors group-hover:text-white" />
                                        <span>Movimentações</span>
                                        <HeroIcon name="chevron-down"
                                            class="h-4 w-4 transition-colors group-hover:text-white" />
                                    </span>
                                </button>
                            </template>
                            <template #default>
                                <Link v-if="canViewOrders" class="dropdown-link" :href="route('orders.index')">
                                <HeroIcon name="shopping-bag" class="h-5 w-5" />
                                <span>Pedidos</span>
                                </Link>
                                <DropdownDivider />
                                <Link v-if="canViewMachineDowntimes" class="dropdown-link"
                                    :href="route('machine_downtimes.index')">
                                <HeroIcon name="pause-circle" class="h-5 w-5" />
                                <span class="whitespace-nowrap">Paradas de Máquina</span>
                                </Link>
                            </template>
                        </Dropdown>
                        <Dropdown panelClass="menu-panel" openClass="is-open" align="center">
                            <template #trigger="{ toggle }">
                                <button type="button" class="group transition hover:text-white" @click="toggle">
                                    <span class="inline-flex items-center gap-2">
                                        <HeroIcon name="office-building"
                                            class="h-4 w-4 transition-colors group-hover:text-white" />
                                        <span>CRM</span>
                                        <HeroIcon name="chevron-down"
                                            class="h-4 w-4 transition-colors group-hover:text-white" />
                                    </span>
                                </button>
                            </template>
                            <template #default>
                                <Link class="dropdown-link" :href="route('leads.index')">
                                <HeroIcon name="chat-bubble-left-right" class="h-5 w-5" />
                                <span>Leads</span>
                                </Link>
                                <Link class="dropdown-link" :href="route('opportunities.index')">
                                <HeroIcon name="document-currency-dollar" class="h-5 w-5" />
                                <span>Oportunidades</span>
                                </Link>
                            </template>
                        </Dropdown>
                        <Dropdown panelClass="menu-panel" openClass="is-open" align="center">
                            <template #trigger="{ toggle }">
                                <button type="button" class="group transition hover:text-white" @click="toggle">
                                    <span class="inline-flex items-center gap-2">
                                        <HeroIcon name="folder"
                                            class="h-4 w-4 transition-colors group-hover:text-white" />
                                        <span>Industrialização EPS</span>
                                        <HeroIcon name="chevron-down"
                                            class="h-4 w-4 transition-colors group-hover:text-white" />
                                    </span>
                                </button>
                            </template>
                            <template #default>
                                <Link v-if="canViewRawMaterials" class="dropdown-link"
                                    :href="route('raw-materials.index')">
                                <HeroIcon name="command-line" class="h-5 w-5" />
                                <span class="whitespace-nowrap">Matérias-Primas</span>
                                </Link>
                                <Link v-if="canViewSilos" class="dropdown-link" :href="route('silos.index')">
                                <HeroIcon name="command-line" class="h-5 w-5" />
                                <span class="whitespace-nowrap">Silos</span>
                                </Link>
                                <Link v-if="canViewBlockTypes" class="dropdown-link" :href="route('block-types.index')">
                                <HeroIcon name="command-line" class="h-5 w-5" />
                                <span class="whitespace-nowrap">Tipos de Blocos</span>
                                </Link>
                                <Link v-if="canViewMoldTypes" class="dropdown-link" :href="route('mold-types.index')">
                                <HeroIcon name="command-line" class="h-5 w-5" />
                                <span class="whitespace-nowrap">Tipos de Moldados</span>
                                </Link>
                                <DropdownDivider />
                                <Link v-if="canViewProductionPointings" class="dropdown-link"
                                    :href="route('production-pointings.index')">
                                <HeroIcon name="command-line" class="h-5 w-5" />
                                <span class="whitespace-nowrap">Apontamentos de Produção</span>
                                </Link>
                                <Link v-if="canViewBlockDispatches" class="dropdown-link"
                                    :href="route('block-dispatches.index')">
                                <HeroIcon name="command-line" class="h-5 w-5" />
                                <span class="whitespace-nowrap">Saída de Blocos</span>
                                </Link>
                                <Link v-if="canViewMoldedDispatches" class="dropdown-link"
                                    :href="route('molded-dispatches.index')">
                                <HeroIcon name="command-line" class="h-5 w-5" />
                                <span class="whitespace-nowrap">Saída de Moldados</span>
                                </Link>
                                <DropdownDivider />
                                <Link v-if="canViewRawMaterials || canViewSilos" class="dropdown-link"
                                    :href="route('inventory.movements.index')">
                                <HeroIcon name="command-line" class="h-5 w-5" />
                                <span class="whitespace-nowrap">Movimentos de Estoque</span>
                                </Link>
                                <Link v-if="canViewRawMaterials || canViewSilos" class="dropdown-link"
                                    :href="route('inventory.dashboard')">
                                <HeroIcon name="command-line" class="h-5 w-5" />
                                <span class="whitespace-nowrap">Estoque (Resumo)</span>
                                </Link>
                            </template>
                        </Dropdown>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 sm:justify-end">
                    <template v-if="user">
                        <Dropdown panelClass="user-dropdown" openClass="is-open">
                            <template #trigger="{ toggle }">
                                <button type="button" class="user-toggle" @click="toggle">
                                    {{ (user?.name || 'U').toString().trim().charAt(0).toUpperCase() }}
                                </button>
                            </template>
                            <template #default>
                                <Link class="dropdown-link" :href="route('profile.edit')">
                                <HeroIcon name="user-circle" class="h-5 w-5" />
                                <span>Meu perfil</span>
                                </Link>
                                <button type="button" class="dropdown-link-danger" @click="logoutOpen = true">
                                    <HeroIcon name="arrow-left-end-on-rectangle" class="h-5 w-5" />
                                    <span>Sair</span>
                                </button>
                            </template>
                        </Dropdown>
                        <ConfirmModal v-model="logoutOpen" title="Encerrar sessão"
                            message="Tem certeza que deseja sair da sua conta?" confirm-text="Sair"
                            :processing="isLoggingOut" @confirm="doLogout" />
                    </template>
                </div>
            </nav>
        </header>

        <main class="container-default py-10 pt-20">
            <slot />
        </main>

        <footer class="container-default py-8 text-center text-sm text-slate-500">
            &copy; {{ new Date().getFullYear() }} {{ $page.props.app?.name ?? 'Example App' }}. Todos os direitos
            reservados.
        </footer>
    </div>
    <ToastContainer />
</template>

<style scoped>
.container-default {
    max-width: 72rem;
    margin: 0 auto;
    padding-left: 1rem;
    padding-right: 1rem;
}

.btn-ghost {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .5rem .75rem;
    border-radius: .5rem;
    border: 1px solid #cbd5e1;
    color: #0f172a;
}
</style>
