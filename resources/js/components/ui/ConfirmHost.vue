<template>
  <BaseModal :open="state.open" :title="state.title" size="sm" @close="settle(false)">
    <p class="confirm-message">{{ state.message }}</p>
    <template #footer>
      <button type="button" class="btn btn--ghost" @click="settle(false)">キャンセル</button>
      <button
        type="button"
        class="btn"
        :class="state.danger ? 'btn--danger' : 'btn--primary'"
        autofocus
        @click="settle(true)"
      >
        {{ state.confirmLabel }}
      </button>
    </template>
  </BaseModal>
</template>

<script>
import BaseModal from './BaseModal.vue';
import { confirmState, settleConfirm } from '../../ui/confirm';

export default {
  name: 'ConfirmHost',
  components: { BaseModal },
  data() {
    return { state: confirmState };
  },
  methods: {
    settle: settleConfirm,
  },
};
</script>
