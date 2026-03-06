<template>
  <div>
    <h2>Register</h2>
    <form @submit.prevent="register">
      <input v-model="name" placeholder="Name" />
      <input v-model="email" placeholder="Email" />
      <input type="password" v-model="password" placeholder="Password" />
      <input type="password" v-model="password_confirmation" placeholder="Confirm" />
      <button type="submit">Register</button>
    </form>
    <p v-if="error">{{ error }}</p>
  </div>
</template>

<script>
export default {
  data() {
    return { name:'', email:'', password:'', password_confirmation:'', error:'' };
  },
  methods: {
    async register() {
      try {
        const resp = await axios.post('/api/register', {
          name: this.name,
          email: this.email,
          password: this.password,
          password_confirmation: this.password_confirmation
        });
        // if token returned automatically log the user in
        if (resp.data.token) {
          localStorage.setItem('api_token', resp.data.token);
          axios.defaults.headers.common['Authorization'] = `Bearer ${resp.data.token}`;
          this.$router.push('/');
        } else {
          this.$router.push('/login');
        }
      } catch (e) {
        this.error = 'Validation failed';
      }
    }
  }
};
</script>