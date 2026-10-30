<template>
  <AuthLayout
    title="パスワードの再設定"
    :lead="sent ? '' : '登録済みのメールアドレスを入力してください。パスワード再設定用のリンクをお送りします。'"
  >
    <div v-if="sent" class="auth-done">
      <div class="auth-done__icon"><AppIcon name="mail" :size="28" /></div>
      <p class="auth-done__title">メールを送信しました</p>
      <p class="auth-done__text">
        <strong>{{ form.email }}</strong> が登録されている場合、パスワード再設定用のリンクを送信しました。
        メールに記載のリンクから、新しいパスワードを設定してください。
      </p>
      <p class="auth-done__hint">数分待ってもメールが届かない場合は、迷惑メールフォルダをご確認いただくか、もう一度お試しください。</p>
      <button type="button" class="btn btn--ghost btn--block" @click="sent = false">別のメールアドレスで試す</button>
    </div>

    <template v-else>
      <div v-if="message" class="alert alert--error" role="alert">
        <AppIcon name="alert" :size="18" />
        <span>{{ message }}</span>
      </div>

      <form class="auth-form" novalidate @submit.prevent="submit">
        <div class="field">
          <label class="field__label" for="forgot-email">メールアドレス</label>
          <input
            id="forgot-email"
            v-model.trim="form.email"
            type="email"
            class="input"
            :class="{ 'is-invalid': errorFor('email') }"
            autocomplete="email"
            inputmode="email"
            placeholder="you@example.com"
            autofocus
          />
          <p v-if="errorFor('email')" class="field__error">{{ errorFor('email') }}</p>
        </div>

        <button type="submit" class="btn btn--primary btn--block" :disabled="submitting">
          <span v-if="submitting" class="spinner" aria-hidden="true"></span>
          {{ submitting ? '送信中…' : '再設定用のリンクを送る' }}
        </button>
      </form>
    </template>

    <template #footer>
      <router-link to="/login" class="text-link text-link--with-icon">
        <AppIcon name="arrowLeft" :size="16" />ログイン画面に戻る
      </router-link>
    </template>
  </AuthLayout>
</template>

<script>
import axios from 'axios';
import AuthLayout from './AuthLayout.vue';
import AppIcon from '../ui/AppIcon.vue';
import { errorsFrom } from '../../ui/auth';

export default {
  name: 'ForgotPasswordPage',
  components: { AuthLayout, AppIcon },
  data() {
    return {
      form: { email: '' },
      sent: false,
      submitting: false,
      clientErrors: {},
      serverErrors: {},
      message: '',
    };
  },
  methods: {
    errorFor(field) {
      if (this.clientErrors[field]) return this.clientErrors[field];
      const messages = this.serverErrors[field];
      return messages && messages.length ? messages[0] : '';
    },
    async submit() {
      this.message = '';
      this.serverErrors = {};
      this.clientErrors = {};
      if (!this.form.email) {
        this.clientErrors = { email: 'メールアドレスを入力してください。' };
        return;
      }

      this.submitting = true;
      try {
        await axios.post('/auth/forgot-password', this.form);
        this.sent = true;
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
