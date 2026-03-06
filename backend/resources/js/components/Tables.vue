<template>
  <div>
    <h2>Tables</h2>
    <form @submit.prevent="create">
      <select v-model.number="restaurant_id">
        <option disabled value="">Select restaurant</option>
        <option v-for="r in restaurants" :key="r.id" :value="r.id">{{ r.name }}</option>
      </select>
      <input type="number" v-model.number="number" placeholder="Number" />
      <button type="submit">Add</button>
    </form>
    <ul>
      <li v-for="t in list" :key="t.id">
        Table {{ t.number }}
        <button @click="remove(t.id)">x</button>
      </li>
    </ul>
  </div>
</template>
<script>
export default {
  data() {
    return { list: [], number: null, restaurants: [], restaurant_id: '' };
  },
  async mounted() {
    await this.fetch();
    const rRes = await axios.get('/api/restaurants');
    this.restaurants = rRes.data;
  },
  methods: {
    async fetch() {
      const res = await axios.get('/api/tables');
      this.list = res.data;
    },
    async create() {
      await axios.post('/api/tables', { number: this.number, restaurant_id: this.restaurant_id });
      this.number = null;
      this.restaurant_id = '';
      await this.fetch();
    },
    async remove(id) {
      await axios.delete(`/api/tables/${id}`);
      await this.fetch();
    }
  }
};
</script>