<template>
  <header class="app-header">
    <button type="button" class="icon-button app-header__menu" aria-label="メニューを開く" @click="$emit('toggle-nav')">
      <AppIcon name="menu" />
    </button>
    <h1 class="app-header__title">{{ pageTitle }}</h1>
    <div class="app-header__clock">
      <span class="app-header__date">{{ dateLabel }}</span>
      <time class="app-header__time">{{ timeLabel }}</time>
    </div>
  </header>
</template>

<script>
import AppIcon from './ui/AppIcon.vue';
import { formatDateLong } from '../ui/schedule';

export default {
  name: 'Header',
  components: { AppIcon },
  emits: ['toggle-nav'],
  data() {
    return { now: new Date() };
  },
  computed: {
    pageTitle() {
      return this.$route.meta.title || '';
    },
    dateLabel() {
      return formatDateLong(this.now);
    },
    timeLabel() {
      const h = String(this.now.getHours()).padStart(2, '0');
      const m = String(this.now.getMinutes()).padStart(2, '0');
      return `${h}:${m}`;
    },
  },
  mounted() {
    // 表示は分単位なので、秒が変わるたびではなく分の切り替わりで更新する
    const tick = () => {
      this.now = new Date();
      this.timer = setTimeout(tick, 60000 - (this.now.getSeconds() * 1000 + this.now.getMilliseconds()));
    };
    tick();
  },
  beforeUnmount() {
    clearTimeout(this.timer);
  },
};
</script>
