import { ref, onUnmounted } from "vue";

/**
 * Composable para gerenciar mouse drag com inércia
 * @param {Object} options - Opções de configuração
 * @param {Object} options.viewport - Ref do viewport
 * @param {Function} options.updateArrows - Função para atualizar setas
 * @param {number} options.inertiaDecay - Decaimento da inércia (0-1)
 * @param {number} options.minVelocity - Velocidade mínima para inércia
 * @returns {Object} Handlers e estado do mouse
 */
export function useTimelineMouse(options = {}) {
    const {
        viewport,
        updateArrows = () => {},
        inertiaDecay = 0.92,
        minVelocity = 2,
    } = options;

    const isDragging = ref(false);
    const dragStartX = ref(0);
    const scrollStart = ref(0);
    const lastMouseX = ref(0);
    const velocity = ref(0);
    const inertiaId = ref(null);

    /**
     * Handler para início do mouse down
     * @param {MouseEvent} e - Evento de mouse
     */
    function onMouseDown(e) {
        if (!viewport?.value) return;

        // Só permite drag com botão esquerdo
        if (e.button !== 0) return;

        e.preventDefault();
        isDragging.value = true;
        dragStartX.value = e.clientX;
        scrollStart.value = viewport.value.scrollLeft;
        lastMouseX.value = dragStartX.value;
        velocity.value = 0;

        // Cancela inércia anterior se existir
        if (inertiaId.value) {
            cancelAnimationFrame(inertiaId.value);
            inertiaId.value = null;
        }

        // Adiciona listeners globais
        document.addEventListener("mousemove", onMouseMove);
        document.addEventListener("mouseup", onMouseUp);
    }

    /**
     * Handler para movimento do mouse
     * @param {MouseEvent} e - Evento de mouse
     */
    function onMouseMove(e) {
        if (!isDragging.value || !viewport?.value) return;

        e.preventDefault();
        const x = e.clientX;
        const dx = dragStartX.value - x;

        viewport.value.scrollLeft = scrollStart.value + dx;

        // Calcula velocidade para inércia
        velocity.value = x - lastMouseX.value;
        lastMouseX.value = x;

        updateArrows();
    }

    /**
     * Handler para fim do mouse up
     */
    function onMouseUp() {
        if (!isDragging.value) return;

        isDragging.value = false;

        // Remove listeners globais
        document.removeEventListener("mousemove", onMouseMove);
        document.removeEventListener("mouseup", onMouseUp);

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
        document.removeEventListener("mousemove", onMouseMove);
        document.removeEventListener("mouseup", onMouseUp);
    });

    return {
        // State
        isDragging,

        // Methods
        onMouseDown,
        cancelInertia,
        isCurrentlyDragging,
    };
}
