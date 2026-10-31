<template>
  <BaseModal
    :open="open"
    :title="project ? 'プロジェクトを編集' : 'プロジェクトを追加'"
    size="sm"
    :closable="!submitting"
    @close="$emit('close')"
  >
    <form v-if="open" class="event-form" novalidate @submit.prevent="submit">
      <div v-if="message" class="alert alert--error" role="alert">
        <AppIcon name="alert" :size="18" />
        <span>{{ message }}</span>
      </div>

      <div class="field">
        <label class="field__label" :for="`${idPrefix}-name`">
          プロジェクト名 <span class="field__required">必須</span>
        </label>
        <input
          :id="`${idPrefix}-name`"
          v-model.trim="form.name"
          type="text"
          class="input"
          :class="{ 'is-invalid': errorFor('name') }"
          placeholder="例：新商品の開発"
          maxlength="100"
          autofocus
        />
        <p v-if="errorFor('name')" class="field__error">{{ errorFor('name') }}</p>
      </div>

      <div class="field">
        <span class="field__label">
          日付 <span class="field__optional">任意</span>
        </span>
        <div class="segmented segmented--grid" role="radiogroup" aria-label="日付の種類">
          <label v-for="option in typeOptions" :key="option.value" class="segmented__option">
            <input
              v-model="form.milestoneType"
              type="radio"
              :name="`${idPrefix}-type`"
              :value="option.value"
              class="visually-hidden"
            />
            <span>{{ option.label }}</span>
          </label>
        </div>
        <template v-if="form.milestoneType">
          <input
            v-model="form.milestoneDate"
            type="date"
            class="input"
            :class="{ 'is-invalid': errorFor('milestone_date') }"
            :aria-label="typeLabel"
          />
          <p v-if="errorFor('milestone_date')" class="field__error">{{ errorFor('milestone_date') }}</p>
          <p v-else class="field__hint">設定した日付はカレンダーにも表示されます。</p>
        </template>
        <p v-if="errorFor('milestone_type')" class="field__error">{{ errorFor('milestone_type') }}</p>
      </div>

      <div class="form-actions">
        <button type="button" class="btn btn--ghost" :disabled="submitting" @click="$emit('close')">キャンセル</button>
        <button type="submit" class="btn btn--primary" :disabled="submitting">
          <span v-if="submitting" class="spinner" aria-hidden="true"></span>
          {{ submitting ? '保存中…' : (project ? '変更を保存' : '追加する') }}
        </button>
      </div>
    </form>
  </BaseModal>
</template>

<script>
import axios from 'axios';
import AppIcon from './ui/AppIcon.vue';
import BaseModal from './ui/BaseModal.vue';
import { errorsFrom } from '../ui/auth';
import { MILESTONE_TYPES, milestoneLabel } from '../ui/project';
import { toast } from '../ui/toast';

let uid = 0;

export default {
  name: 'ProjectFormModal',
  components: { AppIcon, BaseModal },
  props: {
    open: { type: Boolean, default: false },
    // 編集するプロジェクト。null なら新規作成
    project: { type: Object, default: null },
  },
  emits: ['close', 'saved'],
  data() {
    return {
      idPrefix: `project-form-${++uid}`,
      typeOptions: [{ value: '', label: '設定しない' }, ...MILESTONE_TYPES],
      form: { name: '', milestoneType: '', milestoneDate: '' },
      submitting: false,
      clientErrors: {},
      serverErrors: {},
      message: '',
    };
  },
  computed: {
    typeLabel() {
      return milestoneLabel(this.form.milestoneType);
    },
  },
  watch: {
    open: {
      immediate: true,
      handler(isOpen) {
        if (!isOpen) return;
        this.form = {
          name: this.project ? this.project.name : '',
          milestoneType: (this.project && this.project.milestone_type) || '',
          milestoneDate: (this.project && this.project.milestone_date) || '',
        };
        this.clientErrors = {};
        this.serverErrors = {};
        this.message = '';
      },
    },
  },
  methods: {
    errorFor(field) {
      if (this.clientErrors[field]) return this.clientErrors[field];
      const messages = this.serverErrors[field];
      return messages && messages.length ? messages[0] : '';
    },
    validate() {
      const errors = {};
      if (!this.form.name) errors.name = 'プロジェクト名を入力してください。';
      if (this.form.milestoneType && !this.form.milestoneDate) {
        errors.milestone_date = `${this.typeLabel}を選んでください。`;
      }
      this.clientErrors = errors;
      return Object.keys(errors).length === 0;
    },
    async submit() {
      this.message = '';
      this.serverErrors = {};
      if (!this.validate()) return;

      const payload = {
        name: this.form.name,
        milestone_type: this.form.milestoneType || null,
        milestone_date: this.form.milestoneType ? this.form.milestoneDate : null,
      };

      this.submitting = true;
      try {
        const response = this.project
          ? await axios.put(`/api/projects/${this.project.id}`, payload)
          : await axios.post('/api/projects', payload);
        toast(this.project ? 'プロジェクトを更新しました。' : 'プロジェクトを追加しました。');
        this.$emit('saved', response.data);
      } catch (error) {
        const { fields, message } = errorsFrom(error);
        this.serverErrors = fields;
        this.message = message;
      } finally {
        this.submitting = false;
      }
    },
  },
};
</script>
