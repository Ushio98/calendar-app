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
  </aside>
</template>

<script>
import AppIcon from './ui/AppIcon.vue';

export default {
  name: 'LeftNavigation',
  components: { AppIcon },
  emits: ['navigate'],
  data() {
    return {
      items: [
        { to: '/', label: 'ダッシュボード', icon: 'home' },
        { to: '/events', label: 'カレンダー', icon: 'calendar' },
      ],
    };
  },
};
</script>
