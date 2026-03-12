<template>
  <div class="payments-container">
    <div class="header">
      <h1>Gestión de Pagos{{ restaurantName ? ` - ${restaurantName}` : '' }}</h1>
      <p>Panel de caja - Procesa los pagos de las órdenes</p>
    </div>

    <div class="content">
      <div v-if="toast.show" class="toast" :class="`toast-${toast.type}`">{{ toast.message }}</div>

      <div class="actions-row">
        <button class="btn-refresh" @click="fetchOrders" :disabled="isLoading">↻ Actualizar</button>
        <button class="btn-history" @click="showHistory = !showHistory">
          {{ showHistory ? '← Volver a pendientes' : '📋 Ver historial del día' }}
        </button>
        <span v-if="lastUpdated" class="last-updated">Última actualización: {{ lastUpdated }}</span>
      </div>

      <div class="stats">
        <div class="stat-card">
          <div class="stat-value">{{ showHistory ? todayPaidOrders.length : pendingPaymentOrders.length }}</div>
          <div class="stat-label">{{ showHistory ? 'Pagos realizados hoy' : 'Órdenes pendientes de cobro' }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">${{ showHistory ? todayTotal.toFixed(2) : pendingTotal.toFixed(2) }}</div>
          <div class="stat-label">{{ showHistory ? 'Total cobrado hoy' : 'Total pendiente' }}</div>
        </div>
      </div>

      <div class="orders-section">
        <h2>{{ showHistory ? 'Historial del día' : 'Órdenes en espera de pago' }}</h2>

        <div v-if="isLoading" class="loading">Cargando órdenes...</div>
        <div v-else-if="error" class="error-box">{{ error }}</div>

        <div v-else-if="!showHistory && pendingPaymentOrders.length === 0" class="no-orders">
          No hay órdenes pendientes de pago en este momento.
        </div>
        
        <div v-else-if="showHistory && todayPaidOrders.length === 0" class="no-orders">
          No hay pagos realizados el día de hoy.
        </div>

        <div v-else class="orders-list">
          <div v-for="order in (showHistory ? todayPaidOrders : pendingPaymentOrders)" :key="order.id" class="order-card" :class="{ 'order-paid': showHistory }">
            <div class="order-head">
              <h3>Orden #{{ order.id }}</h3>
              <span class="order-type">{{ getOrderTypeLabel(order.type) }}</span>
              <span v-if="showHistory" class="payment-badge">✓ Cobrada</span>
            </div>

            <p class="order-meta">
              <strong>Restaurante:</strong>
              {{ order.restaurant?.name || `#${order.restaurant_id}` }}
            </p>
            <p class="order-meta"><strong>Estado:</strong> {{ getStatusLabel(order.status) }}</p>
            <p class="order-meta"><strong>Total:</strong> ${{ Number(order.total || 0).toFixed(2) }}</p>
            <p class="order-meta"><strong>Método:</strong> {{ getPaymentMethod(order) }}</p>
            <p v-if="showHistory" class="order-meta"><strong>Cobrada:</strong> {{ formatDateTime(order.updated_at) }}</p>

            <ul v-if="order.order_items?.length" class="items-list">
              <li v-for="item in order.order_items" :key="item.id">
                {{ item.quantity }} x {{ item.product?.name || `Producto #${item.product_id}` }}
              </li>
            </ul>

            <div v-if="!showHistory" class="order-actions">
              <button class="btn btn-paid" @click="markAsPaid(order)">Marcar como cobrada</button>
              <button class="btn btn-cancel" @click="markAsCancelled(order)">Cancelar</button>
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
import { isOrderPendingPayment, isOrderCollectedToday } from '../../utils/orderPayments'

const auth = useAuthStore()
const route = useRoute()
const orders = ref([])
const isLoading = ref(false)
const error = ref('')
const toast = ref({ show: false, type: 'success', message: '' })
const refreshIntervalId = ref(null)
const lastUpdated = ref('')
const showHistory = ref(false)
const restaurantId = computed(() => route.params.restaurantId ? parseInt(route.params.restaurantId) : null)
const restaurantName = ref('')

const pendingPaymentOrders = computed(() => {
  let filtered = orders.value
  
  // Filter by restaurant if specified
  if (restaurantId.value) {
    filtered = filtered.filter(order => order.restaurant_id === restaurantId.value)
  }
  
  return filtered.filter(order => {
    return isOrderPendingPayment(order)
  })
})

const pendingTotal = computed(() => {
  return pendingPaymentOrders.value.reduce((sum, order) => sum + Number(order.total || 0), 0)
})

const todayPaidOrders = computed(() => {
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  
  let filtered = orders.value
  
  // Filter by restaurant if specified
  if (restaurantId.value) {
    filtered = filtered.filter(order => order.restaurant_id === restaurantId.value)
  }
  
  return filtered.filter(order => {
    return isOrderCollectedToday(order, today)
  }).sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at))
})

const todayTotal = computed(() => {
  return todayPaidOrders.value.reduce((sum, order) => sum + Number(order.total || 0), 0)
})

onMounted(() => {
  // Validate staff access: must be accessing their assigned restaurant
  if (auth.hasRole('staff')) {
    if (!restaurantId.value) {
      error.value = 'Staff debe acceder mediante su restaurante asignado'
      return
    }
    if (restaurantId.value !== auth.staffRestaurantId) {
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
    pending: 'Pendiente',
    paid: 'Pagada',
    processing: 'En proceso',
    completed: 'Completada',
    cancelled: 'Cancelada'
  }
  return map[status] || status
}

function getPaymentMethod(order) {
  const payment = order.payments?.[0]
  if (!payment) return 'No registrado'
  if (payment.method === 'cash') return 'Efectivo'
  if (payment.method === 'stripe') return 'Tarjeta'
  return payment.method
}

function formatDateTime(dateString) {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
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
  const response = await fetch(`/api/orders/${orderId}`, {
    method: 'PUT',
    headers: {
      Authorization: `Bearer ${auth.token}`,
      'Content-Type': 'application/json',
      Accept: 'application/json'
    },
    body: JSON.stringify({ status })
  })

  if (!response.ok) {
    throw new Error('No se pudo actualizar la orden')
  }
}

async function markAsPaid(order) {
  try {
    // First create a cash payment with immediate success
    const paymentResponse = await fetch(`/api/orders/${order.id}/payments/cash`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${auth.token}`,
        'Content-Type': 'application/json',
        Accept: 'application/json'
      },
      body: JSON.stringify({
        amount: order.total,
        immediate: true
      })
    })
    
    if (!paymentResponse.ok) {
      throw new Error('No se pudo registrar el pago')
    }
    
    // Then update order status to paid
    await updateOrderStatus(order.id, 'paid')
    showToast(`Orden #${order.id} marcada como cobrada`)
    await fetchOrders()
  } catch (err) {
    showToast(err.message, 'error')
  }
}

async function markAsCancelled(order) {
  try {
    await updateOrderStatus(order.id, 'cancelled')
    showToast(`Orden #${order.id} cancelada`, 'error')
    await fetchOrders()
  } catch (err) {
    showToast(err.message, 'error')
  }
}
</script>

<style scoped>
.payments-container {
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

.content {
  background: white;
  border-radius: 10px;
  padding: 2rem;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
  position: relative;
}

.actions-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  gap: 0.75rem;
  flex-wrap: wrap;
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

.btn-history {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.65rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: transform 0.2s;
}

.btn-history:hover {
  transform: translateY(-2px);
}

.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

.order-type {
  background: #edf2ff;
  color: #4b5ed7;
  border-radius: 999px;
  font-size: 0.8rem;
  padding: 0.3rem 0.6rem;
  font-weight: 700;
}

.payment-badge {
  background: #d4edda;
  color: #27ae60;
  border-radius: 999px;
  font-size: 0.8rem;
  padding: 0.3rem 0.6rem;
  font-weight: 700;
}

.order-paid {
  background: #f8f9fa;
  border-color: #27ae60;
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
}

.btn-paid {
  background: #27ae60;
  color: white;
}

.btn-cancel {
  background: #e74c3c;
  color: white;
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
