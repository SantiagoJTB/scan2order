<template>
  <div>
    <h2>Orders</h2>
    <form @submit.prevent="create">
      <select v-model.number="restaurant_id">
        <option disabled value="">Select restaurant</option>
        <option v-for="r in restaurants" :key="r.id" :value="r.id">{{ r.name }}</option>
      </select>
      <select v-model="type">
        <option disabled value="">Type</option>
        <option value="local">Local</option>
        <option value="delivery">Delivery</option>
      </select>
      <select v-model.number="table_id">
        <option disabled value="">Select table</option>
        <option v-for="t in tables" :key="t.id" :value="t.id">Table {{ t.number }}</option>
      </select>
      <button type="submit">New order</button>
    </form>
    <ul>
      <li v-for="o in list" :key="o.id">
        <router-link :to="`/orders/${o.id}`">Order #{{ o.id }} ({{ o.status }})</router-link>
      </li>
    </ul>
  </div>
</template>
<script>
export default {
  data() { return { list: [], tables: [], restaurants: [], restaurant_id: '', table_id: '', type: '' }; },
  async mounted() {
    await this.fetch();
    const tRes = await axios.get('/api/tables');
    this.tables = tRes.data;
    const rRes = await axios.get('/api/restaurants');
    this.restaurants = rRes.data;
  },
  methods: {
    async fetch() {
      const res = await axios.get('/api/orders');
      this.list = res.data;
    },
    async create() {
      await axios.post('/api/orders', {
        restaurant_id: this.restaurant_id,
        table_id: this.table_id,
        type: this.type,
        status: 'pending'
      });
      this.restaurant_id = '';
      this.table_id = '';
      this.type = '';
      await this.fetch();
    }
  }
};
</script>