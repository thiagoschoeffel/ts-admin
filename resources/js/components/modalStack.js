import { reactive } from "vue";

const state = reactive({ stack: [] });
let idCounter = 0;

export function registerModal() {
    const id = ++idCounter;
    state.stack.push(id);
    return id;
}

export function unregisterModal(id) {
    const i = state.stack.indexOf(id);
    if (i !== -1) state.stack.splice(i, 1);
}

export function getIndex(id) {
    return state.stack.indexOf(id);
}

export function hasOpenModals() {
    return state.stack.length > 0;
}
