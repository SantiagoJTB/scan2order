<template>
  <div>
    <h2>Health Check</h2>
    <button @click="check">Check</button>
    <p v-if="status">Status: {{ status }}</p>
  </div>
</template>

<script>
export default {
  data() {
    return { status: null };
  },
  methods: {
    async check() {
      try {
        const res = await axios.get('/api/health');
        this.status = res.data.database;
      } catch (e) {
        this.status = 'disconnected';
      }
    }
  }
};
</script>
