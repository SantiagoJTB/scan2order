<template>
  <div class="users-container">
    <div class="header">
      <h1>Gestión de Usuarios</h1>
      <button @click="showCreateForm = true" class="btn-create">
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
                <button @click="changeStatus(group.admin)" class="btn-action" title="Cambiar estado">
                  🔄
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
                  <button @click="changeStatus(member)" class="btn-action-small" title="Cambiar estado">
                    🔄
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sección de Clientes -->
        <div v-if="clientUsers.length > 0" class="section">
          <h2 class="section-title">👥 Usuarios clientes</h2>
          <div class="users-grid">
            <div v-for="client in clientUsers" :key="client.id" class="client-card">
              <div class="user-info">
                <div class="user-avatar">🛒</div>
                <div class="user-details">
                  <div class="user-name">{{ client.name }}</div>
                  <div class="user-email">{{ client.email }}</div>
                </div>
              </div>
              <div class="user-meta">
                <span class="badge badge-cliente">Cliente</span>
                <span class="status-badge" :class="`status-${client.status}`">
                  {{ client.status }}
                </span>
              </div>
              <div class="user-actions">
                <button @click="changeStatus(client)" class="btn-action" title="Cambiar estado">
                  🔄
                </button>
              </div>
            </div>
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
            <button @click="changeStatus(user)" class="btn-action">Cambiar estado</button>
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()
const users = ref([])
const isLoading = ref(false)
const error = ref(null)
const showCreateForm = ref(false)
const isCreating = ref(false)
const createError = ref(null)
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

async function fetchUsers() {
  isLoading.value = true
  error.value = null
  try {
    const response = await fetch('/api/users', {
      headers: {
        'Authorization': `Bearer ${auth.token}`
      }
    })
    if (!response.ok) throw new Error('Failed to fetch users')
    const data = await response.json()
    users.value = data.data || data
  } catch (err) {
    error.value = err.message
  } finally {
    isLoading.value = false
  }
}

async function createUser() {
  createError.value = null
  isCreating.value = true

  try {
    const response = await fetch('/api/users', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        name: newUser.value.name,
        email: newUser.value.email,
        password: newUser.value.password,
        phone: newUser.value.phone,
        role: newUser.value.role
      })
    })

    if (!response.ok) {
      const data = await response.json()
      throw new Error(data.message || 'Error creating user')
    }

    showCreateForm.value = false
    newUser.value = { name: '', email: '', password: '', phone: '', role: '' }
    fetchUsers()
  } catch (err) {
    createError.value = err.message
  } finally {
    isCreating.value = false
  }
}

async function changeStatus(user) {
  const newStatus = user.status === 'active' ? 'inactive' : 'active'
  try {
    const response = await fetch(`/api/users/${user.id}/status`, {
      method: 'PATCH',
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ status: newStatus })
    })

    if (!response.ok) throw new Error('Error updating status')
    fetchUsers()
  } catch (err) {
    error.value = err.message
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
