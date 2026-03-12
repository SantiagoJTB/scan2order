<template>
  <div v-if="canAccessAdmin" class="orders-container">
    <div v-if="toast.show" class="toast" :class="`toast-${toast.type}`">{{ toast.message }}</div>

    <div class="header">
      <h1>Gestión de Órdenes</h1>
      <div class="header-actions">
        <select v-model="selectedStatus" class="filter-select">
          <option value="all">Todas</option>
          <option value="pending">Pendientes</option>
          <option value="processing">En proceso</option>
          <option value="completed">Completadas</option>
          <option value="cancelled">Canceladas</option>
        </select>
        <button class="btn-refresh" @click="fetchOrders">↻ Actualizar</button>
      </div>
    </div>

    <div class="content">
      <div v-if="isLoading" class="loading">Cargando órdenes...</div>
      <div v-else-if="error" class="error-message">{{ error }}</div>
      <div v-else-if="filteredOrders.length === 0" class="empty-state">
        No hay órdenes para el filtro seleccionado.
      </div>

      <div v-else class="orders-table">
        <div class="table-header">
          <div>#</div>
          <div>Restaurante</div>
          <div>Tipo</div>
          <div>Total</div>
          <div>Estado</div>
          <div>Fecha</div>
        </div>

        <div v-for="order in filteredOrders" :key="order.id" class="table-row">
          <div>#{{ order.id }}</div>
          <div>Restaurante #{{ order.restaurant_id }}</div>
          <div>{{ formatOrderType(order.type) }}</div>
          <div>${{ formatCurrency(order.total) }}</div>
          <div>
            <select
              :value="order.status"
              class="status-select"
              @change="updateOrderStatus(order, $event.target.value)"
            >
              <option value="pending">pending</option>
              <option value="processing">processing</option>
              <option value="completed">completed</option>
              <option value="cancelled">cancelled</option>
            </select>
          </div>
          <div>{{ formatDate(order.created_at) }}</div>
        </div>
      </div>
    </div>
  </div>

  <div v-else class="orders-container">
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
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()
const canAccessAdmin = computed(() => auth.hasAnyRole(['admin', 'superadmin']))

const orders = ref([])
const isLoading = ref(false)
const error = ref('')
const selectedStatus = ref('all')
const toast = ref({ show: false, type: 'success', message: '' })

const filteredOrders = computed(() => {
  const sorted = [...orders.value].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))

  if (selectedStatus.value === 'all') return sorted

  return sorted.filter((order) => order.status === selectedStatus.value)
})

onMounted(() => {
  if (canAccessAdmin.value) {
    fetchOrders()
  }
})

function showToast(message, type = 'success') {
  toast.value = { show: true, type, message }
  setTimeout(() => {
    toast.value.show = false
  }, 2500)
}

function formatCurrency(value) {
  const numericValue = Number(value || 0)
  return numericValue.toFixed(2)
}

function formatDate(dateString) {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleString('es-ES')
}

function formatOrderType(type) {
  if (type === 'delivery') return 'Para llevar'
  if (type === 'local') return 'Consumir en local'
  return type || '-'
}

async function fetchOrders() {
  isLoading.value = true
  error.value = ''

  try {
    const response = await fetch('/api/orders', {
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok) throw new Error('No se pudieron cargar las órdenes')

    orders.value = await response.json()
  } catch (err) {
    error.value = err.message
  } finally {
    isLoading.value = false
  }
}

async function updateOrderStatus(order, status) {
  const previousStatus = order.status
  order.status = status

  try {
    const response = await fetch(`/api/orders/${order.id}`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ status })
    })

    if (!response.ok) throw new Error('No se pudo actualizar el estado')

    showToast(`Orden #${order.id} actualizada a ${status}`)
  } catch (err) {
    order.status = previousStatus
    showToast(err.message, 'error')
  }
}
</script>

<style scoped>
.orders-container {
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

.header-actions {
  display: flex;
  gap: 1rem;
}

.btn-refresh {
  background: #2ecc71;
  color: white;
  border: none;
  padding: 0.75rem 1rem;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 600;
}

.filter-select {
  background: white;
  color: #2c3e50;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 600;
  font-size: 1rem;
}

.content {
  background: white;
  border-radius: 10px;
  padding: 2rem;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.loading,
.empty-state {
  text-align: center;
  padding: 2rem;
  color: #7f8c8d;
}

.error-message {
  text-align: center;
  padding: 1rem;
  border-radius: 8px;
  background: #fdecea;
  color: #c0392b;
}

.orders-table {
  border: 1px solid #ecf0f1;
  border-radius: 8px;
  overflow: hidden;
}

.table-header,
.table-row {
  display: grid;
  grid-template-columns: 70px 1.6fr 1fr 1fr 1.2fr 1.2fr;
  gap: 0.75rem;
  align-items: center;
  padding: 0.75rem 1rem;
}

.table-header {
  background: #f4f6f8;
  color: #2c3e50;
  font-weight: 700;
}

.table-row {
  border-top: 1px solid #ecf0f1;
}

.status-select {
  width: 100%;
  padding: 0.4rem 0.5rem;
  border: 1px solid #dfe6e9;
  border-radius: 5px;
}

.toast {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 1000;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  color: white;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.toast-success {
  background: #2ecc71;
}

.toast-error {
  background: #e74c3c;
}
</style>
