<template>
  <div>
    <h2>API Test</h2>
    <button @click="fetchApi">Call /api/hello</button>
    <p v-if="message">Response: {{ message }}</p>
  </div>
</template>

<script>
import { ref } from 'vue'

export default {
  name: 'ApiTest',
  setup() {
    const message = ref('')
    const fetchApi = async () => {
      try {
        const res = await fetch('/api/hello')
        const data = await res.json()
        message.value = data.message
      } catch (e) {
        message.value = 'error'
      }
    }
    return { message, fetchApi }
  },
}
</script>
