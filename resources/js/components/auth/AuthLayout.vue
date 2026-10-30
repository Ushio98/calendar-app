<template>
  <div class="auth-page">
    <div class="auth-panel">
      <router-link to="/login" class="auth-brand">
        <span class="app-nav__logo" aria-hidden="true">紡</span>
        <span class="auth-brand__text">
          <span class="auth-brand__name">Tsumugu</span>
          <span class="auth-brand__kana">つむぐ</span>
        </span>
      </router-link>

      <div class="auth-card">
        <h2 class="auth-card__title">{{ title }}</h2>
        <p v-if="lead" class="auth-card__lead">{{ lead }}</p>
        <slot />
      </div>

      <div v-if="$slots.footer" class="auth-footer">
        <slot name="footer" />
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AuthLayout',
  props: {
    title: { type: String, required: true },
    lead: { type: String, default: '' },
  },
};
</script>
