<template>
  <div>
    <h2>Categories</h2>
    <form @submit.prevent="create">
      <select v-model.number="restaurant_id">
        <option disabled value="">Select restaurant</option>
        <option v-for="r in restaurants" :key="r.id" :value="r.id">{{ r.name }}</option>
      </select>
      <input v-model="name" placeholder="Name" />
      <button type="submit">Add</button>
    </form>
    <ul>
      <li v-for="c in list" :key="c.id">
        {{ c.name }}
        <button @click="remove(c.id)">x</button>
      </li>
    </ul>
  </div>
</template>
<script>
export default {
  data() {
    return { list: [], name: '', restaurants: [], restaurant_id: '' };
  },
  async mounted() {
    await this.fetch();
    const rRes = await axios.get('/api/restaurants');
    this.restaurants = rRes.data;
  },
  methods: {
    async fetch() {
      const res = await axios.get('/api/categories');
      this.list = res.data;
    },
    async create() {
      await axios.post('/api/categories', { name: this.name, restaurant_id: this.restaurant_id });
      this.name = '';
      this.restaurant_id = '';
      await this.fetch();
    },
    async remove(id) {
      await axios.delete(`/api/categories/${id}`);
      await this.fetch();
    }
  }
};
</script>