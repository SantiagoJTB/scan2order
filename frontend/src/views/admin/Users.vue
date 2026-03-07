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
                <div class="user-details">
                  <div class="user-name">{{ group.admin.name }}</div>
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
                  v-if="canEditUser(group.admin)"
                  @click="openEditModal(group.admin)"
                  class="btn-action"
                  title="Editar usuario"
                >
                  ✏️
                </button>
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
                    v-if="canEditUser(member)"
                    @click="openEditModal(member)"
                    class="btn-edit-small"
                    title="Editar usuario"
                  >
                    ✏️
                  </button>
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

        <div v-if="orphanStaffUsers.length > 0" class="section">
          <h2 class="section-title">⚠️ Usuarios huérfanos (Caja/Cocina)</h2>

          <div class="team-members orphan-members">
            <div v-for="member in orphanStaffUsers" :key="member.id" class="team-card">
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
                  v-if="canEditUser(member)"
                  @click="openEditModal(member)"
                  class="btn-edit-small"
                  title="Editar usuario"
                >
                  ✏️
                </button>
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
                  v-if="canEditUser(client)"
                  @click="openEditModal(client)"
                  class="btn-action"
                  title="Editar usuario"
                >
                  ✏️
                </button>
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
              v-if="canEditUser(user)"
              @click="openEditModal(user)"
              class="btn-action"
              title="Editar usuario"
            >
              ✏️
            </button>
            <button 
              v-if="canChangeStatus(user)"
              @click="changeStatus(user)" 
              class="btn-action"
              title="Cambiar estado"
            >
              🔄
            </button>
            <button 
              v-if="canDeleteUser(user)"
              @click="openDeleteModal(user)" 
              class="btn-delete"
              title="Eliminar usuario"
            >
              🗑️
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
            <select v-model="newUser.role" id="role" required @change="handleNewUserRoleChange">
              <option value="">Seleccionar rol</option>
              <option v-if="auth.hasRole('superadmin')" value="admin">Admin</option>
              <option value="caja">Caja</option>
              <option value="cocina">Cocina</option>
              <option v-if="auth.hasRole('superadmin')" value="cliente">Cliente</option>
            </select>
          </div>

          <div v-if="showAssignAdminOption" class="form-group">
            <label class="assign-admin-check">
              <input v-model="newUser.assignToAdmin" type="checkbox" />
              Asignar a un admin
            </label>

            <select
              v-if="newUser.assignToAdmin"
              v-model="newUser.adminId"
              class="admin-select"
              :disabled="adminOptions.length === 0"
              required
            >
              <option value="">Seleccionar admin</option>
              <option v-for="admin in adminOptions" :key="admin.id" :value="admin.id">
                {{ admin.name }} ({{ admin.email }})
              </option>
            </select>

            <p v-if="newUser.assignToAdmin && adminOptions.length === 0" class="inline-warning">
              No hay admins disponibles para asignar.
            </p>
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

    <div v-if="showEditModal" class="modal-overlay">
      <div class="modal">
        <div class="modal-header">
          <h2>Editar usuario</h2>
          <button @click="cancelEdit" class="btn-close">×</button>
        </div>

        <form @submit.prevent="confirmEdit" class="modal-body">
          <div class="form-group">
            <label for="edit-name">Nombre:</label>
            <input
              v-model="editUser.name"
              type="text"
              id="edit-name"
              placeholder="Nombre completo"
              required
            />
          </div>

          <div class="form-group">
            <label for="edit-email">Email:</label>
            <input
              v-model="editUser.email"
              type="email"
              id="edit-email"
              placeholder="email@example.com"
              required
            />
          </div>

          <div class="form-group">
            <label for="edit-phone">Teléfono:</label>
            <input
              v-model="editUser.phone"
              type="tel"
              id="edit-phone"
              placeholder="Teléfono"
            />
          </div>

          <div v-if="editError" class="error">{{ editError }}</div>

          <div class="form-actions">
            <button type="button" @click="cancelEdit" class="btn-cancel" :disabled="isUpdating">
              Cancelar
            </button>
            <button type="submit" :disabled="isUpdating" class="btn-save">
              {{ isUpdating ? 'Guardando...' : 'Guardar cambios' }}
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
          <p v-if="userToDelete?.role?.name === 'admin'" class="delete-warning">
            Al eliminar este admin también se eliminarán sus cuentas vinculadas.
          </p>

          <label class="delete-confirm-check">
            <input v-model="deleteConfirmed" type="checkbox" />
            Sí, deseo eliminar este usuario
          </label>

          <div class="form-actions">
            <button type="button" @click="cancelDelete" class="btn-cancel" :disabled="isDeleting">
              Cancelar
            </button>
            <button type="button" @click="confirmDelete" class="btn-delete-confirm" :disabled="isDeleting || !deleteConfirmed">
              {{ isDeleting ? 'Eliminando...' : 'Eliminar' }}
            </button>
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
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const userToEdit = ref(null)
const userToDelete = ref(null)
const deleteConfirmed = ref(false)
const isDeleting = ref(false)
const isCreating = ref(false)
const isUpdating = ref(false)
const createError = ref(null)
const editError = ref(null)
const editUser = ref({
  name: '',
  email: '',
  phone: ''
})
const newUser = ref({
  name: '',
  email: '',
  password: '',
  phone: '',
  role: '',
  assignToAdmin: true,
  adminId: ''
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

const orphanStaffUsers = computed(() => {
  if (!auth.hasRole('superadmin')) return []

  const adminIds = new Set(
    users.value
      .filter(u => u.role?.name === 'admin')
      .map(u => u.id)
  )

  return users.value.filter(u => {
    const isStaff = u.role?.name === 'caja' || u.role?.name === 'cocina'
    if (!isStaff) return false

    const createdBy = u.created_by
    return !createdBy || !adminIds.has(createdBy)
  })
})

// Usuarios clientes
const clientUsers = computed(() => {
  if (!auth.hasRole('superadmin')) return []
  return users.value.filter(u => u.role?.name === 'cliente')
})

const adminOptions = computed(() => {
  if (!auth.hasRole('superadmin')) return []
  return users.value.filter(u => u.role?.name === 'admin')
})

const showAssignAdminOption = computed(() => {
  return auth.hasRole('superadmin') && ['caja', 'cocina'].includes(newUser.value.role)
})

const canManageUsers = computed(() => auth.hasAnyRole(['superadmin', 'admin']))

function canDeleteUser(user) {
  // No se puede eliminar a uno mismo
  if (user.id === auth.user?.id) {
    return false
  }
  return true
}

function canEditUser(user) {
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
    if (showAssignAdminOption.value && newUser.value.assignToAdmin && !newUser.value.adminId) {
      throw new Error('Debes seleccionar un admin o desmarcar la asignación')
    }

    const payload = {
      name: newUser.value.name,
      email: newUser.value.email,
      password: newUser.value.password,
      phone: newUser.value.phone,
      role: newUser.value.role
    }

    if (showAssignAdminOption.value) {
      payload.assign_to_admin = newUser.value.assignToAdmin
      payload.admin_id = newUser.value.assignToAdmin ? Number(newUser.value.adminId) : null
    }

    const response = await fetch('/api/users', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
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
    newUser.value = { name: '', email: '', password: '', phone: '', role: '', assignToAdmin: true, adminId: '' }
    
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

function handleNewUserRoleChange() {
  if (!['caja', 'cocina'].includes(newUser.value.role)) {
    newUser.value.assignToAdmin = true
    newUser.value.adminId = ''
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

function openEditModal(user) {
  if (!canManageUsers.value) {
    showToast('No autorizado para editar usuarios', 'error')
    return
  }

  userToEdit.value = user
  editError.value = null
  editUser.value = {
    name: user.name || '',
    email: user.email || '',
    phone: user.phone || ''
  }
  showEditModal.value = true
}

function cancelEdit() {
  showEditModal.value = false
  userToEdit.value = null
  editError.value = null
  editUser.value = { name: '', email: '', phone: '' }
}

async function confirmEdit() {
  if (!canManageUsers.value) {
    showToast('No autorizado para editar usuarios', 'error')
    return
  }

  if (!userToEdit.value) return

  isUpdating.value = true
  editError.value = null

  try {
    const response = await fetch(`/api/users/${userToEdit.value.id}`, {
      method: 'PATCH',
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        name: editUser.value.name,
        email: editUser.value.email,
        phone: editUser.value.phone || null
      })
    })

    const contentType = response.headers.get('content-type') || ''
    let data = null

    if (contentType.includes('application/json')) {
      data = await response.json()
    }

    if (!response.ok) {
      throw new Error(data?.message || 'Error al editar usuario')
    }

    cancelEdit()
    await fetchUsers()
    showToast('Usuario actualizado correctamente', 'success')
  } catch (err) {
    editError.value = err.message
    showToast(err.message, 'error')
  } finally {
    isUpdating.value = false
  }
}

function openDeleteModal(user) {
  if (!canManageUsers.value) {
    showToast('No autorizado para eliminar usuarios', 'error')
    return
  }

  userToDelete.value = user
  deleteConfirmed.value = false
  showDeleteModal.value = true
}

function cancelDelete() {
  showDeleteModal.value = false
  userToDelete.value = null
  deleteConfirmed.value = false
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

.delete-warning {
  margin: 0 0 1rem;
  color: #b23b2a;
  font-size: 0.9rem;
  font-weight: 600;
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

.assign-admin-check {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.6rem;
}

.assign-admin-check input {
  width: auto;
}

.admin-select {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #ecf0f1;
  border-radius: 5px;
  font-size: 1rem;
}

.inline-warning {
  margin: 0.5rem 0 0;
  color: #c0392b;
  font-size: 0.9rem;
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

.client-card .user-info {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
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

.client-card .user-avatar {
  width: 60px;
  height: 60px;
  font-size: 2rem;
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

.client-card .user-details {
  flex: 1;
}
.user-name {
  font-size: 1.1rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.25rem;
}

.client-card .user-name {
  font-size: 1.2rem;
  margin-bottom: 0.5rem;
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

.client-card .user-meta {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}
.user-actions {
  display: flex;
  gap: 0.5rem;
}

.client-card .user-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  border-top: 1px solid #ecf0f1;
  padding-top: 1rem;
}

.client-card .btn-action,
.client-card .btn-delete {
  flex: 1;
  padding: 0.6rem 0.8rem;
  font-size: 0.9rem;
  border-radius: 6px;
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

.btn-edit-small {
  padding: 0.4rem 0.8rem;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 3px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: opacity 0.3s ease;
}

.btn-edit-small:hover {
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

.orphan-members {
  padding-left: 0;
  border-left: none;
  margin-left: 0;
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
  display: flex;
  flex-direction: column;
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  border: 2px solid #e8eef3;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.client-card:hover {
  border-color: #27ae60;
  box-shadow: 0 8px 16px rgba(39, 174, 96, 0.15);
  transform: translateY(-3px);
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
