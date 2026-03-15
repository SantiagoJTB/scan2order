<template>
  <div class="checkout-container">
    <div class="header">
      <h1>Pago</h1>
      <p>Completa tu compra</p>
      <div v-if="cart.items.length > 0" class="header-chips">
        <span class="header-chip">{{ cart.itemCount }} productos</span>
        <span class="header-chip">Total ${{ totalAmount.toFixed(2) }}</span>
      </div>
    </div>

    <div v-if="cart.items.length === 0" class="empty-checkout">
      <div class="empty-icon">🛒</div>
      <h2>Tu carrito está vacío</h2>
      <p>Agrega productos antes de continuar con el pago.</p>
      <router-link to="/restaurants" class="btn-empty-action">Explorar restaurantes</router-link>
    </div>

    <div v-else class="checkout-content">
      <div class="checkout-form">
        <div class="section">
          <h2>Tipo de consumo</h2>
          <div class="order-types">
            <label v-for="option in availableOrderTypes" :key="option.value" class="payment-option">
              <input v-model="formData.orderType" type="radio" :value="option.value" required />
              <span>{{ option.label }}</span>
            </label>
          </div>
          <div v-if="!hasAvailableOrderTypes" class="error-message">
            Este restaurante no tiene modalidades de pedido habilitadas por administración.
          </div>
        </div>

        <div class="section" v-if="formData.orderType === 'local'">
          <h2>Mesa</h2>
          <div class="form-group">
            <label for="table-number">Número de mesa:</label>
            <input
              v-model="formData.tableNumber"
              type="text"
              id="table-number"
              placeholder="Ej: 12"
              required
            />
          </div>
        </div>

        <div class="section" v-if="formData.orderType === 'takeaway'">
          <h2>Datos adicionales</h2>
          <div class="form-group">
            <label for="address">Dirección:</label>
            <input
              v-model="formData.address"
              type="text"
              id="address"
              placeholder="Dirección de entrega"
              required
            />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="city">Ciudad:</label>
              <input
                v-model="formData.city"
                type="text"
                id="city"
                placeholder="Ciudad (opcional)"
              />
            </div>
            <div class="form-group">
              <label for="postalcode">Código postal:</label>
              <input
                v-model="formData.postalcode"
                type="text"
                id="postalcode"
                placeholder="Código postal (opcional)"
              />
            </div>
          </div>
        </div>

        <div class="section" v-if="formData.orderType === 'pickup'">
          <h2>Datos para recoger</h2>
          <div class="form-group">
            <label for="pickup-name">Nombre de quien recoge:</label>
            <input
              v-model="formData.pickupName"
              type="text"
              id="pickup-name"
              placeholder="Nombre y apellido"
              required
            />
          </div>
        </div>

        <div class="section">
          <h2>Método de pago</h2>
          <div class="payment-methods">
            <label class="payment-option">
              <input v-model="formData.paymentMethod" type="radio" value="card" required />
              <span>Pagar con tarjeta</span>
            </label>
            <label class="payment-option">
              <input v-model="formData.paymentMethod" type="radio" value="cash" required />
              <span>Pago en efectivo al recibir</span>
            </label>
            <label class="payment-option">
              <input v-model="formData.paymentMethod" type="radio" value="table" />
              <span>Pago en mesa</span>
            </label>
          </div>

          <div v-if="formData.paymentMethod === 'card'" class="payment-warning">
            No tienes una tarjeta registrada todavía. Por ahora enviaremos el pedido igualmente.
          </div>
        </div>

        <div v-if="error" class="error-message">{{ error }}</div>

        <div class="form-actions">
          <router-link to="/cart" class="btn-back">← Volver</router-link>
          <button
            @click="processPayment"
            :disabled="isLoading || !hasAvailableOrderTypes"
            class="btn-pay"
          >
            {{ isLoading ? 'Procesando...' : `Pagar $${totalAmount.toFixed(2)}` }}
          </button>
        </div>
      </div>

      <!-- Order summary -->
      <div class="order-summary">
        <h2>Resumen de tu orden</h2>
        <p class="summary-caption">Revisa los importes y confirma la modalidad antes de enviar el pedido.</p>
        <div class="summary-items">
          <div v-for="item in cart.items" :key="item.id" class="summary-item">
            <div>
              <span class="item-name">{{ item.name }}</span>
              <span class="item-qty">x{{ item.quantity }}</span>
            </div>
            <span class="item-price">${{ (item.price * item.quantity).toFixed(2) }}</span>
          </div>
        </div>

        <div class="summary-totals">
          <div class="summary-row">
            <span>Subtotal:</span>
            <span>${{ cart.total.toFixed(2) }}</span>
          </div>
          <div class="summary-row">
            <span>Impuesto:</span>
            <span>${{ (cart.total * 0.1).toFixed(2) }}</span>
          </div>
          <div class="summary-row">
            <span>Servicio:</span>
            <span>$0.00</span>
          </div>
          <div class="summary-row total">
            <span>Total:</span>
            <span>${{ totalAmount.toFixed(2) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Success modal -->
    <div v-if="showSuccess" class="modal-overlay">
      <div class="modal">
        <div class="modal-header">
          <h2>¡Pedido realizado!</h2>
        </div>
        <div class="modal-body">
          <p>Tu pedido ha sido procesado exitosamente.</p>
          <p><strong>Número de pedido:</strong> #{{ orderNumber }}</p>
          <p><strong>Total pagado:</strong> ${{ totalAmount.toFixed(2) }}</p>
          <p>{{ successMessage }}</p>
        </div>
        <div class="modal-footer">
          <button @click="goHome" class="btn-modal">Volver al menú</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useCartStore } from '../../stores/cart'
import { useAuthStore } from '../../stores/auth'
import { useRouter } from 'vue-router'

const cart = useCartStore()
const auth = useAuthStore()
const router = useRouter()
const isLoading = ref(false)
const error = ref(null)
const showSuccess = ref(false)
const orderNumber = ref(null)
const restaurantServices = ref({
  local: true,
  takeaway: true,
  pickup: true,
})

const formData = ref({
  orderType: 'local',
  tableNumber: '',
  pickupName: '',
  address: '',
  city: '',
  postalcode: '',
  paymentMethod: 'card'
})

const totalAmount = computed(() => {
  const tax = cart.total * 0.1
  const service = 0
  return cart.total + tax + service
})

const availableOrderTypes = computed(() => {
  const options = []
  if (restaurantServices.value.local) {
    options.push({ value: 'local', label: 'Consumir en el local' })
  }
  if (restaurantServices.value.takeaway) {
    options.push({ value: 'takeaway', label: 'Para llevar' })
  }
  if (restaurantServices.value.pickup) {
    options.push({ value: 'pickup', label: 'Recoger' })
  }
  return options
})

const hasAvailableOrderTypes = computed(() => availableOrderTypes.value.length > 0)

const successMessage = computed(() => {
  if (formData.value.orderType === 'takeaway') {
    return 'Tu pedido estará listo para recoger en aproximadamente 20-30 minutos.'
  }
  if (formData.value.orderType === 'pickup') {
    return 'Tu pedido estará listo para recoger en el local en aproximadamente 20-30 minutos.'
  }
  return 'Tu pedido será preparado para consumir en el local en aproximadamente 20-30 minutos.'
})

async function processPayment() {
  isLoading.value = true
  error.value = null

  try {
    const orderId = await createOrderWithItems()
    orderNumber.value = String(orderId)

    // Use test payment endpoint to complete the payment
    // This works for both card and cash methods
    if (auth.token) {
      await createTestPayment(orderId)
    }

    showSuccess.value = true
    cart.clear()
  } catch (err) {
    error.value = err.message
  } finally {
    isLoading.value = false
  }
}

function ensureSelectedOrderTypeIsValid() {
  const selected = formData.value.orderType
  const exists = availableOrderTypes.value.some(option => option.value === selected)
  if (exists) return

  formData.value.orderType = availableOrderTypes.value[0]?.value || 'local'
}

async function fetchRestaurantServiceOptions() {
  const restaurantId = cart.items[0]?.restaurantId
  if (!restaurantId) return

  try {
    const response = await fetch(`/api/restaurants/${restaurantId}`, {
      headers: {
        Accept: 'application/json',
        ...(auth.token ? { Authorization: `Bearer ${auth.token}` } : {}),
      }
    })

    if (!response.ok) return

    const data = await response.json()
    restaurantServices.value = {
      local: data?.service_local_enabled !== false,
      takeaway: data?.service_takeaway_enabled !== false,
      pickup: data?.service_pickup_enabled !== false,
    }

    ensureSelectedOrderTypeIsValid()
  } catch {
    // Keep defaults when service options cannot be loaded
  }
}

function goHome() {
  showSuccess.value = false
  router.push('/orders')
}

async function createOrderWithItems() {
  if (!auth.token) {
    throw new Error('Debes iniciar sesión para completar el pago')
  }

  if (!cart.items.length) {
    throw new Error('Tu carrito está vacío')
  }

  const restaurantId = cart.items[0]?.restaurantId
  if (!restaurantId) {
    throw new Error('No se pudo determinar el restaurante del pedido')
  }

  if (!hasAvailableOrderTypes.value) {
    throw new Error('Este restaurante no tiene modalidades de pedido habilitadas')
  }

  const details = [formData.value.address, formData.value.city, formData.value.postalcode]
    .filter(Boolean)
    .join(', ')
  const isTakeaway = formData.value.orderType === 'takeaway'
  const isLocal = formData.value.orderType === 'local'
  const isPickup = formData.value.orderType === 'pickup'

  if (isTakeaway && !details.trim()) {
    throw new Error('Introduce una dirección para pedidos para llevar')
  }

  if (isLocal && !String(formData.value.tableNumber || '').trim()) {
    throw new Error('Introduce un número de mesa para consumir en local')
  }

  if (isPickup && !String(formData.value.pickupName || '').trim()) {
    throw new Error('Introduce el nombre de quien recogerá el pedido')
  }

  const serviceMode = isLocal ? 'local' : (isPickup ? 'pickup' : 'takeaway')

  let notes = ''
  if (isTakeaway) {
    notes = `Tipo de consumo: Para llevar. Referencia: ${details}`
  } else if (isPickup) {
    notes = `Tipo de consumo: Recoger en local. Nombre: ${String(formData.value.pickupName).trim()}`
  } else {
    notes = `Tipo de consumo: Consumir en local. Mesa: ${String(formData.value.tableNumber).trim()}`
  }

  const orderResponse = await fetch('/api/orders', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${auth.token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({
      restaurant_id: restaurantId,
      user_id: auth.user?.id || null,
      type: isLocal ? 'local' : 'delivery',
      service_mode: serviceMode,
      status: 'pending',
      total: totalAmount.value,
      delivery_address: isTakeaway
        ? details
        : (isPickup ? `Recoger en local · Nombre: ${String(formData.value.pickupName).trim()}` : null),
      notes
    })
  })

  const orderContentType = orderResponse.headers.get('content-type') || ''
  const orderData = orderContentType.includes('application/json') ? await orderResponse.json() : null

  if (!orderResponse.ok || !orderData?.id) {
    throw new Error(orderData?.message || 'No se pudo crear la orden')
  }

  const orderId = orderData.id

  for (const item of cart.items) {
    const itemResponse = await fetch('/api/order-items', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        order_id: orderId,
        product_id: item.id,
        quantity: item.quantity
      })
    })

    if (!itemResponse.ok) {
      throw new Error('No se pudieron crear los productos del pedido')
    }
  }

  localStorage.setItem('checkout_order_id', String(orderId))
  return orderId
}

async function createTestPayment(orderId) {
  const response = await fetch(`/api/orders/${orderId}/payments/test`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${auth.token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({
      amount: totalAmount.value,
      currency: 'eur',
      method: formData.value.paymentMethod
    })
  })

  const contentType = response.headers.get('content-type') || ''
  const data = contentType.includes('application/json') ? await response.json() : null

  if (!response.ok) {
    throw new Error(data?.message || 'No se pudo procesar el pago')
  }
}

onMounted(() => {
  fetchRestaurantServiceOptions()
})
</script>

<style scoped>
.checkout-container {
  min-height: 100vh;
  padding: max(1rem, env(safe-area-inset-top)) 1rem max(2rem, env(safe-area-inset-bottom));
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.header {
  text-align: center;
  color: white;
  max-width: 1200px;
  margin: 0 auto 1.5rem;
  padding: 1.75rem;
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.14);
  backdrop-filter: blur(14px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.header h1 {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.header p {
  margin: 0;
}

.header-chips {
  margin-top: 1rem;
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  justify-content: center;
}

.header-chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.55rem 0.9rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.24);
  color: white;
  font-weight: 700;
  font-size: 0.92rem;
}

.empty-checkout {
  max-width: 760px;
  margin: 0 auto;
  padding: 2.5rem 1.5rem;
  text-align: center;
  background: white;
  border-radius: 24px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.empty-checkout h2 {
  margin: 0 0 0.5rem;
  color: #2c3e50;
}

.empty-checkout p {
  margin: 0 0 1.5rem;
  color: #7f8c8d;
}

.btn-empty-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 52px;
  padding: 0.9rem 1.25rem;
  border-radius: 14px;
  text-decoration: none;
  color: white;
  font-weight: 700;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.checkout-content {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 2rem;
  align-items: start;
}

.checkout-form {
  background: white;
  border-radius: 24px;
  padding: clamp(1.25rem, 3vw, 2rem);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.section {
  margin-bottom: 2rem;
}

.section h2 {
  color: #2c3e50;
  font-size: 1.3rem;
  margin-bottom: 1rem;
  border-bottom: 2px solid #ecf0f1;
  padding-bottom: 0.5rem;
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

.form-group input {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #ecf0f1;
  border-radius: 5px;
  font-size: 1rem;
  transition: border-color 0.3s ease;
}

.form-group input:focus {
  outline: none;
  border-color: #667eea;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.payment-methods {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 1rem;
}

.order-types {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.payment-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  min-height: 56px;
  padding: 0.9rem 1rem;
  border: 2px solid #ecf0f1;
  border-radius: 14px;
  transition: border-color 0.3s ease;
}

.payment-option:hover {
  border-color: #667eea;
}

.payment-option input[type="radio"] {
  cursor: pointer;
  margin: 0;
}

.payment-warning {
  margin-top: 0.6rem;
  padding: 0.75rem;
  border-radius: 6px;
  background: #fff7e6;
  color: #9a6700;
  border: 1px solid #f3d9a3;
  font-size: 0.92rem;
}

.error-message {
  background: #ffe6e6;
  color: #c0392b;
  padding: 1rem;
  border-radius: 5px;
  margin-bottom: 1rem;
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
}

.btn-back,
.btn-pay {
  flex: 1;
  min-height: 54px;
  padding: 1rem;
  border: none;
  border-radius: 14px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.3s ease;
}

.btn-back {
  background: #ecf0f1;
  color: #2c3e50;
  text-decoration: none;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-back:hover {
  opacity: 0.8;
}

.btn-pay {
  background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
  color: white;
}

.btn-pay:hover:not(:disabled) {
  opacity: 0.9;
}

.btn-pay:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.order-summary {
  background: white;
  border-radius: 24px;
  padding: clamp(1.25rem, 3vw, 2rem);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
  height: fit-content;
  position: sticky;
  top: 1.5rem;
}

.order-summary h2 {
  color: #2c3e50;
  font-size: 1.3rem;
  margin-bottom: 1rem;
  border-bottom: 2px solid #ecf0f1;
  padding-bottom: 0.5rem;
}

.summary-caption {
  margin: -0.25rem 0 1rem;
  color: #7f8c8d;
  font-size: 0.92rem;
  line-height: 1.5;
}

.summary-items {
  margin-bottom: 1.5rem;
  max-height: 300px;
  overflow-y: auto;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  border-bottom: 1px solid #ecf0f1;
}

.item-name {
  color: #2c3e50;
  font-weight: 500;
}

.item-qty {
  color: #7f8c8d;
  font-size: 0.9rem;
  margin-left: 0.5rem;
}

.item-price {
  color: #27ae60;
  font-weight: 600;
}

.summary-totals {
  padding-top: 1rem;
  border-top: 2px solid #ecf0f1;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
  color: #2c3e50;
}

.summary-row.total {
  font-size: 1.3rem;
  font-weight: 700;
  color: #27ae60;
  margin-top: 0.5rem;
  padding-top: 0.75rem;
  border-top: 1px solid #ecf0f1;
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
  border-radius: 20px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  max-width: 400px;
  width: 100%;
  text-align: center;
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 2px solid #ecf0f1;
}

.modal-header h2 {
  color: #27ae60;
  margin: 0;
}

.modal-body {
  padding: 1.5rem;
  color: #2c3e50;
}

.modal-body p {
  margin: 0.75rem 0;
}

.modal-footer {
  padding: 1rem;
  border-top: 1px solid #ecf0f1;
}

.btn-modal {
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 5px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.3s ease;
}

.btn-modal:hover {
  opacity: 0.9;
}

@media (max-width: 768px) {
  .checkout-container {
    padding: max(0.75rem, env(safe-area-inset-top)) 0.75rem max(1.25rem, env(safe-area-inset-bottom));
  }

  .header {
    text-align: left;
    padding: 1.25rem;
    border-radius: 20px;
  }

  .header-chips {
    justify-content: flex-start;
  }

  .checkout-content {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .checkout-form {
    order: 2;
  }

  .order-summary {
    order: 1;
    position: static;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .form-actions {
    flex-direction: column-reverse;
  }

  .btn-back,
  .btn-pay {
    width: 100%;
  }

  .modal {
    width: auto;
    margin: 1rem;
  }
}

@media (max-width: 480px) {
  .empty-checkout {
    padding: 2rem 1.1rem;
  }

  .header-chip {
    font-size: 0.85rem;
  }
}
</style>
