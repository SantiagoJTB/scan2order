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
      <div v-if="actionMessage" class="success-box">{{ actionMessage }}</div>

      <div class="content emergency-wrapper">
        <div class="table-header-row">
          <h2>Protocolo de emergencia y Autoanalizador de contenedores</h2>
          <button class="btn-refresh" @click="refreshUnifiedPanel" :disabled="isActionLoading || isGuardianLoading">
            {{ isActionLoading || isGuardianLoading ? 'Procesando...' : 'Actualizar estado' }}
          </button>
        </div>

        <p class="emergency-help">
          Guardian opera en modo continuo supervisado. Incluye protección anti-bucle y auto-desconexión por fallos repetidos.
          Aquí puedes registrar protocolo, verificar contingencia y ejecutar recuperación puntual (heal/restart), todo auditado.
        </p>

        <div class="health-grid" v-if="healthCheck">
          <div class="health-pill" :class="healthCheck.ok ? 'ok' : 'degraded'">
            Estado general: {{ healthCheck.ok ? 'OK' : 'DEGRADADO' }}
          </div>
          <div class="health-item">BD: <strong>{{ healthCheck.database ? 'OK' : 'FALLA' }}</strong></div>
          <div class="health-item">Storage: <strong>{{ healthCheck.storage ? 'OK' : 'FALLA' }}</strong></div>
          <div class="health-item">Scheduler: <strong>{{ healthCheck.scheduler_signal ? 'SEÑAL' : 'SIN SEÑAL' }}</strong></div>
          <div class="health-item">Revisión: <strong>{{ formatDate(healthCheck.checked_at) }}</strong></div>
        </div>

        <div v-if="guardianOutput" class="guardian-output">
          <pre>{{ guardianOutput }}</pre>
        </div>

        <div class="emergency-actions">
          <button class="btn-danger" :disabled="isActionLoading" @click="askConfirm('activate_protocol')">
            Activar protocolo de emergencia
          </button>
          <button class="btn-warning" :disabled="isActionLoading" @click="askConfirm('run_contingency_check')">
            Ejecutar verificación de contingencia
          </button>
          <button class="btn-warning" :disabled="isGuardianLoading" @click="askGuardianConfirm('heal')">
            Heal automático
          </button>
          <button class="btn-danger" :disabled="isGuardianLoading" @click="askGuardianConfirm('restart')">
            Reiniciar todos los contenedores
          </button>
        </div>
      </div>

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

      <div v-if="showConfirmModal" class="modal-overlay">
        <div class="modal">
          <div class="modal-header">
            <h2>Confirmar acción crítica</h2>
            <button class="btn-close" @click="cancelConfirm">×</button>
          </div>
          <div class="modal-body">
            <p>{{ confirmMessage }}</p>
            <label class="confirm-reason-label">Motivo (opcional):</label>
            <textarea v-model="confirmReason" rows="3" placeholder="Describe brevemente el incidente o motivo"></textarea>
            <div class="confirm-check">
              <input id="confirm-check" v-model="confirmChecked" type="checkbox" />
              <label for="confirm-check">Sí, confirmar esta acción</label>
            </div>
            <div class="modal-actions">
              <button class="btn-cancel" @click="cancelConfirm" :disabled="isActionLoading">Cancelar</button>
              <button class="btn-danger" @click="executeConfirmedAction" :disabled="isActionLoading || !confirmChecked">
                {{ isActionLoading ? 'Procesando...' : 'Confirmar' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="showGuardianConfirmModal" class="modal-overlay">
        <div class="modal">
          <div class="modal-header">
            <h2>Confirmar acción de contenedores</h2>
            <button class="btn-close" @click="cancelGuardianConfirm">×</button>
          </div>
          <div class="modal-body">
            <p>
              Esta acción puede afectar disponibilidad. ¿Seguro que deseas ejecutar:
              <strong>{{ guardianActionLabel }}</strong>?
            </p>
            <label class="confirm-reason-label">Motivo (opcional):</label>
            <textarea v-model="guardianConfirmReason" rows="3" placeholder="Motivo operacional"></textarea>
            <div class="confirm-check">
              <input id="guardian-confirm-check" v-model="guardianConfirmChecked" type="checkbox" />
              <label for="guardian-confirm-check">Sí, confirmar esta acción</label>
            </div>
            <div class="modal-actions">
              <button class="btn-cancel" @click="cancelGuardianConfirm" :disabled="isGuardianLoading">Cancelar</button>
              <button class="btn-danger" @click="executeGuardianAction" :disabled="isGuardianLoading || !guardianConfirmChecked">
                {{ isGuardianLoading ? 'Procesando...' : 'Confirmar' }}
              </button>
            </div>
          </div>
        </div>
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
const actionMessage = ref('')
const healthCheck = ref(null)
const isActionLoading = ref(false)
const guardianOutput = ref('')
const isGuardianLoading = ref(false)
const showGuardianConfirmModal = ref(false)
const guardianConfirmChecked = ref(false)
const guardianConfirmReason = ref('')
const pendingGuardianAction = ref('')
const showConfirmModal = ref(false)
const confirmChecked = ref(false)
const confirmReason = ref('')
const pendingAction = ref('')
const filters = ref({
  action: '',
  from: '',
  to: '',
})

const confirmMessage = computed(() => {
  if (pendingAction.value === 'activate_protocol') {
    return 'Vas a registrar la activación del protocolo de emergencia. Esta acción quedará auditada.'
  }
  if (pendingAction.value === 'run_contingency_check') {
    return 'Vas a ejecutar una verificación de contingencia del sistema y registrarla en auditoría.'
  }
  return 'Confirma la acción.'
})

const guardianActionLabel = computed(() => {
  if (pendingGuardianAction.value === 'heal') return 'heal automático'
  if (pendingGuardianAction.value === 'restart') return 'reiniciar todos los contenedores'
  return pendingGuardianAction.value || 'acción'
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

async function fetchHealth() {
  if (!isSuperadmin.value) return

  isActionLoading.value = true
  error.value = ''

  try {
    const response = await fetch('/api/admin/security/health', {
      headers: {
        Accept: 'application/json',
        ...(auth.token ? { Authorization: `Bearer ${auth.token}` } : {}),
      },
    })

    const data = await response.json()
    if (!response.ok) {
      throw new Error(data?.message || 'No se pudo verificar el estado')
    }

    healthCheck.value = data.check || null
  } catch (err) {
    error.value = err.message || 'No se pudo verificar el estado'
  } finally {
    isActionLoading.value = false
  }
}

function askConfirm(action) {
  pendingAction.value = action
  confirmReason.value = ''
  confirmChecked.value = false
  showConfirmModal.value = true
}

function askGuardianConfirm(action) {
  pendingGuardianAction.value = action
  guardianConfirmReason.value = ''
  guardianConfirmChecked.value = false
  showGuardianConfirmModal.value = true
}

function cancelGuardianConfirm() {
  showGuardianConfirmModal.value = false
  guardianConfirmChecked.value = false
  guardianConfirmReason.value = ''
  pendingGuardianAction.value = ''
}

function cancelConfirm() {
  showConfirmModal.value = false
  pendingAction.value = ''
  confirmReason.value = ''
  confirmChecked.value = false
}

async function executeConfirmedAction() {
  if (!pendingAction.value) return

  isActionLoading.value = true
  error.value = ''
  actionMessage.value = ''

  try {
    const response = await fetch('/api/admin/security/emergency-action', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(auth.token ? { Authorization: `Bearer ${auth.token}` } : {}),
      },
      body: JSON.stringify({
        action: pendingAction.value,
        reason: confirmReason.value || null,
        confirmed: true,
      }),
    })

    const data = await response.json()
    if (!response.ok) {
      throw new Error(data?.message || 'No se pudo ejecutar la acción')
    }

    actionMessage.value = data?.message || 'Acción ejecutada correctamente'
    if (data?.check) {
      healthCheck.value = data.check
    }
    cancelConfirm()
    await fetchOverview()
  } catch (err) {
    error.value = err.message || 'No se pudo ejecutar la acción'
  } finally {
    isActionLoading.value = false
  }
}

async function fetchGuardianStatus() {
  isGuardianLoading.value = true
  error.value = ''

  try {
    const response = await fetch('/api/admin/security/guardian/status', {
      headers: {
        Accept: 'application/json',
        ...(auth.token ? { Authorization: `Bearer ${auth.token}` } : {}),
      },
    })

    const data = await response.json()
    if (!response.ok) {
      throw new Error(data?.message || 'No se pudo consultar guardian')
    }

    const commands = data?.commands
      ? Object.entries(data.commands).map(([key, command]) => `- ${key}: ${command}`).join('\n')
      : ''

    guardianOutput.value = [
      data?.message || '',
      commands ? `\nComandos:\n${commands}` : ''
    ].join('\n').trim() || 'Modo seguro activo. Usa comandos manuales por SSH/VPN.'
  } catch (err) {
    error.value = err.message || 'No se pudo consultar guardian'
  } finally {
    isGuardianLoading.value = false
  }
}

async function refreshUnifiedPanel() {
  await Promise.all([fetchHealth(), fetchGuardianStatus()])
}

async function executeGuardianAction() {
  if (!pendingGuardianAction.value) return

  isGuardianLoading.value = true
  error.value = ''
  actionMessage.value = ''

  try {
    const response = await fetch('/api/admin/security/guardian/action', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(auth.token ? { Authorization: `Bearer ${auth.token}` } : {}),
      },
      body: JSON.stringify({
        action: pendingGuardianAction.value,
        target: pendingGuardianAction.value === 'restart' ? 'all' : null,
        reason: guardianConfirmReason.value || null,
        confirmed: true,
      }),
    })

    const data = await response.json()
    if (!response.ok) {
      throw new Error(data?.message || 'No se pudo ejecutar guardian')
    }

    actionMessage.value = data?.message || 'Acción guardian completada'
    guardianOutput.value = data?.manual_command
      ? `Ejecuta manualmente:\n${data.manual_command}`
      : (data?.message || 'Acción registrada')

    cancelGuardianConfirm()
    await fetchOverview()
  } catch (err) {
    error.value = err.message || 'No se pudo ejecutar guardian'
  } finally {
    isGuardianLoading.value = false
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
  await refreshUnifiedPanel()
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

.success-box {
  background: #eafaf1;
  color: #1e7e34;
  border-radius: 8px;
  padding: 0.9rem 1rem;
  margin-bottom: 1rem;
}

.emergency-wrapper {
  margin-bottom: 1.25rem;
}

.emergency-help {
  margin: 0.25rem 0 0.9rem;
  color: #4b5563;
}

.health-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.health-pill {
  grid-column: 1 / -1;
  padding: 0.55rem 0.75rem;
  border-radius: 8px;
  font-weight: 700;
}

.health-pill.ok {
  background: #e8f5e9;
  color: #1b5e20;
}

.health-pill.degraded {
  background: #ffebee;
  color: #b71c1c;
}

.health-item {
  background: #f8fafc;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 0.5rem 0.65rem;
  color: #374151;
}

.emergency-actions {
  display: flex;
  gap: 0.6rem;
  flex-wrap: wrap;
}

.btn-danger,
.btn-warning,
.btn-cancel {
  border: none;
  border-radius: 6px;
  padding: 0.55rem 0.9rem;
  color: white;
  cursor: pointer;
}

.btn-danger {
  background: #c0392b;
}

.btn-warning {
  background: #d97706;
}

.btn-cancel {
  background: #6b7280;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal {
  width: min(520px, calc(100% - 2rem));
  background: white;
  border-radius: 10px;
  box-shadow: 0 16px 36px rgba(0, 0, 0, 0.2);
  overflow: hidden;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.9rem 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  margin: 0;
  color: #1f2937;
  font-size: 1.05rem;
}

.btn-close {
  border: none;
  background: transparent;
  color: #6b7280;
  font-size: 1.3rem;
  cursor: pointer;
}

.modal-body {
  padding: 1rem;
}

.confirm-reason-label {
  display: block;
  margin: 0.75rem 0 0.35rem;
  color: #374151;
  font-weight: 600;
}

.modal-body textarea {
  width: 100%;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  padding: 0.6rem;
  resize: vertical;
  box-sizing: border-box;
}

.confirm-check {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.75rem;
}

.modal-actions {
  margin-top: 1rem;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.guardian-output {
  margin-bottom: 0.8rem;
}

.guardian-output pre {
  background: #111827;
  color: #e5e7eb;
  border-radius: 8px;
  padding: 0.75rem;
  font-size: 0.82rem;
  white-space: pre-wrap;
  margin: 0;
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
