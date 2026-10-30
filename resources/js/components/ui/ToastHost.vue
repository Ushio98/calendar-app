<template>
  <div class="toast-host" aria-live="polite">
    <TransitionGroup name="toast">
      <div v-for="item in state.items" :key="item.id" class="toast" :class="`toast--${item.type}`" role="status">
        <AppIcon :name="icons[item.type]" :size="18" />
        <span class="toast__message">{{ item.message }}</span>
        <button type="button" class="toast__close" aria-label="通知を閉じる" @click="dismiss(item.id)">
          <AppIcon name="close" :size="16" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script>
import AppIcon from './AppIcon.vue';
import { toastState, dismissToast } from '../../ui/toast';

export default {
  name: 'ToastHost',
  components: { AppIcon },
  data() {
    return {
      state: toastState,
      icons: { success: 'check', error: 'alert', info: 'info' },
    };
  },
  methods: {
    dismiss: dismissToast,
  },
};
</script>
