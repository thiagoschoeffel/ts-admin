import { ref, computed, nextTick, onMounted, onUnmounted } from "vue";

/**
 * Composable para gerenciar o estado básico do scroll da timeline
 * @param {Object} options - Opções de configuração
 * @param {number} options.arrowMargin - Margem para mostrar/ocultar setas
 * @returns {Object} Estado e funções do scroll
 */
export function useTimelineScroll(options = {}) {
    const { arrowMargin = 20 } = options;

    const viewport = ref(null);
    const track = ref(null);
    const showLeftArrow = ref(false);
    const showRightArrow = ref(false);

    const trackStyle = computed(() => ({
        "scroll-snap-type": "x mandatory",
    }));

    /**
     * Atualiza a visibilidade das setas baseado na posição do scroll
     */
    function updateArrows() {
        if (!viewport.value) return;

        const { scrollLeft, scrollWidth, clientWidth } = viewport.value;

        // Mostra seta esquerda se não está no início
        showLeftArrow.value = scrollLeft > arrowMargin;

        // Mostra seta direita se não está no final
        showRightArrow.value =
            scrollLeft + clientWidth < scrollWidth - arrowMargin;
    }

    /**
     * Faz scroll suave por uma quantidade específica
     * @param {number} delta - Quantidade a scrollar (positivo para direita, negativo para esquerda)
     */
    function scrollBy(delta) {
        if (!viewport.value) return;

        viewport.value.scrollBy({ left: delta, behavior: "smooth" });

        // Atualiza as setas imediatamente e novamente após a animação
        updateArrows();
        setTimeout(updateArrows, 350);
    }

    /**
     * Scroll para uma posição específica
     * @param {number} position - Posição para scrollar
     * @param {Object} options - Opções do scroll
     */
    function scrollTo(position, options = {}) {
        if (!viewport.value) return;

        viewport.value.scrollTo({
            left: position,
            behavior: options.behavior || "smooth",
        });

        updateArrows();
        if (options.behavior !== "instant") {
            setTimeout(updateArrows, 350);
        }
    }

    /**
     * Scroll para uma das extremidades
     * @param {string} edge - 'start' ou 'end'
     */
    function scrollToEdge(edge) {
        if (!viewport.value) return;

        const position = edge === "start" ? 0 : viewport.value.scrollWidth;
        scrollTo(position);
    }

    /**
     * Handler para resize da janela
     */
    function onResize() {
        updateArrows();
    }

    // Lifecycle
    onMounted(() => {
        nextTick(updateArrows);
        window.addEventListener("resize", onResize, { passive: true });
    });

    onUnmounted(() => {
        window.removeEventListener("resize", onResize);
    });

    return {
        // Refs
        viewport,
        track,
        showLeftArrow,
        showRightArrow,

        // Computed
        trackStyle,

        // Methods
        updateArrows,
        scrollBy,
        scrollTo,
        scrollToEdge,
        onResize,
    };
}
