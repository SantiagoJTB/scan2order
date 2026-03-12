<template>
  <div class="login-container">
    <div class="login-box">
      <h2>Iniciar Sesión</h2>
      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label for="email">Email:</label>
          <input v-model="email" type="email" id="email" placeholder="tu@email.com" required />
        </div>

        <div class="form-group">
          <label for="password">Contraseña:</label>
          <input v-model="password" type="password" id="password" placeholder="••••••••" required />
        </div>

        <div v-if="error" class="error-message">{{ error }}</div>

        <button :disabled="isLoading" type="submit" class="btn-login">
          {{ isLoading ? 'Cargando...' : 'Ingresar' }}
        </button>
      </form>

      <div class="divider">
        <span>o</span>
      </div>

      <button class="btn-google" type="button">
        <span class="google-icon">🔐</span>
        Iniciar sesión con Google
      </button>

      <div class="demo-accounts">
        <p><strong>Cuentas de prueba:</strong></p>
        <p>Superadmin: superadmin@scan2order.local / superadmin123</p>
        <p>Cliente: crea una nueva cuenta usando "Registrarse"</p>
      </div>

      <div class="register-link">
        <p>¿No tienes cuenta? <router-link to="/register">Registrarse</router-link></p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const email = ref('')
const password = ref('')
const isLoading = ref(false)
const error = ref(null)
const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

function getDefaultRouteByRole(role) {
  if (role === 'superadmin' || role === 'admin') return '/admin'
  if (role === 'staff') {
    // Staff should always access via their linked restaurant
    if (auth.staffRestaurantId) {
      return `/staff/${auth.staffRestaurantId}`
    }
    // Fallback: if no restaurant is assigned, go to generic staff dashboard
    return '/staff'
  }
  if (role === 'cliente') return '/restaurants'
  return '/'
}

async function handleLogin() {
  isLoading.value = true
  error.value = null

  try {
    await auth.login(email.value, password.value)
    const redirectTarget = typeof route.query.redirect === 'string'
      ? route.query.redirect
      : getDefaultRouteByRole(auth.userRole)
    router.push(redirectTarget)
  } catch (err) {
    error.value = err.message
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  padding: 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.login-box {
  background: white;
  border-radius: 10px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  padding: 2.5rem;
  width: 100%;
  max-width: 400px;
}

.login-box h2 {
  text-align: center;
  color: #2c3e50;
  margin-bottom: 1.5rem;
  font-size: 1.8rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: #2c3e50;
  font-weight: 600;
}

.form-group input {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #ecf0f1;
  border-radius: 5px;
  font-size: 1rem;
  transition: border-color 0.3s ease;
}

.form-group input:focus {
  outline: none;
  border-color: #667eea;
}

.error-message {
  background: #ffe6e6;
  color: #c0392b;
  padding: 0.75rem;
  border-radius: 5px;
  margin-bottom: 1rem;
  font-size: 0.9rem;
}

.btn-login {
  width: 100%;
  padding: 0.75rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 5px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.3s ease;
}

.btn-login:hover:not(:disabled) {
  opacity: 0.9;
}

.btn-login:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.divider {
  position: relative;
  text-align: center;
  margin: 1.5rem 0;
}

.divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: #e0e0e0;
}

.divider span {
  position: relative;
  background: white;
  padding: 0 1rem;
  color: #7f8c8d;
  font-size: 0.9rem;
}

.btn-google {
  width: 100%;
  padding: 0.75rem;
  background: white;
  color: #444;
  border: 2px solid #e0e0e0;
  border-radius: 5px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.btn-google:hover {
  background: #f8f9fa;
  border-color: #d0d0d0;
}

.google-icon {
  font-size: 1.2rem;
}

.demo-accounts {
  background: #ecf0f1;
  padding: 1rem;
  border-radius: 5px;
  margin: 1.5rem 0;
  font-size: 0.85rem;
  color: #2c3e50;
}

.demo-accounts p {
  margin: 0.5rem 0;
}

.demo-accounts strong {
  display: block;
  margin-bottom: 0.5rem;
}

.register-link {
  text-align: center;
  margin-top: 1.5rem;
  color: #7f8c8d;
}

.register-link a {
  color: #667eea;
  text-decoration: none;
  font-weight: 600;
}

.register-link a:hover {
  text-decoration: underline;
}
</style>
