import { reactive } from 'vue';

const toasts = reactive([]);
let idCounter = 0;

export function addToast({ title = '', message = '', type = 'info', duration = 4000 } = {}) {
  const id = ++idCounter;
  const item = { id, title, message, type, duration };
  toasts.push(item);
  setTimeout(() => removeToast(id), duration + 50);
  return id;
}

export function removeToast(id) {
  const i = toasts.findIndex((t) => t.id === id);
  if (i !== -1) toasts.splice(i, 1);
}

export function useToasts() {
  const success = (msg, opts = {}) => addToast({ message: msg, type: 'success', ...opts });
  const error = (msg, opts = {}) => addToast({ message: msg, type: 'error', ...opts });
  const info = (msg, opts = {}) => addToast({ message: msg, type: 'info', ...opts });
  const warn = (msg, opts = {}) => addToast({ message: msg, type: 'warning', ...opts });
  return { toasts, addToast, removeToast, success, error, info, warn };
}

