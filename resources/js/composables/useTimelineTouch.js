import { ref, onUnmounted } from "vue";

/**
 * Composable para gerenciar touch/drag com inércia
 * @param {Object} options - Opções de configuração
 * @param {Object} options.viewport - Ref do viewport
 * @param {Function} options.updateArrows - Função para atualizar setas
 * @param {number} options.inertiaDecay - Decaimento da inércia (0-1)
 * @param {number} options.minVelocity - Velocidade mínima para inércia
 * @returns {Object} Handlers e estado do touch
 */
export function useTimelineTouch(options = {}) {
    const {
        viewport,
        updateArrows = () => {},
        inertiaDecay = 0.92,
        minVelocity = 2,
    } = options;

    const isDragging = ref(false);
    const dragStartX = ref(0);
    const scrollStart = ref(0);
    const lastTouchX = ref(0);
    const velocity = ref(0);
    const inertiaId = ref(null);

    /**
     * Handler para início do touch
     * @param {TouchEvent} e - Evento de touch
     */
    function onTouchStart(e) {
        if (!viewport?.value) return;

        isDragging.value = true;
        dragStartX.value = e.touches[0].clientX;
        scrollStart.value = viewport.value.scrollLeft;
        lastTouchX.value = dragStartX.value;
        velocity.value = 0;

        // Cancela inércia anterior se existir
        if (inertiaId.value) {
            cancelAnimationFrame(inertiaId.value);
            inertiaId.value = null;
        }
    }

    /**
     * Handler para movimento do touch
     * @param {TouchEvent} e - Evento de touch
     */
    function onTouchMove(e) {
        if (!isDragging.value || !viewport?.value) return;

        const x = e.touches[0].clientX;
        const dx = dragStartX.value - x;

        viewport.value.scrollLeft = scrollStart.value + dx;

        // Calcula velocidade para inércia
        velocity.value = x - lastTouchX.value;
        lastTouchX.value = x;

        updateArrows();
    }

    /**
     * Handler para fim do touch
     */
    function onTouchEnd() {
        if (!isDragging.value) return;

        isDragging.value = false;

        // Aplica inércia se velocidade for suficiente
        if (Math.abs(velocity.value) > minVelocity) {
            applyInertia();
        } else {
            updateArrows();
        }
    }

    /**
     * Aplica efeito de inércia após o drag
     */
    function applyInertia() {
        if (!viewport?.value) return;

        let v = -velocity.value * 2;

        function inertia() {
            if (!viewport?.value) return;

            viewport.value.scrollLeft += v;
            v *= inertiaDecay;

            if (Math.abs(v) > 1) {
                inertiaId.value = requestAnimationFrame(inertia);
            } else {
                updateArrows();
                inertiaId.value = null;
            }
        }

        inertia();
    }

    /**
     * Cancela qualquer inércia em andamento
     */
    function cancelInertia() {
        if (inertiaId.value) {
            cancelAnimationFrame(inertiaId.value);
            inertiaId.value = null;
        }
    }

    /**
     * Verifica se está arrastando
     * @returns {boolean}
     */
    function isCurrentlyDragging() {
        return isDragging.value;
    }

    // Cleanup
    onUnmounted(() => {
        cancelInertia();
    });

    return {
        // State
        isDragging,

        // Methods
        onTouchStart,
        onTouchMove,
        onTouchEnd,
        cancelInertia,
        isCurrentlyDragging,
    };
}
