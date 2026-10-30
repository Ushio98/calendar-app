<template>
  <form class="event-form" novalidate @submit.prevent="submit">
    <div class="field">
      <label class="field__label" :for="`${idPrefix}-title`">
        タイトル <span class="field__required">必須</span>
      </label>
      <input
        :id="`${idPrefix}-title`"
        v-model.trim="form.title"
        type="text"
        class="input"
        :class="{ 'is-invalid': errorFor('title') }"
        placeholder="例：企画書を書く"
        maxlength="255"
        autofocus
      />
      <p v-if="errorFor('title')" class="field__error">{{ errorFor('title') }}</p>
    </div>

    <div class="field">
      <span class="field__label">
        期間 <span class="field__required">必須</span>
      </span>
      <div class="field__row field__row--period">
        <input
          :id="`${idPrefix}-start`"
          v-model="form.startDate"
          type="date"
          class="input"
          :class="{ 'is-invalid': errorFor('start_date') }"
          aria-label="開始日"
        />
        <span class="field__separator" aria-hidden="true">〜</span>
        <input
          :id="`${idPrefix}-end`"
          v-model="form.endDate"
          type="date"
          class="input"
          :class="{ 'is-invalid': errorFor('end_date') }"
          :min="form.startDate || undefined"
          aria-label="終了日"
        />
      </div>
      <p v-if="errorFor('start_date') || errorFor('end_date')" class="field__error">
        {{ errorFor('start_date') || errorFor('end_date') }}
      </p>
      <p v-else class="field__hint">終了日を空にすると、開始日だけの予定になります。</p>
    </div>

    <div class="field">
      <span class="field__label">色</span>
      <div class="swatches" role="radiogroup" aria-label="色">
        <label
          v-for="color in colors"
          :key="color.value"
          class="swatch"
          :style="{ '--swatch': color.value }"
          :title="color.name"
        >
          <input v-model="form.color" type="radio" :name="`${idPrefix}-color`" :value="color.value" class="visually-hidden" />
          <span class="swatch__dot"><AppIcon v-if="form.color === color.value" name="check" :size="14" /></span>
          <span class="swatch__name">{{ color.name }}</span>
        </label>
      </div>
    </div>

    <div class="field">
      <label class="field__label" :for="`${idPrefix}-workload`">
        作業量 <span class="field__optional">任意</span>
      </label>
      <div class="field__row">
        <input
          :id="`${idPrefix}-workload`"
          v-model.number="form.workloadValue"
          type="number"
          min="0"
          inputmode="numeric"
          class="input input--short"
          :class="{ 'is-invalid': errorFor('workload_value') }"
          placeholder="例：10"
        />
        <select v-model="form.workloadUnit" class="input input--short" aria-label="作業量の単位">
          <option value="">単位なし</option>
          <option v-for="unit in units" :key="unit.value" :value="unit.value">{{ unit.label }}</option>
        </select>
      </div>
      <p v-if="errorFor('workload_value')" class="field__error">{{ errorFor('workload_value') }}</p>
      <p v-else class="field__hint">作業量を入れると、ダッシュボードの「本日の作業」に表示されます。</p>
    </div>

    <div class="field">
      <label class="field__label" :for="`${idPrefix}-memo`">
        メモ <span class="field__optional">任意</span>
      </label>
      <textarea
        :id="`${idPrefix}-memo`"
        v-model="form.memo"
        class="input input--textarea"
        rows="3"
        placeholder="補足や持ち物など"
      ></textarea>
    </div>

    <div class="form-actions">
      <button type="button" class="btn btn--ghost" :disabled="submitting" @click="$emit('cancel')">キャンセル</button>
      <button type="submit" class="btn btn--primary" :disabled="submitting">
        <span v-if="submitting" class="spinner" aria-hidden="true"></span>
        {{ submitting ? '保存中…' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script>
import AppIcon from './ui/AppIcon.vue';
import { EVENT_COLORS, WORKLOAD_UNITS, DEFAULT_EVENT_COLOR } from '../ui/schedule';

let uid = 0;

export default {
  name: 'EventForm',
  components: { AppIcon },
  props: {
    // { title, startDate, endDate, color, workloadValue, workloadUnit, memo }
    initial: { type: Object, default: () => ({}) },
    submitting: { type: Boolean, default: false },
    // サーバーから返ったバリデーションエラー（422）
    serverErrors: { type: Object, default: () => ({}) },
    submitLabel: { type: String, default: '保存する' },
  },
  emits: ['submit', 'cancel'],
  data() {
    return {
      idPrefix: `event-form-${++uid}`,
      colors: EVENT_COLORS,
      units: WORKLOAD_UNITS,
      clientErrors: {},
      form: {
        title: '',
        startDate: '',
        endDate: '',
        color: DEFAULT_EVENT_COLOR,
        workloadValue: null,
        workloadUnit: '',
        memo: '',
        ...this.initial,
      },
    };
  },
  methods: {
    errorFor(field) {
      if (this.clientErrors[field]) return this.clientErrors[field];
      const messages = this.serverErrors[field];
      return messages && messages.length ? messages[0] : '';
    },
    validate() {
      const errors = {};
      if (!this.form.title) {
        errors.title = 'タイトルを入力してください。';
      }
      if (!this.form.startDate) {
        errors.start_date = '開始日を選んでください。';
      } else if (this.form.endDate && this.form.endDate < this.form.startDate) {
        errors.end_date = '終了日は開始日以降の日付にしてください。';
      }
      if (this.form.workloadValue !== null && this.form.workloadValue !== '' && this.form.workloadValue < 0) {
        errors.workload_value = '作業量は0以上の数字で入力してください。';
      }
      this.clientErrors = errors;
      return Object.keys(errors).length === 0;
    },
    submit() {
      if (!this.validate()) return;
      const workloadValue = this.form.workloadValue === '' ? null : this.form.workloadValue;
      this.$emit('submit', {
        title: this.form.title,
        color: this.form.color,
        memo: this.form.memo || null,
        workload_value: workloadValue,
        workload_unit: workloadValue === null ? null : this.form.workloadUnit || null,
        start_date: this.form.startDate,
        end_date: this.form.endDate || null,
        all_day: false,
        location: null,
      });
    },
  },
};
</script>
