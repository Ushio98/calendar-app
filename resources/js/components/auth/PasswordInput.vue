<template>
  <div class="password-input">
    <input
      :id="id"
      :value="modelValue"
      :type="visible ? 'text' : 'password'"
      class="input"
      :class="{ 'is-invalid': invalid }"
      :autocomplete="autocomplete"
      :placeholder="placeholder"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <button
      type="button"
      class="password-input__toggle"
      :aria-label="visible ? 'パスワードを隠す' : 'パスワードを表示する'"
      :aria-pressed="visible"
      @click="visible = !visible"
    >
      <AppIcon :name="visible ? 'eyeOff' : 'eye'" :size="18" />
    </button>
  </div>
</template>

<script>
import AppIcon from '../ui/AppIcon.vue';

export default {
  name: 'PasswordInput',
  components: { AppIcon },
  props: {
    modelValue: { type: String, default: '' },
    id: { type: String, required: true },
    invalid: { type: Boolean, default: false },
    autocomplete: { type: String, default: 'current-password' },
    placeholder: { type: String, default: '' },
  },
  emits: ['update:modelValue'],
  data() {
    return { visible: false };
  },
};
</script>
