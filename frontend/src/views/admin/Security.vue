<template>
  <div class="security-container">
    <div class="header">
      <h1>Seguridad del sistema</h1>
      <p>Vista operativa para superadmin</p>
    </div>

    <div v-if="!isSuperadmin" class="content">
      <div class="info-box">
        <h2>Acceso denegado</h2>
        <p>Solo superadmin puede ver este panel.</p>
      </div>
    </div>

    <div v-else>
      <div v-if="error" class="error-box">{{ error }}</div>

      <div class="summary-grid" v-if="summary">
        <div class="stat-card">
          <p class="stat-label">Superadmins activos</p>
          <p class="stat-value">{{ summary.active_superadmins }}</p>
        </div>
        <div class="stat-card">
          <p class="stat-label">Superadmins con MFA</p>
          <p class="stat-value">{{ summary.superadmins_with_mfa }}</p>
        </div>
        <div class="stat-card">
          <p class="stat-label">Eventos (24h)</p>
          <p class="stat-value">{{ summary.events_last_24h }}</p>
        </div>
        <div class="stat-card">
          <p class="stat-label">Acciones de alto riesgo (24h)</p>
          <p class="stat-value">{{ summary.high_risk_actions_last_24h }}</p>
        </div>
      </div>

      <div class="content table-wrapper">
        <div class="table-header-row">
          <h2>Eventos recientes</h2>
          <div class="header-actions">
            <button class="btn-refresh btn-clear" @click="clearFilters" :disabled="isLoading">
              Limpiar filtros
            </button>
            <button class="btn-refresh" @click="fetchOverview" :disabled="isLoading">
              {{ isLoading ? 'Actualizando...' : 'Actualizar' }}
            </button>
          </div>
        </div>

        <div class="filters-row">
          <div class="filter-item">
            <label>Acción</label>
            <select v-model="filters.action">
              <option value="">Todas</option>
              <option v-for="action in availableActions" :key="action" :value="action">{{ action }}</option>
            </select>
          </div>
          <div class="filter-item">
            <label>Desde</label>
            <input v-model="filters.from" type="date" />
          </div>
          <div class="filter-item">
            <label>Hasta</label>
            <input v-model="filters.to" type="date" />
          </div>
        </div>

        <div v-if="isLoading" class="loading">Cargando eventos...</div>

        <table v-else class="events-table">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Acción</th>
              <th>Actor</th>
              <th>Objetivo</th>
              <th>Recurso</th>
              <th>IP</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="events.length === 0">
              <td colspan="6" class="empty">Sin eventos recientes</td>
            </tr>
            <tr v-for="event in events" :key="event.id">
              <td>{{ formatDate(event.created_at) }}</td>
              <td><span class="action-pill">{{ event.action }}</span></td>
              <td>{{ event.actor?.name || '-' }}</td>
              <td>{{ event.target?.name || '-' }}</td>
              <td>{{ formatResource(event) }}</td>
              <td>{{ event.ip_address || '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()
const isSuperadmin = computed(() => auth.hasRole('superadmin'))

const isLoading = ref(false)
const error = ref('')
const summary = ref(null)
const events = ref([])
const availableActions = ref([])
const filters = ref({
  action: '',
  from: '',
  to: '',
})

function formatDate(value) {
  if (!value) return '-'
  return new Date(value).toLocaleString('es-ES')
}

function formatResource(event) {
  if (!event?.resource_type) return '-'
  if (!event?.resource_id) return event.resource_type
  return `${event.resource_type}#${event.resource_id}`
}

async function fetchOverview() {
  if (!isSuperadmin.value) return

  isLoading.value = true
  error.value = ''

  try {
    const query = new URLSearchParams({ limit: '25' })

    if (filters.value.action) query.set('action', filters.value.action)
    if (filters.value.from) query.set('from', filters.value.from)
    if (filters.value.to) query.set('to', filters.value.to)

    const response = await fetch(`/api/admin/security/overview?${query.toString()}`, {
      headers: {
        Accept: 'application/json',
        ...(auth.token ? { Authorization: `Bearer ${auth.token}` } : {}),
      },
    })

    const data = await response.json()

    if (!response.ok) {
      throw new Error(data?.message || 'No se pudo cargar el panel de seguridad')
    }

    summary.value = data.summary || null
    events.value = Array.isArray(data.recent_events) ? data.recent_events : []
    availableActions.value = Array.isArray(data.available_actions) ? data.available_actions : []
  } catch (err) {
    error.value = err.message || 'No se pudo cargar el panel de seguridad'
  } finally {
    isLoading.value = false
  }
}

function clearFilters() {
  filters.value = {
    action: '',
    from: '',
    to: '',
  }
  fetchOverview()
}

onMounted(async () => {
  if (!auth.initialized) {
    await auth.initFromStorage()
  }

  await fetchOverview()
})
</script>

<style scoped>
.security-container {
  max-width: 1200px;
  margin: 0 auto;
}

.header {
  color: white;
  margin-bottom: 1.5rem;
}

.header h1 {
  margin: 0;
  font-size: 2rem;
}

.header p {
  margin-top: 0.35rem;
  opacity: 0.9;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.stat-card {
  background: white;
  border-radius: 10px;
  padding: 1rem;
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08);
}

.stat-label {
  margin: 0;
  color: #7f8c8d;
  font-size: 0.85rem;
  text-transform: uppercase;
}

.stat-value {
  margin: 0.4rem 0 0;
  color: #2c3e50;
  font-size: 1.7rem;
  font-weight: 700;
}

.content {
  background: white;
  border-radius: 10px;
  padding: 1.25rem;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.info-box {
  text-align: center;
  padding: 2rem;
}

.error-box {
  background: #ffe6e6;
  color: #c0392b;
  border-radius: 8px;
  padding: 0.9rem 1rem;
  margin-bottom: 1rem;
}

.table-wrapper {
  overflow: auto;
}

.table-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.header-actions {
  display: flex;
  gap: 0.5rem;
}

.filters-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.filter-item {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.filter-item label {
  color: #2c3e50;
  font-size: 0.85rem;
  font-weight: 600;
}

.filter-item select,
.filter-item input {
  border: 1px solid #dfe6eb;
  border-radius: 6px;
  padding: 0.5rem 0.6rem;
  font-size: 0.9rem;
}

.table-header-row h2 {
  margin: 0;
  color: #2c3e50;
}

.btn-refresh {
  background: #667eea;
  color: white;
  border: none;
  padding: 0.55rem 0.9rem;
  border-radius: 6px;
  cursor: pointer;
}

.btn-clear {
  background: #95a5a6;
}

.btn-refresh:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.loading {
  color: #7f8c8d;
}

.events-table {
  width: 100%;
  border-collapse: collapse;
}

.events-table th,
.events-table td {
  border-bottom: 1px solid #ecf0f1;
  text-align: left;
  padding: 0.65rem 0.5rem;
  font-size: 0.9rem;
}

.events-table th {
  color: #2c3e50;
  font-weight: 600;
}

.action-pill {
  background: #ecf0f1;
  color: #2c3e50;
  border-radius: 999px;
  padding: 0.2rem 0.55rem;
  font-size: 0.8rem;
}

.empty {
  text-align: center;
  color: #7f8c8d;
}
</style>
