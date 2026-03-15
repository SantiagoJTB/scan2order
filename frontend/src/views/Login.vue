<template>
  <div class="login-container">
    <div class="login-box">
      <div class="login-header">
        <p class="eyebrow">🍽️ Scan2Order</p>
        <h2>Iniciar Sesión</h2>
        <p class="subtitle">Accede rápido a tus pedidos y restaurantes favoritos desde cualquier dispositivo.</p>
      </div>
      <form @submit.prevent="handleLogin">
        <div class="form-group" v-if="!mfaStep">
          <label for="email">Email:</label>
          <input v-model="email" type="email" id="email" placeholder="tu@email.com" required />
        </div>

        <div class="form-group" v-if="!mfaStep">
          <label for="password">Contraseña:</label>
          <input v-model="password" type="password" id="password" placeholder="••••••••" required />
        </div>

        <div class="form-group" v-if="mfaStep">
          <label for="mfa">Código MFA (correo):</label>
          <input v-model="mfaCode" type="text" id="mfa" inputmode="numeric" maxlength="6" placeholder="123456" required />
          <small class="helper-text">Revisa tu email e ingresa el código de 6 dígitos.</small>
        </div>

        <div v-if="error" class="error-message">{{ error }}</div>
        <div v-if="infoMessage" class="info-message">{{ infoMessage }}</div>

        <button :disabled="isLoading" type="submit" class="btn-login">
          {{ isLoading ? 'Cargando...' : (mfaStep ? 'Verificar código' : 'Ingresar') }}
        </button>

        <button
          v-if="mfaStep"
          type="button"
          class="btn-secondary btn-secondary-light"
          :disabled="isLoading"
          @click="resendMfaCode"
        >
          Reenviar código MFA
        </button>

        <button
          v-if="mfaStep"
          type="button"
          class="btn-secondary"
          :disabled="isLoading"
          @click="resetMfaStep"
        >
          Cambiar credenciales
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
        <p><strong>Cuentas de prueba (local):</strong></p>
        <div class="demo-list">
          <div v-for="account in testAccounts" :key="account.email" class="demo-item">
            <div class="demo-item-info">
              <p class="demo-role">{{ account.role }}</p>
              <p>{{ account.email }} / {{ account.password }}</p>
              <p v-if="account.note" class="demo-note">{{ account.note }}</p>
            </div>
            <button type="button" class="btn-demo" @click="fillTestAccount(account)">Usar</button>
          </div>
        </div>
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
const mfaCode = ref('')
const mfaStep = ref(false)
const isLoading = ref(false)
const error = ref(null)
const infoMessage = ref('')
const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const testAccounts = [
  {
    role: 'Superadmin',
    email: 'superadmin@scan2order.local',
    password: 'superadmin123',
    note: 'Puede pedir MFA por correo',
  },
  {
    role: 'Admin Demo',
    email: 'admin.demo@scan2order.local',
    password: 'admin123',
    note: '',
  },
  {
    role: 'Staff Demo (Caja/Cocina)',
    email: 'staff.demo@scan2order.local',
    password: 'staff123',
    note: '',
  },
  {
    role: 'Cliente Demo',
    email: 'cliente.demo@scan2order.local',
    password: 'cliente123',
    note: '',
  },
]

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
  infoMessage.value = ''

  try {
    await auth.login(email.value, password.value, mfaStep.value ? mfaCode.value : null)
    const redirectTarget = typeof route.query.redirect === 'string'
      ? route.query.redirect
      : getDefaultRouteByRole(auth.userRole)
    router.push(redirectTarget)
  } catch (err) {
    error.value = err.message

    if (err?.mfaRequired) {
      mfaStep.value = true
      mfaCode.value = ''
    }
  } finally {
    isLoading.value = false
  }
}

function resetMfaStep() {
  mfaStep.value = false
  mfaCode.value = ''
  error.value = null
  infoMessage.value = ''
}

async function resendMfaCode() {
  isLoading.value = true
  error.value = null
  infoMessage.value = ''

  try {
    await auth.login(email.value, password.value)
  } catch (err) {
    if (err?.mfaRequired) {
      infoMessage.value = 'Código reenviado. Revisa tu correo.'
      return
    }

    error.value = err.message || 'No se pudo reenviar el código'
  } finally {
    isLoading.value = false
  }
}

function fillTestAccount(account) {
  email.value = account.email
  password.value = account.password
  resetMfaStep()
}
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  padding: max(1rem, env(safe-area-inset-top)) 1rem max(1.5rem, env(safe-area-inset-bottom));
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.login-box {
  background: white;
  border-radius: 24px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  padding: clamp(1.5rem, 4vw, 2.5rem);
  width: 100%;
  max-width: 460px;
}

.login-header {
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

.login-box h2 {
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

.info-message {
  background: #e8f6ff;
  color: #1f618d;
  padding: 0.75rem;
  border-radius: 5px;
  margin-bottom: 1rem;
  font-size: 0.9rem;
}

.btn-login {
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

.btn-secondary {
  width: 100%;
  margin-top: 0.75rem;
  min-height: 50px;
  padding: 0.85rem 1rem;
  background: #ecf0f1;
  color: #2c3e50;
  border: none;
  border-radius: 14px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
}

.btn-secondary:hover:not(:disabled) {
  background: #dde3e6;
}

.btn-secondary-light {
  margin-top: 0.75rem;
  background: #f8f9fa;
}

.helper-text {
  display: block;
  margin-top: 0.5rem;
  color: #7f8c8d;
  font-size: 0.85rem;
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
  min-height: 52px;
  padding: 0.9rem 1rem;
  background: white;
  color: #444;
  border: 2px solid #e0e0e0;
  border-radius: 14px;
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

.demo-list {
  display: grid;
  gap: 0.6rem;
}

.demo-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  background: #fff;
  border-radius: 12px;
  padding: 0.75rem;
}

.demo-item-info {
  min-width: 0;
}

.demo-role {
  font-weight: 700;
  color: #2c3e50;
}

.demo-note {
  color: #7f8c8d;
  font-size: 0.8rem;
}

.btn-demo {
  border: none;
  border-radius: 10px;
  background: #667eea;
  color: #fff;
  font-weight: 600;
  padding: 0.55rem 0.9rem;
  cursor: pointer;
  flex-shrink: 0;
}

.btn-demo:hover {
  opacity: 0.9;
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

@media (max-width: 480px) {
  .login-container {
    align-items: stretch;
  }

  .login-box {
    border-radius: 20px;
    margin: auto 0;
  }

  .login-box h2 {
    font-size: 1.6rem;
  }

  .subtitle {
    font-size: 0.92rem;
  }

  .demo-item {
    flex-direction: column;
    align-items: stretch;
  }

  .btn-demo {
    width: 100%;
  }
}
</style>
