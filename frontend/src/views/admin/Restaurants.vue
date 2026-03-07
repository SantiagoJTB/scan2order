<template>
  <div v-if="canAccessAdmin" class="restaurants-container">
    <div v-if="toast.show" class="toast" :class="`toast-${toast.type}`">
      {{ toast.message }}
    </div>

    <div class="header">
      <h1>Gestión de Restaurantes</h1>
      <button class="btn-create" @click="openCreateModal">
        + Crear Restaurante
      </button>
    </div>

    <div class="content">
      <div v-if="isLoading" class="loading">Cargando restaurantes...</div>
      <div v-else-if="error" class="error">{{ error }}</div>

      <div v-else-if="restaurants.length === 0" class="empty-state">
        <h2>📭 No hay restaurantes</h2>
        <p>Crea el primero para comenzar.</p>
      </div>

      <div v-else class="restaurants-grid">
        <div v-for="restaurant in restaurants" :key="restaurant.id" class="restaurant-card">
          <div class="restaurant-main">
            <h3 class="restaurant-name">{{ restaurant.name }}</h3>
            <p class="restaurant-address">{{ restaurant.address || 'Sin dirección' }}</p>
            <p class="restaurant-phone">{{ restaurant.phone || 'Sin teléfono' }}</p>
            <p class="restaurant-created">📅 {{ formatDate(restaurant.created_at) }}</p>
            <div class="restaurant-admins">
              <strong>Admins:</strong>
              <div v-if="Array.isArray(restaurant.admins) && restaurant.admins.length" class="admin-lines">
                <div v-for="admin in restaurant.admins" :key="admin.id" class="admin-line">
                  {{ admin.name }}
                  <span v-if="admin.phone"> · {{ admin.phone }}</span>
                  <span v-if="admin.email"> · {{ admin.email }}</span>
                </div>
              </div>
              <span v-else>Sin admins vinculados</span>
            </div>
          </div>
          <div class="restaurant-meta">
            <span class="status-badge" :class="restaurant.active ? 'status-active' : 'status-inactive'">
              {{ restaurant.active ? 'Activo' : 'Inactivo' }}
            </span>
          </div>
          <div class="restaurant-actions">
            <button class="btn-action" @click="openEditModal(restaurant)" title="Editar restaurante">✏️</button>
            <button class="btn-action" @click="openMenuEditor(restaurant)" title="Editar carta/menu">📋</button>
            <button class="btn-action" @click="toggleStatus(restaurant)" title="Cambiar estado">🔄</button>
            <button class="btn-delete" @click="openDeleteModal(restaurant)" title="Eliminar restaurante">🗑️</button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showFormModal" class="modal-overlay">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ isEditing ? 'Editar restaurante' : 'Crear restaurante' }}</h2>
          <button @click="closeFormModal" class="btn-close">×</button>
        </div>

        <form @submit.prevent="saveRestaurant" class="modal-body">
          <div class="form-group">
            <label for="name">Nombre:</label>
            <input id="name" v-model="form.name" type="text" required placeholder="Nombre del restaurante" />
          </div>

          <div class="form-group">
            <label for="address">Dirección:</label>
            <input id="address" v-model="form.address" type="text" placeholder="Dirección" />
          </div>

          <div class="form-group">
            <label for="phone">Teléfono:</label>
            <input id="phone" v-model="form.phone" type="text" placeholder="Teléfono" />
          </div>

          <div class="form-group checkbox-group">
            <label>
              <input v-model="form.active" type="checkbox" />
              Restaurante activo
            </label>
          </div>

          <div class="form-group">
            <label>Admins vinculados:</label>
            <div v-if="isLoadingAdmins" class="admins-loading">Cargando admins...</div>
            <div v-else-if="adminOptions.length === 0" class="admins-empty">No hay admins disponibles</div>
            <div v-else class="admins-list">
              <label v-for="admin in adminOptions" :key="admin.id" class="admin-item">
                <input
                  v-model="form.adminIds"
                  type="checkbox"
                  :value="admin.id"
                  :disabled="isOnlyOwnAdminSelectable && admin.id !== auth.user?.id"
                />
                <span>{{ admin.name }} ({{ admin.email }})</span>
              </label>
            </div>
          </div>

          <div v-if="formError" class="error">{{ formError }}</div>

          <div class="form-actions">
            <button type="button" class="btn-cancel" @click="closeFormModal" :disabled="isSaving">Cancelar</button>
            <button type="submit" class="btn-save" :disabled="isSaving">
              {{ isSaving ? 'Guardando...' : (isEditing ? 'Actualizar' : 'Crear') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="showDeleteModal" class="modal-overlay">
      <div class="modal modal-delete">
        <div class="modal-header">
          <h2>Confirmar eliminación</h2>
          <button @click="closeDeleteModal" class="btn-close">×</button>
        </div>
        <div class="modal-body">
          <p class="delete-message">¿Seguro que quieres eliminar este restaurante?</p>
          <p class="delete-name">{{ restaurantToDelete?.name }}</p>

          <label class="delete-confirm-check">
            <input v-model="deleteConfirmed" type="checkbox" />
            Sí, deseo eliminar este restaurante
          </label>

          <div class="form-actions">
            <button type="button" @click="closeDeleteModal" class="btn-cancel" :disabled="isDeleting">Cancelar</button>
            <button type="button" @click="confirmDelete" class="btn-delete-confirm" :disabled="isDeleting || !deleteConfirmed">
              {{ isDeleting ? 'Eliminando...' : 'Eliminar' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div v-else class="restaurants-container">
    <div class="content">
      <div class="info-box">
        <h2>⛔ Acceso denegado</h2>
        <p>Solo Admin y Superadmin pueden acceder a esta sección.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const canAccessAdmin = computed(() => auth.hasAnyRole(['admin', 'superadmin']))

const restaurants = ref([])
const isLoading = ref(false)
const error = ref(null)

const showFormModal = ref(false)
const showDeleteModal = ref(false)
const isEditing = ref(false)
const isSaving = ref(false)
const isDeleting = ref(false)
const isLoadingAdmins = ref(false)
const formError = ref(null)
const restaurantToDelete = ref(null)
const deleteConfirmed = ref(false)
const adminOptions = ref([])
const isOnlyOwnAdminSelectable = computed(() => auth.userRole === 'admin' && auth.userRole !== 'superadmin')

const toast = ref({ show: false, type: 'success', message: '' })
let toastTimer = null

const form = ref({
  id: null,
  name: '',
  address: '',
  phone: '',
  active: true,
  adminIds: []
})

function showToast(message, type = 'success') {
  if (toastTimer) clearTimeout(toastTimer)
  toast.value = { show: true, type, message }
  toastTimer = setTimeout(() => {
    toast.value.show = false
  }, 2500)
}

function formatDate(date) {
  if (!date) return 'N/A'
  const dateObj = new Date(date)
  const options = { year: 'numeric', month: '2-digit', day: '2-digit' }
  return dateObj.toLocaleDateString('es-ES', options)
}

async function fetchRestaurants() {
  isLoading.value = true
  error.value = null
  try {
    const response = await fetch('/api/restaurants', {
      headers: {
        'Accept': 'application/json',
        ...(auth.token ? { 'Authorization': `Bearer ${auth.token}` } : {})
      }
    })

    if (!response.ok) throw new Error('No se pudo cargar la lista de restaurantes')

    const contentType = response.headers.get('content-type') || ''
    if (!contentType.includes('application/json')) {
      throw new Error('La API devolvió una respuesta inválida')
    }

    const data = await response.json()
    restaurants.value = Array.isArray(data) ? data : []
  } catch (err) {
    error.value = err.message
  } finally {
    isLoading.value = false
  }
}

async function fetchAdminOptions() {
  if (!auth.token) return

  isLoadingAdmins.value = true
  try {
    const response = await fetch('/api/users', {
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok) throw new Error('No se pudo cargar la lista de admins')

    const data = await response.json()
    const users = Array.isArray(data) ? data : []
    const admins = users.filter(user => user?.role?.name === 'admin')

    if (auth.userRole === 'admin' && auth.user) {
      const meExists = admins.some(admin => admin.id === auth.user.id)
      if (!meExists) {
        admins.push({
          id: auth.user.id,
          name: auth.user.name,
          email: auth.user.email
        })
      }
    }

    adminOptions.value = admins
  } catch (err) {
    adminOptions.value = []
    showToast(err.message, 'error')
  } finally {
    isLoadingAdmins.value = false
  }
}

function resetForm() {
  form.value = {
    id: null,
    name: '',
    address: '',
    phone: '',
    active: true,
    adminIds: []
  }
}

async function openCreateModal() {
  isEditing.value = false
  formError.value = null
  resetForm()
  await fetchAdminOptions()

  if (auth.userRole === 'admin' && auth.user) {
    form.value.adminIds = [auth.user.id]
  }

  showFormModal.value = true
}

async function openEditModal(restaurant) {
  isEditing.value = true
  formError.value = null
  await fetchAdminOptions()

  const currentAdminIds = Array.isArray(restaurant.admins)
    ? restaurant.admins.map(admin => admin.id)
    : []

  form.value = {
    id: restaurant.id,
    name: restaurant.name || '',
    address: restaurant.address || '',
    phone: restaurant.phone || '',
    active: Boolean(restaurant.active),
    adminIds: currentAdminIds
  }

  if (auth.userRole === 'admin' && auth.user && !form.value.adminIds.includes(auth.user.id)) {
    form.value.adminIds = [auth.user.id]
  }

  showFormModal.value = true
}

function openMenuEditor(restaurant) {
  router.push({
    path: '/admin/products',
    query: {
      restaurantId: String(restaurant.id),
      restaurantName: restaurant.name || ''
    }
  })
}

function closeFormModal() {
  showFormModal.value = false
  formError.value = null
  resetForm()
}

async function saveRestaurant() {
  if (!canAccessAdmin.value) {
    showToast('No autorizado', 'error')
    return
  }

  isSaving.value = true
  formError.value = null

  try {
    const url = isEditing.value ? `/api/restaurants/${form.value.id}` : '/api/restaurants'
    const method = isEditing.value ? 'PUT' : 'POST'

    const response = await fetch(url, {
      method,
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        name: form.value.name,
        address: form.value.address || null,
        phone: form.value.phone || null,
        active: form.value.active
      })
    })

    const contentType = response.headers.get('content-type') || ''
    let data = null
    if (contentType.includes('application/json')) {
      data = await response.json()
    }

    if (!response.ok) {
      if (data?.errors) {
        const firstError = Object.values(data.errors)[0]
        if (Array.isArray(firstError) && firstError.length > 0) {
          throw new Error(firstError[0])
        }
      }
      throw new Error(data?.message || 'No se pudo guardar el restaurante')
    }

    const restaurantId = data?.id || form.value.id
    const shouldSyncAdmins = restaurantId && (isEditing.value || auth.userRole === 'superadmin' || auth.userRole === 'admin')
    if (shouldSyncAdmins) {
      await syncRestaurantAdmins(restaurantId)
    }

    closeFormModal()
    await fetchRestaurants()
    showToast(isEditing.value ? 'Restaurante actualizado' : 'Restaurante creado', 'success')
  } catch (err) {
    formError.value = err.message
    showToast(err.message, 'error')
  } finally {
    isSaving.value = false
  }
}

async function syncRestaurantAdmins(restaurantId) {
  const response = await fetch(`/api/restaurants/${restaurantId}/admins`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${auth.token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({
      admin_ids: Array.isArray(form.value.adminIds) ? form.value.adminIds : []
    })
  })

  if (!response.ok) {
    const data = await response.json().catch(() => null)
    throw new Error(data?.message || 'No se pudieron actualizar los admins del restaurante')
  }
}

async function toggleStatus(restaurant) {
  if (!canAccessAdmin.value) {
    showToast('No autorizado', 'error')
    return
  }

  try {
    const response = await fetch(`/api/restaurants/${restaurant.id}`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ active: !restaurant.active })
    })

    if (!response.ok) throw new Error('No se pudo actualizar el estado')

    await fetchRestaurants()
    showToast('Estado actualizado', 'success')
  } catch (err) {
    showToast(err.message, 'error')
  }
}

function openDeleteModal(restaurant) {
  restaurantToDelete.value = restaurant
  deleteConfirmed.value = false
  showDeleteModal.value = true
}

function closeDeleteModal() {
  showDeleteModal.value = false
  restaurantToDelete.value = null
  deleteConfirmed.value = false
}

async function confirmDelete() {
  if (!restaurantToDelete.value) return
  if (!canAccessAdmin.value) {
    showToast('No autorizado', 'error')
    return
  }

  isDeleting.value = true

  try {
    const response = await fetch(`/api/restaurants/${restaurantToDelete.value.id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok && response.status !== 204) {
      throw new Error('No se pudo eliminar el restaurante')
    }

    closeDeleteModal()
    await fetchRestaurants()
    showToast('Restaurante eliminado', 'success')
  } catch (err) {
    showToast(err.message, 'error')
  } finally {
    isDeleting.value = false
  }
}

onMounted(() => {
  fetchRestaurants()
})
</script>

<style scoped>
.restaurants-container {
  max-width: 1200px;
  margin: 0 auto;
}

.toast {
  position: fixed;
  top: 1.5rem;
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

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: white;
  margin-bottom: 2rem;
}

.header h1 {
  font-size: 2rem;
  margin: 0;
}

.btn-create {
  background: #27ae60;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 600;
  transition: opacity 0.3s ease;
}

.btn-create:hover {
  opacity: 0.9;
}

.content {
  background: white;
  border-radius: 10px;
  padding: 2rem;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.loading,
.error,
.empty-state {
  text-align: center;
  padding: 2rem;
}

.error {
  background: #ffe6e6;
  color: #c0392b;
  border-radius: 5px;
}

.restaurants-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1rem;
}

.restaurant-card {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  border: 2px solid #e8eef3;
  border-radius: 10px;
  padding: 1rem;
  background: #fff;
  transition: all 0.2s ease;
}

.restaurant-card:hover {
  border-color: #667eea;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.12);
}

.restaurant-name {
  margin: 0 0 0.4rem;
  color: #2c3e50;
}

.restaurant-address,
.restaurant-phone {
  margin: 0.15rem 0;
  color: #7f8c8d;
  font-size: 0.95rem;
}

.restaurant-created {
  margin: 0.2rem 0 0;
  color: #95a5a6;
  font-size: 0.85rem;
  font-weight: 500;
}

.restaurant-admins {
  margin: 0.35rem 0 0;
  color: #34495e;
  font-size: 0.9rem;
}

.admin-lines {
  margin-top: 0.25rem;
}

.admin-line {
  margin: 0.1rem 0;
}

.restaurant-meta {
  display: flex;
  align-items: center;
}

.status-badge {
  display: inline-block;
  padding: 0.35rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
}

.status-active {
  background: #d4edda;
  color: #155724;
}

.status-inactive {
  background: #f8d7da;
  color: #721c24;
}

.restaurant-actions {
  display: flex;
  gap: 0.5rem;
  border-top: 1px solid #ecf0f1;
  padding-top: 0.9rem;
}

.btn-action,
.btn-delete {
  flex: 1;
  padding: 0.55rem 0.7rem;
  border: none;
  border-radius: 6px;
  color: white;
  cursor: pointer;
  transition: opacity 0.3s ease;
}

.btn-action {
  background: #667eea;
}

.btn-delete {
  background: #e74c3c;
}

.btn-action:hover,
.btn-delete:hover {
  opacity: 0.85;
}

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
}

.modal {
  background: white;
  border-radius: 10px;
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

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  color: #2c3e50;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.form-group input {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #ecf0f1;
  border-radius: 5px;
  font-size: 1rem;
}

.form-group input:focus {
  outline: none;
  border-color: #667eea;
}

.checkbox-group label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0;
}

.checkbox-group input {
  width: auto;
}

.admins-loading,
.admins-empty {
  color: #7f8c8d;
  font-size: 0.9rem;
}

.admins-list {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  max-height: 170px;
  overflow-y: auto;
  padding: 0.6rem;
  border: 1px solid #ecf0f1;
  border-radius: 6px;
}

.admin-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0;
  color: #2c3e50;
  font-weight: 500;
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
}

.btn-cancel,
.btn-save,
.btn-delete-confirm {
  flex: 1;
  padding: 0.75rem;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 600;
  transition: opacity 0.3s ease;
}

.btn-cancel {
  background: #ecf0f1;
  color: #2c3e50;
}

.btn-save {
  background: #27ae60;
  color: white;
}

.btn-delete-confirm {
  background: #e74c3c;
  color: white;
}

.btn-cancel:hover,
.btn-save:hover:not(:disabled),
.btn-delete-confirm:hover:not(:disabled) {
  opacity: 0.8;
}

.btn-save:disabled,
.btn-delete-confirm:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.delete-message {
  margin: 0 0 0.75rem;
  color: #2c3e50;
  font-weight: 600;
}

.delete-name {
  margin: 0 0 1rem;
  color: #7f8c8d;
}

.delete-confirm-check {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  color: #2c3e50;
  font-size: 0.95rem;
}

.delete-confirm-check input {
  width: 16px;
  height: 16px;
}

@media (max-width: 768px) {
  .modal {
    margin: 1rem;
  }

  .restaurants-grid {
    grid-template-columns: 1fr;
  }
}
</style>
