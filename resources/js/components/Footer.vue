<template>
  <footer class="app-footer">
    <p>&copy; {{ year }} Tsumugu</p>
  </footer>
</template>

<script>
export default {
  name: 'Footer',
  data() {
    return { year: new Date().getFullYear() };
  },
};
</script>
