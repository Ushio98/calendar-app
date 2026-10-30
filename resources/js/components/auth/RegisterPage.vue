<template>
  <AuthLayout title="新規登録" lead="メールアドレスがログインIDになります。">
    <div v-if="message" class="alert alert--error" role="alert">
      <AppIcon name="alert" :size="18" />
      <span>{{ message }}</span>
    </div>

    <GoogleButton label="Googleで登録" />
    <div class="divider"><span>またはメールアドレスで登録</span></div>

    <form class="auth-form" novalidate @submit.prevent="submit">
      <div class="field">
        <label class="field__label" for="register-name">名前</label>
        <input
          id="register-name"
          v-model.trim="form.name"
          type="text"
          class="input"
          :class="{ 'is-invalid': errorFor('name') }"
          autocomplete="name"
          placeholder="山田 太郎"
          maxlength="255"
        />
        <p v-if="errorFor('name')" class="field__error">{{ errorFor('name') }}</p>
      </div>

      <div class="field">
        <label class="field__label" for="register-email">メールアドレス</label>
        <input
          id="register-email"
          v-model.trim="form.email"
          type="email"
          class="input"
          :class="{ 'is-invalid': errorFor('email') }"
          autocomplete="email"
          inputmode="email"
          placeholder="you@example.com"
        />
        <p v-if="errorFor('email')" class="field__error">{{ errorFor('email') }}</p>
      </div>

      <div class="field">
        <label class="field__label" for="register-password">パスワード</label>
        <PasswordInput
          id="register-password"
          v-model="form.password"
          :invalid="!!errorFor('password')"
          autocomplete="new-password"
        />
        <p v-if="errorFor('password')" class="field__error">{{ errorFor('password') }}</p>
        <p v-else class="field__hint">8文字以上で、英字と数字を両方含めてください。</p>
      </div>

      <div class="field">
        <label class="field__label" for="register-password-confirm">パスワード（確認）</label>
        <PasswordInput
          id="register-password-confirm"
          v-model="form.password_confirmation"
          :invalid="!!errorFor('password_confirmation')"
          autocomplete="new-password"
        />
        <p v-if="errorFor('password_confirmation')" class="field__error">{{ errorFor('password_confirmation') }}</p>
      </div>

      <button type="submit" class="btn btn--primary btn--block" :disabled="submitting">
        <span v-if="submitting" class="spinner" aria-hidden="true"></span>
        {{ submitting ? '登録中…' : '登録する' }}
      </button>
    </form>

    <template #footer>
      すでにアカウントをお持ちの方は
      <router-link :to="{ path: '/login', query: $route.query.redirect ? { redirect: $route.query.redirect } : {} }" class="text-link">ログイン</router-link>
    </template>
  </AuthLayout>
</template>

<script>
import AuthLayout from './AuthLayout.vue';
import PasswordInput from './PasswordInput.vue';
import GoogleButton from './GoogleButton.vue';
import AppIcon from '../ui/AppIcon.vue';
import { register, errorsFrom, safeRedirect } from '../../ui/auth';
import { validatePassword } from '../../ui/password';
import { toast } from '../../ui/toast';

export default {
  name: 'RegisterPage',
  components: { AuthLayout, PasswordInput, GoogleButton, AppIcon },
  data() {
    return {
      form: { name: '', email: '', password: '', password_confirmation: '' },
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
    validate() {
      const errors = {};
      if (!this.form.name) errors.name = '名前を入力してください。';
      if (!this.form.email) {
        errors.email = 'メールアドレスを入力してください。';
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) {
        errors.email = 'メールアドレスの形式が正しくありません。';
      }
      const passwordError = validatePassword(this.form.password);
      if (passwordError) errors.password = passwordError;
      if (!passwordError && this.form.password !== this.form.password_confirmation) {
        errors.password_confirmation = 'パスワードが一致しません。';
      }
      this.clientErrors = errors;
      return Object.keys(errors).length === 0;
    },
    async submit() {
      this.message = '';
      this.serverErrors = {};
      if (!this.validate()) return;

      this.submitting = true;
      try {
        await register(this.form);
        toast('アカウントを作成しました。ようこそ！');
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
