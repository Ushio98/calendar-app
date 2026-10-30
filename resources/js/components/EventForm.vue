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
      <span class="field__label">
        作業量 <span class="field__optional">任意</span>
      </span>
      <div class="segmented" role="radiogroup" aria-label="作業量の設定方法">
        <label v-for="mode in modes" :key="mode.value" class="segmented__option">
          <input
            v-model="form.workloadMode"
            type="radio"
            :name="`${idPrefix}-workload-mode`"
            :value="mode.value"
            class="visually-hidden"
            @change="onModeChange"
          />
          <span>{{ mode.label }}</span>
        </label>
      </div>

      <!-- 毎日同じ -->
      <template v-if="form.workloadMode === 'fixed'">
        <div class="field__row">
          <input
            :id="`${idPrefix}-workload`"
            v-model.number="form.workloadValue"
            type="number"
            min="0"
            inputmode="numeric"
            class="input input--short"
            :class="{ 'is-invalid': errorFor('workload_value') }"
            placeholder="1日あたり 例：10"
            aria-label="1日あたりの作業量"
          />
          <select v-model="form.workloadUnit" class="input input--short" aria-label="作業量の単位">
            <option value="">単位なし</option>
            <option v-for="unit in units" :key="unit.value" :value="unit.value">{{ unit.label }}</option>
          </select>
        </div>
        <p v-if="errorFor('workload_value')" class="field__error">{{ errorFor('workload_value') }}</p>
        <p v-else class="field__hint">
          期間中の毎日、同じ作業量が「本日の作業」に表示されます。
          <template v-if="periodDates.length > 1 && isFilled(form.workloadValue)">
            （{{ periodDates.length }}日間で合計 {{ form.workloadValue * periodDates.length }}{{ unitLabel }}）
          </template>
        </p>
      </template>

      <!-- 日ごとに設定 -->
      <template v-else-if="form.workloadMode === 'daily'">
        <p v-if="!periodDates.length" class="field__hint">先に期間を選んでください。</p>
        <template v-else>
          <div class="field__row daily-toolbar">
            <select v-model="form.workloadUnit" class="input input--short" aria-label="作業量の単位">
              <option value="">単位なし</option>
              <option v-for="unit in units" :key="unit.value" :value="unit.value">{{ unit.label }}</option>
            </select>
            <div class="daily-toolbar__bulk">
              <input
                v-model.number="bulkValue"
                type="number"
                min="0"
                inputmode="numeric"
                class="input input--compact"
                placeholder="一括"
                aria-label="全日に入れる作業量"
                @keydown.enter.prevent="applyBulk"
              />
              <button type="button" class="btn btn--soft btn--sm" :disabled="!isFilled(bulkValue)" @click="applyBulk">
                全日に入れる
              </button>
            </div>
          </div>

          <ul class="daily-list" :class="{ 'is-invalid': errorFor('daily_workloads') }">
            <li
              v-for="date in periodDates"
              :key="date"
              class="daily-list__row"
              :class="{ 'is-sunday': weekdayOf(date) === 0, 'is-saturday': weekdayOf(date) === 6 }"
            >
              <label class="daily-list__date" :for="`${idPrefix}-daily-${date}`">{{ formatDay(date) }}</label>
              <input
                :id="`${idPrefix}-daily-${date}`"
                v-model.number="form.dailyValues[date]"
                type="number"
                min="0"
                inputmode="numeric"
                class="input input--compact"
                placeholder="—"
              />
              <span class="daily-list__unit">{{ unitLabel }}</span>
            </li>
          </ul>

          <p v-if="errorFor('daily_workloads')" class="field__error">{{ errorFor('daily_workloads') }}</p>
          <p class="daily-total">
            <span>合計 <strong>{{ dailyTotal }}</strong>{{ unitLabel }}</span>
            <span class="daily-total__days">{{ filledDates.length }} / {{ periodDates.length }}日に入力</span>
          </p>
          <p class="field__hint">空欄の日は作業なしとして扱い、「本日の作業」にも表示されません。</p>
        </template>
      </template>

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
import {
  DEFAULT_EVENT_COLOR,
  EVENT_COLORS,
  WORKLOAD_MODES,
  WORKLOAD_UNITS,
  datesBetween,
  formatDateShort,
  formatWorkloadUnit,
  parseYmd,
} from '../ui/schedule';

// 「日ごとに設定」で入力できる最大日数（サーバー側の上限と合わせる）
const MAX_DAILY_DAYS = 366;

let uid = 0;

export default {
  name: 'EventForm',
  components: { AppIcon },
  props: {
    // { title, startDate, endDate, color, workloadMode, workloadValue, workloadUnit, dailyValues, memo }
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
      modes: WORKLOAD_MODES,
      clientErrors: {},
      bulkValue: null,
      form: {
        title: '',
        startDate: '',
        endDate: '',
        color: DEFAULT_EVENT_COLOR,
        workloadValue: null,
        workloadUnit: '',
        memo: '',
        ...this.initial,
        workloadMode: this.initial.workloadMode
          || (this.isFilled(this.initial.workloadValue) ? 'fixed' : 'none'),
        dailyValues: { ...(this.initial.dailyValues || {}) },
      },
    };
  },
  computed: {
    periodDates() {
      if (!this.form.startDate) return [];
      return datesBetween(this.form.startDate, this.form.endDate || this.form.startDate).slice(0, MAX_DAILY_DAYS);
    },
    filledDates() {
      return this.periodDates.filter(date => this.isFilled(this.form.dailyValues[date]));
    },
    dailyTotal() {
      return this.filledDates.reduce((sum, date) => sum + Number(this.form.dailyValues[date]), 0);
    },
    unitLabel() {
      return formatWorkloadUnit(this.form.workloadUnit);
    },
  },
  methods: {
    isFilled(value) {
      return value !== null && value !== undefined && value !== '';
    },
    weekdayOf(date) {
      return parseYmd(date).getDay();
    },
    formatDay(date) {
      return formatDateShort(parseYmd(date));
    },
    onModeChange() {
      // 「毎日同じ」から切り替えたときは、その量を各日の初期値にする
      if (this.form.workloadMode === 'daily' && this.isFilled(this.form.workloadValue) && this.filledDates.length === 0) {
        this.periodDates.forEach(date => {
          this.form.dailyValues[date] = this.form.workloadValue;
        });
      }
    },
    applyBulk() {
      if (!this.isFilled(this.bulkValue) || this.bulkValue < 0) return;
      this.periodDates.forEach(date => {
        this.form.dailyValues[date] = this.bulkValue;
      });
    },
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
      if (this.form.workloadMode === 'fixed') {
        if (!this.isFilled(this.form.workloadValue)) {
          errors.workload_value = '1日あたりの作業量を入力してください。';
        } else if (this.form.workloadValue < 0) {
          errors.workload_value = '作業量は0以上の数字で入力してください。';
        }
      }
      if (this.form.workloadMode === 'daily' && this.form.startDate) {
        const allDates = datesBetween(this.form.startDate, this.form.endDate || this.form.startDate);
        if (allDates.length > MAX_DAILY_DAYS) {
          errors.daily_workloads = `日ごとに設定できるのは${MAX_DAILY_DAYS}日までです。期間を短くしてください。`;
        } else if (this.filledDates.length === 0) {
          errors.daily_workloads = '作業量を1日以上入力してください。';
        } else if (this.filledDates.some(date => this.form.dailyValues[date] < 0)) {
          errors.daily_workloads = '作業量は0以上の数字で入力してください。';
        }
      }
      this.clientErrors = errors;
      return Object.keys(errors).length === 0;
    },
    submit() {
      if (!this.validate()) return;
      const mode = this.form.workloadMode;
      this.$emit('submit', {
        title: this.form.title,
        color: this.form.color,
        memo: this.form.memo || null,
        workload_mode: mode,
        workload_value: mode === 'fixed' ? this.form.workloadValue : null,
        workload_unit: mode === 'none' ? null : this.form.workloadUnit || null,
        // 期間外の日や空欄の日は送らない
        daily_workloads: mode === 'daily'
          ? this.filledDates.map(date => ({ date, value: Number(this.form.dailyValues[date]) }))
          : [],
        start_date: this.form.startDate,
        end_date: this.form.endDate || null,
        all_day: false,
        location: null,
      });
    },
  },
};
</script>
