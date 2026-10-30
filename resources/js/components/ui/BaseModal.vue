<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="open" class="modal-overlay" :style="{ zIndex }" @mousedown.self="requestClose">
        <div
          ref="panel"
          class="modal"
          :class="`modal--${size}`"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="titleId"
          tabindex="-1"
        >
          <div v-if="accentColor" class="modal__accent" :style="{ backgroundColor: accentColor }"></div>
          <header class="modal__header">
            <h3 :id="titleId" class="modal__title">{{ title }}</h3>
            <button type="button" class="icon-button" aria-label="閉じる" :disabled="!closable" @click="requestClose">
              <AppIcon name="close" />
            </button>
          </header>
          <div class="modal__body">
            <slot />
          </div>
          <footer v-if="$slots.footer" class="modal__footer">
            <slot name="footer" />
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script>
import AppIcon from './AppIcon.vue';

// 開いているモーダルの重なり順。Esc は一番上のモーダルだけが受け取る
const stack = [];
let uid = 0;

export default {
  name: 'BaseModal',
  components: { AppIcon },
  props: {
    open: { type: Boolean, default: false },
    title: { type: String, default: '' },
    size: { type: String, default: 'md' }, // 'sm' | 'md'
    closable: { type: Boolean, default: true },
    accentColor: { type: String, default: '' },
  },
  emits: ['close'],
  data() {
    return { titleId: `modal-title-${++uid}`, zIndex: 100 };
  },
  watch: {
    open: {
      immediate: true,
      handler(isOpen) {
        if (isOpen) {
          this.activate();
        } else {
          this.deactivate();
        }
      },
    },
  },
  beforeUnmount() {
    this.deactivate();
  },
  methods: {
    activate() {
      if (stack.includes(this)) return;
      stack.push(this);
      // 後から開いたモーダルを必ず手前に表示する
      this.zIndex = 100 + stack.length;
      document.addEventListener('keydown', this.onKeydown);
      document.body.classList.add('is-modal-open');
      this.previousFocus = document.activeElement;
      this.$nextTick(() => {
        const panel = this.$refs.panel;
        if (!panel) return;
        const autofocus = panel.querySelector('[autofocus]');
        (autofocus || panel).focus();
      });
    },
    deactivate() {
      const index = stack.indexOf(this);
      if (index === -1) return;
      stack.splice(index, 1);
      document.removeEventListener('keydown', this.onKeydown);
      if (stack.length === 0) {
        document.body.classList.remove('is-modal-open');
      }
      if (this.previousFocus && this.previousFocus.focus) {
        this.previousFocus.focus();
      }
    },
    onKeydown(event) {
      if (event.key === 'Escape' && stack[stack.length - 1] === this) {
        event.stopPropagation();
        this.requestClose();
      }
    },
    requestClose() {
      if (this.closable) {
        this.$emit('close');
      }
    },
  },
};
</script>
