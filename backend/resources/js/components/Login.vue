<template>
  <div>
    <h2>Login</h2>
    <form @submit.prevent="login">
      <input v-model="email" placeholder="Email" />
      <input type="password" v-model="password" placeholder="Password" />
      <button type="submit">Login</button>
    </form>
    <p v-if="error">{{ error }}</p>
  </div>
</template>

<script>
export default {
  data() {
    return { email: '', password: '', error: '' };
  },
  methods: {
    async login() {
      try {
        const resp = await axios.post('/api/login', { email: this.email, password: this.password });
        localStorage.setItem('api_token', resp.data.token);
        axios.defaults.headers.common['Authorization'] = `Bearer ${resp.data.token}`;
        this.$router.push('/');
      } catch (e) {
        this.error = 'Invalid credentials';
      }
    }
  }
};
</script>