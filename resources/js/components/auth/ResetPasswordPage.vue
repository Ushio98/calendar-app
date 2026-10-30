<template>
  <AuthLayout title="新しいパスワードの設定" :lead="email ? `${email} のパスワードを再設定します。` : ''">
    <div v-if="!email" class="alert alert--error" role="alert">
      <AppIcon name="alert" :size="18" />
      <span>
        再設定用のリンクが正しくありません。お手数ですが、
        <router-link to="/forgot-password" class="text-link">もう一度お手続き</router-link>ください。
      </span>
    </div>

    <template v-else>
      <div v-if="message" class="alert alert--error" role="alert">
        <AppIcon name="alert" :size="18" />
        <span>
          {{ message }}
          <router-link v-if="tokenInvalid" to="/forgot-password" class="text-link">再設定をやり直す</router-link>
        </span>
      </div>

      <form class="auth-form" novalidate @submit.prevent="submit">
        <div class="field">
          <label class="field__label" for="reset-password">新しいパスワード</label>
          <PasswordInput
            id="reset-password"
            v-model="form.password"
            :invalid="!!errorFor('password')"
            autocomplete="new-password"
          />
          <p v-if="errorFor('password')" class="field__error">{{ errorFor('password') }}</p>
          <p v-else class="field__hint">8文字以上で、英字と数字を両方含めてください。</p>
        </div>

        <div class="field">
          <label class="field__label" for="reset-password-confirm">新しいパスワード（確認）</label>
          <PasswordInput
            id="reset-password-confirm"
            v-model="form.password_confirmation"
            :invalid="!!errorFor('password_confirmation')"
            autocomplete="new-password"
          />
          <p v-if="errorFor('password_confirmation')" class="field__error">{{ errorFor('password_confirmation') }}</p>
        </div>

        <button type="submit" class="btn btn--primary btn--block" :disabled="submitting">
          <span v-if="submitting" class="spinner" aria-hidden="true"></span>
          {{ submitting ? '設定中…' : 'パスワードを変更する' }}
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
import PasswordInput from './PasswordInput.vue';
import AppIcon from '../ui/AppIcon.vue';
import { errorsFrom } from '../../ui/auth';
import { validatePassword } from '../../ui/password';
import { toast } from '../../ui/toast';

export default {
  name: 'ResetPasswordPage',
  components: { AuthLayout, PasswordInput, AppIcon },
  data() {
    return {
      form: { password: '', password_confirmation: '' },
      submitting: false,
      clientErrors: {},
      serverErrors: {},
      message: '',
      tokenInvalid: false,
    };
  },
  computed: {
    token() {
      return this.$route.params.token;
    },
    email() {
      return this.$route.query.email || '';
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
      this.tokenInvalid = false;
      this.serverErrors = {};
      if (!this.validate()) return;

      this.submitting = true;
      try {
        await axios.post('/auth/reset-password', { ...this.form, token: this.token, email: this.email });
        toast('パスワードを変更しました。新しいパスワードでログインしてください。');
        this.$router.replace({ path: '/login', query: { email: this.email } });
      } catch (error) {
        const { fields, message } = errorsFrom(error);
        // トークンやメールアドレスの誤りは email のエラーとして返ってくる
        if (fields.email) {
          this.message = fields.email[0];
          this.tokenInvalid = true;
        } else {
          this.serverErrors = fields;
          this.message = message;
        }
      } finally {
        this.submitting = false;
      }
    },
  },
};
</script>
