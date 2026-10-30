<template>
  <div class="app-shell" :class="{ 'is-nav-open': navOpen }">
    <LeftNavigation @navigate="navOpen = false" />
    <div class="nav-backdrop" @click="navOpen = false"></div>

    <div class="app-main">
      <Header @toggle-nav="navOpen = !navOpen" />
      <main class="app-content">
        <router-view />
      </main>
      <Footer />
    </div>

    <ToastHost />
    <ConfirmHost />
  </div>
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
    return { navOpen: false };
  },
  watch: {
    $route() {
      this.navOpen = false;
    },
  },
};
</script>
