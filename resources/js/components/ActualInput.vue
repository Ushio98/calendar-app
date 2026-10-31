<template>
  <span class="actual-input">
    <input
      v-model.number="text"
      type="number"
      min="0"
      step="1"
      inputmode="numeric"
      class="input input--compact"
      placeholder="—"
      :aria-label="label"
      @change="save"
      @keydown.enter.prevent="$event.target.blur()"
    />
    <span class="actual-input__status" :class="{ 'is-visible': status === 'saved' }" aria-hidden="true">
      <span v-if="status === 'saving'" class="spinner spinner--dark"></span>
      <AppIcon v-else-if="status === 'saved'" name="check" :size="14" />
    </span>
  </span>
</template>

<script>
import axios from 'axios';
import AppIcon from './ui/AppIcon.vue';
import { errorsFrom } from '../ui/auth';
import { toast } from '../ui/toast';

export default {
  name: 'ActualInput',
  components: { AppIcon },
  props: {
    scheduleId: { type: Number, required: true },
    date: { type: String, required: true }, // YYYY-MM-DD
    value: { type: Number, default: null }, // 保存済みの実績
    label: { type: String, default: '実績' },
    // 保存直後にモーダルが閉じて部品が消えていても親に結果を伝えられるよう、
    // emit ではなく関数の prop で受け取る
    onSaved: { type: Function, default: null },
  },
  data() {
    return {
      text: this.value ?? '',
      sent: this.value, // 最後に保存を依頼した値（同じ値を二重に送らないため）
      status: '', // '' | 'saving' | 'saved'
      requestId: 0,
    };
  },
  watch: {
    value(next) {
      this.text = next ?? '';
      this.sent = next;
    },
  },
  beforeUnmount() {
    clearTimeout(this.statusTimer);
    // Esc で閉じたときなど、入力欄から離れずに消える場合も入力を失わないようにする
    this.save();
  },
  methods: {
    async save() {
      const next = this.text === '' ? null : this.text;
      if (next === this.sent) return;

      if (next !== null && (!Number.isInteger(next) || next < 0)) {
        toast('実績は0以上の整数で入力してください。', 'error');
        this.text = this.value ?? '';
        return;
      }

      // 続けて入力された場合は、最後の保存の結果だけを画面に反映する
      const requestId = ++this.requestId;
      this.sent = next;
      clearTimeout(this.statusTimer);
      this.status = 'saving';
      try {
        await axios.put(`/api/schedules/${this.scheduleId}/actual`, { date: this.date, value: next });
        if (requestId !== this.requestId) return;
        if (this.onSaved) this.onSaved(next);
        this.status = 'saved';
        clearTimeout(this.statusTimer);
        this.statusTimer = setTimeout(() => {
          this.status = '';
        }, 1500);
      } catch (error) {
        if (requestId !== this.requestId) return;
        const { fields, message } = errorsFrom(error);
        const first = Object.values(fields)[0];
        toast(first ? first[0] : message, 'error');
        this.text = this.value ?? '';
        this.sent = this.value;
        this.status = '';
      }
    },
  },
};
</script>
