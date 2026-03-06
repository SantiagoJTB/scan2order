<template>
  <div>
    <h2>Products</h2>
    <form @submit.prevent="create">
      <select v-model.number="restaurant_id">
        <option disabled value="">Select restaurant</option>
        <option v-for="r in restaurants" :key="r.id" :value="r.id">{{ r.name }}</option>
      </select>
      <input v-model="name" placeholder="Name" />
      <input type="number" step="0.01" v-model.number="price" placeholder="Price" />
      <select v-model.number="category_id">
        <option disabled value="">Category</option>
        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
      <button type="submit">Add</button>
    </form>
    <ul>
      <li v-for="p in list" :key="p.id">
        {{ p.name }} - {{ p.price }}
        <button @click="remove(p.id)">x</button>
      </li>
    </ul>
  </div>
</template>
<script>
export default {
  data() { return { list: [], categories: [], restaurants: [], restaurant_id: '', name: '', price: null, category_id: '' }; },
  async mounted() {
    await this.fetch();
    const catRes = await axios.get('/api/categories');
    this.categories = catRes.data;
  },
  methods: {
    async fetch() {
      const res = await axios.get('/api/products');
      this.list = res.data;
    },
    async create() {
      await axios.post('/api/products', { name: this.name, price: this.price, category_id: this.category_id, restaurant_id: this.restaurant_id });
      this.name = '';
      this.price = null;
      this.category_id = '';
      this.restaurant_id = '';
      await this.fetch();
    }
    async remove(id) {
      await axios.delete(`/api/products/${id}`);
      await this.fetch();
    }
  }
};
</script>