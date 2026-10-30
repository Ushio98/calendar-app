import { reactive } from 'vue';

export const toastState = reactive({ items: [] });

let nextId = 0;

export function dismissToast(id) {
  const index = toastState.items.findIndex(item => item.id === id);
  if (index !== -1) {
    toastState.items.splice(index, 1);
  }
}

// type: 'success' | 'error' | 'info'
export function toast(message, type = 'success', timeout = 3200) {
  const item = { id: ++nextId, message, type };
  toastState.items.push(item);
  setTimeout(() => dismissToast(item.id), timeout);
}
