import { reactive } from 'vue';

export const confirmState = reactive({
  open: false,
  title: '',
  message: '',
  confirmLabel: '',
  danger: false,
  resolve: null,
});

// ブラウザ標準の confirm() の代わりに使う。結果は Promise<boolean> で返る
export function confirmDialog(options) {
  return new Promise(resolve => {
    Object.assign(confirmState, {
      title: '確認',
      message: '',
      confirmLabel: 'OK',
      danger: false,
      ...options,
      open: true,
      resolve,
    });
  });
}

export function settleConfirm(result) {
  const resolve = confirmState.resolve;
  confirmState.open = false;
  confirmState.resolve = null;
  if (resolve) {
    resolve(result);
  }
}
