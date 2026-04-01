<template>
  <footer class="main-footer">
    <p>Copyright &copy; {{ formattedDateTime }} Calendar App.</p>
  </footer>
</template>

<script>
export default {
  name: 'Footer',
  data() {
    return {
      currentDateTime: new Date(),
    };
  },
  computed: {
    formattedDateTime() {
      const options = {
        year: 'numeric',
      };
      return this.currentDateTime.toLocaleDateString('en-US', options);
    },
  },
};
</script>