<template>
  <div class="orders-container">
    <div class="header">
      <h1>Órdenes en cocina{{ restaurantName ? ` - ${restaurantName}` : '' }}</h1>
      <p>Panel de cocina - Órdenes a preparar</p>
      <p v-if="operatorName" class="operator-label">👤 Operando como: <strong>{{ operatorName }}</strong></p>
    </div>

    <div class="content">
      <!-- Operator check-in modal -->
      <div v-if="!operatorName" class="operator-overlay">
        <div class="operator-modal">
          <h2>🔐 Identificación requerida</h2>
          <p>Introduce tu nombre completo para registrar tu acceso a cocina. Quedará vinculado a todas las operaciones que realices.</p>
          <input
            v-model="operatorInputName"
            type="text"
            placeholder="Ej: Carlos López"
            class="operator-input"
            @keyup.enter="confirmOperator"
            autofocus
          />
          <button class="btn-operator-confirm" :disabled="!operatorInputName.trim()" @click="confirmOperator">
            Acceder a cocina
          </button>
        </div>
      </div>
      <div v-if="toast.show" class="toast" :class="`toast-${toast.type}`">{{ toast.message }}</div>

      <div class="actions-row">
        <button class="btn-refresh" @click="fetchOrders" :disabled="isLoading">↻ Actualizar</button>
        <span v-if="lastUpdated" class="last-updated">Última actualización: {{ lastUpdated }}</span>
      </div>

      <div class="stats">
        <div class="stat-card">
          <div class="stat-value">{{ newOrders.length }}</div>
          <div class="stat-label">Órdenes nuevas</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ preparingOrders.length }}</div>
          <div class="stat-label">En preparación</div>
        </div>
      </div>

      <div class="orders-section">
        <h2>Órdenes por preparar</h2>

        <div v-if="isLoading" class="loading">Cargando órdenes...</div>
        <div v-else-if="error" class="error-box">{{ error }}</div>

        <div v-else-if="kitchenOrders.length === 0" class="no-orders">
          No hay órdenes nuevas en este momento.
        </div>

        <div v-else class="orders-list">
          <div v-for="order in kitchenOrders" :key="order.id" class="order-card">
            <div class="order-head">
              <h3>Orden #{{ order.id }}</h3>
              <div class="tags">
                <span :class="['status-tag', `status-${order.status}`]">{{ getStatusLabel(order.status) }}</span>
                <span class="type-tag">{{ getOrderTypeLabel(order.type) }}</span>
              </div>
            </div>

            <p class="order-meta">
              <strong>Restaurante:</strong>
              {{ order.restaurant?.name || `#${order.restaurant_id}` }}
            </p>
            <p class="order-meta"><strong>Hora:</strong> {{ formatDate(order.created_at) }}</p>
            <p v-if="order.operator_name" class="order-meta operator-tag"><strong>👤 Operador:</strong> {{ order.operator_name }}</p>
            <p v-if="order.type === 'delivery' && order.delivery_address" class="order-meta"><strong>Dirección:</strong> {{ order.delivery_address }}</p>
            <p class="order-meta"><strong>Notas:</strong> {{ order.notes || 'Sin notas' }}</p>

            <ul v-if="order.order_items?.length" class="items-list">
              <li v-for="item in order.order_items" :key="item.id">
                {{ item.quantity }} x {{ item.product?.name || `Producto #${item.product_id}` }}
              </li>
            </ul>

            <div class="order-actions">
              <button
                v-if="order.status === 'pending' || order.status === 'paid'"
                class="btn btn-process"
                @click="updateOrderStatus(order.id, 'processing')"
              >
                Iniciar preparación
              </button>
              <button
                v-if="order.status === 'processing'"
                class="btn btn-complete"
                @click="updateOrderStatus(order.id, 'completed')"
              >
                Marcar lista
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useRoute } from 'vue-router'

const auth = useAuthStore()
const route = useRoute()
const orders = ref([])
const isLoading = ref(false)
const error = ref('')
const toast = ref({ show: false, type: 'success', message: '' })
const refreshIntervalId = ref(null)
const lastUpdated = ref('')
const restaurantId = computed(() => route.params.restaurantId ? parseInt(route.params.restaurantId) : null)
const restaurantName = ref('')
const operatorName = ref(sessionStorage.getItem('scan2order_operator') || '')
const operatorInputName = ref('')

const kitchenOrders = computed(() => {
  let filtered = orders.value
  
  // Filter by restaurant if specified
  if (restaurantId.value) {
    filtered = filtered.filter(order => order.restaurant_id === restaurantId.value)
  }
  
  return filtered.filter(order => {
    const status = String(order.status || '').toLowerCase()
    return ['pending', 'paid', 'processing'].includes(status)
  })
})

const newOrders = computed(() => kitchenOrders.value.filter(order => ['pending', 'paid'].includes(order.status)))
const preparingOrders = computed(() => kitchenOrders.value.filter(order => order.status === 'processing'))

onMounted(() => {
  if (auth.hasRole('staff')) {
    if (!restaurantId.value) {
      error.value = 'Staff debe acceder mediante su restaurante asignado'
      return
    }
    if (auth.staffRestaurantId && restaurantId.value !== auth.staffRestaurantId) {
      error.value = 'No tienes permiso para acceder a este restaurante'
      return
    }
  }

  fetchOrders()
  
  // Set restaurant name if filtering by restaurant
  if (restaurantId.value && orders.value.length > 0) {
    const firstOrder = orders.value.find(o => o.restaurant_id === restaurantId.value)
    if (firstOrder?.restaurant) {
      restaurantName.value = firstOrder.restaurant.name
    }
  }

  refreshIntervalId.value = setInterval(() => {
    fetchOrders({ silent: true })
  }, 20000)
})

onBeforeUnmount(() => {
  if (refreshIntervalId.value) {
    clearInterval(refreshIntervalId.value)
  }
})

function confirmOperator() {
  const name = operatorInputName.value.trim()
  if (!name) return
  operatorName.value = name
  sessionStorage.setItem('scan2order_operator', name)
}

function showToast(message, type = 'success') {
  toast.value = { show: true, type, message }
  setTimeout(() => {
    toast.value.show = false
  }, 2500)
}

function getOrderTypeLabel(type) {
  if (type === 'delivery') return 'Para llevar'
  if (type === 'local') return 'Consumir en local'
  return type || '-'
}

function getStatusLabel(status) {
  const map = {
    pending: 'Nueva',
    paid: 'Pagada',
    processing: 'En preparación',
    completed: 'Completada',
    cancelled: 'Cancelada'
  }
  return map[status] || status
}

function formatDate(value) {
  if (!value) return '-'
  return new Date(value).toLocaleString('es-ES')
}

async function fetchOrders(options = {}) {
  if (!auth.token || isLoading.value) return

  const { silent = false } = options
  if (!silent) {
    isLoading.value = true
  }
  error.value = ''

  try {
    const response = await fetch('/api/orders', {
      headers: {
        Authorization: `Bearer ${auth.token}`,
        Accept: 'application/json'
      }
    })

    if (!response.ok) throw new Error('No se pudieron cargar las órdenes')

    orders.value = await response.json()
    
    // Update restaurant name if filtering by restaurant
    if (restaurantId.value && orders.value.length > 0) {
      const firstOrder = orders.value.find(o => o.restaurant_id === restaurantId.value)
      if (firstOrder?.restaurant) {
        restaurantName.value = firstOrder.restaurant.name
      }
    }
  } catch (err) {
    error.value = err.message
  } finally {
    if (!silent) {
      isLoading.value = false
    }
    const now = new Date()
    lastUpdated.value = now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
  }
}

async function updateOrderStatus(orderId, status) {
  try {
    const response = await fetch(`/api/orders/${orderId}`, {
      method: 'PUT',
      headers: {
        Authorization: `Bearer ${auth.token}`,
        'Content-Type': 'application/json',
        Accept: 'application/json'
      },
      body: JSON.stringify({ status, operator_name: operatorName.value || null })
    })

    if (!response.ok) throw new Error('No se pudo actualizar la orden')

    showToast(`Orden #${orderId} actualizada`)
    await fetchOrders()
  } catch (err) {
    showToast(err.message, 'error')
  }
}
</script>

<style scoped>
.orders-container {
  max-width: 1100px;
  margin: 0 auto;
}

.header {
  text-align: center;
  color: white;
  margin-bottom: 2rem;
}

.header h1 {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.operator-label {
  font-size: 0.95rem;
  color: rgba(255,255,255,0.9);
  margin-top: 0.25rem;
}

.content {
  background: white;
  border-radius: 10px;
  padding: 2rem;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
  position: relative;
}

.operator-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.65);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
}

.operator-modal {
  background: white;
  border-radius: 12px;
  padding: 2rem 2.5rem;
  max-width: 420px;
  width: 100%;
  text-align: center;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.operator-modal h2 {
  font-size: 1.4rem;
  margin-bottom: 0.75rem;
  color: #2c3e50;
}

.operator-modal p {
  color: #666;
  font-size: 0.9rem;
  margin-bottom: 1.25rem;
}

.operator-input {
  width: 100%;
  border: 2px solid #ddd;
  border-radius: 8px;
  padding: 0.75rem 1rem;
  font-size: 1rem;
  margin-bottom: 1rem;
  box-sizing: border-box;
  transition: border-color 0.2s;
}

.operator-input:focus {
  outline: none;
  border-color: #e74c3c;
}

.btn-operator-confirm {
  width: 100%;
  background: #e74c3c;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 0.75rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-operator-confirm:disabled {
  background: #bdc3c7;
  cursor: not-allowed;
}

.btn-operator-confirm:not(:disabled):hover {
  background: #c0392b;
}

.operator-tag {
  color: #1565c0;
  font-style: italic;
}

.actions-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.last-updated {
  color: #666;
  font-size: 0.85rem;
  font-style: italic;
}

.btn-refresh {
  background: #3498db;
  color: white;
  border: none;
  padding: 0.65rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
}

.btn-refresh:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
  color: white;
  padding: 1.25rem;
  border-radius: 10px;
  text-align: center;
}

.stat-value {
  font-size: 1.9rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.stat-label {
  font-size: 0.9rem;
  opacity: 0.95;
}

.orders-section h2 {
  color: #2c3e50;
  font-size: 1.3rem;
  margin-bottom: 1rem;
  border-bottom: 2px solid #ecf0f1;
  padding-bottom: 0.5rem;
}

.loading,
.no-orders {
  text-align: center;
  padding: 1.5rem;
  color: #7f8c8d;
  background: #f5f6fa;
  border-radius: 6px;
}

.error-box {
  text-align: center;
  padding: 1rem;
  color: #c0392b;
  background: #fdecec;
  border-radius: 6px;
}

.orders-list {
  display: grid;
  gap: 1rem;
}

.order-card {
  border: 1px solid #e7ecf3;
  border-radius: 10px;
  padding: 1rem;
  background: #fff;
}

.order-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.6rem;
}

.order-head h3 {
  margin: 0;
  color: #2c3e50;
}

.tags {
  display: flex;
  gap: 0.5rem;
}

.status-tag,
.type-tag {
  border-radius: 999px;
  font-size: 0.8rem;
  padding: 0.3rem 0.6rem;
  font-weight: 700;
}

.status-pending,
.status-paid {
  background: #fff4dd;
  color: #9a6a00;
}

.status-processing {
  background: #eaf2ff;
  color: #2458b8;
}

.type-tag {
  background: #edf2ff;
  color: #4b5ed7;
}

.order-meta {
  margin: 0.25rem 0;
  color: #5f6c7b;
}

.items-list {
  margin: 0.7rem 0;
  padding-left: 1rem;
  color: #4a5568;
}

.order-actions {
  margin-top: 0.8rem;
  display: flex;
  gap: 0.6rem;
}

.btn {
  border: none;
  border-radius: 6px;
  padding: 0.55rem 0.8rem;
  cursor: pointer;
  font-weight: 600;
  color: white;
}

.btn-process {
  background: #e67e22;
}

.btn-complete {
  background: #27ae60;
}

.toast {
  position: fixed;
  top: 1rem;
  right: 1rem;
  padding: 0.8rem 1rem;
  border-radius: 6px;
  color: white;
  font-weight: 600;
  z-index: 3000;
}

.toast-success {
  background: #27ae60;
}

.toast-error {
  background: #e74c3c;
}

@media (max-width: 768px) {
  .content {
    padding: 1rem;
  }

  .order-head {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }

  .order-actions {
    flex-direction: column;
  }
}
</style>
