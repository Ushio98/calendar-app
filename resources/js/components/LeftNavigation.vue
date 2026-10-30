<template>
  <aside class="app-nav" aria-label="メインメニュー">
    <router-link to="/" class="app-nav__brand" @click="$emit('navigate')">
      <span class="app-nav__logo" aria-hidden="true">紡</span>
      <span class="app-nav__brand-text">
        <span class="app-nav__brand-name">Tsumugu</span>
        <span class="app-nav__brand-kana">つむぐ</span>
      </span>
    </router-link>

    <nav>
      <ul class="app-nav__list">
        <li v-for="item in items" :key="item.to">
          <router-link :to="item.to" class="app-nav__link" exact-active-class="is-active" @click="$emit('navigate')">
            <AppIcon :name="item.icon" />
            <span>{{ item.label }}</span>
          </router-link>
        </li>
      </ul>
    </nav>

    <div v-if="user" class="app-nav__user">
      <div class="app-nav__profile">
        <img v-if="user.avatar" :src="user.avatar" alt="" class="avatar" referrerpolicy="no-referrer" />
        <span v-else class="avatar avatar--initial" aria-hidden="true">{{ initial }}</span>
        <span class="app-nav__profile-text">
          <span class="app-nav__user-name">{{ user.name }}</span>
          <span class="app-nav__user-email">{{ user.email }}</span>
        </span>
      </div>
      <button type="button" class="app-nav__link app-nav__logout" :disabled="loggingOut" @click="confirmLogout">
        <AppIcon name="logout" />
        <span>ログアウト</span>
      </button>
    </div>
  </aside>
</template>

<script>
import AppIcon from './ui/AppIcon.vue';
import { authState, logout } from '../ui/auth';
import { confirmDialog } from '../ui/confirm';
import { toast } from '../ui/toast';

export default {
  name: 'LeftNavigation',
  components: { AppIcon },
  emits: ['navigate'],
  data() {
    return {
      auth: authState,
      loggingOut: false,
      items: [
        { to: '/', label: 'ダッシュボード', icon: 'home' },
        { to: '/events', label: 'カレンダー', icon: 'calendar' },
      ],
    };
  },
  computed: {
    user() {
      return this.auth.user;
    },
    initial() {
      return (this.user.name || this.user.email || '?').trim().charAt(0).toUpperCase();
    },
  },
  methods: {
    async confirmLogout() {
      const ok = await confirmDialog({
        title: 'ログアウトしますか？',
        message: 'もう一度使うときは、メールアドレスとパスワード（またはGoogle）でログインしてください。',
        confirmLabel: 'ログアウト',
      });
      if (!ok) return;

      this.loggingOut = true;
      try {
        await logout();
        toast('ログアウトしました。');
      } catch (error) {
        console.error('Error logging out:', error);
      } finally {
        this.loggingOut = false;
        this.$emit('navigate');
        this.$router.replace('/login');
      }
    },
  },
};
</script>
