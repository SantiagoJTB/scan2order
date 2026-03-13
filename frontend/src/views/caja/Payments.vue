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
        <button class="btn-create" @click="toggleCreateOrderPanel" :disabled="!restaurantId || isCreatingOrder">
          {{ showCreateOrder ? '✕ Cerrar crear pedido' : '➕ Crear pedido' }}
        </button>
        <button class="btn-history" @click="showHistory = !showHistory">
          {{ showHistory ? '← Volver a pendientes' : '📋 Ver historial del día' }}
        </button>
        <span v-if="lastUpdated" class="last-updated">Última actualización: {{ lastUpdated }}</span>
      </div>

      <div v-if="showCreateOrder" class="create-order-panel">
        <h2>Crear pedido{{ restaurantName ? ` - ${restaurantName}` : '' }}</h2>
        <p v-if="!restaurantId" class="error-inline">
          Debes entrar a caja con un restaurante seleccionado para crear pedidos.
        </p>

        <div v-else>
          <div class="create-order-controls">
            <label>
              Tipo de pedido
              <select v-model="newOrder.type">
                <option value="local">Consumir en local</option>
                <option value="delivery">Para llevar</option>
              </select>
            </label>
            <label v-if="newOrder.type === 'delivery'">
              Dirección para llevar
              <input v-model="newOrder.deliveryAddress" type="text" placeholder="Calle, número, referencia" />
            </label>
            <label>
              Notas
              <input v-model="newOrder.notes" type="text" placeholder="Notas para cocina (opcional)" />
            </label>
          </div>

          <div v-if="isLoadingMenu" class="loading">Cargando menú del restaurante...</div>
          <div v-else-if="menuError" class="error-box">{{ menuError }}</div>
          <div v-else-if="menuProducts.length === 0" class="no-orders">No hay productos activos en el menú.</div>

          <div v-else class="menu-grid">
            <div v-for="product in menuProducts" :key="product.id" class="menu-product-card">
              <div>
                <h3>{{ product.name }}</h3>
                <p class="menu-product-section">{{ product.sectionName }}</p>
                <p v-if="product.description" class="menu-product-description">{{ product.description }}</p>
              </div>
              <div class="menu-product-footer">
                <span>${{ Number(product.price || 0).toFixed(2) }}</span>
                <button class="btn btn-add" @click="addProductToDraft(product)">Agregar</button>
              </div>
            </div>
          </div>

          <div class="draft-order">
            <h3>Pedido en creación</h3>
            <div v-if="draftItems.length === 0" class="no-orders">Aún no agregaste productos.</div>
            <ul v-else class="items-list">
              <li v-for="item in draftItems" :key="item.product_id" class="draft-item-row">
                <span>{{ item.quantity }} x {{ item.name }}</span>
                <div class="draft-item-actions">
                  <button class="qty-btn" @click="decreaseDraftItem(item.product_id)">-</button>
                  <button class="qty-btn" @click="increaseDraftItem(item.product_id)">+</button>
                  <button class="qty-btn qty-remove" @click="removeDraftItem(item.product_id)">✕</button>
                </div>
              </li>
            </ul>

            <div class="draft-summary">
              <span>Total: ${{ draftTotal.toFixed(2) }}</span>
              <button
                class="btn btn-create-order"
                @click="createOrder"
                :disabled="draftItems.length === 0 || isCreatingOrder"
              >
                {{ isCreatingOrder ? 'Creando...' : 'Crear pedido' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="stats">
        <div class="stat-card">
          <div class="stat-value">{{ showHistory ? todayPaidOrders.length : pendingCollectionOrders.length }}</div>
          <div class="stat-label">{{ showHistory ? 'Pagos realizados hoy' : 'Pendientes de cobro' }}</div>
        </div>
        <div v-if="!showHistory" class="stat-card">
          <div class="stat-value">{{ readyPaymentOrders.length }}</div>
          <div class="stat-label">Órdenes listas</div>
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

        <div v-else-if="showHistory && todayPaidOrders.length === 0" class="no-orders">
          No hay pagos realizados el día de hoy.
        </div>

        <div v-else-if="showHistory" class="orders-list">
          <div v-for="order in todayPaidOrders" :key="order.id" class="order-card" :class="{ 'order-paid': showHistory }">
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
            <p v-if="order.type === 'delivery' && order.delivery_address" class="order-meta"><strong>Dirección:</strong> {{ order.delivery_address }}</p>
            <p class="order-meta"><strong>Método:</strong> {{ getPaymentMethod(order) }}</p>
            <p v-if="showHistory" class="order-meta"><strong>Cobrada:</strong> {{ formatDateTime(order.updated_at) }}</p>

            <ul v-if="order.order_items?.length" class="items-list">
              <li v-for="item in order.order_items" :key="item.id">
                {{ item.quantity }} x {{ item.product?.name || `Producto #${item.product_id}` }}
              </li>
            </ul>

          </div>
        </div>

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

                <p class="order-meta">
                  <strong>Restaurante:</strong>
                  {{ order.restaurant?.name || `#${order.restaurant_id}` }}
                </p>
                <p class="order-meta"><strong>Estado:</strong> {{ getStatusLabel(order.status) }}</p>
                <p class="order-meta"><strong>Total:</strong> ${{ Number(order.total || 0).toFixed(2) }}</p>
                <p v-if="order.type === 'delivery' && order.delivery_address" class="order-meta"><strong>Dirección:</strong> {{ order.delivery_address }}</p>
                <p class="order-meta"><strong>Método:</strong> {{ getPaymentMethod(order) }}</p>

                <ul v-if="order.order_items?.length" class="items-list">
                  <li v-for="item in order.order_items" :key="item.id">
                    {{ item.quantity }} x {{ item.product?.name || `Producto #${item.product_id}` }}
                  </li>
                </ul>

                <div class="order-actions">
                  <button class="btn btn-card" @click="processPayment(order, 'card')" :disabled="processingOrderId === order.id">
                    Tarjeta
                  </button>
                  <button class="btn btn-cash" @click="processPayment(order, 'cash')" :disabled="processingOrderId === order.id">
                    Efectivo
                  </button>
                  <button class="btn btn-cancel" @click="markAsCancelled(order)">Cancelar</button>
                </div>
              </div>
            </div>
          </div>

          <div class="payments-column">
            <h3 class="payments-column-title">Órdenes listas</h3>
            <div v-if="readyPaymentOrders.length === 0" class="no-orders">
              No hay órdenes listas pendientes de cobro.
            </div>
            <div v-else class="orders-list">
              <div v-for="order in readyPaymentOrders" :key="order.id" class="order-card order-ready">
                <div class="order-head">
                  <h3>Orden #{{ order.id }}</h3>
                  <span class="order-type">{{ getOrderTypeLabel(order.type) }}</span>
                </div>

                <p class="ready-priority">🔔 Prioridad de cobro</p>

                <p class="order-meta">
                  <strong>Restaurante:</strong>
                  {{ order.restaurant?.name || `#${order.restaurant_id}` }}
                </p>
                <p class="order-meta"><strong>Estado:</strong> {{ getStatusLabel(order.status) }}</p>
                <p class="order-meta"><strong>Total:</strong> ${{ Number(order.total || 0).toFixed(2) }}</p>
                <p v-if="order.type === 'delivery' && order.delivery_address" class="order-meta"><strong>Dirección:</strong> {{ order.delivery_address }}</p>
                <p class="order-meta"><strong>Método:</strong> {{ getPaymentMethod(order) }}</p>

                <ul v-if="order.order_items?.length" class="items-list">
                  <li v-for="item in order.order_items" :key="item.id">
                    {{ item.quantity }} x {{ item.product?.name || `Producto #${item.product_id}` }}
                  </li>
                </ul>

                <div class="order-actions">
                  <button class="btn btn-card" @click="processPayment(order, 'card')" :disabled="processingOrderId === order.id">
                    Tarjeta
                  </button>
                  <button class="btn btn-cash" @click="processPayment(order, 'cash')" :disabled="processingOrderId === order.id">
                    Efectivo
                  </button>
                  <button class="btn btn-cancel" @click="markAsCancelled(order)">Cancelar</button>
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
const showCreateOrder = ref(false)
const isLoadingMenu = ref(false)
const menuError = ref('')
const menuProducts = ref([])
const isCreatingOrder = ref(false)
const processingOrderId = ref(null)
const newOrder = ref({
  type: 'local',
  deliveryAddress: '',
  notes: '',
})
const draftItems = ref([])

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

function orderRecencyValue(order) {
  const byUpdatedAt = new Date(order?.updated_at || '').getTime()
  if (!Number.isNaN(byUpdatedAt) && byUpdatedAt > 0) return byUpdatedAt

  const byCreatedAt = new Date(order?.created_at || '').getTime()
  if (!Number.isNaN(byCreatedAt) && byCreatedAt > 0) return byCreatedAt

  return Number(order?.id || 0)
}

const readyPaymentOrders = computed(() => {
  return pendingPaymentOrders.value
    .filter(order => String(order.status || '').toLowerCase() === 'completed')
    .slice()
    .sort((a, b) => orderRecencyValue(b) - orderRecencyValue(a))
})

const pendingCollectionOrders = computed(() => {
  return pendingPaymentOrders.value
    .filter(order => String(order.status || '').toLowerCase() !== 'completed')
    .slice()
    .sort((a, b) => orderRecencyValue(b) - orderRecencyValue(a))
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
    completed: 'Lista',
    cancelled: 'Cancelada'
  }
  return map[status] || status
}

function getPaymentMethod(order) {
  const payment = order.payments?.[0]
  if (!payment) return 'No registrado'
  if (payment.method === 'cash') return 'Efectivo'
  if (payment.method === 'stripe') return 'Tarjeta'
  if (payment.method === 'card') return 'Tarjeta'
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

async function fetchRestaurantMenu() {
  if (!restaurantId.value) return

  isLoadingMenu.value = true
  menuError.value = ''

  try {
    const response = await fetch(`/api/restaurants/${restaurantId.value}/catalogs`, {
      headers: {
        Authorization: `Bearer ${auth.token}`,
        Accept: 'application/json',
      },
    })

    if (!response.ok) {
      throw new Error('No se pudo cargar el menú del restaurante')
    }

    const catalogs = await response.json()
    const nextProducts = []

    ;(Array.isArray(catalogs) ? catalogs : []).forEach((catalog) => {
      ;(catalog.sections || []).forEach((section) => {
        ;(section.products || []).forEach((product) => {
          nextProducts.push({
            id: product.id,
            name: product.name,
            description: product.description,
            price: Number(product.price || 0),
            sectionName: section.name,
          })
        })
      })
    })

    menuProducts.value = nextProducts
  } catch (err) {
    menuError.value = err.message
  } finally {
    isLoadingMenu.value = false
  }
}

function toggleCreateOrderPanel() {
  showCreateOrder.value = !showCreateOrder.value
  if (showCreateOrder.value && menuProducts.value.length === 0) {
    fetchRestaurantMenu()
  }
}

function addProductToDraft(product) {
  const existing = draftItems.value.find((item) => item.product_id === product.id)
  if (existing) {
    existing.quantity += 1
    return
  }

  draftItems.value.push({
    product_id: product.id,
    name: product.name,
    unit_price: Number(product.price || 0),
    quantity: 1,
  })
}

function increaseDraftItem(productId) {
  const item = draftItems.value.find((entry) => entry.product_id === productId)
  if (!item) return
  item.quantity += 1
}

function decreaseDraftItem(productId) {
  const item = draftItems.value.find((entry) => entry.product_id === productId)
  if (!item) return
  if (item.quantity <= 1) {
    removeDraftItem(productId)
    return
  }
  item.quantity -= 1
}

function removeDraftItem(productId) {
  draftItems.value = draftItems.value.filter((entry) => entry.product_id !== productId)
}

const draftTotal = computed(() => {
  return draftItems.value.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0)
})

async function createOrder() {
  if (!restaurantId.value || draftItems.value.length === 0 || isCreatingOrder.value) return

  if (newOrder.value.type === 'delivery' && !newOrder.value.deliveryAddress.trim()) {
    showToast('Debes indicar una dirección para pedidos para llevar', 'error')
    return
  }

  isCreatingOrder.value = true

  try {
    const orderResponse = await fetch('/api/orders', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${auth.token}`,
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({
        restaurant_id: restaurantId.value,
        type: newOrder.value.type,
        status: 'pending',
        total: Number(draftTotal.value.toFixed(2)),
        delivery_address: newOrder.value.type === 'delivery' ? newOrder.value.deliveryAddress.trim() : null,
        notes: newOrder.value.notes || null,
      }),
    })

    if (!orderResponse.ok) {
      throw new Error('No se pudo crear la orden')
    }

    const createdOrder = await orderResponse.json()

    for (const item of draftItems.value) {
      const itemResponse = await fetch('/api/order-items', {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${auth.token}`,
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({
          order_id: createdOrder.id,
          product_id: item.product_id,
          quantity: item.quantity,
        }),
      })

      if (!itemResponse.ok) {
        throw new Error('La orden se creó, pero falló al agregar un producto')
      }
    }

    draftItems.value = []
    newOrder.value = { type: 'local', deliveryAddress: '', notes: '' }
    showToast(`Pedido #${createdOrder.id} creado y enviado a cocina`)
    await fetchOrders()
  } catch (err) {
    showToast(err.message, 'error')
  } finally {
    isCreatingOrder.value = false
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

async function processPayment(order, method) {
  if (processingOrderId.value === order.id) return

  processingOrderId.value = order.id

  try {
    const endpoint = method === 'cash'
      ? `/api/orders/${order.id}/payments/cash`
      : `/api/orders/${order.id}/payments/test`

    const paymentPayload = method === 'cash'
      ? { amount: order.total, immediate: true }
      : { amount: order.total, method: 'stripe' }

    const paymentResponse = await fetch(endpoint, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${auth.token}`,
        'Content-Type': 'application/json',
        Accept: 'application/json'
      },
      body: JSON.stringify(paymentPayload)
    })
    
    if (!paymentResponse.ok) {
      throw new Error('No se pudo registrar el pago')
    }
    
    await updateOrderStatus(order.id, 'paid')
    showToast(`Orden #${order.id} cobrada con ${method === 'cash' ? 'efectivo' : 'tarjeta'} y enviada a cocina`)
    await fetchOrders()
  } catch (err) {
    showToast(err.message, 'error')
  } finally {
    processingOrderId.value = null
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

.btn-create {
  background: #16a085;
  color: white;
  border: none;
  padding: 0.65rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
}

.btn-create:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.create-order-panel {
  margin-bottom: 2rem;
  border: 1px solid #e7ecf3;
  border-radius: 10px;
  padding: 1rem;
  background: #f8fbff;
}

.create-order-controls {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.create-order-controls label {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  color: #2c3e50;
  font-weight: 600;
}

.create-order-controls input,
.create-order-controls select {
  border: 1px solid #d7deea;
  border-radius: 6px;
  padding: 0.55rem 0.65rem;
}

.menu-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.menu-product-card {
  border: 1px solid #e7ecf3;
  border-radius: 8px;
  padding: 0.8rem;
  background: white;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 0.6rem;
}

.menu-product-card h3 {
  margin: 0;
  color: #2c3e50;
  font-size: 1rem;
}

.menu-product-section {
  margin: 0.25rem 0;
  font-size: 0.8rem;
  color: #5f6c7b;
}

.menu-product-description {
  margin: 0.2rem 0 0;
  color: #7a8797;
  font-size: 0.85rem;
}

.menu-product-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 700;
  color: #2c3e50;
}

.draft-order {
  border-top: 1px solid #e7ecf3;
  padding-top: 1rem;
}

.draft-item-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  padding: 0.25rem 0;
}

.draft-item-actions {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.qty-btn {
  border: 1px solid #d7deea;
  background: white;
  color: #2c3e50;
  border-radius: 6px;
  min-width: 28px;
  height: 28px;
  cursor: pointer;
  font-weight: 700;
}

.qty-remove {
  color: #c0392b;
}

.draft-summary {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 0.8rem;
  font-weight: 700;
  color: #2c3e50;
}

.error-inline {
  color: #c0392b;
  margin: 0.5rem 0;
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

.payments-columns {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.payments-column {
  border: 1px solid #e7ecf3;
  border-radius: 10px;
  padding: 0.75rem;
  background: #fafbff;
}

.payments-column-title {
  margin: 0 0 0.75rem;
  color: #2c3e50;
  font-size: 0.95rem;
}

.order-card {
  border: 1px solid #e7ecf3;
  border-radius: 10px;
  padding: 1rem;
  background: #fff;
}

.order-ready {
  border-left: 4px solid #27ae60;
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

.ready-priority {
  margin: 0.2rem 0 0.55rem;
  display: inline-block;
  background: #e9f8ef;
  color: #1e8a4c;
  border: 1px solid #bfe8cd;
  border-radius: 999px;
  padding: 0.2rem 0.55rem;
  font-size: 0.78rem;
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

.btn-card {
  background: #2d7df6;
  color: white;
}

.btn-cash {
  background: #27ae60;
  color: white;
}

.btn-add {
  background: #4b5ed7;
  color: white;
}

.btn-create-order {
  background: #16a085;
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

  .payments-columns {
    grid-template-columns: 1fr;
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
