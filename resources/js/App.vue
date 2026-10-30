<template>
  <!-- ログイン状態を確認し終えるまでは何も出さない（画面のちらつき防止） -->
  <template v-if="ready">
    <router-view v-if="isAuthLayout" />

    <div v-else class="app-shell" :class="{ 'is-nav-open': navOpen }">
      <LeftNavigation @navigate="navOpen = false" />
      <div class="nav-backdrop" @click="navOpen = false"></div>

      <div class="app-main">
        <Header @toggle-nav="navOpen = !navOpen" />
        <main class="app-content">
          <router-view />
        </main>
        <Footer />
      </div>
    </div>
  </template>

  <ToastHost />
  <ConfirmHost />
</template>

<script>
import LeftNavigation from './components/LeftNavigation.vue';
import Header from './components/Header.vue';
import Footer from './components/Footer.vue';
import ToastHost from './components/ui/ToastHost.vue';
import ConfirmHost from './components/ui/ConfirmHost.vue';

export default {
  components: {
    LeftNavigation,
    Header,
    Footer,
    ToastHost,
    ConfirmHost,
  },
  data() {
    return { navOpen: false, ready: false };
  },
  computed: {
    isAuthLayout() {
      return !!(this.$route.meta.guest || this.$route.meta.public);
    },
  },
  watch: {
    $route() {
      this.navOpen = false;
    },
  },
  async created() {
    await this.$router.isReady();
    this.ready = true;
  },
};
</script>
