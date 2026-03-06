<template>
  <div>
    <h2>Order {{ id }}</h2>
    <div v-if="order">
      <p>Status: {{ order.status }}</p>
      <h3>Items</h3>
      <ul>
        <li v-for="item in order.items" :key="item.id">
          {{ item.product.name }} x{{ item.quantity }} - {{ item.price }}
          <button @click="removeItem(item.id)">x</button>
        </li>
      </ul>
      <form @submit.prevent="addItem">
        <select v-model.number="product_id">
          <option disabled value="">Product</option>
          <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
        <input type="number" v-model.number="quantity" placeholder="Qty" />
        <button type="submit">Add</button>
      </form>

      <h3>Payments</h3>
      <ul>
        <li v-for="pay in order.payments" :key="pay.id">
          {{ pay.amount }} - {{ pay.method }}
        </li>
      </ul>
      <form @submit.prevent="addPayment">
        <input type="number" step="0.01" v-model.number="amount" placeholder="Amount" />
        <input v-model="method" placeholder="Method" />
        <button type="submit">Pay</button>
      </form>
    </div>
  </div>
</template>
<script>
export default {
  props: ['id'],
  data() { return { order: null, products: [], product_id: '', quantity: 1, amount: null, method: '' }; },
  async mounted() {
    await this.load();
    const pRes = await axios.get('/api/products');
    this.products = pRes.data;
  },
  methods: {
    async load() {
      const res = await axios.get(`/api/orders/${this.id}`);
      this.order = res.data;
    },
    async addItem() {
      await axios.post('/api/order-items', { order_id: this.id, product_id: this.product_id, quantity: this.quantity });
      this.product_id = '';
      this.quantity = 1;
      await this.load();
    },
    async removeItem(id) {
      await axios.delete(`/api/order-items/${id}`);
      await this.load();
    },
    async addPayment() {
      await axios.post('/api/payments', { order_id: this.id, amount: this.amount, method: this.method });
      this.amount = null;
      this.method = '';
      await this.load();
    }
  }
};
</script>