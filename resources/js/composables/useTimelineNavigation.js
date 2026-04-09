import { ref } from "vue";

/**
 * Composable para gerenciar navegação por teclado e setas
 * @param {Object} options - Opções de configuração
 * @param {number} options.scrollStep - Passo do scroll
 * @param {Function} options.scrollBy - Função de scroll
 * @param {Function} options.scrollToEdge - Função de scroll para extremidades
 * @returns {Object} Handlers e estado da navegação
 */
export function useTimelineNavigation(options = {}) {
    const {
        scrollStep = 320,
        scrollBy = () => {},
        scrollToEdge = () => {},
    } = options;

    const isKeyboardNavigating = ref(false);

    /**
     * Handler para eventos de teclado
     * @param {KeyboardEvent} e - Evento do teclado
     */
    function onKeydown(e) {
        const navigationKeys = ["ArrowLeft", "ArrowRight", "Home", "End"];

        if (navigationKeys.includes(e.key)) {
            e.preventDefault();
            isKeyboardNavigating.value = true;

            switch (e.key) {
                case "ArrowLeft":
                    scrollBy(-scrollStep);
                    break;
                case "ArrowRight":
                    scrollBy(scrollStep);
                    break;
                case "Home":
                    scrollToEdge("start");
                    break;
                case "End":
                    scrollToEdge("end");
                    break;
            }

            // Reset flag after navigation
            setTimeout(() => {
                isKeyboardNavigating.value = false;
            }, 100);
        }
    }

    /**
     * Handler para clique nas setas
     * @param {string} direction - 'left' ou 'right'
     */
    function onArrowClick(direction) {
        const delta = direction === "left" ? -scrollStep : scrollStep;
        scrollBy(delta);
    }

    /**
     * Verifica se está navegando por teclado
     * @returns {boolean}
     */
    function isNavigatingByKeyboard() {
        return isKeyboardNavigating.value;
    }

    return {
        // State
        isKeyboardNavigating,

        // Methods
        onKeydown,
        onArrowClick,
        isNavigatingByKeyboard,
    };
}
