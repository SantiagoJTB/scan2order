<template>
  <div class="register-container">
    <div class="register-box">
      <div class="register-header">
        <p class="eyebrow">✨ Empieza en minutos</p>
        <h2>Crear Cuenta</h2>
        <p class="subtitle">Regístrate desde móvil o escritorio y comienza a pedir sin complicaciones.</p>
      </div>
      <form @submit.prevent="handleRegister">
        <div class="form-group">
          <label for="name">Nombre:</label>
          <input v-model="name" type="text" id="name" placeholder="Tu nombre completo" required />
        </div>

        <div class="form-group">
          <label for="email">Email:</label>
          <input v-model="email" type="email" id="email" placeholder="tu@email.com" required />
        </div>

        <div class="form-group">
          <label for="password">Contraseña:</label>
          <input v-model="password" type="password" id="password" placeholder="••••••••" required />
        </div>

        <div class="form-group">
          <label for="password-confirm">Confirmar contraseña:</label>
          <input v-model="passwordConfirm" type="password" id="password-confirm" placeholder="••••••••" required />
        </div>

        <div v-if="error" class="error-message">{{ error }}</div>

        <button :disabled="isLoading || password !== passwordConfirm" type="submit" class="btn-register">
          {{ isLoading ? 'Creando cuenta...' : 'Registrarse' }}
        </button>
      </form>

      <div class="login-link">
        <p>¿Ya tienes cuenta? <router-link to="/login">Inicia sesión</router-link></p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirm = ref('')
const isLoading = ref(false)
const error = ref(null)
const router = useRouter()
const auth = useAuthStore()

async function handleRegister() {
  if (password.value !== passwordConfirm.value) {
    error.value = 'Las contraseñas no coinciden'
    return
  }

  isLoading.value = true
  error.value = null

  try {
    await auth.register(name.value, email.value, password.value)
    router.push('/')
  } catch (err) {
    error.value = err.message
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.register-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  padding: max(1rem, env(safe-area-inset-top)) 1rem max(1.5rem, env(safe-area-inset-bottom));
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.register-box {
  background: white;
  border-radius: 24px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  padding: clamp(1.5rem, 4vw, 2.5rem);
  width: 100%;
  max-width: 460px;
}

.register-header {
  text-align: center;
  margin-bottom: 1.5rem;
}

.eyebrow {
  margin: 0 0 0.35rem;
  color: #667eea;
  font-size: 0.9rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.register-box h2 {
  text-align: center;
  color: #2c3e50;
  margin: 0 0 0.6rem;
  font-size: 1.8rem;
}

.subtitle {
  margin: 0;
  color: #7f8c8d;
  font-size: 0.98rem;
  line-height: 1.5;
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
  padding: 0.9rem 1rem;
  border: 2px solid #ecf0f1;
  border-radius: 12px;
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

.btn-register {
  width: 100%;
  min-height: 52px;
  padding: 0.9rem 1rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 14px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.3s ease;
}

.btn-register:hover:not(:disabled) {
  opacity: 0.9;
}

.btn-register:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.login-link {
  text-align: center;
  margin-top: 1.5rem;
  color: #7f8c8d;
}

.login-link a {
  color: #667eea;
  text-decoration: none;
  font-weight: 600;
}

.login-link a:hover {
  text-decoration: underline;
}

@media (max-width: 480px) {
  .register-container {
    align-items: stretch;
  }

  .register-box {
    border-radius: 20px;
    margin: auto 0;
  }

  .register-box h2 {
    font-size: 1.6rem;
  }

  .subtitle {
    font-size: 0.92rem;
  }
}
</style>
