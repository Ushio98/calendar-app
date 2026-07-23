<template>
  <header class="main-header">
    <nav class="navbar">
      <!-- Left side of header (e.g., brand or toggle button) -->
      <div class="navbar-left">
        <!-- You can add a toggle button for the sidebar here if needed -->
        <a href="/" class="brand-link-header">
          <span class="brand-text-header">Tsumugu（つむぐ）</span>
        </a>
      </div>

      <!-- Right side of header -->
      <div class="navbar-right">
        <span class="current-datetime">{{ formattedDateTime }}</span>
        <span class="username">{{ currentUser }}</span>
      </div>
    </nav>
  </header>
</template>

<script>
export default {
  name: 'Header',
  data() {
    return {
      currentDateTime: new Date(),
      currentUser: 'ログインユーザー', // 実際のユーザー名に置き換える
    };
  },
  computed: {
    formattedDateTime() {
      const options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
      };
      return this.currentDateTime.toLocaleDateString('ja-JP', options);
    },
  },
  mounted() {
    // 1秒ごとに現在日時を更新
    this.timer = setInterval(() => {
      this.currentDateTime = new Date();
    }, 1000);
  },
  beforeUnmount() {
    // コンポーネントが破棄される前にタイマーをクリア
    clearInterval(this.timer);
  },
};
</script>