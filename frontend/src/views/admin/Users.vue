<template>
  <div class="users-container">
    <div v-if="toast.show" class="toast" :class="`toast-${toast.type}`">
      {{ toast.message }}
    </div>

    <div class="header">
      <h1>Gestión de Usuarios</h1>
      <button v-if="canManageUsers" @click="showCreateForm = true" class="btn-create">
        + Crear Usuario
      </button>
    </div>

    <div class="content">
      <div v-if="isLoading" class="loading">Cargando usuarios...</div>
      <div v-if="error" class="error">{{ error }}</div>

      <!-- Vista para SUPERADMIN: Usuarios agrupados -->
      <div v-if="!isLoading && auth.hasRole('superadmin')">
        <!-- Sección de Admins -->
        <div v-if="adminGroups.length > 0" class="section">
          <h2 class="section-title">👨‍💼 Administradores y sus equipos</h2>
          
          <div v-for="group in adminGroups" :key="group.admin.id" class="admin-group">
            <div class="admin-card">
              <div class="user-info">
                <div class="user-avatar">👤</div>
                <div class="user-details clickable" @click="viewRelatedUsers(group.admin)">
                  <div class="user-name admin-name-clickable">{{ group.admin.name }}</div>
                  <div class="user-email">{{ group.admin.email }}</div>
                </div>
              </div>
              <div class="user-meta">
                <span class="badge badge-admin">Admin</span>
                <span class="status-badge" :class="`status-${group.admin.status}`">
                  {{ group.admin.status }}
                </span>
              </div>
              <div class="user-actions">
                <button 
                  v-if="canChangeStatus(group.admin)"
                  @click="changeStatus(group.admin)" 
                  class="btn-action" 
                  title="Cambiar estado"
                >
                  🔄
                </button>
                <button 
                  v-if="canDeleteUser(group.admin)"
                  @click="openDeleteModal(group.admin)" 
                  class="btn-delete" 
                  title="Eliminar usuario"
                >
                  🗑️
                </button>
              </div>
            </div>

            <!-- Caja y Cocina del admin -->
            <div v-if="group.team && group.team.length > 0" class="team-members">
              <div v-for="member in group.team" :key="member.id" class="team-card">
                <div class="user-info">
                  <div class="user-avatar-small">
                    {{ member.role?.name === 'caja' ? '💰' : '👨‍🍳' }}
                  </div>
                  <div class="user-details">
                    <div class="user-name-small">{{ member.name }}</div>
                    <div class="user-email-small">{{ member.email }}</div>
                  </div>
                </div>
                <div class="user-meta">
                  <span class="badge" :class="`badge-${member.role?.name}`">
                    {{ member.role?.name }}
                  </span>
                  <span class="status-badge" :class="`status-${member.status}`">
                    {{ member.status }}
                  </span>
                </div>
                <div class="user-actions">
                  <button 
                    v-if="canChangeStatus(member)"
                    @click="changeStatus(member)" 
                    class="btn-action-small" 
                    title="Cambiar estado"
                  >
                    🔄
                  </button>
                  <button 
                    v-if="canDeleteUser(member)"
                    @click="openDeleteModal(member)" 
                    class="btn-delete-small" 
                    title="Eliminar usuario"
                  >
                    🗑️
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sección de Clientes -->
        <div class="section">
          <h2 class="section-title">👥 Usuarios clientes ({{ clientUsers.length }})</h2>
          <div v-if="clientUsers.length > 0" class="users-grid">
            <div v-for="client in clientUsers" :key="client.id" class="client-card">
              <div class="user-info">
                <div class="user-avatar">🛒</div>
                <div class="user-details">
                  <div class="user-name">{{ client.name }}</div>
                  <div class="user-email">{{ client.email }}</div>
                  <div v-if="client.phone" class="user-phone">{{ client.phone }}</div>
                </div>
              </div>
              <div class="user-meta">
                <span class="badge badge-cliente">Cliente</span>
                <span class="status-badge" :class="`status-${client.status}`">
                  {{ client.status }}
                </span>
              </div>
              <div class="user-actions">
                <button 
                  v-if="canChangeStatus(client)"
                  @click="changeStatus(client)" 
                  class="btn-action" 
                  title="Cambiar estado"
                >
                  🔄
                </button>
                <button 
                  v-if="canDeleteUser(client)"
                  @click="openDeleteModal(client)" 
                  class="btn-delete" 
                  title="Eliminar usuario"
                >
                  🗑️
                </button>
              </div>
            </div>
          </div>
          <div v-else class="empty-section">
            <p>📭 No hay clientes registrados todavía</p>
          </div>
        </div>
      </div>

      <!-- Vista para ADMIN: Lista simple de sus usuarios -->
      <div v-else-if="!isLoading && users.length > 0" class="users-table">
        <div class="table-header">
          <div class="col-name">Nombre</div>
          <div class="col-email">Email</div>
          <div class="col-role">Rol</div>
          <div class="col-status">Estado</div>
          <div class="col-actions">Acciones</div>
        </div>

        <div v-for="user in users" :key="user.id" class="table-row">
          <div class="col-name">{{ user.name }}</div>
          <div class="col-email">{{ user.email }}</div>
          <div class="col-role">
            <span class="badge" :class="`badge-${user.role?.name}`">
              {{ user.role?.name || 'Sin rol' }}
            </span>
          </div>
          <div class="col-status">
            <span class="status-badge" :class="`status-${user.status}`">
              {{ user.status }}
            </span>
          </div>
          <div class="col-actions">
            <button 
              v-if="canChangeStatus(user)"
              @click="changeStatus(user)" 
              class="btn-action"
            >
              Cambiar estado
            </button>
            <button 
              v-if="canDeleteUser(user)"
              @click="openDeleteModal(user)" 
              class="btn-delete"
            >
              Eliminar
            </button>
          </div>
        </div>
      </div>

      <div v-else-if="!isLoading" class="no-users">No hay usuarios</div>
    </div>

    <!-- Create user modal -->
    <div v-if="showCreateForm" class="modal-overlay">
      <div class="modal">
        <div class="modal-header">
          <h2>Crear nuevo usuario</h2>
          <button @click="showCreateForm = false" class="btn-close">×</button>
        </div>

        <form @submit.prevent="createUser" class="modal-body">
          <div class="form-group">
            <label for="name">Nombre:</label>
            <input
              v-model="newUser.name"
              type="text"
              id="name"
              placeholder="Nombre completo"
              required
            />
          </div>

          <div class="form-group">
            <label for="email">Email:</label>
            <input
              v-model="newUser.email"
              type="email"
              id="email"
              placeholder="email@example.com"
              required
            />
          </div>

          <div class="form-group">
            <label for="password">Contraseña:</label>
            <input
              v-model="newUser.password"
              type="password"
              id="password"
              placeholder="••••••••"
              required
            />
          </div>

          <div class="form-group">
            <label for="phone">Teléfono:</label>
            <input
              v-model="newUser.phone"
              type="tel"
              id="phone"
              placeholder="Teléfono"
            />
          </div>

          <div class="form-group">
            <label for="role">Rol:</label>
            <select v-model="newUser.role" id="role" required>
              <option value="">Seleccionar rol</option>
              <option v-if="auth.hasRole('superadmin')" value="admin">Admin</option>
              <option value="caja">Caja</option>
              <option value="cocina">Cocina</option>
              <option v-if="auth.hasRole('superadmin')" value="cliente">Cliente</option>
            </select>
          </div>

          <div v-if="createError" class="error">{{ createError }}</div>

          <div class="form-actions">
            <button type="button" @click="showCreateForm = false" class="btn-cancel">
              Cancelar
            </button>
            <button type="submit" :disabled="isCreating" class="btn-save">
              {{ isCreating ? 'Creando...' : 'Crear' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="showDeleteModal" class="modal-overlay">
      <div class="modal modal-delete">
        <div class="modal-header">
          <h2>Confirmar eliminación</h2>
          <button @click="cancelDelete" class="btn-close">×</button>
        </div>

        <div class="modal-body">
          <p class="delete-message">¿Seguro que quieres eliminar este usuario?</p>
          <p class="delete-user-name">{{ userToDelete?.name }} ({{ userToDelete?.email }})</p>

          <div class="form-actions">
            <button type="button" @click="cancelDelete" class="btn-cancel" :disabled="isDeleting">
              Cancelar
            </button>
            <button type="button" @click="confirmDelete" class="btn-delete-confirm" :disabled="isDeleting">
              {{ isDeleting ? 'Eliminando...' : 'Eliminar' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Related users modal -->
    <div v-if="showRelatedModal" class="modal-overlay">
      <div class="modal modal-related">
        <div class="modal-header">
          <h2>Usuarios de {{ relatedData?.admin?.name || 'Admin' }}</h2>
          <button @click="closeRelatedModal" class="btn-close">×</button>
        </div>

        <div class="modal-body">
          <div v-if="isLoadingRelated" class="loading-related">Cargando...</div>

          <div v-else-if="relatedData">
            <!-- Usuarios creados por el admin -->
            <div v-if="relatedData.created_users && relatedData.created_users.length > 0" class="related-section">
              <h3 class="related-title">👨‍💼 Usuarios creados ({{ relatedData.created_users.length }})</h3>
              <div class="related-users-list">
                <div v-for="user in relatedData.created_users" :key="user.id" class="related-user-card">
                  <div class="related-user-info">
                    <div class="related-user-avatar">
                      {{ user.role?.name === 'caja' ? '💰' : '👨‍🍳' }}
                    </div>
                    <div class="related-user-details">
                      <div class="related-user-name">{{ user.name }}</div>
                      <div class="related-user-email">{{ user.email }}</div>
                    </div>
                  </div>
                  <div class="related-user-meta">
                    <span class="badge" :class="`badge-${user.role?.name}`">
                      {{ user.role?.name }}
                    </span>
                    <span class="status-badge" :class="`status-${user.status}`">
                      {{ user.status }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="no-created-users">
              Este admin no tiene usuarios creados
            </div>

            <!-- Usuarios clientes -->
            <div v-if="relatedData.client_users && relatedData.client_users.length > 0" class="related-section">
              <h3 class="related-title">🛒 Clientes registrados ({{ relatedData.client_users.length }})</h3>
              <div class="related-users-list">
                <div v-for="client in relatedData.client_users" :key="client.id" class="related-user-card">
                  <div class="related-user-info">
                    <div class="related-user-avatar">🛒</div>
                    <div class="related-user-details">
                      <div class="related-user-name">{{ client.name }}</div>
                      <div class="related-user-email">{{ client.email }}</div>
                    </div>
                  </div>
                  <div class="related-user-meta">
                    <span class="badge badge-cliente">Cliente</span>
                    <span class="status-badge" :class="`status-${client.status}`">
                      {{ client.status }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="no-clients">
              No hay clientes registrados
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()
const users = ref([])
const isLoading = ref(false)
const error = ref(null)
const toast = ref({ show: false, type: 'success', message: '' })
let toastTimer = null
const showCreateForm = ref(false)
const showDeleteModal = ref(false)
const showRelatedModal = ref(false)
const userToDelete = ref(null)
const isDeleting = ref(false)
const isCreating = ref(false)
const createError = ref(null)
const isLoadingRelated = ref(false)
const relatedData = ref(null)
const newUser = ref({
  name: '',
  email: '',
  password: '',
  phone: '',
  role: ''
})

// Grupos de administradores con sus equipos (caja y cocina)
const adminGroups = computed(() => {
  if (!auth.hasRole('superadmin')) return []
  
  const admins = users.value.filter(u => u.role?.name === 'admin')
  return admins.map(admin => {
    // Buscar usuarios creados por este admin (caja y cocina)
    const team = users.value.filter(u => 
      u.created_by === admin.id && 
      (u.role?.name === 'caja' || u.role?.name === 'cocina')
    )
    return { admin, team }
  })
})

// Usuarios clientes
const clientUsers = computed(() => {
  if (!auth.hasRole('superadmin')) return []
  return users.value.filter(u => u.role?.name === 'cliente')
})

const canManageUsers = computed(() => auth.hasAnyRole(['superadmin', 'admin']))

function canDeleteUser(user) {
  // No se puede eliminar a uno mismo
  if (user.id === auth.user?.id) {
    return false
  }
  return true
}

function canChangeStatus(user) {
  // No se puede cambiar el estado a uno mismo
  if (user.id === auth.user?.id) {
    return false
  }
  return true
}

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

async function fetchUsers() {
  isLoading.value = true
  error.value = null
  try {
    const response = await fetch('/api/users', {
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Accept': 'application/json'
      }
    })
    if (!response.ok) throw new Error('No se pudo cargar la lista de usuarios')

    const contentType = response.headers.get('content-type') || ''
    if (!contentType.includes('application/json')) {
      throw new Error('La API devolvió una respuesta inválida al listar usuarios')
    }

    const data = await response.json()
    // Backend now returns array directly (not paginated)
    users.value = Array.isArray(data) ? data : (data.data || [])
  } catch (err) {
    error.value = err.message
  } finally {
    isLoading.value = false
  }
}

async function createUser() {
  if (!canManageUsers.value) {
    showToast('No autorizado para crear usuarios', 'error')
    return
  }

  createError.value = null
  isCreating.value = true

  try {
    const response = await fetch('/api/users', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        name: newUser.value.name,
        email: newUser.value.email,
        password: newUser.value.password,
        phone: newUser.value.phone,
        role: newUser.value.role
      })
    })

    const contentType = response.headers.get('content-type') || ''

    if (!response.ok || !contentType.includes('application/json')) {
      let message = 'Error al crear usuario'

      if (contentType.includes('application/json')) {
        const data = await response.json()
        if (data?.errors) {
          const firstError = Object.values(data.errors)[0]
          if (Array.isArray(firstError) && firstError.length > 0) {
            message = firstError[0]
          }
        } else if (data?.message) {
          message = data.message
        }
      }

      throw new Error(message)
    }

    await response.json()

    // Close modal and reset form
    showCreateForm.value = false
    newUser.value = { name: '', email: '', password: '', phone: '', role: '' }
    
    // Refresh users list
    await fetchUsers()
    showToast('Usuario creado correctamente', 'success')
  } catch (err) {
    createError.value = err.message
    showToast(err.message, 'error')
  } finally {
    isCreating.value = false
  }
}

async function changeStatus(user) {
  if (!canManageUsers.value) {
    showToast('No autorizado para cambiar estado', 'error')
    return
  }

  const newStatus = user.status === 'active' ? 'inactive' : 'active'
  try {
    const response = await fetch(`/api/users/${user.id}/status`, {
      method: 'PATCH',
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ status: newStatus })
    })

    if (!response.ok) throw new Error('Error updating status')
    await fetchUsers()
    showToast('Estado actualizado correctamente', 'success')
  } catch (err) {
    error.value = err.message
    showToast(err.message, 'error')
  }
}

function openDeleteModal(user) {
  if (!canManageUsers.value) {
    showToast('No autorizado para eliminar usuarios', 'error')
    return
  }

  userToDelete.value = user
  showDeleteModal.value = true
}

function cancelDelete() {
  showDeleteModal.value = false
  userToDelete.value = null
}

async function confirmDelete() {
  if (!canManageUsers.value) {
    showToast('No autorizado para eliminar usuarios', 'error')
    return
  }

  if (!userToDelete.value) return

  isDeleting.value = true

  try {
    const response = await fetch(`/api/users/${userToDelete.value.id}`, {
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
      throw new Error(data?.message || 'Error al eliminar usuario')
    }

    cancelDelete()
    await fetchUsers()
    showToast('Usuario eliminado correctamente', 'success')
  } catch (err) {
    error.value = err.message
    showToast(err.message, 'error')
  } finally {
    isDeleting.value = false
  }
}

async function viewRelatedUsers(admin) {
  if (!auth.hasAnyRole(['superadmin', 'admin'])) {
    showToast('No autorizado', 'error')
    return
  }

  isLoadingRelated.value = true
  relatedData.value = null
  showRelatedModal.value = true

  try {
    const response = await fetch(`/api/users/${admin.id}/related`, {
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok) throw new Error('Error al cargar usuarios relacionados')

    const contentType = response.headers.get('content-type') || ''
    if (!contentType.includes('application/json')) {
      throw new Error('La API devolvió una respuesta inválida')
    }

    const data = await response.json()
    relatedData.value = data
  } catch (err) {
    showToast(err.message, 'error')
    showRelatedModal.value = false
  } finally {
    isLoadingRelated.value = false
  }
}

function closeRelatedModal() {
  showRelatedModal.value = false
  relatedData.value = null
}

onMounted(() => {
  fetchUsers()
})
</script>

<style scoped>
.users-container {
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

.loading, .error, .no-users {
  text-align: center;
  padding: 2rem;
  color: #7f8c8d;
}

.error {
  background: #ffe6e6;
  color: #c0392b;
  border-radius: 5px;
}

.users-table {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.table-header {
  display: grid;
  grid-template-columns: 1.5fr 2fr 1fr 1fr 1.2fr;
  gap: 1rem;
  padding: 1rem;
  background: #f5f6fa;
  border-radius: 5px;
  font-weight: 600;
  color: #2c3e50;
}

.table-row {
  display: grid;
  grid-template-columns: 1.5fr 2fr 1fr 1fr 1.2fr;
  gap: 1rem;
  padding: 1rem;
  border: 1px solid #ecf0f1;
  border-radius: 5px;
  align-items: center;
}

.col-name {
  color: #2c3e50;
  font-weight: 500;
}

.col-email {
  color: #7f8c8d;
  font-size: 0.9rem;
}

.badge {
  display: inline-block;
  padding: 0.4rem 0.75rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
  text-transform: uppercase;
}

.badge-admin {
  background: #3498db;
  color: white;
}

.badge-caja {
  background: #f39c12;
  color: white;
}

.badge-cocina {
  background: #e74c3c;
  color: white;
}

.badge-cliente {
  background: #27ae60;
  color: white;
}

.status-badge {
  display: inline-block;
  padding: 0.4rem 0.75rem;
  border-radius: 20px;
  font-size: 0.85rem;
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

.btn-action {
  padding: 0.5rem 1rem;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 3px;
  cursor: pointer;
  font-size: 0.85rem;
  transition: opacity 0.3s ease;
}

.btn-action:hover {
  opacity: 0.8;
}

.btn-delete {
  padding: 0.5rem 1rem;
  background: #e74c3c;
  color: white;
  border: none;
  border-radius: 3px;
  cursor: pointer;
  font-size: 0.85rem;
  transition: opacity 0.3s ease;
}

.btn-delete:hover {
  opacity: 0.8;
}

.btn-delete-confirm {
  flex: 1;
  padding: 0.75rem;
  background: #e74c3c;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 600;
  transition: opacity 0.3s ease;
}

.btn-delete-confirm:hover:not(:disabled) {
  opacity: 0.8;
}

.btn-delete-confirm:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.modal-delete {
  max-width: 520px;
}

.delete-message {
  margin: 0 0 0.75rem;
  color: #2c3e50;
  font-weight: 600;
}

.delete-user-name {
  margin: 0 0 1rem;
  color: #7f8c8d;
  word-break: break-word;
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
}

.modal {
  background: white;
  border-radius: 10px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  max-width: 500px;
  width: 100%;
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

.form-group input,
.form-group select {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #ecf0f1;
  border-radius: 5px;
  font-size: 1rem;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #667eea;
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
}

.btn-cancel,
.btn-save {
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

.btn-cancel:hover,
.btn-save:hover:not(:disabled) {
  opacity: 0.8;
}

.btn-save:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* New grouped styles for superadmin view */
.section {
  margin-bottom: 3rem;
}

.section-title {
  color: #2c3e50;
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #ecf0f1;
}

.admin-group {
  margin-bottom: 2rem;
  border: 2px solid #e8eef3;
  border-radius: 10px;
  padding: 1rem;
  background: #f8fafb;
}

.admin-card {
  display: grid;
  grid-template-columns: 2fr 1.5fr auto;
  align-items: center;
  gap: 1rem;
  background: white;
  padding: 1.25rem;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  margin-bottom: 1rem;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.user-avatar {
  width: 50px;
  height: 50px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.user-avatar-small {
  width: 35px;
  height: 35px;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}

.user-details {
  flex: 1;
}

.user-name {
  font-size: 1.1rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.25rem;
}

.user-email {
  font-size: 0.9rem;
  color: #7f8c8d;
}

.user-name-small {
  font-size: 0.95rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.2rem;
}

.user-email-small {
  font-size: 0.8rem;
  color: #7f8c8d;
}

.user-meta {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.user-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-action-small {
  padding: 0.4rem 0.8rem;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 3px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: opacity 0.3s ease;
}

.btn-action-small:hover {
  opacity: 0.8;
}

.btn-delete-small {
  padding: 0.4rem 0.8rem;
  background: #e74c3c;
  color: white;
  border: none;
  border-radius: 3px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: opacity 0.3s ease;
}

.btn-delete-small:hover {
  opacity: 0.8;
}

.team-members {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding-left: 2rem;
  border-left: 3px solid #667eea;
  margin-left: 1rem;
}

.team-card {
  display: grid;
  grid-template-columns: 2fr 1.5fr auto;
  align-items: center;
  gap: 1rem;
  background: white;
  padding: 1rem;
  border-radius: 6px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
}

.users-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1rem;
}

.client-card {
  display: grid;
  grid-template-columns: 2fr 1.5fr auto;
  align-items: center;
  gap: 1rem;
  background: white;
  padding: 1.25rem;
  border-radius: 8px;
  border: 2px solid #e8eef3;
  transition: all 0.3s ease;
}

.client-card:hover {
  border-color: #27ae60;
  box-shadow: 0 4px 12px rgba(39, 174, 96, 0.1);
}

/* Clickable admin name */
.clickable {
  cursor: pointer;
  transition: all 0.2s ease;
}

.clickable:hover {
  transform: translateX(3px);
}

.admin-name-clickable {
  position: relative;
  text-decoration: underline;
  text-decoration-style: dotted;
  text-decoration-color: #667eea;
}

.admin-name-clickable:hover {
  color: #667eea;
}

/* Related users modal */
.modal-related {
  max-width: 700px;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-related .modal-body {
  overflow-y: auto;
  max-height: calc(90vh - 120px);
}

.loading-related {
  text-align: center;
  padding: 2rem;
  color: #7f8c8d;
}

.related-section {
  margin-bottom: 2rem;
}

.related-section:last-child {
  margin-bottom: 0;
}

.related-title {
  color: #2c3e50;
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #ecf0f1;
}

.related-users-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.related-user-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  background: #f8fafb;
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid #e8eef3;
  transition: all 0.2s ease;
}

.related-user-card:hover {
  background: white;
  border-color: #667eea;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
}

.related-user-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
}

.related-user-avatar {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}

.related-user-details {
  flex: 1;
}

.related-user-name {
  font-size: 0.95rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.25rem;
}

.related-user-email {
  font-size: 0.85rem;
  color: #7f8c8d;
}

.related-user-meta {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.no-created-users,
.no-clients {
  text-align: center;
  padding: 1.5rem;
  color: #7f8c8d;
  background: #f8fafb;
  border-radius: 8px;
  margin-bottom: 1rem;
}

.empty-section {
  text-align: center;
  padding: 2rem;
  color: #7f8c8d;
  background: #f8fafb;
  border-radius: 8px;
  font-size: 1.1rem;
}

.user-phone {
  font-size: 0.85rem;
  color: #7f8c8d;
  margin-top: 0.25rem;
}

@media (max-width: 768px) {
  .table-header,
  .table-row {
    grid-template-columns: 1fr;
  }

  .modal {
    margin: 1rem;
  }

  .admin-card,
  .team-card,
  .client-card {
    grid-template-columns: 1fr;
    text-align: center;
  }

  .user-info {
    flex-direction: column;
  }

  .user-meta {
    justify-content: center;
  }

  .team-members {
    padding-left: 1rem;
    margin-left: 0.5rem;
  }

  .users-grid {
    grid-template-columns: 1fr;
  }
}
</style>
