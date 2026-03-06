<template>
  <div>
    <h2>Restaurants</h2>
    <form @submit.prevent="create">
      <input v-model="name" placeholder="Name" />
      <button type="submit">Add</button>
    </form>
    <ul>
      <li v-for="r in list" :key="r.id">
        {{ r.name }}
        <button @click="remove(r.id)">x</button>
      </li>
    </ul>
  </div>
</template>
<script>
export default {
  data() { return { list: [], name: '' }; },
  async mounted() {
    await this.fetch();
  },
  methods: {
    async fetch() {
      const res = await axios.get('/api/restaurants');
      this.list = res.data;
    },
    async create() {
      await axios.post('/api/restaurants', { name: this.name });
      this.name = '';
      await this.fetch();
    },
    async remove(id) {
      await axios.delete(`/api/restaurants/${id}`);
      await this.fetch();
    }
  }
};
</script>