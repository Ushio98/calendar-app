<template>
  <div class="wrapper">
    <LeftNavigation />
    <Header />

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
      <!-- Main content -->
      <div class="content">
        <div class="container-fluid">
          <router-view></router-view>
        </div><!-- /.container-fluid -->
      </div>
      <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <Footer />
  </div>
</template>

<script>
import LeftNavigation from './components/LeftNavigation.vue';
import Header from './components/Header.vue';
import Footer from './components/Footer.vue'; // Footerコンポーネントをインポート

export default {
  components: {
    LeftNavigation,
    Header,
    Footer, // Footerコンポーネントを登録
  },
};
</script>