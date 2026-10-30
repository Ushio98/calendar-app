<template>
  <AuthLayout title="ログイン" lead="メールアドレスとパスワードを入力してください。">
    <div v-if="alert" class="alert" :class="`alert--${alert.type}`" role="alert">
      <AppIcon :name="alert.type === 'error' ? 'alert' : 'info'" :size="18" />
      <span>{{ alert.message }}</span>
    </div>

    <form class="auth-form" novalidate @submit.prevent="submit">
      <div class="field">
        <label class="field__label" for="login-email">メールアドレス</label>
        <input
          id="login-email"
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

      <div class="field">
        <div class="field__label-row">
          <label class="field__label" for="login-password">パスワード</label>
          <router-link to="/forgot-password" class="text-link text-link--small">パスワードをお忘れの方</router-link>
        </div>
        <PasswordInput id="login-password" v-model="form.password" :invalid="!!errorFor('password')" />
        <p v-if="errorFor('password')" class="field__error">{{ errorFor('password') }}</p>
      </div>

      <label class="checkbox">
        <input v-model="form.remember" type="checkbox" />
        <span>ログインしたままにする</span>
      </label>

      <button type="submit" class="btn btn--primary btn--block" :disabled="submitting">
        <span v-if="submitting" class="spinner" aria-hidden="true"></span>
        {{ submitting ? 'ログイン中…' : 'ログイン' }}
      </button>
    </form>

    <div class="divider"><span>または</span></div>
    <GoogleButton />

    <template #footer>
      アカウントをお持ちでない方は
      <router-link :to="{ path: '/register', query: $route.query.redirect ? { redirect: $route.query.redirect } : {} }" class="text-link">新規登録</router-link>
    </template>
  </AuthLayout>
</template>

<script>
import AuthLayout from './AuthLayout.vue';
import PasswordInput from './PasswordInput.vue';
import GoogleButton from './GoogleButton.vue';
import AppIcon from '../ui/AppIcon.vue';
import { login, errorsFrom, safeRedirect } from '../../ui/auth';
import { toast } from '../../ui/toast';

const GOOGLE_ERRORS = {
  google_unavailable: 'Googleログインは現在ご利用いただけません。メールアドレスとパスワードでログインしてください。',
  google_failed: 'Googleでのログインに失敗しました。もう一度お試しください。',
  google_unverified: 'このGoogleアカウントのメールアドレスが確認されていないため、ログインできませんでした。',
};

export default {
  name: 'LoginPage',
  components: { AuthLayout, PasswordInput, GoogleButton, AppIcon },
  data() {
    return {
      form: { email: this.$route.query.email || '', password: '', remember: true },
      submitting: false,
      clientErrors: {},
      serverErrors: {},
      message: '',
    };
  },
  computed: {
    alert() {
      if (this.message) return { type: 'error', message: this.message };
      const googleError = GOOGLE_ERRORS[this.$route.query.error];
      if (googleError) return { type: 'error', message: googleError };
      if (this.$route.query.redirect) return { type: 'info', message: '続けるにはログインしてください。' };
      return null;
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
      if (!this.form.email) errors.email = 'メールアドレスを入力してください。';
      if (!this.form.password) errors.password = 'パスワードを入力してください。';
      this.clientErrors = errors;
      return Object.keys(errors).length === 0;
    },
    async submit() {
      this.message = '';
      this.serverErrors = {};
      if (!this.validate()) return;

      this.submitting = true;
      try {
        await login(this.form);
        toast('ログインしました。');
        this.$router.replace(safeRedirect(this.$route.query.redirect));
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
