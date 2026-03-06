<template>
  <div>
    <nav>
      <router-link to="/">Home</router-link> |
      <router-link v-if="!isLogged" to="/login">Login</router-link> |
      <router-link v-if="!isLogged" to="/register">Register</router-link> |
      <router-link v-if="isLogged" to="/restaurants">Restaurants</router-link> |
      <router-link v-if="isLogged" to="/categories">Categories</router-link> |
      <router-link v-if="isLogged" to="/products">Products</router-link> |
      <router-link v-if="isLogged" to="/tables">Tables</router-link> |
      <router-link v-if="isLogged" to="/orders">Orders</router-link> |
      <a v-if="isLogged" href="/docs" target="_blank">API Docs</a> |
      <button v-if="isLogged" @click="logout">Logout</button>
    </nav>
    <router-view />
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'App',
  computed: {
    isLogged() {
      // depend on route to recalc when navigation occurs
      const _ = this.$route;
      return !!localStorage.getItem('api_token');
    }
  },
  methods: {
    async logout() {
      try {
        await axios.post('/api/logout');
      } catch (e) {
        // ignore
      }
      localStorage.removeItem('api_token');
      delete axios.defaults.headers.common['Authorization'];
      this.$router.push('/login');
    }
  }
};
</script>
