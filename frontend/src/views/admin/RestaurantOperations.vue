<template>
  <div class="restaurant-operations-container">
    <div class="header">
      <h1>{{ restaurantName }}</h1>
      <p>Gestión de operaciones - Caja y Cocina</p>
      <div class="header-actions">
        <button class="btn-history" @click="showHistory = !showHistory">
          {{ showHistory ? 'Ocultar histórico diario' : 'Ver histórico diario' }}
        </button>
        <router-link to="/admin/restaurants" class="btn-back">← Volver a restaurantes</router-link>
      </div>
    </div>

    <div class="operations-grid">
      <div class="operations-panel payments-panel">
        <div class="panel-header">
          <h2>💰 Caja</h2>
          <p>Procesa los pagos de las órdenes</p>
          <p class="section-note">Los cobros y cancelaciones solo pueden hacerse desde caja.</p>
        </div>

        <div class="panel-content">
          <div v-if="paymentToast.show" class="toast" :class="`toast-${paymentToast.type}`">
            {{ paymentToast.message }}
          </div>

          <div class="actions-row">
            <button class="btn-refresh" @click="fetchPayments" :disabled="paymentLoading">↻ Actualizar</button>
            <span v-if="paymentLastUpdated" class="last-updated">{{ paymentLastUpdated }}</span>
          </div>

          <div class="stats">
            <div class="stat-card">
              <div class="stat-value">{{ pendingCollectionOrders.length }}</div>
              <div class="stat-label">Pendientes de cobro</div>
            </div>
            <div class="stat-card">
              <div class="stat-value">{{ readyPaymentOrders.length }}</div>
              <div class="stat-label">Órdenes listas</div>
            </div>
            <div class="stat-card">
              <div class="stat-value">${{ pendingTotal.toFixed(2) }}</div>
              <div class="stat-label">Total</div>
            </div>
          </div>

          <div v-if="paymentLoading" class="loading">Cargando órdenes...</div>
          <div v-else-if="paymentError" class="error-box">{{ paymentError }}</div>
          <div v-else class="payments-columns">
            <div class="payments-column">
              <h3 class="payments-column-title">Pendientes de cobro</h3>
              <div v-if="pendingCollectionOrders.length === 0" class="no-orders">
                No hay órdenes pendientes de cobro.
              </div>
              <div v-else class="orders-list">
                <div v-for="order in pendingCollectionOrders" :key="order.id" class="order-card">
                  <div class="order-head">
                    <h3>Orden #{{ order.id }}</h3>
                    <span class="order-type">{{ getOrderTypeLabel(order.type) }}</span>
                  </div>

                  <p class="order-meta"><strong>Estado:</strong> {{ getStatusLabel(order.status) }}</p>
                  <p class="order-meta"><strong>Total:</strong> ${{ Number(order.total || 0).toFixed(2) }}</p>
                  <p class="order-meta"><strong>Método:</strong> {{ getPaymentMethod(order) }}</p>

                  <ul v-if="order.order_items?.length" class="items-list">
                    <li v-for="item in order.order_items" :key="item.id">
                      {{ item.quantity }} x {{ item.product?.name || `Producto #${item.product_id}` }}
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <div class="payments-column">
              <h3 class="payments-column-title">Órdenes listas</h3>
              <div v-if="readyPaymentOrders.length === 0" class="no-orders">
                No hay órdenes listas pendientes de cobro.
              </div>
              <div v-else class="orders-list">
                <div v-for="order in readyPaymentOrders" :key="order.id" class="order-card status-completed">
                  <div class="order-head">
                    <h3>Orden #{{ order.id }}</h3>
                    <span class="order-type">{{ getOrderTypeLabel(order.type) }}</span>
                  </div>

                  <p class="ready-priority">🔔 Prioridad de cobro</p>

                  <p class="order-meta"><strong>Estado:</strong> {{ getStatusLabel(order.status) }}</p>
                  <p class="order-meta"><strong>Total:</strong> ${{ Number(order.total || 0).toFixed(2) }}</p>
                  <p class="order-meta"><strong>Método:</strong> {{ getPaymentMethod(order) }}</p>

                  <ul v-if="order.order_items?.length" class="items-list">
                    <li v-for="item in order.order_items" :key="item.id">
                      {{ item.quantity }} x {{ item.product?.name || `Producto #${item.product_id}` }}
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="operations-panel kitchen-panel">
        <div class="panel-header">
          <h2>👨‍🍳 Cocina</h2>
          <p>Gestiona la preparación de órdenes</p>
          <p class="section-note">La preparación solo puede gestionarse desde cocina.</p>
        </div>

        <div class="panel-content">
          <div v-if="kitchenToast.show" class="toast" :class="`toast-${kitchenToast.type}`">
            {{ kitchenToast.message }}
          </div>

          <div class="actions-row">
            <button class="btn-refresh" @click="fetchKitchenOrders" :disabled="kitchenLoading">↻ Actualizar</button>
            <span v-if="kitchenLastUpdated" class="last-updated">{{ kitchenLastUpdated }}</span>
          </div>

          <div class="stats">
            <div class="stat-card">
              <div class="stat-value">{{ kitchenOrders.length }}</div>
              <div class="stat-label">En preparación</div>
            </div>
          </div>

          <div v-if="kitchenLoading" class="loading">Cargando órdenes...</div>
          <div v-else-if="kitchenError" class="error-box">{{ kitchenError }}</div>
          <div v-else-if="kitchenOrders.length === 0" class="no-orders">
            No hay órdenes en preparación.
          </div>

          <div v-else class="orders-list">
            <div v-for="order in kitchenOrders" :key="order.id" class="order-card" :class="`status-${order.status}`">
              <div class="order-head">
                <h3>Orden #{{ order.id }}</h3>
                <span class="order-type">{{ getOrderTypeLabel(order.type) }}</span>
              </div>

              <p class="order-meta"><strong>Estado:</strong> {{ getStatusLabel(order.status) }}</p>

              <ul v-if="order.order_items?.length" class="items-list">
                <li v-for="item in order.order_items" :key="item.id">
                  {{ item.quantity }} x {{ item.product?.name || `Producto #${item.product_id}` }}
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showHistory" class="history-section">
      <div class="history-header">
        <h2>Histórico por días</h2>
        <p>Pedidos realizados y dinero cobrado (caja y online)</p>
      </div>

      <div v-if="dailyHistory.length === 0" class="no-orders">
        No hay registros históricos para este restaurante.
      </div>

      <div v-else class="history-list">
        <div v-for="day in dailyHistory" :key="day.dateKey" class="history-card">
          <div class="history-day">{{ day.displayDate }}</div>
          <div class="history-stats">
            <div class="history-stat">
              <span class="history-label">Pedidos hechos</span>
              <span class="history-value">{{ day.ordersCount }}</span>
            </div>
            <div class="history-stat">
              <span class="history-label">Cobrado en caja</span>
              <span class="history-value">${{ day.cashCollected.toFixed(2) }}</span>
            </div>
            <div class="history-stat">
              <span class="history-label">Cobrado online</span>
              <span class="history-value">${{ day.onlineCollected.toFixed(2) }}</span>
            </div>
            <div class="history-stat history-total">
              <span class="history-label">Total cobrado</span>
              <span class="history-value">${{ day.totalCollected.toFixed(2) }}</span>
            </div>
          </div>

          <div v-if="day.consumedItems.length > 0" class="history-details">
            <h4>Consumo del día</h4>
            <ul class="history-consumed-list">
              <li v-for="item in day.consumedItems" :key="`${day.dateKey}-consumed-${item.name}`">
                {{ item.quantity }} x {{ item.name }}
              </li>
            </ul>
          </div>

          <div v-if="day.invoices.length > 0" class="history-details">
            <h4>Facturas del día</h4>
            <div class="history-invoices">
              <div v-for="invoice in day.invoices" :key="`${day.dateKey}-invoice-${invoice.orderId}`" class="history-invoice-card">
                <div class="history-invoice-row">
                  <strong>Pedido #{{ invoice.orderId }}</strong>
                  <span>{{ invoice.methodLabel }}</span>
                </div>
                <div class="history-invoice-row">
                  <span>{{ invoice.itemsCount }} ítems</span>
                  <span>${{ invoice.amount.toFixed(2) }}</span>
                </div>
                <div v-if="invoice.operatorName" class="history-invoice-row history-operator">
                  <span>👤 {{ invoice.operatorName }}</span>
                </div>
              </div>
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
const restaurantId = computed(() => parseInt(route.params.restaurantId))
const restaurantName = ref('')
const showHistory = ref(false)

// Payments panel
const paymentOrders = ref([])
const paymentLoading = ref(false)
const paymentError = ref('')
const paymentToast = ref({ show: false, type: 'success', message: '' })
const paymentLastUpdated = ref('')
const paymentRefreshId = ref(null)

// Kitchen panel
const kitchenOrders = ref([])
const kitchenLoading = ref(false)
const kitchenError = ref('')
const kitchenToast = ref({ show: false, type: 'success', message: '' })
const kitchenLastUpdated = ref('')
const kitchenRefreshId = ref(null)

const pendingPaymentOrders = computed(() =>
  paymentOrders.value.filter(order => {
    const status = String(order.status || '').toLowerCase()
    if (['cancelled', 'paid'].includes(status)) return false

    const payments = order.payments || []
    if (payments.length === 0) return true

    return payments.some(payment => String(payment.status || '').toLowerCase() === 'pending')
  })
)

function orderRecencyValue(order) {
  const byUpdatedAt = new Date(order?.updated_at || '').getTime()
  if (!Number.isNaN(byUpdatedAt) && byUpdatedAt > 0) return byUpdatedAt

  const byCreatedAt = new Date(order?.created_at || '').getTime()
  if (!Number.isNaN(byCreatedAt) && byCreatedAt > 0) return byCreatedAt

  return Number(order?.id || 0)
}

const readyPaymentOrders = computed(() =>
  pendingPaymentOrders.value
    .filter(order => String(order.status || '').toLowerCase() === 'completed')
    .slice()
    .sort((a, b) => orderRecencyValue(b) - orderRecencyValue(a))
)

const pendingCollectionOrders = computed(() =>
  pendingPaymentOrders.value
    .filter(order => String(order.status || '').toLowerCase() !== 'completed')
    .slice()
    .sort((a, b) => orderRecencyValue(b) - orderRecencyValue(a))
)

const pendingTotal = computed(() =>
  pendingPaymentOrders.value.reduce((sum, order) => sum + Number(order.total || 0), 0)
)

function getDateKey(dateValue) {
  if (!dateValue) return null
  const date = new Date(dateValue)
  if (Number.isNaN(date.getTime())) return null

  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function getOrderPaymentMethod(order) {
  const payment = Array.isArray(order.payments) && order.payments.length ? order.payments[0] : null
  return String(payment?.method || '').toLowerCase()
}

function getOrderItems(order) {
  if (Array.isArray(order.order_items)) return order.order_items
  if (Array.isArray(order.orderItems)) return order.orderItems
  return []
}

function getSucceededPayment(order) {
  const payments = Array.isArray(order.payments) ? order.payments : []
  return payments.find(payment => {
    const paymentStatus = String(payment.status || '').toLowerCase()
    return paymentStatus === 'succeeded' || Boolean(payment.paid_at)
  }) || null
}

function getCollectedAmount(order) {
  const succeededPayment = getSucceededPayment(order)
  if (succeededPayment) {
    return Number(succeededPayment.amount || order.total || 0)
  }

  const orderStatus = String(order.status || '').toLowerCase()
  if (orderStatus === 'paid') {
    return Number(order.total || 0)
  }

  return 0
}

function getCollectedMethod(order) {
  const succeededPayment = getSucceededPayment(order)
  if (succeededPayment) {
    return String(succeededPayment.method || '').toLowerCase()
  }
  return getOrderPaymentMethod(order)
}

function isOrderCollected(order) {
  const orderStatus = String(order.status || '').toLowerCase()
  if (orderStatus === 'paid') return true

  const payments = Array.isArray(order.payments) ? order.payments : []
  return payments.some(payment => {
    const paymentStatus = String(payment.status || '').toLowerCase()
    return paymentStatus === 'succeeded' || Boolean(payment.paid_at)
  })
}

function getCollectionDate(order) {
  const payments = Array.isArray(order.payments) ? order.payments : []
  const succeededPayment = payments.find(payment => {
    const paymentStatus = String(payment.status || '').toLowerCase()
    return paymentStatus === 'succeeded' || Boolean(payment.paid_at)
  })

  return succeededPayment?.paid_at || succeededPayment?.created_at || order.updated_at || order.created_at || null
}

const dailyHistory = computed(() => {
  const byDate = new Map()

  const ensureDayEntry = (dateKey) => {
    if (!byDate.has(dateKey)) {
      byDate.set(dateKey, {
        dateKey,
        displayDate: new Date(`${dateKey}T00:00:00`).toLocaleDateString('es-ES'),
        ordersCount: 0,
        cashCollected: 0,
        onlineCollected: 0,
        totalCollected: 0,
        consumedMap: new Map(),
        consumedItems: [],
        invoices: []
      })
    }

    return byDate.get(dateKey)
  }

  for (const order of paymentOrders.value) {
    const orderDateKey = getDateKey(order.created_at)
    if (orderDateKey) {
      const dayEntry = ensureDayEntry(orderDateKey)
      dayEntry.ordersCount += 1

      const orderItems = getOrderItems(order)
      for (const item of orderItems) {
        const productName = item?.product?.name || `Producto #${item?.product_id || 'N/A'}`
        const quantity = Number(item?.quantity || 0)
        if (quantity <= 0) continue

        const currentQty = dayEntry.consumedMap.get(productName) || 0
        dayEntry.consumedMap.set(productName, currentQty + quantity)
      }

      const invoiceAmount = getCollectedAmount(order)
      const method = getCollectedMethod(order)
      const methodLabel = method ? getPaymentMethod({ payments: [{ method }] }) : 'Por determinar'
      dayEntry.invoices.push({
        orderId: order.id,
        amount: Number(invoiceAmount || 0),
        methodLabel,
        itemsCount: orderItems.reduce((sum, item) => sum + Number(item?.quantity || 0), 0),
        operatorName: order.operator_name || null
      })
    }

    if (!isOrderCollected(order)) continue

    const collectionDateKey = getDateKey(getCollectionDate(order))
    if (!collectionDateKey) continue

    const entry = ensureDayEntry(collectionDateKey)
    const amount = getCollectedAmount(order)
    const method = getCollectedMethod(order)
    const isCash = ['cash', 'table'].includes(method)

    if (isCash) {
      entry.cashCollected += amount
    } else {
      entry.onlineCollected += amount
    }
    entry.totalCollected += amount
  }

  return Array.from(byDate.values())
    .map((entry) => {
      entry.consumedItems = Array.from(entry.consumedMap.entries())
        .map(([name, quantity]) => ({ name, quantity }))
        .sort((a, b) => b.quantity - a.quantity)
      return entry
    })
    .sort((a, b) => b.dateKey.localeCompare(a.dateKey))
})

function getStatusLabel(status) {
  const labels = {
    pending: 'Pendiente',
    preparing: 'En preparación',
    completed: 'Lista',
    cancelled: 'Cancelada'
  }
  return labels[String(status || '').toLowerCase()] || status
}

function getOrderTypeLabel(type) {
  return type === 'delivery' ? 'Para llevar' : 'Consumir en local'
}

function getPaymentMethod(order) {
  if (!order.payments?.length) return 'Por determinar'
  const payment = order.payments[0]
  const methods = {
    card: 'Tarjeta',
    cash: 'Efectivo',
    table: 'En mesa',
    stripe: 'Tarjeta',
    test: 'Test'
  }
  return methods[String(payment.method || '').toLowerCase()] || payment.method
}

function showPaymentToast(message, type = 'success') {
  paymentToast.value = { show: true, type, message }
  setTimeout(() => {
    paymentToast.value.show = false
  }, 2500)
}

function showKitchenToast(message, type = 'success') {
  kitchenToast.value = { show: true, type, message }
  setTimeout(() => {
    kitchenToast.value.show = false
  }, 2500)
}

function updateTime() {
  const now = new Date()
  const timeStr = now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
  paymentLastUpdated.value = `Última actualización: ${timeStr}`
  kitchenLastUpdated.value = `Última actualización: ${timeStr}`
}

async function fetchRestaurantName() {
  try {
    const response = await fetch(`/api/restaurants/${restaurantId.value}`, {
      headers: {
        'Accept': 'application/json',
        ...(auth.token ? { 'Authorization': `Bearer ${auth.token}` } : {})
      }
    })

    if (response.ok) {
      const data = await response.json()
      restaurantName.value = data.name || 'Restaurante'
    }
  } catch (err) {
    restaurantName.value = `Restaurante #${restaurantId.value}`
  }
}

async function fetchPayments(options = {}) {
  if (!auth.token || paymentLoading.value) return
  
  const { silent = false } = options
  if (!silent) {
    paymentLoading.value = true
  }
  paymentError.value = ''

  try {
    const response = await fetch('/api/orders', {
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok) throw new Error('No se pudieron cargar las órdenes')

    const data = await response.json()
    paymentOrders.value = (Array.isArray(data) ? data : []).filter(
      order => Number(order.restaurant_id) === Number(restaurantId.value)
    )
    updateTime()
  } catch (err) {
    paymentError.value = err.message
  } finally {
    if (!silent) {
      paymentLoading.value = false
    }
  }
}

async function fetchKitchenOrders(options = {}) {
  if (!auth.token || kitchenLoading.value) return
  
  const { silent = false } = options
  if (!silent) {
    kitchenLoading.value = true
  }
  kitchenError.value = ''

  try {
    const response = await fetch('/api/orders', {
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok) throw new Error('No se pudieron cargar las órdenes')

    const data = await response.json()
    kitchenOrders.value = (Array.isArray(data) ? data : []).filter(order => {
      if (Number(order.restaurant_id) !== Number(restaurantId.value)) return false
      const status = String(order.status || '').toLowerCase()
      return !['paid', 'completed', 'cancelled'].includes(status)
    })
    updateTime()
  } catch (err) {
    kitchenError.value = err.message
  } finally {
    if (!silent) {
      kitchenLoading.value = false
    }
  }
}

async function markAsPaid(order) {
  try {
    const response = await fetch(`/api/orders/${order.id}`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ status: 'paid', operator_name: auth.user?.name || null })
    })

    if (!response.ok) throw new Error('No se pudo actualizar la orden')

    showPaymentToast('Orden marcada como cobrada')
    await fetchPayments()
  } catch (err) {
    showPaymentToast(err.message, 'error')
  }
}

async function markAsCancelled(order) {
  try {
    const response = await fetch(`/api/orders/${order.id}`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ status: 'cancelled', operator_name: auth.user?.name || null })
    })

    if (!response.ok) throw new Error('No se pudo cancelar la orden')

    showPaymentToast('Orden cancelada')
    await fetchPayments()
  } catch (err) {
    showPaymentToast(err.message, 'error')
  }
}

async function updateOrderStatus(order, newStatus) {
  try {
    const response = await fetch(`/api/orders/${order.id}`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ status: newStatus, operator_name: auth.user?.name || null })
    })

    if (!response.ok) throw new Error('No se pudo actualizar la orden')

    showKitchenToast('Estado de la orden actualizado')
    await fetchKitchenOrders()
  } catch (err) {
    showKitchenToast(err.message, 'error')
  }
}

onMounted(async () => {
  await fetchRestaurantName()
  await fetchPayments()
  await fetchKitchenOrders()

  paymentRefreshId.value = setInterval(() => fetchPayments({ silent: true }), 20000)
  kitchenRefreshId.value = setInterval(() => fetchKitchenOrders({ silent: true }), 20000)
})

onBeforeUnmount(() => {
  if (paymentRefreshId.value) clearInterval(paymentRefreshId.value)
  if (kitchenRefreshId.value) clearInterval(kitchenRefreshId.value)
})
</script>

<style scoped>
.restaurant-operations-container {
  padding: 20px;
  background-color: #f5f5f5;
  min-height: 100vh;
}

.header {
  margin-bottom: 30px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 20px;
}

.header h1 {
  font-size: 32px;
  margin: 0;
  color: #333;
}

.header p {
  color: #666;
  margin: 5px 0 0 0;
  flex-basis: 100%;
}

.header-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.btn-history {
  padding: 10px 20px;
  background-color: #2c7a7b;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.3s;
}

.btn-history:hover {
  background-color: #246364;
}

.btn-back {
  padding: 10px 20px;
  background-color: #666;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  text-decoration: none;
  font-size: 14px;
  transition: background-color 0.3s;
}

.btn-back:hover {
  background-color: #555;
}

.operations-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-top: 20px;
}

.history-section {
  margin-top: 24px;
  background-color: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  padding: 20px;
}

.history-header h2 {
  margin: 0;
  color: #333;
}

.history-header p {
  margin: 6px 0 16px 0;
  color: #666;
  font-size: 14px;
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.history-card {
  background-color: #f9f9f9;
  border-radius: 8px;
  padding: 14px;
  border-left: 4px solid #2c7a7b;
}

.history-day {
  font-weight: 700;
  color: #333;
  margin-bottom: 10px;
}

.history-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 10px;
}

.history-stat {
  background: white;
  border-radius: 6px;
  padding: 10px;
}

.history-label {
  display: block;
  color: #666;
  font-size: 12px;
  margin-bottom: 4px;
}

.history-value {
  color: #333;
  font-weight: 700;
}

.history-total {
  border-left: 4px solid #4caf50;
}

.history-details {
  margin-top: 14px;
  background: #ffffff;
  border-radius: 8px;
  padding: 10px;
}

.history-details h4 {
  margin: 0 0 8px;
  color: #333;
  font-size: 14px;
}

.history-consumed-list {
  margin: 0;
  padding-left: 18px;
  color: #555;
  font-size: 13px;
}

.history-consumed-list li {
  margin: 2px 0;
}

.history-invoices {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.history-invoice-card {
  background: #f9f9f9;
  border: 1px solid #ececec;
  border-radius: 6px;
  padding: 8px;
}

.history-invoice-row {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  color: #444;
}

.history-operator {
  color: #1565c0;
  font-style: italic;
  font-size: 12px;
  margin-top: 2px;
}

@media (max-width: 1400px) {
  .operations-grid {
    grid-template-columns: 1fr;
  }
}

.operations-panel {
  background-color: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.panel-header {
  background-color: #f9f9f9;
  padding: 20px;
  border-bottom: 2px solid #e0e0e0;
}

.panel-header h2 {
  margin: 0 0 5px 0;
  font-size: 24px;
  color: #333;
}

.panel-header p {
  margin: 0;
  color: #666;
  font-size: 14px;
}

.panel-content {
  padding: 20px;
}

.actions-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  gap: 10px;
}

.btn-refresh {
  padding: 8px 16px;
  background-color: #007bff;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.3s;
}

.btn-refresh:hover:not(:disabled) {
  background-color: #0056b3;
}

.btn-refresh:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.last-updated {
  font-size: 12px;
  color: #999;
  white-space: nowrap;
}

.stats {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
  margin-bottom: 20px;
}

.stat-card {
  background-color: #f9f9f9;
  padding: 15px;
  border-radius: 8px;
  text-align: center;
  border-left: 4px solid #007bff;
}

.stat-value {
  font-size: 24px;
  font-weight: bold;
  color: #333;
}

.stat-label {
  font-size: 12px;
  color: #666;
  margin-top: 5px;
}

.loading,
.error-box,
.no-orders {
  padding: 20px;
  text-align: center;
  color: #666;
  border-radius: 8px;
  background-color: #f9f9f9;
}

.error-box {
  color: #d32f2f;
  background-color: #ffebee;
}

.orders-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.payments-columns {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.payments-column {
  background: #fdfdfd;
  border: 1px solid #eceff3;
  border-radius: 10px;
  padding: 10px;
}

.payments-column-title {
  margin: 2px 0 10px;
  color: #2c3e50;
  font-size: 14px;
}

.ready-priority {
  margin: 0 0 8px;
  font-size: 12px;
  font-weight: 700;
  color: #1e8a4c;
  background: #e9f8ef;
  border: 1px solid #bfe8cd;
  border-radius: 999px;
  display: inline-block;
  padding: 3px 10px;
}

.order-card {
  background-color: #f9f9f9;
  padding: 15px;
  border-radius: 8px;
  border-left: 4px solid #007bff;
  transition: box-shadow 0.3s;
}

.order-card:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.order-card.status-preparing {
  border-left-color: #ff9800;
}

.order-card.status-completed {
  border-left-color: #4caf50;
}

.order-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.order-head h3 {
  margin: 0;
  font-size: 16px;
  color: #333;
}

.order-type {
  background-color: #e7f3ff;
  color: #0066cc;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.order-meta {
  margin: 8px 0;
  font-size: 14px;
  color: #555;
}

.section-note {
  margin: 12px 0 0;
  font-size: 13px;
  color: #2458b8;
  font-weight: 600;
}

.order-meta strong {
  color: #333;
}

.items-list {
  list-style: none;
  padding: 10px 0;
  margin: 10px 0;
  border-top: 1px solid #e0e0e0;
  border-bottom: 1px solid #e0e0e0;
  font-size: 13px;
  color: #666;
}

.items-list li {
  padding: 4px 0;
}

.order-actions {
  display: flex;
  gap: 8px;
  margin-top: 12px;
  flex-wrap: wrap;
}

.btn {
  padding: 6px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
  font-weight: 500;
  transition: opacity 0.3s;
}

.btn:hover {
  opacity: 0.9;
}

.btn-paid {
  background-color: #4caf50;
  color: white;
}

.btn-cancel {
  background-color: #f44336;
  color: white;
}

.btn-prepare {
  background-color: #ff9800;
  color: white;
  flex-grow: 1;
}

.status-completed {
  padding: 6px 12px;
  background-color: #4caf50;
  color: white;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
  text-align: center;
  flex-grow: 1;
}

@media (max-width: 1100px) {
  .stats {
    grid-template-columns: 1fr;
  }

  .payments-columns {
    grid-template-columns: 1fr;
  }
}

.toast {
  padding: 12px 16px;
  margin-bottom: 15px;
  border-radius: 6px;
  animation: slideDown 0.3s ease-out;
}

.toast-success {
  background-color: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.toast-error {
  background-color: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
