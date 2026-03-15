<template>
  <div class="profile-container">
    <div v-if="toast.show" class="toast" :class="`toast-${toast.type}`">
      {{ toast.message }}
    </div>

    <div class="profile-header">
      <h1>👤 Mi Perfil</h1>
      <p class="subtitle">Información de tu cuenta</p>
    </div>

    <div class="profile-content">
      <div v-if="isLoading" class="loading">Cargando perfil...</div>

      <div v-else class="profile-card">
        <div class="profile-info">
          <div class="info-group">
            <label>Nombre:</label>
            <p class="info-value">{{ auth.user?.name }}</p>
          </div>

          <div class="info-group">
            <label>Email:</label>
            <p class="info-value">{{ auth.user?.email }}</p>
          </div>

          <div v-if="auth.user?.phone" class="info-group">
            <label>Teléfono:</label>
            <p class="info-value">{{ auth.user?.phone }}</p>
          </div>
        </div>

        <div class="credit-card-section">
          <h3>💳 Tarjeta de crédito</h3>

          <div class="form-group">
            <label for="card-holder">Nombre del titular:</label>
            <input
              id="card-holder"
              v-model="cardForm.holder"
              type="text"
              placeholder="Como aparece en la tarjeta"
            />
          </div>

          <div class="form-group">
            <label for="card-number">Número de tarjeta:</label>
            <input
              id="card-number"
              v-model="cardForm.number"
              type="text"
              inputmode="numeric"
              maxlength="19"
              placeholder="1234 5678 9012 3456"
            />
          </div>

          <div class="card-row">
            <div class="form-group">
              <label for="card-expiry">Vencimiento:</label>
              <input
                id="card-expiry"
                v-model="cardForm.expiry"
                type="text"
                maxlength="5"
                placeholder="MM/AA"
              />
            </div>

            <div class="form-group">
              <label for="card-cvv">CVV:</label>
              <input
                id="card-cvv"
                v-model="cardForm.cvv"
                type="password"
                maxlength="4"
                placeholder="123"
              />
            </div>
          </div>

          <div class="card-actions">
            <button @click="saveCard" class="btn-primary" type="button">
              Guardar tarjeta
            </button>
          </div>
        </div>

        <div class="profile-actions">
          <button @click="goHome" class="btn-primary">
            ← Volver al inicio
          </button>
          <button @click="openDeleteModal" class="btn-danger">
            🗑️ Eliminar mi cuenta
          </button>
        </div>
      </div>
    </div>

    <!-- Delete confirmation modal -->
    <div v-if="showDeleteModal" class="modal-overlay">
      <div class="modal modal-delete">
        <div class="modal-header">
          <h2>Eliminar mi cuenta</h2>
          <button @click="cancelDelete" class="btn-close">×</button>
        </div>

        <div class="modal-body">
          <p class="delete-message">⚠️ Esta acción es permanente y no se puede deshacer</p>
          <p class="delete-warning">
            ¿Estás seguro de que deseas eliminar tu cuenta? Se perderán todos tus datos.
          </p>

          <div class="form-actions">
            <button 
              type="button" 
              @click="cancelDelete" 
              class="btn-cancel" 
              :disabled="isDeleting"
            >
              Cancelar
            </button>
            <button 
              type="button" 
              @click="confirmDelete" 
              class="btn-delete-confirm" 
              :disabled="isDeleting"
            >
              {{ isDeleting ? 'Eliminando...' : 'Eliminar cuenta' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const auth = useAuthStore()
const isLoading = ref(false)
const showDeleteModal = ref(false)
const isDeleting = ref(false)
const toast = ref({ show: false, type: 'success', message: '' })
const cardForm = ref({
  holder: '',
  number: '',
  expiry: '',
  cvv: ''
})
let toastTimer = null

function showToast(message, type = 'success') {
  if (toastTimer) {
    clearTimeout(toastTimer)
  }

  toast.value = {
    show: true,
    type,
    message
  }

  toastTimer = setTimeout(() => {
    toast.value.show = false
  }, 2500)
}

function openDeleteModal() {
  showDeleteModal.value = true
}

function cancelDelete() {
  showDeleteModal.value = false
}

async function confirmDelete() {
  isDeleting.value = true

  try {
    const response = await fetch(`/api/users/${auth.user?.id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Accept': 'application/json'
      }
    })

    const contentType = response.headers.get('content-type') || ''
    let data = null

    if (contentType.includes('application/json')) {
      data = await response.json()
    }

    if (!response.ok) {
      throw new Error(data?.message || 'Error al eliminar la cuenta')
    }

    showToast('Cuenta eliminada correctamente', 'success')
    
    // Logout and redirect
    await auth.logout()
    router.push('/')
  } catch (err) {
    showToast(err.message, 'error')
  } finally {
    isDeleting.value = false
  }
}

function goHome() {
  router.push('/')
}

function saveCard() {
  if (!cardForm.value.holder || !cardForm.value.number || !cardForm.value.expiry || !cardForm.value.cvv) {
    showToast('Completa los datos de la tarjeta', 'error')
    return
  }

  showToast('Tarjeta guardada correctamente', 'success')
}
</script>

<style scoped>
.profile-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: max(1rem, env(safe-area-inset-top)) 1rem max(2rem, env(safe-area-inset-bottom));
}

.toast {
  position: fixed;
  top: calc(env(safe-area-inset-top) + 1rem);
  right: 1.5rem;
  z-index: 2000;
  padding: 0.85rem 1rem;
  border-radius: 8px;
  color: white;
  font-weight: 600;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

.toast-success {
  background: #27ae60;
}

.toast-error {
  background: #e74c3c;
}

.profile-header {
  text-align: center;
  color: white;
  margin-bottom: 2rem;
}

.profile-header h1 {
  font-size: 2.5rem;
  margin: 0 0 0.5rem;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.subtitle {
  font-size: 1.1rem;
  opacity: 0.9;
  margin: 0;
}

.profile-content {
  max-width: 720px;
  margin: 0 auto;
}

.loading {
  text-align: center;
  color: white;
  padding: 2rem;
  font-size: 1.1rem;
}

.profile-card {
  background: white;
  border-radius: 24px;
  padding: clamp(1.5rem, 4vw, 2.25rem);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

.profile-info {
  margin-bottom: 2rem;
}

.credit-card-section {
  border-top: 1px solid #ecf0f1;
  padding-top: 1.5rem;
  margin-top: 0.5rem;
}

.credit-card-section h3 {
  color: #2c3e50;
  margin: 0 0 1rem;
  font-size: 1.2rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  color: #7f8c8d;
  font-size: 0.9rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  text-transform: uppercase;
}

.form-group input {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #ecf0f1;
  border-radius: 8px;
  font-size: 1rem;
}

.form-group input:focus {
  outline: none;
  border-color: #667eea;
}

.card-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.card-actions {
  margin-top: 0.5rem;
}

.info-group {
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #ecf0f1;
}

.info-group:last-child {
  border-bottom: none;
}

.info-group label {
  display: block;
  color: #7f8c8d;
  font-size: 0.9rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  text-transform: uppercase;
}

.info-value {
  color: #2c3e50;
  font-size: 1.1rem;
  margin: 0;
  font-weight: 500;
}

.profile-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin-top: 2rem;
  flex-wrap: wrap;
}

.btn-primary,
.btn-danger {
  min-height: 52px;
  padding: 0.85rem 1.5rem;
  border: none;
  border-radius: 14px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-primary {
  background: #667eea;
  color: white;
}

.btn-primary:hover {
  background: #5568d3;
  transform: translateY(-2px);
}

.btn-danger {
  background: #e74c3c;
  color: white;
}

.btn-danger:hover {
  background: #c0392b;
  transform: translateY(-2px);
}

/* Modal styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal {
  background: white;
  border-radius: 20px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  max-width: 500px;
  width: 100%;
}

.modal-delete {
  max-width: 520px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 2px solid #ecf0f1;
}

.modal-header h2 {
  color: #2c3e50;
  margin: 0;
}

.btn-close {
  background: none;
  border: none;
  font-size: 2rem;
  cursor: pointer;
  color: #7f8c8d;
  padding: 0;
  width: 30px;
  height: 30px;
}

.modal-body {
  padding: 1.5rem;
}

.delete-message {
  margin: 0 0 0.75rem;
  color: #2c3e50;
  font-weight: 600;
}

.delete-warning {
  margin: 0 0 1.5rem;
  color: #7f8c8d;
  line-height: 1.6;
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
}

.btn-cancel,
.btn-delete-confirm {
  flex: 1;
  padding: 0.75rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-cancel {
  background: #ecf0f1;
  color: #2c3e50;
}

.btn-cancel:hover:not(:disabled) {
  background: #d5dbdb;
}

.btn-delete-confirm {
  background: #e74c3c;
  color: white;
}

.btn-delete-confirm:hover:not(:disabled) {
  background: #c0392b;
}

.btn-cancel:disabled,
.btn-delete-confirm:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .toast {
    left: 1rem;
    right: 1rem;
    text-align: center;
  }

  .profile-header h1 {
    font-size: 1.8rem;
  }

  .profile-actions {
    flex-direction: column;
  }

  .card-row {
    grid-template-columns: 1fr;
  }

  .btn-primary,
  .btn-danger {
    width: 100%;
  }
}
</style>
